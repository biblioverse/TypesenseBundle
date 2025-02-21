<?php

namespace Biblioverse\TypesenseBundle\Query;

class SearchQueryWithWithCollectionAdapter implements SearchQueryWithCollectionInterface
{
    public function __construct(private readonly SearchQueryInterface $searchQuery, private readonly string $collectionName)
    {
    }

    public function getCollection(): string
    {
        return $this->collectionName;
    }

    public function toArray(): array
    {
        return $this->searchQuery->toArray();
    }
}
