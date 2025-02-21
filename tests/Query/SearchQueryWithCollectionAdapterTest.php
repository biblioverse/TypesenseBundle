<?php

namespace Biblioverse\TypesenseBundle\Tests\Query;

use Biblioverse\TypesenseBundle\Query\SearchQueryInterface;
use Biblioverse\TypesenseBundle\Query\SearchQueryWithWithCollectionAdapter;
use PHPUnit\Framework\TestCase;

class SearchQueryWithCollectionAdapterTest extends TestCase
{
    public function testAdapter(): void
    {
        $query = new class implements SearchQueryInterface {
            public function toArray(): array
            {
                return ['q' => '12'];
            }
        };

        $searchQueryWithWithCollectionAdapter = new SearchQueryWithWithCollectionAdapter($query, 'def');
        $this->assertSame('def', $searchQueryWithWithCollectionAdapter->getCollection());
        $this->assertSame(['q' => '12'], $searchQueryWithWithCollectionAdapter->toArray());
    }
}
