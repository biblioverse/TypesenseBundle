<?php

declare(strict_types=1);

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

    public function searchRaw(SearchQuery $searchQuery): SearchResults;
}
