<?php

namespace Biblioverse\TypesenseBundle\Tests\Client;

use Biblioverse\TypesenseBundle\Client\ClientInterface;
use Biblioverse\TypesenseBundle\Tests\KernelTestCase;

class ClientAdapterTest extends KernelTestCase
{
    public function testClient(): void
    {
        $client = $this->get(ClientInterface::class);
        // Make sure the call is successful.
        $client->getCollection('books');

        $this->assertArrayHasKey('books', $client->getCollections());

        // Calling all methods to ensure they are available and don't crash.
        // Testing with instanceOf is making phpstan unhappy as the type is already narrowed.
        $client->getDebug();
        $client->getAliases();
        $client->getKeys();
        $client->getMetrics();
        $client->getHealth();
        $client->getOperations();
        $client->getMultiSearch();
        $client->getPresets();
        $client->getAnalytics();
    }
}
