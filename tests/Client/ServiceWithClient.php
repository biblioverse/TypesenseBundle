<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Tests\Client;

use Biblioverse\TypesenseBundle\Client\ClientInterface;
use Psr\Log\LoggerInterface;

class ServiceWithClient
{
    public function __construct(
        private readonly ClientInterface $client,
        public readonly LoggerInterface $logger,
    ) {
        $logger->debug(
            'ServiceWithClient::__construct',
            ['client' => $client]
        );
    }

    public function getClient(): ClientInterface
    {
        return $this->client;
    }
}
