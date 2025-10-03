<?php

namespace Biblioverse\TypesenseBundle\Search\Hydrate;

use Biblioverse\TypesenseBundle\CollectionAlias\CollectionAliasInterface;
use Biblioverse\TypesenseBundle\Search\Results\AbstractSearchResults;
use Biblioverse\TypesenseBundle\Search\Results\SearchResults;
use Biblioverse\TypesenseBundle\Search\Results\SearchResultsHydrated;

/**
 * @phpstan-import-type Hit from AbstractSearchResults
 */
class HydrateUnion
{
    public function __construct(
        private readonly CollectionAliasInterface $collectionAlias,
        /** @var HydrateSearchResultInterface<object> */
        private readonly HydrateSearchResultInterface $hydrateSearchResult,
        /** @var array<class-string, array<int, string>> Map of Entity class and collections */
        private readonly array $entityMapping,
    ) {
    }

    /**
     * @return SearchResultsHydrated<object>
     */
    public function hydrate(SearchResults $searchResults): SearchResultsHydrated
    {
        if ($searchResults->getUnionRequestParameters() === null) {
            throw new \LogicException('Union request parameters not set');
        }

        $hitsByCollection = [];
        foreach ($searchResults->getHits() as $hit) {
            $collection = $this->getCollectionFromHit($hit);
            $hitsByCollection[$collection][] = $hit;
        }

        $hydratedByCollectionAndIds = [];
        foreach ($hitsByCollection as $collection => $hits) {
            $hydratedByCollectionAndIds[$collection] = $this->hydrateHitsById($collection, $hits);
        }

        $hydratedResults = [];
        foreach ($searchResults->getHits() as $hit) {
            $collection = $this->getCollectionFromHit($hit);
            $id = $hit['document']['id'] ?? null;
            if ($id === null || false === is_scalar($id)) {
                throw new \RuntimeException('One hit has no identifier: '.json_encode($hit, \JSON_THROW_ON_ERROR));
            }
            $id = (string) $id;
            if (false === array_key_exists($id, $hydratedByCollectionAndIds[$collection])) {
                // Skip Hits not in database
                continue;
            }
            $hydratedResults[$collection.'_'.$id] = $hydratedByCollectionAndIds[$collection][$id];
        }

        return SearchResultsHydrated::fromResultAndCollection($searchResults, $hydratedResults);
    }

    /**
     * @param Hit $hit
     */
    private function getCollectionFromHit(array $hit): string
    {
        $collection = $hit['collection'] ?? throw new \LogicException('You hit has no collection, which is not normal for union search');

        return $this->removeCollectionAlias($collection);
    }

    private function removeCollectionAlias(string $collection): string
    {
        return $this->collectionAlias->revertName($collection);
    }

    /**
     * @param array<Hit> $hits
     *
     * @return array<string,object> Object indexed by ids (as string)
     */
    private function hydrateHitsById(string $collection, array $hits): array
    {
        foreach ($this->entityMapping as $class => $mapped_collection) {
            if (false === in_array($collection, $mapped_collection)) {
                continue;
            }
            $results = $this->hydrateSearchResult->hydrate($class, new SearchResults([
                'hits' => $hits,
            ]))->getResults();
            $response = [];
            foreach ($results as $result) {
                $response[$this->hydrateSearchResult->getId($result)] = $result;
            }

            return $response;
        }
        throw new \RuntimeException(sprintf('Collection %s is not supported to be hydrated', $collection));
    }
}
