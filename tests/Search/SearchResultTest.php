<?php

namespace Biblioverse\TypesenseBundle\Tests\Search;

use Biblioverse\TypesenseBundle\Search\Results\SearchResults;

class SearchResultTest extends \PHPUnit\Framework\TestCase
{
    public const DATA = [
        'facet_counts' => [
            [
                'counts' => [
                    ['count' => 10, 'highlighted' => 'Robert', 'value' => 'Robert C. Martin'],
                    ['count' => 20, 'highlighted' => 'Gene Kim', 'value' => 'Gene Kim'],
                    ['count' => 30, 'highlighted' => 'Matthias Noback And Tomas Votruba', 'value' => 'Matthias Noback And Tomas Votruba'],
                ],
                'field_name' => 'authors',
                'sampled' => false,
                'stats' => ['total_values' => 3],
            ],
        ],
        'found' => 120,
        'search_time_ms' => 15,
        'page' => 2,
        'out_of' => 120,
        'hits' => [
            [
                'document' => [
                    'id' => '1',
                    'title' => 'Introduction to Typesense',
                    'content' => 'Typesense is a modern, fast, typo-tolerant search engine...',
                    'author' => 'John Doe',
                    'tags' => ['search', 'API', 'typesense'],
                ],
                'highlights' => [
                    [
                        'field' => 'content',
                        'snippet' => 'Typesense is a modern, fast, typo-tolerant <em>search</em> engine...',
                        'matched_tokens' => ['search'],
                    ],
                ],
            ],
            [
                'document' => [
                    'id' => '2',
                    'title' => 'Advanced Typesense Features',
                    'content' => 'Learn about advanced features like faceting, filtering, and more.',
                    'author' => 'Jane Smith',
                    'tags' => ['advanced', 'features', 'typesense'],
                ],
                'highlights' => [
                    [
                        'field' => 'title',
                        'snippet' => 'Advanced <em>Typesense</em> Features',
                        'matched_tokens' => ['typesense'],
                    ],
                ],
            ],
        ],
        'request_params' => ['per_page' => 12],
        'total_pages' => 12,
    ];

    public const DATA_MULTISEARCH_UNION = [
        'found' => 3,
        'hits' => [
            [
                'collection' => 'books-2025-10-03-11-19-09',
                'document' => [
                    'title' => 'My book 1',
                    'id' => '124',
                ],
                'highlight' => [],
                'highlights' => [],
            ],
            [
                'collection' => 'books-2025-10-03-11-19-09',
                'document' => [
                    'title' => 'my book 2',
                    'id' => '125',
                ],
                'highlight' => [],
                'highlights' => [],
            ],
        ],
        'out_of' => 3,
        'page' => 1,
        'search_cutoff' => false,
        'search_time_ms' => 0,
        'union_request_params' => [
            [
                'collection' => 'books',
                'q' => 'a',
            ],
            [
                'collection' => 'posts',
                'first_q' => '*',
                'q' => 'b',
            ],
        ],
    ];

    public function testFound(): void
    {
        $searchResults = $this->getSearchResult();
        $this->assertEquals(120, $searchResults->getFound());
    }

    public function testFacetCounts(): void
    {
        $searchResults = $this->getSearchResult();
        $this->assertEquals([[
            'counts' => [
                ['count' => 10, 'highlighted' => 'Robert', 'value' => 'Robert C. Martin'],
                ['count' => 20, 'highlighted' => 'Gene Kim', 'value' => 'Gene Kim'],
                ['count' => 30, 'highlighted' => 'Matthias Noback And Tomas Votruba', 'value' => 'Matthias Noback And Tomas Votruba'],
            ],
            'field_name' => 'authors',
            'sampled' => false,
            'stats' => ['total_values' => 3],
        ]], $searchResults->getFacetCounts());
    }

    public function testFacetCountsEmpty(): void
    {
        $data = self::DATA;
        unset($data['facet_counts']);
        $searchResults = $this->getSearchResult($data);
        $this->assertEquals([], $searchResults->getFacetCounts());
    }

    public function testTotalPage(): void
    {
        $searchResults = $this->getSearchResult();
        $this->assertEquals(10, $searchResults->getTotalPage());
    }

    public function testHits(): void
    {
        $searchResults = $this->getSearchResult();
        $this->assertCount(2, $searchResults->getIterator());
    }

    public function testPage(): void
    {
        $searchResults = $this->getSearchResult();
        $this->assertEquals(2, $searchResults->getPage());
    }

