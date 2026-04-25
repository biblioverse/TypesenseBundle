<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Query;

interface SearchQueryInterface
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(): array;
}
