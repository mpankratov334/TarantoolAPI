<?php

namespace App\Actions\Api\v1\User;

use App\Domains\Api\v1\UserDomain;
use BlackHole\Http\Request;
use BlackHole\Http\Response;
use BlackHole\Interfaces\ActionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class UpdateAction implements ActionInterface
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(Request $request): Response
    {
        $eventDomain = $this->container->get(UserDomain::class);
        return $eventDomain->update($request);
    }
}