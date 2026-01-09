<?php

namespace Biblioverse\TypesenseBundle\Tests\Indexer;

use Biblioverse\TypesenseBundle\Indexer\Indexer;
use Biblioverse\TypesenseBundle\Indexer\IndexerInterface;
use Biblioverse\TypesenseBundle\Mapper\DataGeneratorInterface;
use Biblioverse\TypesenseBundle\Mapper\Entity\EntityTransformerInterface;
use Biblioverse\TypesenseBundle\Mapper\Entity\Identifier\EntityIdentifierInterface;
use Biblioverse\TypesenseBundle\Mapper\Locator\MapperLocatorInterface;
use Biblioverse\TypesenseBundle\Populate\PopulateService;
use Biblioverse\TypesenseBundle\Tests\Entity\Product;
use Biblioverse\TypesenseBundle\Tests\KernelTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Indexer::class)]
class IndexerTest extends KernelTestCase
{
    public function testIndexEntity(): void
    {
        self::bootKernel();

        // Mock the PopulateService to assert 'fillData' is called once
        $populateMock = $this->createMock(PopulateService::class);
        $populateMock->expects($this->once())
            ->method('fillData')
            ->with('products', ['id' => 12, 'name' => 'test'])
            ->willReturnCallback(function () {
                yield ['id' => (string) 12, 'name' => 'test'];
            });

        static::getContainer()->set(PopulateService::class, $populateMock);

        $indexer = $this->get(IndexerInterface::class);

        $product = new Product();
        $product->id = 12;
        $product->name = 'test';

        $indexer->indexEntity($product);
    }

    public function testRemoveEntity(): void
    {
        self::bootKernel();

        // Mock the PopulateService to assert 'fillData' is called once
        $populateMock = $this->createMock(PopulateService::class);
        $populateMock->expects($this->once())
            ->method('deleteData')
            ->with('products', ['id' => 12, 'name' => 'test'])
            ->willReturnCallback(function () {
                yield ['id' => '12', 'name' => 'test'];
            });

        static::getContainer()->set(PopulateService::class, $populateMock);

        $indexer = $this->get(IndexerInterface::class);

        $product = new Product();
        $product->id = 12;
        $product->name = 'test';

        $indexer->removeEntity($product);
    }

    public function testNoIndexOnUnsupportedEntity(): void
    {
        // Mock the PopulateService to assert 'fillData' is called once
        $populateMock = $this->createMock(PopulateService::class);
        $populateMock->expects($this->never())
            ->method('fillData');

        $mapperLocator = new class implements MapperLocatorInterface {
            public function hasDataGenerator(string $name): bool
            {
                return true;
            }

            public function getMappers(): array
            {
                return [];
            }

            public function countDataGenerator(): int
            {
                return 0;
            }

            /**
             * @template T of object
             *
             * @param class-string<T> $entity
             *
             * @return EntityTransformerInterface<T>[]
             */
            public function getEntityTransformers(string $entity): array
            {
                $myEntityTransformer = new class implements EntityTransformerInterface {
                    public function support(object $entity): bool
                    {
                        return false;
                    }

                    public function transform(object $entity): array
                    {
                        return [];
                    }
                };

                /** @var array<string, EntityTransformerInterface<T>> $result */
                $result = ['myEntityTransformer' => $myEntityTransformer];

                return $result;
            }

            public function hasEntityTransformer(string $classString): bool
            {
                return true;
            }

            public function getDataGenerator(string $shortName): DataGeneratorInterface
            {
                throw new \RuntimeException('Not implemented');
            }
        };

        $entityIdentifier = new class implements EntityIdentifierInterface {
            public function getIdentifiersValue(object $entity): array
            {
                return ['id' => 'fake'];
            }
        };
        $indexer = new Indexer($populateMock, $mapperLocator, $entityIdentifier);
        $indexer->indexEntity(new Product());
        $indexer->removeEntity(new Product());
    }
}
