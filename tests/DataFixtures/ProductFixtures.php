<?php

namespace Biblioteca\TypesenseBundle\Tests\DataFixtures;

use Biblioteca\TypesenseBundle\Tests\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public const MAX = 20;

    public function load(ObjectManager $objectManager): void
    {
        // create 20 Product! Bam!
        foreach (range(1, self::MAX) as $i) {
            $product = new Product();
            $product->name = 'Product '.$i;
            $objectManager->persist($product);
        }

        $objectManager->flush();
    }
}
