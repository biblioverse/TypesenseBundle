<?php

namespace Biblioverse\TypesenseBundle\Search;

use Biblioverse\TypesenseBundle\Exception\SearchException;
use Biblioverse\TypesenseBundle\Query\SearchQuery;
use Biblioverse\TypesenseBundle\Search\Results\SearchResults;
use Biblioverse\TypesenseBundle\Search\Results\SearchResultsHydrated;

/**
 * @template T of object
 */
interface SearchCollectionInterface
{
    /**
     * @return SearchResultsHydrated<T>
     *
     * @throws SearchException
     */
    public function search(SearchQuery $searchQuery): SearchResultsHydrated;

    /**
     * @param SearchQuery[] $searchQueries
     *
     * @return list<SearchResultsHydrated<T>>
     */
    public function multisearch(array $searchQueries): array;

    public function searchRaw(SearchQuery $searchQuery): SearchResults;

    /**
     * @param SearchQuery[] $searchQueries
     *
     * @return SearchResults[]
     */
    public function multisearchRaw(array $searchQueries): array;
}
