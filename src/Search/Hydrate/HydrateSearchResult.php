<?php

namespace Biblioverse\TypesenseBundle\Search\Hydrate;

use Biblioverse\TypesenseBundle\Search\Results\SearchResults;
use Biblioverse\TypesenseBundle\Search\Results\SearchResultsHydrated;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * @template T of object
 *
 * @implements HydrateSearchResultInterface<T>
 */
class HydrateSearchResult implements HydrateSearchResultInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param class-string<T> $class
     *
     * @return SearchResultsHydrated<T>
     *
     * @throws \Exception
     */
    public function hydrate(string $class, SearchResults $searchResults): SearchResultsHydrated
    {
        // Fetch the primary key of the entity
        /** @var ClassMetadata<T> $classMetadata */
        $classMetadata = $this->entityManager->getClassMetadata($class);
        // TODO Support of composed primary keys ?
        $primaryKeyName = $classMetadata->isIdentifierComposite ? null : $this->getIdNameFromMetadata($classMetadata);

        $hits = $searchResults['hits'] ?? [];
        $ids = array_map(static function (mixed $result) use ($primaryKeyName): ?int {
            if (!is_array($result) || !is_array($result['document']) || !is_scalar($result['document'][$primaryKeyName] ?? null)) {
                return null;
            }

            return (int) $result['document'][$primaryKeyName];
        }, is_array($hits) ? $hits : []);
        $ids = array_filter($ids);

        if ($ids === []) {
            /** @var SearchResultsHydrated<T> $result */
            $result = SearchResultsHydrated::fromPayload($searchResults->toArray());

            return $result;
        }

        $entityRepository = $this->entityManager->getRepository($class);
        if ($entityRepository instanceof HydrateRepositoryInterface) {
            /** @var array<int,T> $collectionData */
            $collectionData = $entityRepository->findByIds($ids)->toArray();

            /** @var SearchResultsHydrated<T> $result */
            $result = SearchResultsHydrated::fromResultAndCollection($searchResults, $collectionData);

            return $result;
        }

        // Build a basic query to fetch the entities by their primary key
        $query = $entityRepository->createQueryBuilder('e')
            ->where('e.'.$primaryKeyName.' IN (:ids)')
            ->indexBy('e', 'e.'.$primaryKeyName)
            ->setParameter('ids', $ids)
            ->getQuery();
        /** @var array<int,T> $hydratedResults */
        $hydratedResults = (array) $query->getResult();

        /** @var SearchResultsHydrated<T> $result */
        $result = SearchResultsHydrated::fromResultAndCollection($searchResults, $hydratedResults);

        return $result;
    }

    /**
     * @param ClassMetadata<object> $classMetadata
     */
    private function getIdNameFromMetadata(ClassMetadata $classMetadata): string
    {
        $identifiers = $classMetadata->getIdentifier();
        if ($identifiers === []) {
            throw new \BadMethodCallException('Unable to read identifier field for class '.$classMetadata->getName());
        }

        return $identifiers[0];
    }
}
