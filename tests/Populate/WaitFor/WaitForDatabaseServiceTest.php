<?php

namespace Biblioverse\TypesenseBundle\Tests\Populate\WaitFor;

use Biblioverse\TypesenseBundle\Populate\WaitFor\WaitForDatabaseService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\InvalidArgumentException as DBALException;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class WaitForDatabaseServiceTest extends TestCase
{
    public function testWaitReturnsImmediatelyIfConnected(): void
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('isConnected')->willReturn(true);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        $waitForDatabaseService = new WaitForDatabaseService($entityManager);

        $callbackCalls = [];
        $callback = function (int $step, int $total, \Throwable $throwable) use (&$callbackCalls) {
            $callbackCalls[] = [$step, $total, $throwable];
        };

        $waitForDatabaseService->wait(5, $callback, 0);
        $this->assertCount(0, $callbackCalls);
    }

    public function testWaitRetriesUntilConnected(): void
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('isConnected')->willReturn(false);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        $waitForDatabaseService = new WaitForDatabaseService($entityManager);

        // Simulate failure twice before succeeding
        $attempts = 0;
        $connection->method('executeQuery')->willReturnCallback(function () use (&$attempts) {
            if ($attempts < 2) {
                ++$attempts;
                throw new DBALException('Internal error');
            }

            return $this->createStub(Result::class);
        });

        $waitForDatabaseService->wait(5, null, 0); // No sleep for faster test execution
        $this->assertEquals(2, $attempts);
    }

    public function testWaitThrowsExceptionAfterMaxAttempts(): void
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('isConnected')->willReturn(false);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        $waitForDatabaseService = new WaitForDatabaseService($entityManager);

        $connection->method('executeQuery')->willThrowException(new DBALException('Internal error'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database is not available');

        $waitForDatabaseService->wait(3, null, 0);
    }

    public function testWaitCallsCallbackOnFailure(): void
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('isConnected')->willReturn(false);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        $waitForDatabaseService = new WaitForDatabaseService($entityManager);

        $attempts = 0;
        $connection->method('executeQuery')->willReturnCallback(function () use (&$attempts) {
            ++$attempts;
            throw new DBALException('Internal error');
        });

        $callbackCalls = [];
        $callback = function ($step, $total, $exception) use (&$callbackCalls) {
            $callbackCalls[] = [$step, $total, $exception];
        };

        try {
            $waitForDatabaseService->wait(3, $callback, 0);
            $this->fail('Expected exception for waiting too many times');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Database is not available', $e->getMessage());
        }

        $this->assertCount(3, $callbackCalls);
        $i = 1;
        foreach ($callbackCalls as [$step, $total, $exception]) {
            $this->assertEquals($i++, $step, 'step number is not valid');
            $this->assertEquals(3, $total, 'total number is not valid');
            $this->assertInstanceOf(\Throwable::class, $exception);
        }
    }
}
