<?php

namespace Biblioverse\TypesenseBundle\Search;

use Biblioverse\TypesenseBundle\Query\SearchQuery;
use Biblioverse\TypesenseBundle\Search\Hydrate\HydrateSearchResultInterface;
use Biblioverse\TypesenseBundle\Search\Results\SearchResults;
use Biblioverse\TypesenseBundle\Search\Results\SearchResultsHydrated;

/**
 * @template T of object
 *
 * @implements SearchCollectionInterface<T>
 */
class SearchCollection implements SearchCollectionInterface
{
    /**
     * @param class-string<T>                 $entityClass
     * @param HydrateSearchResultInterface<T> $hydrateSearchResult
     */
    public function __construct(
        private readonly string $collectionName,
        private readonly string $entityClass,
        private readonly Search $search,
        private readonly HydrateSearchResultInterface $hydrateSearchResult,
    ) {
    }

    public function searchRaw(SearchQuery $searchQuery): SearchResults
    {
        return $this->search->search($this->collectionName, $searchQuery);
    }

    public function search(SearchQuery $searchQuery): SearchResultsHydrated
    {
        $searchResults = $this->search->search($this->collectionName, $searchQuery);

        return $this->hydrateSearchResult->hydrate($this->entityClass, $searchResults);
    }
}
