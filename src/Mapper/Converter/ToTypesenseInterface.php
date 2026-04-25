<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Mapper\Converter;

interface ToTypesenseInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toTypesense(): array;
}
