<?php

namespace Biblioverse\TypesenseBundle\Tests;

use Biblioverse\TypesenseBundle\Mapper\Locator\MapperLocator;
use Biblioverse\TypesenseBundle\Tests\Client\ServiceWithClient;
use Biblioverse\TypesenseBundle\Tests\Entity\Product;

class ContainerTest extends KernelTestCase
{
    public function testMapperLocatorExists(): void
    {
        $kernel = self::bootKernel();
        $kernel->getContainer();

        $this->assertContainerHas(MapperLocator::class);
    }

    public function testClientFactory(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $this->assertContainerHas(ServiceWithClient::class);

        $service = $container->get(ServiceWithClient::class);
        $this->assertInstanceOf(ServiceWithClient::class, $service);

        // Do the call to make sure the client is working.
        $service->getClient();
    }

    public function testClientFactoryFilterSubMapping(): void
    {
        self::bootKernel([
            'configs' => [TestKernel::CONFIG_KEY => 'config/packages/biblioverse_typesense_embedding.yaml'],
        ]);
        $mapperLocator = $this->get(MapperLocator::class);
        $mappingGenerators = $mapperLocator->getEntityMappers(Product::class);
        self::assertArrayHasKey('products', $mappingGenerators);
        $mapping = $mappingGenerators['products']->getMapping();
        $fields = $mapping->getFields();
        self::assertCount(2, $fields);
        $embeddingValues = $fields[1]->toArray();
        self::assertSame(
            [
                'index' => true,
                'name' => 'embedding',
                'num_dims' => 768,
                'type' => 'float[]',
                'embed' => [
                    'from' => ['tags', 'summary'],
                    'model_config' => ['model_name' => 'mymodel'],
                ],
            ], $embeddingValues);
    }

    public function testClientFactoryInvalidUrl(): void
    {
        self::bootKernel([
            'configs' => [TestKernel::CONFIG_KEY => 'config/packages/biblioverse_typesense_wrong_url.yaml'],
        ]);
        $container = self::getContainer();

        $this->assertContainerHas(ServiceWithClient::class);

        $service = $container->get(ServiceWithClient::class);
        $this->assertInstanceOf(ServiceWithClient::class, $service);

        try {
            // https://github.com/symfony/symfony/issues/53812 => can't use expectException yet.
            $service->getClient()->getCollection('books');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('Invalid URI ./s?a=12&b=12.3.3.4:1233', $e->getMessage());

            return;
        }

        $this->fail('An \InvalidArgumentException has not been raised.');
    }
}
