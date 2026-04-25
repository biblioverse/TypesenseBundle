<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Tests\Search;

use Biblioverse\TypesenseBundle\Search\Results\SearchResults;
use Biblioverse\TypesenseBundle\Search\Results\SearchResultsHydrated;

class SearchResultHydratedTest extends \PHPUnit\Framework\TestCase
{
    public const DATA = [
        'facet_counts' => [
            0 => [
                'counts' => [
                    0 => [
                        'count' => 10,
                        'highlighted' => 'Robert',
                        'value' => 'Robert C. Martin',
                    ],
                    1 => [
                        'count' => 10,
                        'highlighted' => 'Gene Kim',
                        'value' => 'Gene Kim',
                    ],
                    2 => [
                        'count' => 10,
                        'highlighted' => 'Matthias Noback And Tomas Votruba',
                        'value' => 'Matthias Noback And Tomas Votruba',
                    ],
                ],
                'field_name' => 'authors',
                'sampled' => false,
                'stats' => [
                    'total_values' => 7,
                ],
            ],
        ],
        'found' => 120,
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
        'request_params' => [
            'collection_name' => 'books',
            'first_q' => 'code',
            'per_page' => 16,
            'q' => 'code',
        ],
        'search_cutoff' => false,
        'search_time_ms' => 3,
    ];

    public function testFound(): void
    {
        $searchResultsHydrated = $this->getResult();
        $this->assertEquals(120, $searchResultsHydrated->getFound());
    }

    public function testTotalPages(): void
    {
        $searchResultsHydrated = $this->getResult([
            'hits' => [],
            'found' => 17,
            'request_params' => [
                'per_page' => 3,
            ] + self::DATA,
        ]);

        $this->assertEquals(6, $searchResultsHydrated->getTotalPage());
    }

    public function testFacetCounts(): void
    {
        $searchResultsHydrated = $this->getResult();
        $this->assertEquals([0 => [
            'counts' => [
                0 => [
                    'count' => 10,
                    'highlighted' => 'Robert',
                    'value' => 'Robert C. Martin',
                ],
                1 => [
                    'count' => 10,
                    'highlighted' => 'Gene Kim',
                    'value' => 'Gene Kim',
                ],
                2 => [
                    'count' => 10,
                    'highlighted' => 'Matthias Noback And Tomas Votruba',
                    'value' => 'Matthias Noback And Tomas Votruba',
                ],
            ],
            'field_name' => 'authors',
            'sampled' => false,
            'stats' => [
                'total_values' => 7,
            ]]], $searchResultsHydrated->getFacetCounts());
    }

    public function testTotalPage(): void
    {
        $searchResultsHydrated = $this->getResult();
        $this->assertEquals(8, $searchResultsHydrated->getTotalPage());
    }

    public function testTotalPageEmpty(): void
    {
        $data = self::DATA;
        unset($data['found']);
        unset($data['request_params']);

        $searchResultsHydrated = $this->getResult($data);
        $this->assertNull($searchResultsHydrated->getTotalPage());
    }

    public function testHits(): void
    {
        $searchResultsHydrated = $this->getResult();
        $this->assertCount(2, $searchResultsHydrated->getIterator());
    }

    public function testResults(): void
    {
        $searchResultsHydrated = $this->getResult();
        $this->assertCount(2, $searchResultsHydrated->getResults());
        $this->assertInstanceOf(\stdClass::class, $searchResultsHydrated->getResults()['_1']);
    }

    public function testIterator(): void
    {
        $searchResultsHydrated = $this->getResult();
        $objects = iterator_to_array($searchResultsHydrated->getIterator());
        $this->assertCount(2, $objects);
        $this->assertSame(['_1', '_2'], array_keys($objects), 'Keys should be preserved');
    }

    public function testToArray(): void
    {
        $searchResultsHydrated = $this->getResult();
        $this->assertEquals(self::DATA, $searchResultsHydrated->toArray());
    }

    /**
     * @param ?array<string,mixed> $data
     *
     * @return SearchResultsHydrated<object>
     */
    private function getResult(?array $data = null): SearchResultsHydrated
    {
        $objects = $this->getObjects();
        $data ??= self::DATA;

        return SearchResultsHydrated::fromPayloadAndCollection($data, $objects);
    }

    /**
     * @return array<string, object>
     */
    private function getObjects(): array
    {
        $objects = [];
        foreach (self::DATA['hits'] as $hit) {
            $document = (object) $hit['document'];
            $objects['_'.$document->id] = $document;
        }

        return $objects;
    }

    public function testResultConstructor(): void
    {
        $objects = $this->getObjects();
        $searchResults = new SearchResults(self::DATA);

        $searchResultsHydrated = SearchResultsHydrated::fromResultAndCollection($searchResults, $objects);
        $this->assertSame(self::DATA, $searchResultsHydrated->toArray());
        $this->assertCount(2, $searchResultsHydrated->getIterator());
    }

    public function testPayloadConstructor(): void
    {
        $searchResultsHydrated = SearchResultsHydrated::fromPayload(self::DATA);
        $this->assertSame(self::DATA, $searchResultsHydrated->toArray());
        $this->assertCount(0, $searchResultsHydrated->getIterator());
    }
}
