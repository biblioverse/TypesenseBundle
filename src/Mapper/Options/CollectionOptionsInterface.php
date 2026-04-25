<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Mapper\Options;

interface CollectionOptionsInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
