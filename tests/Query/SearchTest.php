<?php

namespace Biblioverse\TypesenseBundle\Tests\Query;

use Biblioverse\TypesenseBundle\Client\ClientInterface;
use Biblioverse\TypesenseBundle\Query\SearchQuery;
use Biblioverse\TypesenseBundle\Query\SearchQueryWithWithCollectionAdapter;
use Biblioverse\TypesenseBundle\Search\Results\SearchResults;
use Biblioverse\TypesenseBundle\Search\Search;
use PHPUnit\Framework\TestCase;
use Typesense\ApiCall;
use Typesense\Collection;
use Typesense\Documents;
use Typesense\MultiSearch;

class SearchTest extends TestCase
{
    /**
     * @param array<string,mixed> $result
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    private function withSearchResult(string $collectionName, array $result): ClientInterface
    {
        $docs = $this->createMock(Documents::class);
        $docs->expects($this->once())->method('search')->willReturn($result);

        $collection = new Collection('collection', $this->createMock(ApiCall::class));
        $collection->documents = $docs;

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())->method('getCollection')->with($collectionName)->willReturn($collection);

        return $client;
    }

    /**
     * @param array<string,mixed> $result
     */
    private function withMultiSearchResult(array $result): ClientInterface
    {
        $multiSearch = $this->createMock(MultiSearch::class);
        $multiSearch->expects($this->any())->method('perform')->willReturn($result);

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())->method('getMultiSearch')->willReturn($multiSearch);

        return $client;
    }

    public function testEmptySearch(): void
    {
        $client = $this->withSearchResult('mycollection', []);

        $search = new Search($client);
        $searchResults = $search->search('mycollection', new SearchQuery(q: 'test', queryBy: 'name'));

        $this->assertCount(0, $searchResults->getResults());
    }

    public function testOneResultSearch(): void
    {
        $client = $this->withSearchResult('mybooks', [
            'found' => 1,
            'hits' => [
                0 => [
                    'document' => [
                        'id' => '117',
                        'title' => 'My Book title',
                        'updated' => 1738617395,
                        'verified' => true,
                    ],
                ],
            ],
            'out_of' => 42,
            'page' => 1,
            'request_params' => [
                'collection_name' => 'mybooks',
                'first_q' => 'book',
                'per_page' => 200,
                'q' => 'book',
            ],
            'search_cutoff' => false,
            'search_time_ms' => 6,
        ]);

        $search = new Search($client);
        $searchResults = $search->search('mybooks', new SearchQuery(q: 'test', queryBy: 'name'));

        $this->assertCount(1, $searchResults->getResults());
        $this->assertSame('My Book title', $searchResults->getResults()[0]['title']);
    }

    public function testMultiSearch(): void
    {
        $client = $this->withMultiSearchResult([
            'results' => [
                0 => [
                    'found' => 1,
                    'hits' => [
                        0 => [
                            'document' => [
                                'id' => '117',
                                'title' => 'Book title',
                            ],
                            'highlight' => [
                                'title' => [
                                    'matched_tokens' => [
                                        0 => 'Book',
                                    ],
                                    'snippet' => '<mark>Book</mark> title',
                                ],
                            ],
                            'highlights' => [
                                0 => [
                                    'field' => 'title',
                                    'matched_tokens' => [
                                        0 => 'book',
                                    ],
                                    'snippet' => '<mark>Book</mark> title',
                                ],
                            ],
                            'text_match' => 578730054645710969,
                            'text_match_info' => [
                                'best_field_score' => '1108057784320',
                                'best_field_weight' => 15,
                                'fields_matched' => 1,
                                'score' => '578730054645710969',
                                'tokens_matched' => 1,
                            ],
                        ],
                    ],
                    'out_of' => 42,
                    'page' => 1,
                    'request_params' => [
                        'collection_name' => 'mybookcollection',
                        'first_q' => 'book',
                        'per_page' => 200,
                        'q' => 'book',
                    ],
                    'search_cutoff' => false,
                    'search_time_ms' => 3,
                ],
            ],
        ]);

        $search = new Search($client);
        $searchResults = $search->multiSearch([new SearchQueryWithWithCollectionAdapter(new SearchQuery(q: 'book', queryBy: 'title'), 'mybookcollection')]);

        $this->assertCount(1, $searchResults);
        $this->assertCount(1, $searchResults[0]->getResults());
        $this->assertCount(1, $searchResults[0]->getResults());

        /** @var SearchResults $result */
        $result = $searchResults[0]->getResults()[0];
        $this->assertSame('117', $result['id']);
    }
}
