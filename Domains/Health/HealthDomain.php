<?php

namespace App\Domains\Health;

use BlackHole\Http\Request;
use BlackHole\Http\Response;
use BlackHole\Interfaces\DomainInterface;
use Psr\Container\ContainerInterface;
use BlackHole\Utils\Logger;

class HealthDomain implements DomainInterface
{

    public function __construct(protected Logger $logger, protected ContainerInterface $container)
    {
    }

    public function getHealth(Request $request): Response
    {
        $result = [
            'success' => true,
            'health' => 'OK'
        ];
        $this->logger->info('HealthDomain:getHealth', $result);
        $response = new Response();
        $response->setStatusCode(200);
        $response->setBody($result);
        return $response;
    }
}