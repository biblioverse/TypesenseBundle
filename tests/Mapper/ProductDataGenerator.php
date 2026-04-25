<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Tests\Mapper;

use Biblioverse\TypesenseBundle\Mapper\Entity\AbstractEntityDataGenerator;
use Biblioverse\TypesenseBundle\Mapper\Mapping\Mapping;
use Biblioverse\TypesenseBundle\Mapper\MappingGeneratorInterface;
use Biblioverse\TypesenseBundle\Mapper\StandaloneCollectionManagerInterface;
use Biblioverse\TypesenseBundle\Tests\Entity\Product;
use Biblioverse\TypesenseBundle\Type\DataTypeEnum;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends AbstractEntityDataGenerator<Product>
 */
class ProductDataGenerator extends AbstractEntityDataGenerator implements MappingGeneratorInterface, StandaloneCollectionManagerInterface
{
    public function __construct(
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($entityManager, Product::class);
    }

    public static function getName(): string
    {
        return 'products';
    }

    public function getMapping(): Mapping
    {
        return (new Mapping())
            ->add(name: 'id', type: DataTypeEnum::STRING)
            ->add(name: 'name', type: DataTypeEnum::STRING)
        ;
    }

    public function transform(object $entity): array
    {
        return [
            'id' => (string) $entity->id,
            'name' => $entity->name,
        ];
    }

    public function support(object $entity): bool
    {
        return $entity::class === Product::class;
    }
}
