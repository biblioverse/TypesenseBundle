<?php

namespace Biblioverse\TypesenseBundle\Tests\Mapper\Locator;

use Biblioverse;
use Biblioverse\TypesenseBundle\Mapper\Entity\EntityTransformerInterface;
use Biblioverse\TypesenseBundle\Mapper\Locator\InvalidTypeMapperException;
use Biblioverse\TypesenseBundle\Mapper\Locator\MapperLocator;
use Biblioverse\TypesenseBundle\Tests\Entity\Product;
use Biblioverse\TypesenseBundle\Tests\TestKernel;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Contracts\Service\ServiceCollectionInterface;

#[CoversClass(MapperLocator::class)]
class MapperLocatorTest extends Biblioverse\TypesenseBundle\Tests\KernelTestCase
{
    public function testLocatorMyMapper(): void
    {
        static::ensureKernelShutdown();
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $locator = $container->get(MapperLocator::class);
        $this->assertInstanceOf(MapperLocator::class, $locator);

        $this->assertSame(1, $locator->countDataGenerator(), 'The locator should have exactly 1 data generator.');
        $this->assertTrue($locator->hasDataGenerator('products'), 'The locator should have the products service.');

        $locator->getDataGenerator('products');
    }

    public function testLocatorUnknownService(): void
    {
        self::bootKernel();
        $mapperLocator = $this->get(MapperLocator::class);

        $this->expectException(\InvalidArgumentException::class);
        $mapperLocator->getDataGenerator('unknown');
    }

    public function testLocatorInstanceOfIssue(): void
    {
        static::ensureKernelShutdown();
        self::bootKernel([
            'configs' => ['config/services_with_wrong_mapper.yaml'],
        ]);

        $mapperLocator = $this->get(MapperLocator::class);
        try {
            $mapperLocator->getDataGenerator('myInvalidMapper');
            $this->fail('The locator should throw an exception.');
        } catch (InvalidTypeMapperException $e) {
            $this->assertStringContainsString('No data generator found', $e->getMessage());
        }
    }

    public function testLocatorGetMappersInstanceOfIssue(): void
    {
        self::bootKernel([
            'configs' => ['config/services_with_wrong_mapper.yaml'],
        ]);

        $mapperLocator = $this->get(MapperLocator::class);

        try {
            $this->assertGreaterThan(42, count($mapperLocator->getMappers()), 'The locator have thrown an exception.');
            $this->fail('The locator should throw an exception.');
        } catch (InvalidTypeMapperException $e) {
            $this->assertStringContainsString('not found', $e->getMessage());
        }
    }

    public function testLocatorGetMappers(): void
    {
        self::bootKernel();

        $mapperLocator = $this->get(MapperLocator::class);
        $result = $mapperLocator->getMappers();

        $this->assertArrayHasKey('products', $result);
    }

    public function testLocatorGetMappingGenerator(): void
    {
        self::bootKernel([
            'configs' => [TestKernel::CONFIG_KEY => 'config/packages/biblioverse_typesense_mapping.yaml'],
        ]);

        $mapperLocator = $this->get(MapperLocator::class);
        $result = $mapperLocator->getMappers();

        $this->assertArrayHasKey('products', $result);
        $this->assertSame(1, $mapperLocator->countDataGenerator());
        // Make sure the mapping generator don't crash. Calling instanceof is making phpunit unhappy because of already narrowed type.
        $mapperLocator->getDataGenerator('products');

        $entityMappers = $mapperLocator->getEntityMappers(Product::class);
        $this->assertCount(1, $entityMappers);
        $this->assertArrayHasKey('products', $entityMappers);
    }

    public function testNoEntityTransformer(): void
    {
        self::bootKernel([
            'configs' => [TestKernel::CONFIG_KEY => 'config/packages/biblioverse_typesense_mapping.yaml'],
        ]);

        $mapperLocator = $this->get(MapperLocator::class);

        $this->assertFalse($mapperLocator->hasEntityTransformer(\stdClass::class));
    }

    public function testGetEntityTransformers(): void
    {
        $myClass = new class {
        };

        $testEntityTransformer = $this->getTestEntityTransformer();

        $mapperLocator = new MapperLocator(
            collectionManagers: $this->getServiceCollection([]),
            dataGenerators: $this->getServiceCollection([]),
            mappingGenerators: $this->getServiceCollection([]),
            entityTransformers: $this->getServiceCollection(['test' => $testEntityTransformer]),
            entityMapping: [$myClass::class => ['test']],
        );

        $transformer = $mapperLocator->getEntityTransformers($myClass::class);
        $this->assertArrayHasKey('test', $transformer);
        $this->assertSame(spl_object_hash($testEntityTransformer), spl_object_hash($transformer['test']));
    }

    /**
     * @template T of object
     *
     * @param array<string,T> $services
     *
     * @return ServiceCollectionInterface<T>
     */
    public function getServiceCollection(array $services): ServiceCollectionInterface
    {
        return new class($services) implements ServiceCollectionInterface {
            public function __construct(
                /** @var array<string,T> $services */
                private readonly array $services,
            ) {
            }

            /**
             * @return \Traversable<T>
             */
            public function getIterator(): \Traversable
            {
                return new \ArrayIterator($this->services);
            }

            public function count(): int
            {
                return count($this->services);
            }

            /**
             * @return ?T
             */
            public function get(string $id): mixed
            {
                return $this->services[$id] ?? null;
            }

            public function has(string $id): bool
            {
                return array_key_exists($id, $this->services);
            }

            /**
             * @return array<string, string>
             */
            public function getProvidedServices(): array
            {
                $keys = array_keys($this->services);
                $names = array_map(get_class(...), $this->services);

                return array_combine($keys, $names);
            }
        };
    }

    /**
     * @return EntityTransformerInterface<object>
     */
    private function getTestEntityTransformer(): EntityTransformerInterface
    {
        return new class implements EntityTransformerInterface {
            public function support(object $entity): bool
            {
                return true;
            }

            public function transform(object $entity): array
            {
                return [];
            }
        };
    }
}
