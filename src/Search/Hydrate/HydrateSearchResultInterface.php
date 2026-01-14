<?php

namespace Biblioverse\TypesenseBundle\Search\Hydrate;

use Biblioverse\TypesenseBundle\Search\Results\SearchResults;
use Biblioverse\TypesenseBundle\Search\Results\SearchResultsHydrated;

/**
 * @template T of object
 */
interface HydrateSearchResultInterface
{
    /**
     * @param class-string<T> $class
     *
     * @return SearchResultsHydrated<T>
     */
    public function hydrate(string $class, SearchResults $searchResults): SearchResultsHydrated;

    public function getId(object $object): string;
}
