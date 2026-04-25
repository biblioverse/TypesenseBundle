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

    public function testMultiSearchUnion(): void
    {
        $client = $this->withMultiSearchResult([
            'found' => 31,
            'hits' => [
                0 => [
                    'collection' => 'books-2025-10-03-11-19-09',
                    'document' => [
                        'age' => 'enum.agecategories.notset',
                        'authors' => [
                            0 => 'Peter Yaworski',
                        ],
                        'book_path' => 'p/peter-yaworski/real-world-bug-hunting-a-field-guide-to-web-hacking/',
                        'extension' => 'epub',
                        'favorite' => [],
                        'hidden' => [],
                        'id' => '136',
                        'publisher' => 'No Starch Press, Inc.',
                        'read' => [],
                        'sortable_id' => 136,
                        'summary' => '"Real-World Bug Hunting: A Field Guide to Web Hacking" by Peter Yaworski is a practical guide that equips readers with the knowledge and techniques needed to identify and exploit vulnerabilities in web applications. Through real-world examples and hands-on exercises, the book offers valuable insights into the world of ethical hacking and bug bounty programs.',
                        'summary_empty' => false,
                        'tags' => [
                            0 => 'Informatique',
                            1 => 'Sécurité Informatique',
                            2 => 'Hacking',
                            3 => 'Développement Web',
                        ],
                        'tags_empty' => false,
                        'title' => 'Real-World Bug Hunting: A Field Guide to Web Hacking',
                        'updated' => 1735038510,
                        'verified' => true,
                    ],
                    'highlight' => [
                        'title' => [
                            'matched_tokens' => [
                                0 => 'A',
                            ],
                            'snippet' => 'Real-World Bug Hunting: <mark>A</mark> Field Guide to Web Hacking',
                        ],
                    ],
                    'highlights' => [
                        0 => [
                            'field' => 'title',
                            'matched_tokens' => [
                                0 => 'A',
                            ],
                            'snippet' => 'Real-World Bug Hunting: <mark>A</mark> Field Guide to Web Hacking',
                        ],
                    ],
                    'search_index' => 0,
                    'text_match' => 578730123365187705,
                    'text_match_info' => [
                        'best_field_score' => '1108091338752',
                        'best_field_weight' => 15,
                        'fields_matched' => 1,
                        'num_tokens_dropped' => 0,
                        'score' => '578730123365187705',
                        'tokens_matched' => 1,
                        'typo_prefix_score' => 0,
                    ],
                ],
                1 => [
                    'collection' => 'books-2025-10-03-11-19-09',
                    'document' => [
                        'age' => 'enum.agecategories.notset',
                        'authors' => [
                            0 => 'author1',
                        ],
                        'book_path' => 'sample.pdf',
                        'extension' => 'epub',
                        'favorite' => [],
                        'hidden' => [],
                        'id' => '134',
                        'publisher' => 'No Starch Press',
                        'read' => [],
                        'sortable_id' => 134,
                        'summary' => '"mybook" by author1 provides a practical guide to penetration testing techniques',
                        'summary_empty' => false,
                        'tags' => [
                            0 => 'Cybersecurity',
                            1 => 'Ethical Hacking',
                            2 => 'Information Security',
                            3 => 'Technology',
                            4 => 'Computer Science',
                            5 => 'Informatique',
                            7 => 'Hacking',
                        ],
                        'tags_empty' => false,
                        'title' => 'mybook about blabla',
                        'updated' => 1735550597,
                        'verified' => false,
                    ],
                    'highlight' => [
                        'title' => [
                            'matched_tokens' => [
                                0 => 'A',
                            ],
                            'snippet' => 'blabla',
                        ],
                    ],
                    'highlights' => [
                        0 => [
                            'field' => 'title',
                            'matched_tokens' => [
                                0 => 'A',
                            ],
                            'snippet' => 'blabla',
                        ],
                    ],
                    'search_index' => 0,
                    'text_match' => 578730123365187705,
                    'text_match_info' => [
                        'best_field_score' => '1108091338752',
                        'best_field_weight' => 15,
                        'fields_matched' => 1,
                        'num_tokens_dropped' => 0,
                        'score' => '578730123365187705',
                        'tokens_matched' => 1,
                        'typo_prefix_score' => 0,
                    ],
                ],
            ],
            'out_of' => 71,
            'page' => 1,
            'search_cutoff' => false,
            'search_time_ms' => 0,
            'union_request_params' => [
                0 => [
                    'collection' => 'books-2025-10-03-11-19-09',
                    'first_q' => 'a',
                    'found' => 22,
                    'per_page' => 2,
                    'q' => 'a',
                ],
                1 => [
                    'collection' => 'books-2025-10-03-11-19-09',
                    'found' => 9,
                    'per_page' => 2,
                    'q' => 'b',
                ],
            ],
        ]);
        $search = new Search($client);
        $searchResults = $search->multiSearch([
            new SearchQueryWithWithCollectionAdapter(new SearchQuery(q: 'a', queryBy: 'title'), 'books'),
            new SearchQueryWithWithCollectionAdapter(new SearchQuery(q: 'b', queryBy: 'title'), 'books'),
        ], ['union' => true]);
        $this->assertCount(1, $searchResults);
        $this->assertEquals(31, $searchResults[0]->getFound());
        $this->assertCount(2, $searchResults[0]->getResults());
        $this->assertArrayHasKey('collection', $searchResults[0]->getHits()[0]);
        $this->assertEquals('books-2025-10-03-11-19-09', $searchResults[0]->getHits()[0]['collection']);
        $this->assertEquals(2, $searchResults[0]->getPerPage());
        $this->assertEquals(ceil(31 / 2), $searchResults[0]->getTotalPage());
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
