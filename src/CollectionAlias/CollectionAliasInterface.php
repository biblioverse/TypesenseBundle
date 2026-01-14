<?php

namespace Biblioverse\TypesenseBundle\CollectionAlias;

interface CollectionAliasInterface
{
    public function getName(string $name): string;

    public function revertName(string $name): string;

    public function switch(string $shortName, string $longName): void;
}
