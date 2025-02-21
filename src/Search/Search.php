<?php

namespace Biblioverse\TypesenseBundle\Search;

use Biblioverse\TypesenseBundle\Client\ClientInterface;
use Biblioverse\TypesenseBundle\Exception\SearchException;
use Biblioverse\TypesenseBundle\Query\SearchQuery;
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
    public function search(string $collectionName, SearchQuery $searchQuery): SearchResults
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
     * @param SearchQuery[] $searchQueries
     *
     * @return SearchResults[]
     */
    public function multiSearch(string $collectionName, array $searchQueries): array
    {
        $rawSearchQueries = array_map(fn (SearchQuery $searchQuery) => ['collection' => $collectionName] + $searchQuery->toArray(), $searchQueries);
        try {
            /** @var array{'results'?: array<string, mixed>} $rawResult * */
            $rawResult = $this->client->getMultiSearch()
                ->perform(['searches' => $rawSearchQueries]); // TODO Support for query parameters

            $results = $rawResult['results'] ?? [];
            $response = [];
            /**
             * @var int                 $index
             * @var array<string,mixed> $result
             */
            foreach ($results as $index => $result) {
                if (isset($result['error']) && isset($result['code'])) {
                    $code = is_resource($result['code']) || !is_int($result['code']) ? 0 : $result['code'];
                    $error = is_resource($result['error']) || !is_string($result['error']) ? 'Error' : $result['error'];
                    throw new SearchException(sprintf('Multi-search sub-result error at %d: %s %s', $index, $code, $error));
                }
                $response[] = new SearchResults($result);
            }

            return $response;
        } catch (TypesenseClientError|Exception $e) {
            throw new SearchException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
