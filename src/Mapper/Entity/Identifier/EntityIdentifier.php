<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Mapper\Entity\Identifier;

use Doctrine\ORM\EntityManagerInterface;

class EntityIdentifier implements EntityIdentifierInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{id: string}
     */
    public function getIdentifiersValue(object $entity): array
    {
        $identifiers = $this->entityManager->getClassMetadata($entity::class)->getIdentifierValues($entity);

        $identifiers = array_map(function (mixed $value) {
            if (!is_scalar($value)) {
                throw new \InvalidArgumentException('Identifier value must be scalar');
            }

            return (string) $value;
        }, $identifiers);

        ksort($identifiers);

        return ['id' => implode('_', array_values($identifiers))];
    }
}
