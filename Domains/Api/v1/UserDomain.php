<?php

namespace App\Domains\Api\v1;

use App\Database\DB;
use BlackHole\Http\Request;
use BlackHole\Http\Response;
use BlackHole\Interfaces\DomainInterface;
use BlackHole\Utils\Logger;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tarantool\Client\Client;

class UserDomain implements DomainInterface
{

    protected Client $client;
    protected DB $db;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(protected Logger $logger, protected ContainerInterface $container)
    {
        $this->db = $this->container->get(DB::class);
        $this->client = $this->db->getClient();
    }

    public function get(Request $request): Response
    {
        try {
            $data = $request->getBody();
            $userId = $data['user_id'];
            $userArray = $this->db->select($userId, 'USERS')[0][0];
            $userEvents = [];
            foreach ($userArray[2] as $userEvent) {
                $userEvents[] = ($this->db->select($userEvent, 'EVENTS'))[0][0];
            }
            $result = [
                'success' => true,
                'user' => [
                    'info' => $userArray,
                    'events' => $userEvents
                ]
            ];
            $this->logger->info('UserDomain:get', $result);
            return new Response(200, ['Content-Type' => 'application/json'], $result);
        } catch (\Throwable $e) {
            $result = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
            $this->logger->error('UserDomain:get', $result);
            return new Response(500, ['Content-Type' => 'application/json'], $result);
        }
    }

    public function update(Request $request): Response
    {
        try {
            $data = $request->getBody();
            $userId = $data['user_id'];
            $field = intval($data['field']);
            $newValue = $data['value'];
            if (gettype($newValue) == 'array')
                $newValue = $this->convertArrayToLuaStr($newValue);
            $query = "{'=', " . $field . ', ' . $newValue . "}";
            $this->db->update($userId, $query, 'USERS');
            $result = [
                'success' => true,
            ];
            $this->logger->info('UserDomain:update', $result);
            return new Response(200, ['Content-Type' => 'application/json'], $result);
        } catch (\Throwable $e) {
            $result = [
                'success' => false,
                'message' => $e->getMessage(),
                'query'=> $query
            ];
            $this->logger->error('UserDomain:update', $result);
            return new Response(500, ['Content-Type' => 'application/json'], $result);
        }
    }

    protected function convertArrayToLuaStr(array $array): string
    {
        $string = json_encode($array);
        return str_replace(['[', ']', '"'], ['{', '}', '\''], $string);
    }

    public function create(Request $request): Response
    {
        try {
            $data = $request->getBody();
            $id = 1;
            if ($this->db->getCount('USERS') != 0) {
                $query = "{}, {iterator = 'LT', limit = 1}";
                $id = $this->db->select($query, 'USERS')[0][0][0] + 1;
            }
            /**
             * " {}, {offset = 0} "
             * Заглушка до момента написания регистрации/авторизации
             * $this->db->insert([$id, [$data['settings']], [], []], 'USERS');
             */
            $this->db->insert([$id, [], [], []], 'USERS');
            $result = [
                'success' => true,
                'id' => $id,
            ];
            $this->logger->info('UserDomain:create', $result);
            return new Response(200, ['Content-Type' => 'application/json'], $result);
        } catch (\Throwable $e) {
            $result = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
            $this->logger->error('UserDomain:create', $result);
            return new Response(500, ['Content-Type' => 'application/json'], $result);
        }
    }

    public function delete(Request $request): Response
    {
        try {
            $data = $request->getBody();
            $userId = $data['user_id'];
            $this->db->delete([$userId], null, 'USERS');
            $result = [
                'success' => true,
            ];
            $this->logger->info('UserDomain:delete', $result);
            return new Response(200, ['Content-Type' => 'application/json'], $result);
        } catch (\Throwable $e) {
            $result = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
            $this->logger->error('UserDomain:delete', $result);
            return new Response(500, ['Content-Type' => 'application/json'], $result);
        }
    }
}