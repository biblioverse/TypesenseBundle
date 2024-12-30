<?php

use Biblioteca\TypesenseBundle\Populate\PopulateService;
use Biblioteca\TypesenseBundle\Tests\DataFixtures\ProductFixtures;
use Biblioteca\TypesenseBundle\Tests\Entity\Product;

class AutopopulateTest extends Biblioteca\TypesenseBundle\Tests\KernelTestCase
{
    private ?int $lastId = null;

    private function getLastId(): int
    {
        // TODO Find a clever way to have the last ID
        $entityManager = $this->get(Doctrine\ORM\EntityManagerInterface::class);
        $product = new Product();
        $product->name = 'lastId';
        $entityManager->persist($product);
        $entityManager->flush();

        return $product->id ?? 0;
    }

    public function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->lastId = $this->getLastId();

        // Each test only keep the fixtures
        $entityManager = $this->get(Doctrine\ORM\EntityManagerInterface::class);
        $entityManager->getRepository(Product::class)->createQueryBuilder('p')
            ->delete()
            ->where('p.id > :max')
            ->getQuery()
            ->setParameter('max', ProductFixtures::MAX)
            ->execute();

        // Reset product 1's name
        $entityManager->getRepository(Product::class)->findOneBy(['id' => 1])
            ?->setName('Product 1');
        $entityManager->flush();

        self::ensureKernelShutdown();
    }

    public function testNewEntity(): void
    {
        self::bootKernel();

        // Mock the PopulateService to assert 'fillData' is called once
        $populateMock = $this->createMock(PopulateService::class);
        $populateMock->expects($this->once())
        ->method('fillData')
            ->with('products', ['id' => $this->lastId + 1, 'name' => 'test'])
            ->willReturnCallback(function () {
                yield ['id' => (string) ($this->lastId + 1), 'name' => 'test'];
            });

        static::getContainer()->set(PopulateService::class, $populateMock);

        $product = new Product();
        $product->name = 'test';

        $entityManager = $this->get(Doctrine\ORM\EntityManagerInterface::class);
        $entityManager->persist($product);
        $entityManager->flush();

        $this->assertTrue(true); // @phpstan-ignore-line The mock does the assertion, this remove a warning
    }

    public function testUpdateEntity(): void
    {
        self::bootKernel();
        $name = 'Product 1 +test'.uniqid();
        // Mock the PopulateService to assert 'fillData' is called once
        $populateMock = $this->createMock(PopulateService::class);
        $populateMock->expects($this->once())
            ->method('fillData')
            ->with('products', ['id' => 1, 'name' => $name])
            ->willReturnCallback(function () use ($name) {
                yield ['id' => (string) 1, 'name' => $name];
            });

        static::getContainer()->set(PopulateService::class, $populateMock);

        $entityManager = $this->get(Doctrine\ORM\EntityManagerInterface::class);
        $product = $entityManager->getRepository(Product::class)->findOneBy(['id' => 1]);
        $this->assertNotNull($product);

        $product->name = $name;
        $entityManager->flush();

        $this->assertTrue(true); // @phpstan-ignore-line The mock does the assertion, this remove a warning
    }

    public function testDeleteEntity(): void
    {
        self::bootKernel();
        $newProduct = new Product();
        $newProduct->name = 'deleteMe';

        $em = $this->get(Doctrine\ORM\EntityManagerInterface::class);
        $em->persist($newProduct);
        $em->flush();

        self::ensureKernelShutdown();
        self::bootKernel();

        // Mock the PopulateService to assert 'fillData' is called once
        $populateMock = $this->createMock(PopulateService::class);
        $populateMock->expects($this->once())
            ->method('deleteData')
            ->with('products', ['id' => $newProduct->id, 'name' => $newProduct->name])
            ->willReturnCallback(function () use ($newProduct) {
                yield ['id' => (string) $newProduct->id, 'name' => $newProduct->name];
            });

        static::getContainer()->set(PopulateService::class, $populateMock);

        $em = $this->get(Doctrine\ORM\EntityManagerInterface::class);
        $product = $em->getRepository(Product::class)->findOneBy(['name' => 'deleteMe']);
        $this->assertNotNull($product);
        $em->remove($product);
        $em->flush();
    }
}
