<?php

namespace Biblioverse\TypesenseBundle\Search;

use Biblioverse\TypesenseBundle\Query\SearchQuery;
use Biblioverse\TypesenseBundle\Query\SearchQueryInterface;
use Biblioverse\TypesenseBundle\Query\SearchQueryWithCollectionInterface;
use Biblioverse\TypesenseBundle\Query\SearchQueryWithWithCollectionAdapter;
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

    /**
     * @param SearchQueryWithCollectionInterface[] $searchQueries
     * @param array<string, mixed>                 $queryParameters
     *
     * @return array<SearchResultsHydrated<T>>
     */
    public function multisearch(array $searchQueries, array $queryParameters = []): array
    {
        $searchQueriesWithCollection = array_map(fn (SearchQueryInterface $searchQuery) => new SearchQueryWithWithCollectionAdapter($searchQuery, $this->collectionName), $searchQueries);

        $searchResults = $this->search->multisearch($searchQueriesWithCollection, $queryParameters);

        return array_map(fn (SearchResults $searchResults) => $this->hydrateSearchResult->hydrate($this->entityClass, $searchResults), $searchResults);
    }

    /**
     * @param SearchQuery[]        $searchQueries
     * @param array<string, mixed> $queryParameters
     *
     * @return array<SearchResults>
     */
    public function multisearchRaw(array $searchQueries, array $queryParameters = []): array
    {
        $searchQueriesWithCollection = array_map(fn (SearchQueryInterface $searchQuery) => new SearchQueryWithWithCollectionAdapter($searchQuery, $this->collectionName), $searchQueries);

        return $this->search->multisearch($searchQueriesWithCollection, $queryParameters);
    }
}
