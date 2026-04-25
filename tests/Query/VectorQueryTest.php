<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Tests\Query;

use Biblioverse\TypesenseBundle\Query\VectorQuery;
use PHPUnit\Framework\TestCase;

class VectorQueryTest extends TestCase
{
    public function testToString(): void
    {
        $vectorQuery = new VectorQuery('embedding', queryVector: [0.96826, 0.94, 0.39557, 0.306488], k: 100, flatSearchCutoff: 20);

        $this->assertSame('embedding:([0.96826, 0.94, 0.39557, 0.306488], k:100, flat_search_cutoff:20)', $vectorQuery->__toString());
    }
}
