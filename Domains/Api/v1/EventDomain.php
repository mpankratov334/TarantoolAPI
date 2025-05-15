<?php

namespace App\Domains\Api\v1;

use App\Database\DB;
use BlackHole\Http\Request;
use BlackHole\Http\Response;
use BlackHole\Interfaces\DomainInterface;
use BlackHole\Utils\Logger;
use Psr\Container\ContainerInterface;
use Tarantool\Client\Client;

class EventDomain implements DomainInterface
{

    protected Client $client;
    protected DB $db;

    public function __construct(protected Logger $logger, protected ContainerInterface $container)
    {
        $this->db = $this->container->get(DB::class);
        $this->client = $this->db->getClient();
    }

    public function get(Request $request): Response
    {
        try {
            $data = $request->getBody();
            $eventId = $data['event_id'];
            $eventArray = $this->db->select($eventId, 'EVENTS')[0][0];
            $eventType = $eventArray[1];
            $eventTasks = $eventArray[2];
            $eventName = $eventArray[3];
            $result = [
                'success' => true,
                'event' => [
                    'info' => $eventArray,
                    'tasks' => $eventTasks,
                    'type' => $eventType,
                    'name' => $eventName
                ]
            ];
            $this->logger->info('EventDomain:get', $result);
            return new Response(200, ['Content-Type' => 'application/json'], $result);
        } catch (\Throwable $e) {
            $result = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
            $this->logger->error('EventDomain:get', $result);
            return new Response(500, ['Content-Type' => 'application/json'], $result);
        }
    }

    public function create(Request $request): Response
    {
        try {
            $data = $request->getBody();
            $id = 1;
            if ($this->db->getCount('EVENTS') != 0) {
                $query = "{}, {iterator = 'LT', limit = 1}";
                $id = $this->db->select($query, 'EVENTS')[0][0][0] + 1;
            }
            $this->db->insert([$id, $data['type'], [], $data['name']], 'EVENTS');
            $result = [
                'success' => true,
                'id' => $id,
            ];
            $this->logger->info('EventDomain:create', $result);
            return new Response(200, ['Content-Type' => 'application/json'], $result);

        } catch (\Throwable $e) {
            $result = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
            $this->logger->error('EventDomain:create', $result);
            return new Response(500, ['Content-Type' => 'application/json'], $result);
        }
    }

    public function delete(Request $request): Response
    {
        try {
            $data = $request->getBody();
            $eventId = $data['event_id'];
            $this->db->delete([$eventId], null, 'EVENTS');

            $result = [
                'success' => true,
            ];
            $this->logger->info('EventDomain:delete', $result);
            return new Response(200, ['Content-Type' => 'application/json'], $result);
        } catch (\Throwable $e) {
            $result = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
            $this->logger->error('EventDomain:delete', $result);
            return new Response(500, ['Content-Type' => 'application/json'], $result);
        }
    }

    public function deleteTask(Request $request): Response
    {
        try {
            $data = $request->getBody();
            $eventId = intval($data['event_id']);
            $tasks = $this->db->select($eventId, 'EVENTS')[0][0][2];
            $tasksId = intval($data['task_id']);
            $tasks[$tasksId - 1][4] = true;
            $stringTasks = $this->convertArrayToLuaStr($tasks);
            $this->db->update($eventId, "{'=', 3, {$stringTasks}}", 'EVENTS');
            $result = [
                'success' => true,
            ];
            $this->logger->info('EventDomain:deleteTask', $result);
            return new Response(200, ['Content-Type' => 'application/json'], $result);
        } catch (\Throwable $e) {
            $result = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
            $this->logger->error('EventDomain:deleteTask', $result);
            return new Response(500, ['Content-Type' => 'application/json'], $result);
        }

    }

    protected function convertArrayToLuaStr(array $array): string
    {
        $string = json_encode($array);
        return str_replace(['[', ']'], ['{', '}'], $string);
    }

    public function update(Request $request): Response
    {
        try {
            $data = $request->getBody();
            $eventId = $data['event_id'];
            $field = intval($data['field']);
            $newValue = $data['value'];
            $query = "{'=', " . $field . ', ' . "'" . $newValue . "'" . "}";
            $this->db->update($eventId, $query, 'EVENTS');
            $result = [
                'success' => true,
            ];
            $this->logger->info('EventDomain:update', $result);
            return new Response(200, ['Content-Type' => 'application/json'], $result);
        } catch (\Throwable $e) {
            $result = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
            $this->logger->error('EventDomain:update', $result);
            return new Response(500, ['Content-Type' => 'application/json'], $result);
        }
    }

    public function createTask(Request $request): Response
    {
        try {
            $data = $request->getBody();
            $eventId = intval($data['event_id']);
            $tasks = $this->db->select($eventId, 'EVENTS')[0][0][2];
            $id = count($tasks) + 1;
            $tasks[] = [$id, $data['type'], $data['info'], $data['users_to_see'], false];
            $stringTasks = $this->convertArrayToLuaStr($tasks);
            $this->db->update($eventId, "{'=', 3, {$stringTasks} }", 'EVENTS');
            $result = [
                'success' => true,
                'id' => $id,
            ];
            $this->logger->info('EventDomain:createTask', $result);
            return new Response(200, ['Content-Type' => 'application/json'], $result);
        } catch (\Throwable $e) {
            $result = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
            $this->logger->error('EventDomain:createTask', $result);
            return new Response(500, ['Content-Type' => 'application/json'], $result);
        }
    }

    public function getTask(Request $request): Response
    {
        try {
            $data = $request->getBody();
            $eventId = intval($data['event_id']);
            $taskId = intval($data['task_id']);
            $tasks = $this->db->select($eventId, 'EVENTS')[0][0][2];
            $task = $tasks[$taskId - 1];
            $result = [
                'task' => [
                    'id' => $task[0],
                    'type' => $task[1],
                    'info' => $task[2],
                    'users_to_see' => $task[3],
                    'deleted' => $task[4]
                ],
                'success' => true,
            ];
            $this->logger->info('EventDomain:getTask', $result);
            return new Response(200, ['Content-Type' => 'application/json'], $result);
        } catch (\Throwable $e) {
            $result = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
            $this->logger->error('EventDomain:getTask', $result);
            return new Response(500, ['Content-Type' => 'application/json'], $result);
        }
    }

    public function updateTask(Request $request): Response
    {
        try {
            $data = $request->getBody();
            $field = intval($data['field']);
            $newValue = $data['value'];
            $eventId = intval($data['event_id']);
            $taskId = intval($data['task_id']);
            $tasks = $this->db->select($eventId, 'EVENTS')[0][0][2];
            $tasks[$taskId - 1][$field - 1] = $newValue;
            $stringTasks = $this->convertArrayToLuaStr($tasks);
            $query = "{'=', " . 3 . ', ' . "$stringTasks" . "}";
            $this->db->update($eventId, $query, 'EVENTS');
            $result = [
                'success' => true,
            ];
            $this->logger->info('EventDomain:updateTask', $result);
            return new Response(200, ['Content-Type' => 'application/json'], $result);
        } catch (\Throwable $e) {
            $result = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
            $this->logger->error('EventDomain:updateTask', $result);
            return new Response(500, ['Content-Type' => 'application/json'], $result);
        }
    }
}