<?php

namespace Biblioverse\TypesenseBundle\Tests\Populate\WaitFor;

use Biblioverse\TypesenseBundle\Client\ClientInterface;
use Biblioverse\TypesenseBundle\Populate\WaitFor\WaitForTypesenseService;
use PHPUnit\Framework\TestCase;
use Typesense\Health as HealthInterface;

class WaitForTypesenseServiceTest extends TestCase
{
    public function testWaitSucceedsImmediately(): void
    {
        $client = $this->createStub(ClientInterface::class);
        $health = $this->createStub(HealthInterface::class);
        $health->method('retrieve')->willReturn(['ok' => true]);

        $client->method('getHealth')->willReturn($health);

        $waitForTypesenseService = new WaitForTypesenseService($client);

        $this->expectNotToPerformAssertions();
        $waitForTypesenseService->wait(3, null, 0);
    }

    public function testWaitRetriesAndSucceeds(): void
    {
        $client = $this->createStub(ClientInterface::class);
        $health = $this->createStub(HealthInterface::class);
        $health->method('retrieve')->willReturn(['ok' => true]);

        $client->method('getHealth')->willReturn($health);

        $callCount = 0;
        $health->method('retrieve')->willReturnCallback(function () use (&$callCount) {
            if ($callCount++ === 0) {
                throw new \Exception('Service unavailable');
            }

            return true;
        });

        $waitForTypesenseService = new WaitForTypesenseService($client);

        $this->expectNotToPerformAssertions();
        $waitForTypesenseService->wait(3, null, 0);
    }

    public function testWaitThrowsExceptionOnUnHealthy(): void
    {
        $client = $this->createStub(ClientInterface::class);
        $health = $this->createStub(HealthInterface::class);
        $health->method('retrieve')->willReturn(['ok' => false]);
        $client->method('getHealth')->willReturn($health);

        $waitForTypesenseService = new WaitForTypesenseService($client);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Typesense is not available');

        $waitForTypesenseService->wait(3, null, 0);
    }

    public function testWaitThrowsExceptionAfterMaxRetries(): void
    {
        $client = $this->createStub(ClientInterface::class);
        $health = $this->createStub(HealthInterface::class);

        $client->method('getHealth')->willReturn($health);

        $health->method('retrieve')
            ->willThrowException(new \Exception('Service unavailable'));

        $waitForTypesenseService = new WaitForTypesenseService($client);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Typesense is not available');

        $waitForTypesenseService->wait(3, null, 0);
    }

    public function testGetNameReturnsTypesense(): void
    {
        $client = $this->createStub(ClientInterface::class);
        $waitForTypesenseService = new WaitForTypesenseService($client);

        $this->assertEquals('Typesense', $waitForTypesenseService->getName());
    }
}
