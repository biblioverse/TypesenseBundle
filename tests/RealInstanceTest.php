<?php

namespace Biblioverse\TypesenseBundle\Tests;

use Biblioverse\TypesenseBundle\Client\ClientInterface;
use Biblioverse\TypesenseBundle\Client\ClientSingletonFactory;
use Http\Discovery\Psr18ClientDiscovery;
use PHPUnit\Framework\TestCase;
use Typesense\Exceptions\ConfigError;

class RealInstanceTest extends TestCase
{
    /**
     * @throws ConfigError
     */
    protected function getClient(): ?ClientInterface
    {
        $httpClient = (new Psr18ClientDiscovery())->find();

        /** @var string|null $url */
        $url = $_ENV['TYPESENSE_URL'] ?? null;
        /** @var string|null $apiKey */
        $apiKey = $_ENV['TYPESENSE_API_KEY'] ?? null;

        if ($url === null || $apiKey === null) {
            return null;
        }

        $clientSingletonFactory = new ClientSingletonFactory($url, $apiKey, $httpClient);

        return $clientSingletonFactory->__invoke();
    }

    /**
     * @throws \Exception
     */
    public function testCanRetrieveMetrics(): void
    {
        $client = $this->getClient();
        if (!$client instanceof ClientInterface) {
            $this->markTestSkipped('env TYPESENSE_URL or TYPESENSE_API_KEY not provided');
        }

        $returnData = $client->getMetrics()->retrieve();
        $this->assertArrayHasKey('system_memory_used_bytes', $returnData);
    }
}
