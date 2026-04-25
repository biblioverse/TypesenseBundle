<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Mapper\Locator;

use Biblioverse\TypesenseBundle\Mapper\CollectionManagerInterface;
use Biblioverse\TypesenseBundle\Mapper\DataGeneratorInterface;
use Biblioverse\TypesenseBundle\Mapper\Entity\EntityTransformerInterface;

interface MapperLocatorInterface
{
    public function hasDataGenerator(string $name): bool;

    /**
     * TODO: Split this.
     *
     * @return array<string, CollectionManagerInterface>
     */
    public function getMappers(): array;

    public function countDataGenerator(): int;

    /**
     * @template T of object
     *
     * @param class-string<T> $entity
     *
     * @return array<string, EntityTransformerInterface<T>>
     */
    public function getEntityTransformers(string $entity): array;

    /**
     * @template T of object
     *
     * @param class-string<T> $classString $classString
     */
    public function hasEntityTransformer(string $classString): bool;

    public function getDataGenerator(string $shortName): DataGeneratorInterface;
}
