<?php

namespace Biblioverse\TypesenseBundle\Search;

use Biblioverse\TypesenseBundle\Exception\SearchException;
use Biblioverse\TypesenseBundle\Query\SearchQuery;
use Biblioverse\TypesenseBundle\Query\SearchQueryWithCollectionInterface;
use Biblioverse\TypesenseBundle\Search\Results\SearchResults;

interface SearchInterface
{
    /**
     * @throws SearchException
     */
    public function search(string $collectionName, SearchQuery $searchQuery): SearchResults;

    /**
     * @param SearchQueryWithCollectionInterface[] $searchQueries
     * @param array<string, mixed>                 $queryParameters
     *
     * @return SearchResults[]
     */
    public function multiSearch(array $searchQueries, array $queryParameters = []): array;
}
