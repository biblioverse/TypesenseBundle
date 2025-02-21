<?php

namespace Biblioverse\TypesenseBundle\Tests\Populate;

use Biblioverse\TypesenseBundle\Client\ClientInterface;
use Biblioverse\TypesenseBundle\Mapper\DataGeneratorInterface;
use Biblioverse\TypesenseBundle\Mapper\Mapping\Mapping;
use Biblioverse\TypesenseBundle\Mapper\Mapping\MappingInterface;
use Biblioverse\TypesenseBundle\Mapper\Metadata\MetadataMapping;
use Biblioverse\TypesenseBundle\Populate\PopulateService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Typesense\ApiCall;
use Typesense\Collection;
use Typesense\Collections;
use Typesense\Document;
use Typesense\Documents;

class PopulateServiceTest extends TestCase
{
    /**
     * @return array<string, array{collectionName: string, mapping: MappingInterface, expectedPayload: array<string, mixed>}>
     */
    public static function provideCreateCollection(): array
    {
        return [
            'simple' => [
                'collectionName' => 'collection',
                'mapping' => (new Mapping())->add('id', 'string')->add('name', 'string'),
                'expectedPayload' => [
                    'name' => 'collection',
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'name', 'type' => 'string'],
                    ],
                ],
            ],
            'with metadata' => [
                'collectionName' => 'collection',
                'mapping' => (new Mapping(metadataMapping: new MetadataMapping(['extra' => true])))->add('id', 'string')->add('value', 'int'),
                'expectedPayload' => [
                    'name' => 'collection',
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'value', 'type' => 'int'],
                    ],
                    'metadata' => ['extra' => true],
                ],
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function testThrowWithoutId(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $populateService = $this->getInstance();
        $populateService->createCollection('collection', new Mapping());
    }

    /**
     * @throws Exception
     */
    public function testDeleteDataWithoutId(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $populateService = $this->getInstance();
        $populateService->deleteData('collection', ['name' => 'my name']); // No id provided.
    }

    public function testFillDataDocument(): void
    {
        $data = ['id' => '42', 'name' => 'my name'];
        $docs = $this->createMock(Documents::class);
        $docs->expects($this->once())->method('upsert')->with($data)->willReturn([]);
        $collection = new Collection('collection', $this->createMock(ApiCall::class));
        $collection->documents = $docs;

        $populateService = $this->getInstance(function (MockObject $mockObject) use ($collection) {
            $mockObject
                ->expects($this->once())
                ->method('getCollection')
                ->with('collection')
                ->willReturn($collection);
        });

        $populateService->fillData('collection', $data);
    }

    /**
     * @param array<string, mixed> $expectedPayload
     *
     * @throws Exception
     */
    #[DataProvider('provideCreateCollection')]
    public function testCreateCollection(string $collectionName, MappingInterface $mapping, array $expectedPayload): void
    {
        $collections = $this->createMock(Collections::class);
        $collections->expects($this->once())
            ->method('create')
            ->with($expectedPayload)
            ->willReturn([]);

        $populateService = $this->getInstance(function (MockObject $mockObject) use ($collections, $collectionName) {
            $mockObject->expects($this->once())
                ->method('getCollections')
                ->willReturn($collections);

            $mockObject->expects($this->once())
                ->method('getCollection')
                ->willReturn(new Collection($collectionName, $this->createMock(ApiCall::class)));
        });

        $populateService->createCollection($collectionName, $mapping);
    }

    public function testFillCollection(): void
    {
        $datas = [
            ['id' => 42, 'name' => 'my name 42'],
            ['id' => 43, 'name' => 'my name 43'],
            ['id' => 44, 'name' => 'my name 44'],
            ['id' => 45, 'name' => 'my name 45'],
        ];
        $expected = [
            [$datas[0], $datas[1]],
            [$datas[2], $datas[3]],
        ];
        $batchSize = 2;

        $docs = $this->createMock(Documents::class);

        $docs->expects($this->exactly(count($datas) / $batchSize))
            ->method('import')
            ->with($this->isArrayBC())
            ->willReturnCallback(function ($arg) use ($expected) {
                /** @var int $index */
                static $index = 0;
                $this->assertSame($expected[$index], $arg, 'Mismatch on call '.$index);
                ++$index;

                return [];
            });

        $collection = new Collection('collection', $this->createMock(ApiCall::class));
        $collection->documents = $docs;

        $populateService = $this->getInstance(function (MockObject $mockObject) use ($collection) {
            $mockObject
                ->expects($this->once())
                ->method('getCollection')
                ->with('collection')
                ->willReturn($collection);
        }, $batchSize);

        $dataGenerator = new class($datas) implements DataGeneratorInterface {
            /** @param array<string, mixed>[] $datas */
            public function __construct(private readonly array $datas)
            {
            }

            public function getData(): \Generator
            {
                yield from $this->datas;
            }

            public function getDataCount(): int
            {
                return count($this->datas);
            }
        };

        $populateService->fillCollection('collection', $dataGenerator,
            function ($arg) use ($expected) {
                /** @var int $index */
                static $index = 0;
                $this->assertSame($expected[$index], $arg, 'Mismatch on callback result '.$index);
                ++$index;
            }
        );
    }

    /**
     * @throws Exception
     */
    public function testDeleteDocument(): void
    {
        $docs = $this->createMock(Documents::class);
        $doc = $this->createMock(Document::class);
        $docs->expects($this->once())->method('offsetGet')->with('42')->willReturn($doc);
        $doc->expects($this->once())
            ->method('delete');

        $collection = new Collection('collection', $this->createMock(ApiCall::class));
        $collection->documents = $docs;

        $populateService = $this->getInstance(function ($client) use ($collection) {
            $client->expects($this->once())
                ->method('getCollection')
                ->with('collection')
                ->willReturn($collection);
        });

        $populateService->deleteData('collection', ['id' => '42']);
    }

    public function testDeleteCollection(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())
            ->method('delete');

        $populateService = $this->getInstance(function (MockObject $mockObject) use ($collection) {
            $mockObject->expects($this->once())
                ->method('getCollection')
                ->with('collection')
                ->willReturn($collection);
        });

        $populateService->deleteCollection('collection');
    }

    /**
     * @param ?callable(MockObject):void $configureClientMock
     *
     * @throws Exception
     */
    public function getInstance(?callable $configureClientMock = null, int $batchSize = 100): PopulateService
    {
        $client = $this->createMock(ClientInterface::class);
        if ($configureClientMock !== null) {
            $configureClientMock($client);
        }

        return new PopulateService($client, $batchSize);
    }

    public function isArrayBC(): mixed
    {
        if (method_exists(TestCase::class, 'isArray')) {
            return TestCase::isArray();
        }

        return $this->isType('array');
    }
}
