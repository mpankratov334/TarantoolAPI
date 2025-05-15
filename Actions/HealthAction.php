<?php

namespace App\Actions;

use App\Domains\Health\HealthDomain;
use BlackHole\Http\Request;
use BlackHole\Http\Response;
use BlackHole\Interfaces\ActionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class HealthAction implements ActionInterface
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
        $healthDomain = $this->container->get(HealthDomain::class);
        return $healthDomain->getHealth($request);
    }
}