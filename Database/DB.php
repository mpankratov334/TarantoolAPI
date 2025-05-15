<?php

namespace App\Database;

use Tarantool\Client\Client;
use Tarantool\Client\Schema\Space;

class DB
{
    protected $client;
    protected $curSpace;

    public function __construct()
    {
        $this->client = Client::fromDefaults();
    }

    public function getClient($uri = 'http://localhost:3301', $username = '', $password = 'password'): Client
    {
        return Client::fromOptions(['uri' => $uri, 'username' => $username, 'password' => $password]);
    }

    function setDummyDB(): void
    {
        $this->evaluate("box.cfg{listen = 3301}");
        $this->makeSpace('USERS', ['id' => 'unsigned', 'settings' => 'array', 'events_user' => 'array', 'favorit_tasks' => 'array']);
        $this->evaluate("USERS:create_index('primary',
         {type = 'TREE',
          parts = {'id'},
          if_not_exists = true
         })");

        $this->makeSpace('EVENTS', ['id' => 'unsigned', 'type' => 'string', 'tasks' => 'array']);
        $this->evaluate("EVENTS:create_index('primary',
         {type = 'hash',
          parts = {'id'},
          if_not_exists = true
         })");
    }

    function evaluate(string $command): array
    {
        return $this->client->evaluate($command);
    }

    public function makeSpace(string $spaceName, array $format, string $if_not_exist = 'true'): Space
    {
        $this->client->evaluate("{$spaceName} = box.schema.space.create('{$spaceName}', {if_not_exists = {$if_not_exist}})");
        $toCall = "{$spaceName}:format({";
        foreach ($format as $name => $type) {
            $field = "{name = '{$name}', type = '{$type}'},";
            $toCall = $toCall . $field;
        }
        $toCall = mb_substr($toCall, 0, -1) . "})";
        $this->client->evaluate($toCall);
        $this->curSpace = $this->client->getSpace("{$spaceName}");
        return $this->curSpace;
    }

    function truncate(string $spaceName = null): void
    {
        if (is_null($spaceName)) {
            $spaceId = $this->curSpace->getId();
            $this->client->call("box.space[{$spaceId}]:truncate");
        } else {
            $this->client->call("box.space.{$spaceName}:truncate");
        }
    }

    function drop(string $spaceName = null): void
    {
        if (is_null($spaceName)) {
            $spaceId = $this->curSpace->getId();
            $this->client->call("box.space[{$spaceId}]:drop");
        } else {
            $this->client->call("box.space.{$spaceName}:drop");
        }
    }

    public function setSpace(string $spaceName): Space
    {
        $this->curSpace = $this->client->getSpace("{$spaceName}");
        return $this->curSpace;
    }

    public function select(string $query, string $spaceName = null): array
    {
        if (is_null($spaceName)) {
            $spaceId = $this->curSpace->getId();
            return $this->client->evaluate("return box.space[{$spaceId}]:select({$query})");
        } else {
            return $this->client->evaluate("return box.space.{$spaceName}:select({$query})");
        }
    }

    public function insert(array $data, string $spaceName = NULL): void
    {
        if (is_null($spaceName)) {
            $this->curSpace->insert($data);
        } else {
            $this->client->getSpace("{$spaceName}")->insert($data);
        }
    }

    public function indexSelect(string $query, string $spaceName = null): array
    {
        if (is_null($spaceName)) {
            $spaceId = $this->curSpace->getId();
            return $this->client->evaluate("return box.space[{$spaceId}].index.{$query}");
        } else {
            return $this->client->evaluate("return box.space.{$spaceName}.index.{$query}");
        }
    }

    public function update(int $tupleId, string $query, string $spaceName = null): array
    {
        if (is_null($spaceName)) {
            $spaceId = $this->curSpace->getId();
            return $this->client->evaluate("return box.space[{$spaceId}]:update({$tupleId}, {{$query}})");
        } else {
            return $this->client->evaluate("return box.space.{$spaceName}:update({$tupleId}, {{$query}})");
        }
    }

    public function upsert(string $query, string $spaceName = null): array
    {
        if (is_null($spaceName)) {
            $spaceId = $this->curSpace->getId();
            return $this->client->evaluate("return box.space[{$spaceId}]:upsert({$query})");
        } else {
            return $this->client->evaluate("return box.space.{$spaceName}:upsert({$query})");
        }
    }

    public function replace(array $tuple, string $spaceName = null): array
    {
        if (is_null($spaceName)) {
            return $this->curSpace->replace($tuple);
        } else {
            $space = $this->client->getSpace($spaceName);
            return $space->replace($tuple);
        }
    }

    public function delete(array $keyPart1, string $keyPart2 = null, string $spaceName = null): array
    {

        if (is_null($spaceName)) {
            $space = $this->curSpace;
        } else {
            $space = $this->client->getSpace($spaceName);
        }
        if (is_null($keyPart2)) {
            return $space->delete($keyPart1);
        }
        return $space->delete($keyPart1, $keyPart2);
    }

    public function getCount(string $spaceName = null): int
    {
        if (is_null($spaceName)) {
            $spaceId = $this->curSpace->getId();
            return $this->curSpace->evaluate("return box.space[{$spaceId}]:count()")[0];
        } else {
            return $this->client->evaluate("return box.space.{$spaceName}:count()")[0];
        }
    }
}
