<?php

namespace Biblioverse\TypesenseBundle\Search;

use Biblioverse\TypesenseBundle\Client\ClientInterface;
use Biblioverse\TypesenseBundle\Exception\SearchException;
use Biblioverse\TypesenseBundle\Query\SearchQueryInterface;
use Biblioverse\TypesenseBundle\Query\SearchQueryWithCollectionInterface;
use Biblioverse\TypesenseBundle\Search\Results\SearchResults;
use Http\Client\Exception;
use Typesense\Exceptions\TypesenseClientError;

class Search implements SearchInterface
{
    public function __construct(private readonly ClientInterface $client)
    {
    }

    /**
     * @throws SearchException
     */
    public function search(string $collectionName, SearchQueryInterface $searchQuery): SearchResults
    {
        try {
            /** @var array<string, mixed> $result */
            $result = $this->client->getCollection($collectionName)
                ->documents->search($searchQuery->toArray());

            return new SearchResults($result);
        } catch (TypesenseClientError|Exception $e) {
            throw new SearchException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param SearchQueryWithCollectionInterface[] $searchQueries
     * @param array<string, mixed>                 $queryParameters
     *
     * @return SearchResults[]
     */
    public function multiSearch(array $searchQueries, array $queryParameters = []): array
    {
        $rawSearchQueries = array_map(fn (SearchQueryWithCollectionInterface $searchQueryWithCollection) => ['collection' => $searchQueryWithCollection->getCollection()] + $searchQueryWithCollection->toArray(), $searchQueries);
        try {
            /** @var array{'results'?: array<string, mixed>} $rawResult * */
            $rawResult = $this->client->getMultiSearch()
                ->perform(['searches' => $rawSearchQueries], $queryParameters);

            $results = $rawResult['results'] ?? [];
            $response = [];
            /**
             * @var int                 $index
             * @var array<string,mixed> $result
             */
            foreach ($results as $index => $result) {
                if (isset($result['error']) && isset($result['code'])) {
                    $this->throwMultiSearchException($index, $result);
                }
                $response[] = new SearchResults($result);
            }

            return $response;
        } catch (TypesenseClientError|Exception $e) {
            throw new SearchException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param array<string, mixed> $result
     */
    private function throwMultiSearchException(int $index, array $result): void
    {
        $code = is_resource($result['code']) || !is_int($result['code']) ? 0 : $result['code'];
        $error = is_resource($result['error']) || !is_string($result['error']) ? 'Error' : $result['error'];

        throw new SearchException(sprintf('Multi-search sub-result error at %d: %s %s', $index, $code, $error));
    }
}
