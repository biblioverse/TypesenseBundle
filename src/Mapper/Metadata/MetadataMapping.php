<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Mapper\Metadata;

use Biblioverse\TypesenseBundle\Utils\ArrayAccessTrait;

/**
 * @implements \ArrayAccess<string, mixed>
 * @implements \IteratorAggregate<string, mixed>
 */
class MetadataMapping implements MetadataMappingInterface, \ArrayAccess, \IteratorAggregate
{
    /**
     * @use ArrayAccessTrait<string, mixed>
     */
    use ArrayAccessTrait;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
