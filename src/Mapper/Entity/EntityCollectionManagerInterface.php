<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Mapper\Entity;

use Biblioverse\TypesenseBundle\Mapper\CollectionManagerInterface;

/**
 * @template T of object
 *
 * @extends EntityTransformerInterface<T>
 */
interface EntityCollectionManagerInterface extends CollectionManagerInterface, EntityTransformerInterface
{
}
