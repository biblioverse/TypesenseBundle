<?php

namespace Biblioteca\TypesenseBundle\Mapper\Entity;

use Doctrine\ORM\EntityManagerInterface;

/**
 * @template T of Object
 *
 * @extends AbstractEntityDataGenerator<T>
 */
class EntityDataGenerator extends AbstractEntityDataGenerator
{
    /**
     * @param class-string<T>               $className
     * @param EntityTransformerInterface<T> $entityTransformer
     */
    public function __construct(
        readonly EntityManagerInterface $entityManager,
        private readonly EntityTransformerInterface $entityTransformer,
        private readonly string $className,
    ) {
        parent::__construct($entityManager, $className);
    }

    public function transform(object $entity): array
    {
        return $this->entityTransformer->transform($entity);
    }

    public function support(object $entity): bool
    {
        return $this->className === $entity::class;
    }
}
