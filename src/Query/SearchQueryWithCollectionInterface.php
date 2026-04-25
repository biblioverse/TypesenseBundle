<?php

namespace Biblioverse\TypesenseBundle\Query;

interface SearchQueryWithCollectionInterface extends SearchQueryInterface
{
    public function getCollection(): string;
}