    public function testGetRequestParameters(): void
    {
        $searchResults = $this->getSearchResult();
        $this->assertEquals(['per_page' => 12], $searchResults->getRequestParameters());
    }

    public function testGetRequestParametersEmpty(): void
    {
        $data = self::DATA;
        unset($data['request_params']);
        $searchResults = $this->getSearchResult($data);
        $this->assertEquals([], $searchResults->getRequestParameters());
    }

    public function testMultisearchUnion(): void
    {
        $data = self::DATA_MULTISEARCH_UNION;
        $searchResults = $this->getSearchResult($data);
        $this->assertEquals(self::DATA_MULTISEARCH_UNION['union_request_params'], $searchResults->getUnionRequestParameters());
    }

    public function testGetHighlightEmpty(): void
    {
        $data = self::DATA;
        unset($data['hits'][0]['highlights']);
        unset($data['hits'][1]['highlights']);
        $searchResults = $this->getSearchResult($data);
        $this->assertEquals([], $searchResults->getHighlight());

        $data = self::DATA;
        unset($data['hits']);
        $searchResults = $this->getSearchResult($data);
        $this->assertEquals([], $searchResults->getHighlight());
    }

    public function testGetHighlight(): void
    {
        $data = self::DATA;
        $searchResults = $this->getSearchResult($data);

        $expected = [
            [
                'content' => [
                    'field' => 'content',
                    'snippet' => 'Typesense is a modern, fast, typo-tolerant <em>search</em> engine...',
                    'matched_tokens' => ['search'],
                ],
            ],
            [
                'title' => [
                    'field' => 'title',
                    'snippet' => 'Advanced <em>Typesense</em> Features',
                    'matched_tokens' => ['typesense'],
                ],
            ],
        ];

        $this->assertEquals($expected, $searchResults->getHighlight());
    }

    public function testHitsEmpty(): void
    {
        $data = self::DATA;
        unset($data['hits']);
        $searchResults = $this->getSearchResult($data);
        $this->assertSame([], $searchResults->getHits());

        $data = self::DATA;
        $data['hits'] = null;
        $searchResults = $this->getSearchResult($data);
        $this->assertSame([], $searchResults->getHits());
    }

    public function testPageNull(): void
    {
        $data = self::DATA;
        unset($data['page']);
        $searchResults = $this->getSearchResult($data);
        $this->assertNull($searchResults->getPage());
    }

    public function testPerPgeNull(): void
    {
        $data = self::DATA;
        unset($data['request_params']);
        $searchResults = $this->getSearchResult($data);
        $this->assertNull($searchResults->getPerPage());
        $this->assertNull($searchResults->getTotalPage());

        $data = self::DATA;
        unset($data['request_params']['per_page']);
        $searchResults = $this->getSearchResult($data);
        $this->assertNull($searchResults->getPerPage());
        $this->assertNull($searchResults->getTotalPage());
    }

    public function testIterator(): void
    {
        $searchResults = $this->getSearchResult();
        $data = iterator_to_array($searchResults->getIterator());
        $this->assertCount(2, $data);
        $this->assertSame([0, 1], array_keys($data), 'Keys should be preserved');
    }

    public function testToArray(): void
    {
        $searchResults = $this->getSearchResult();
        $this->assertEquals(self::DATA, $searchResults->toArray());
    }

    public function testCount(): void
    {
        $searchResults = $this->getSearchResult();
        $this->assertEquals(2, $searchResults->count());
    }

    public function testCountEmpty(): void
    {
        $data = self::DATA;
        unset($data['hits']);
        $searchResults = $this->getSearchResult($data);
        $this->assertEquals(0, $searchResults->count());
    }

    /**
     * @param ?array<string,mixed> $data
     */
    private function getSearchResult(?array $data = null): SearchResults
    {
        return new SearchResults($data ?? self::DATA);
    }

    public function testEmptyIterator(): void
    {
        $data = self::DATA;
        $data['hits'] = [[], []]; // invalid hits

        $searchResults = new SearchResults($data);
        $this->assertCount(0, $searchResults->getIterator());
    }

    public function testGetResults(): void
    {
        $searchResults = $this->getSearchResult();
        $result = $searchResults->getResults();
        $this->assertCount(2, $result);
        for ($i = 0; $i < 2; ++$i) {
            $this->assertArrayNotHasKey('document', $result[$i]);
            $this->assertArrayNotHasKey('highlights', $result[$i]);
            $this->assertArrayHasKey('author', $result[$i]);
            $this->assertArrayHasKey('content', $result[$i]);
        }
    }
}
