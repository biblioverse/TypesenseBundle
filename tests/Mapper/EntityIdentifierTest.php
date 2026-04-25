<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Tests\Mapper;

use Biblioverse\TypesenseBundle\Mapper\Entity\Identifier\EntityIdentifier;
use Biblioverse\TypesenseBundle\Tests\Entity\Product;
use Biblioverse\TypesenseBundle\Tests\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

class EntityIdentifierTest extends KernelTestCase
{
    public const PRODUCT_GET_IDENTIFIER = 'Product GetIdentifier';

    public function testGetIdentifiersValue(): void
    {
        $this->bootKernel();

        $product = new Product();
        $product->name = self::PRODUCT_GET_IDENTIFIER;
        $entityManager = $this->get(EntityManagerInterface::class);
        $entityManager->persist($product);
        $entityManager->flush();

        $entityIdentifier = $this->get(EntityIdentifier::class);

        $this->assertSame(['id' => (string) $product->id], $entityIdentifier->getIdentifiersValue($product));
    }

    public function tearDown(): void
    {
        $entityManager = $this->get(EntityManagerInterface::class);
        $entityManager->getRepository(Product::class)->createQueryBuilder('p')
            ->delete()
            ->where('p.name = :name')
            ->getQuery()
            ->setParameter('name', self::PRODUCT_GET_IDENTIFIER)
            ->execute();
        parent::tearDown();
    }
}
