<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Search\Hydrate;

use Doctrine\Common\Collections\Collection;

/**
 * @template T of object
 */
interface HydrateRepositoryInterface
{
    /**
     * @param int[] $ids
     *
     * @return Collection<int, T>
     */
    public function findByIds(array $ids): Collection;
}
