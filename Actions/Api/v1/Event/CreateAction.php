<?php
namespace App\Actions\Api\v1\Event;

use App\Domains\Api\v1\EventDomain;
use BlackHole\Http\Request;
use BlackHole\Http\Response;
use BlackHole\Interfaces\ActionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class CreateAction implements ActionInterface
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
        $eventDomain = $this->container->get(EventDomain::class);
        return $eventDomain->create($request);
    }
}