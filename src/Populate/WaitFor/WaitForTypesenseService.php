<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Populate\WaitFor;

use Biblioverse\TypesenseBundle\Client\ClientInterface;

class WaitForTypesenseService extends AbstractWaitForService
{
    public function __construct(
        private readonly ClientInterface $client,
    ) {
    }

    public function getName(): string
    {
        return 'Typesense';
    }

    public function doCheck(): void
    {
        $health = $this->client->getHealth()->retrieve();
        if ($health !== ['ok' => true]) {
            throw new \Exception('Typesense is not healthy');
        }
    }
}
