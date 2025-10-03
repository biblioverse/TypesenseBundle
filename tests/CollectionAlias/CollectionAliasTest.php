<?php

namespace Biblioverse\TypesenseBundle\Tests\CollectionAlias;

use Biblioverse\TypesenseBundle\Client\ClientInterface;
use Biblioverse\TypesenseBundle\CollectionAlias\CollectionAlias;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Typesense\Aliases;
use Typesense\Collection;
use Typesense\Exceptions\TypesenseClientError;

#[CoversClass(CollectionAlias::class)]
class CollectionAliasTest extends TestCase
{
    public function testTemplate(): void
    {
        $client = $this->createMock(ClientInterface::class);

        $collectionAlias = new CollectionAlias($client, 'pre-%s-suffix');

        // Suffix works
        $this->assertStringStartsWith('pre-books-suffix-', $collectionAlias->getName('books'));

        // Date is injected
        $this->assertStringStartsWith('pre-books-suffix-'.date('Y'), $collectionAlias->getName('books'));

        // Revert works
        $this->assertSame('books', $collectionAlias->revertName('pre-books-suffix-'.date('Y-m-d-H-i-s')));
    }

    public function testSwitch(): void
    {
        $collection = $this->createMock(Collection::class);
        $aliases = $this->createMock(Aliases::class);

        $client = $this->getMockBuilder(ClientInterface::class)
            ->enableOriginalConstructor()
            ->getMock();
        $client->method('getCollection')
            ->willReturn($collection);

        $client->method('getAliases')
            ->willReturn($aliases);

        $aliases->expects($this->once())
            ->method('upsert')
            ->with('books', ['collection_name' => 'books_alias']);

        $collectionAlias = new CollectionAlias($client);
        $collectionAlias->switch('books', 'books_alias');

        $this->assertTrue(true); // @phpstan-ignore-line The mock will fail if the method is not called
    }

    public function testMissingAliasSwitch(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->method('retrieve')->willThrowException(new TypesenseClientError('Collection books_alias not found'));
        $aliases = $this->createMock(Aliases::class);

        $client = $this->getMockBuilder(ClientInterface::class)
            ->enableOriginalConstructor()
            ->getMock();
        $client->method('getCollection')->willReturn($collection);

        $client->method('getAliases')
            ->willReturn($aliases);

        $aliases->expects($this->once())
            ->method('upsert')
            ->willThrowException(new TypesenseClientError('Collection books not found'));

        $collectionAlias = new CollectionAlias($client);

        try {
            $collectionAlias->switch('books', 'books_alias');
            $this->fail('Collection should be not found');
        } catch (\Exception $e) {
            $this->assertEquals('Collection books not found', $e->getMessage());
        }
    }
}
