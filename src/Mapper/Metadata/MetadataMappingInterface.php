<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Mapper\Metadata;

interface MetadataMappingInterface
{
    /**
     * Metadata options and value.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array;
}
