<?php

namespace Biblioverse\TypesenseBundle\Search\Results;

use Biblioverse\TypesenseBundle\Utils\ArrayAccessTrait;

/**
 * @template T of mixed|object
 *
 * @implements \ArrayAccess<string, mixed>
 * @implements \IteratorAggregate<int, array<string,T>>
 *
 * @phpstan-type Document array<string,mixed>
 * @phpstan-type Highlight array{field:string, snippet?: string, 'matched_tokens': string[]}
 * @phpstan-type Hit array{document: Document, highlight?: array<string, mixed>,highlights?: list<Highlight>, 'collection'?: string}
 * @phpstan-type FacetCountItem array{count: int, value: string, highlighted: string}
 * @phpstan-type FacetCount array{field_name: string, sampled: bool, stats: array{total_values: int}, counts: FacetCountItem[]}
 * @phpstan-type UnionRequestParameters array<int, array{'collection': string, 'found': int, 'q': string, 'per_page': int}>
 */
abstract class AbstractSearchResults implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /**
     * @use ArrayAccessTrait<string, mixed>
     */
    use ArrayAccessTrait;

    /**
     * @return array<string, mixed>
     */
    public function getRequestParameters(): array
    {
        if (!$this->offsetExists('request_params') || !is_array($this->data['request_params'])) {
            return [];
        }

        /** @var array<string,mixed> $data */
        $data = $this->data['request_params'];

        return $data;
    }

    /**
     * Get the raw query JSON output as array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Return the 'hits' key from the search result.
     *
     * @return array<int, Hit>
     */
    public function getHits(): array
    {
        if (!$this->offsetExists('hits')) {
            return [];
        }

        /** @var Hit[] $hits */
        $hits = $this->data['hits'];

        return array_values($hits);
    }

    public function getPage(): ?int
    {
        return $this->getInt('page');
    }

    public function getTotalPage(): ?int
    {
        $found = $this->getFound();
        $perPage = $this->getPerPage();
        if ($perPage === null || $perPage < 1) {
            return null;
        }

        return (int) ceil($found / $perPage);
    }

    public function getPerPage(): ?int
    {
        if ($this->getUnionRequestParameters() !== null) {
            $result = $this->getUnionRequestParameter(0, 'per_page');

            return is_int($result) ? $result : null;
        }

        $params = $this->offsetGet('request_params');
        if (!is_array($params)) {
            return null;
        }
        $perPage = $params['per_page'] ?? null;
        if ($perPage == null || !is_int($perPage) || $perPage < 1) {
            return null;
        }

        return $perPage;
    }

    private function getInt(string $name): ?int
    {
        if (!$this->offsetExists($name) || !is_int($this->data[$name])) {
            return null;
        }

        return $this->data[$name];
    }

    public function getFound(): int
    {
        return $this->getInt('found') ?? 0;
    }

    /**
     * @return array<int,non-empty-array<string,array{field:string, snippet?: string, 'matched_tokens': string[]}>>
     **/
    public function getHighlight(): array
    {
        if (!$this->offsetExists('hits') || !is_array($this->data['hits'])) {
            return [];
        }

        $response = [];
        foreach (array_values($this->data['hits']) as $index => $result) {
            if (!is_array($result) || !isset($result['highlights']) || !is_array($result['highlights'])) {
                continue;
            }
            /** @var array{'field': string, 'snippet'?: string, 'matched_tokens': string[]} $highlight */
            foreach ($result['highlights'] as $highlight) {
                $field = $highlight['field'];
                $response[$index][$field] = $highlight;
            }
        }
        reset($this->data['hits']);

        return $response;
    }

    public function count(): int
    {
        if (!$this->offsetExists('hits')) {
            return 0;
        }

        return count((array) $this->data['hits']);
    }

    /**
     * @return FacetCount[]
     */
    public function getFacetCounts(): array
    {
        if (!$this->offsetExists('facet_counts') || !is_array($this->data['facet_counts']) || $this->data['facet_counts'] === []) {
            return [];
        }

        /** @var FacetCount[] $data */
        $data = array_map(fn ($value) =>
            /* @var FacetCount $value */
        $value, $this->data['facet_counts']);

        return $data;
    }

    /**
     * @return UnionRequestParameters|null
     */
    public function getUnionRequestParameters(): ?array
    {
        if (!$this->offsetExists('union_request_params')) {
            return null;
        }

        /** @var UnionRequestParameters $data */
        $data = $this->data['union_request_params'];

        return $data;
    }

    /**
     * @return \Traversable<int|string, T>
     */
    abstract public function getIterator(): \Traversable;

    /**
     * @return array<int|string, T>
     */
    abstract public function getResults(): array;

    private function getUnionRequestParameter(int $int, string $key): mixed
    {
        $payload = $this->getUnionRequestParameters();
        if (!isset($payload[$int]) || false === array_key_exists($key, $payload[$int])) {
            return null;
        }

        return $payload[$int][$key];
    }
}
