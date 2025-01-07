<?php

namespace Biblioteca\TypesenseBundle\Tests\Search\Hydrate;

use Biblioteca\TypesenseBundle\Search\Hydrate\HydrateSearchResult;
use Biblioteca\TypesenseBundle\Search\Results\SearchResults;
use Biblioteca\TypesenseBundle\Search\Results\SearchResultsHydrated;
use Biblioteca\TypesenseBundle\Tests\Entity\Product;
use Biblioteca\TypesenseBundle\Tests\KernelTestCase;

class HydrateSearchResultTest extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testHydrateWithIdIndexes(): void
    {
        $searchResults = new SearchResults(['hits' => [
            ['document' => ['id' => 1]],
            ['document' => ['id' => 2]],
        ]]);

        $searchResultsHydrated = $this->get(HydrateSearchResult::class)->hydrate(Product::class, $searchResults);

        $this->assertInstanceOf(SearchResultsHydrated::class, $searchResultsHydrated);
        $objects = iterator_to_array($searchResultsHydrated->getIterator());
        $this->assertCount(2, $objects);
        $this->assertArrayHasKey(1, $objects);
        $this->assertArrayHasKey(2, $objects);
        $this->assertInstanceOf(Product::class, $objects[1]);
        $this->assertInstanceOf(Product::class, $objects[2]);
    }

    public function testHydrateEmpty(): void
    {
        $searchResults = new SearchResults(['hits' => []]);

        $searchResultsHydrated = $this->get(HydrateSearchResult::class)->hydrate(Product::class, $searchResults);

        $this->assertInstanceOf(SearchResultsHydrated::class, $searchResultsHydrated);
        $objects = iterator_to_array($searchResultsHydrated->getIterator());
        $this->assertCount(0, $objects);
    }

    public function testHydrateInvalidIds(): void
    {
        $searchResults = new SearchResults(['hits' => [['document' => ['no_id' => 'a']]]]);

        $searchResultsHydrated = $this->get(HydrateSearchResult::class)->hydrate(Product::class, $searchResults);

        $this->assertInstanceOf(SearchResultsHydrated::class, $searchResultsHydrated);
        $objects = iterator_to_array($searchResultsHydrated->getIterator());
        $this->assertCount(0, $objects);
    }
}
