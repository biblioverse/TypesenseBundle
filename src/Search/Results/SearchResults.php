<?php

namespace Biblioverse\TypesenseBundle\Search\Results;

/**
 * @extends AbstractSearchResults<array<string,mixed>>
 *
 * @phpstan-import-type Document from AbstractSearchResults
 */
class SearchResults extends AbstractSearchResults
{
    public function getIterator(): \Traversable
    {
        $data = [];
        if (is_array($this->data['hits']) && $this->offsetExists('hits')) {
            $data = $this->data['hits'];
        }
        /** @var array<int, array<string, mixed>> $data */
        $data = array_filter(array_map(static function (mixed $hits): mixed {
            if (!is_array($hits) || $hits === [] || !isset($hits['document'])) {
                return null;
            }

            return $hits['document'];
        }, $data), fn ($a): bool => $a !== null);

        return new \ArrayIterator($data);
    }

    /**
     * @return array<int|string, Document>
     */
    public function getResults(): array
    {
        return array_map(fn ($value) => $value['document'], $this->getHits());
    }
}
