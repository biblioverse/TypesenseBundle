<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Mapper\Mapping;

use Biblioverse\TypesenseBundle\Mapper\Fields\FieldMappingInterface;
use Biblioverse\TypesenseBundle\Mapper\Metadata\MetadataMappingInterface;
use Biblioverse\TypesenseBundle\Mapper\Options\CollectionOptionsInterface;

interface MappingInterface
{
    /**
     * @return FieldMappingInterface[]
     */
    public function getFields(): array;

    public function getCollectionOptions(): ?CollectionOptionsInterface;

    public function getMetadata(): ?MetadataMappingInterface;
}
