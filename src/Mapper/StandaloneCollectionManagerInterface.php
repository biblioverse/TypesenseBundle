<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Mapper;

/**
 * Implement this interface if you want to create a mapper not attached to any entity.
 */
interface StandaloneCollectionManagerInterface extends CollectionManagerInterface
{
    public const TAG_NAME = 'biblioverse_typesense.mapper';

    public static function getName(): string;
}
