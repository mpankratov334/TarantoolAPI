<?php

use App\Actions\HealthAction;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

return [
    'BlackHole\Dispatcher' => simpleDispatcher(function (RouteCollector $router) {
        $router->post('/health', HealthAction::class);
        $router->post('/api/v1/user/create', \App\Actions\Api\v1\User\CreateAction::class);
        $router->post('/api/v1/user/delete', \App\Actions\Api\v1\User\DeleteAction::class);
        $router->post('/api/v1/user/get', \App\Actions\Api\v1\User\GetAction::class);
        $router->post('/api/v1/user/update', \App\Actions\Api\v1\User\UpdateAction::class);
        $router->post('/api/v1/event/create', \App\Actions\Api\v1\Event\CreateAction::class);
        $router->post('/api/v1/event/delete', \App\Actions\Api\v1\Event\DeleteAction::class);
        $router->post('/api/v1/event/get', \App\Actions\Api\v1\Event\GetAction::class);
        $router->post('/api/v1/event/update', \App\Actions\Api\v1\Event\UpdateAction::class);
        $router->post('/api/v1/task/create', \App\Actions\Api\v1\Event\Task\CreateAction::class);
        $router->post('/api/v1/task/delete', \App\Actions\Api\v1\Event\Task\DeleteAction::class);
        $router->post('/api/v1/task/get', \App\Actions\Api\v1\Event\Task\GetAction::class);
        $router->post('/api/v1/task/update', \App\Actions\Api\v1\Event\Task\UpdateAction::class);
    })
];
