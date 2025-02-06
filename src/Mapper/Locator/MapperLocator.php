<?php

namespace Biblioverse\TypesenseBundle\Mapper\Locator;

use Biblioverse\TypesenseBundle\Mapper\CollectionManagerInterface;
use Biblioverse\TypesenseBundle\Mapper\DataGeneratorInterface;
use Biblioverse\TypesenseBundle\Mapper\Entity\EntityTransformerInterface;
use Biblioverse\TypesenseBundle\Mapper\MappingGeneratorInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Contracts\Service\ServiceCollectionInterface;

final class MapperLocator implements MapperLocatorInterface
{
    /**
     * @param ServiceCollectionInterface<CollectionManagerInterface|object>  $collectionManagers
     * @param ServiceCollectionInterface<DataGeneratorInterface>             $dataGenerators
     * @param ServiceCollectionInterface<MappingGeneratorInterface>          $mappingGenerators
     * @param ServiceCollectionInterface<EntityTransformerInterface<object>> $entityTransformers
     * @param array<class-string, string[]>                                  $entityMapping      index is the entity class name, values are the mapper names for this entity
     */
    public function __construct(
        private readonly ServiceCollectionInterface $collectionManagers,
        private readonly ServiceCollectionInterface $dataGenerators,
        private readonly ServiceCollectionInterface $mappingGenerators,
        private readonly ServiceCollectionInterface $entityTransformers,
        private readonly array $entityMapping,
    ) {
    }

    public function hasDataGenerator(string $name): bool
    {
        return $this->collectionManagers->has($name) || $this->dataGenerators->has($name);
    }

    /**
     * @return array<string, MappingGeneratorInterface>
     */
    public function getMappers(): array
    {
        try {
            /** @var array<string, MappingGeneratorInterface> $mappers */
            $mappers = [];
            /** @var string $name */
            foreach (array_keys($this->collectionManagers->getProvidedServices()) as $name) {
                /** @var object|MappingGeneratorInterface $service */
                $service = $this->collectionManagers->get($name);
                if (!$service instanceof MappingGeneratorInterface) {
                    throw new InvalidTypeMapperException(sprintf('Service %s not found. Class "%s" must implement "%s".', $name, $service::class, MappingGeneratorInterface::class));
                }
                $mappers[$name] = $service;
            }
            /** @var string $name */
            foreach (array_keys($this->mappingGenerators->getProvidedServices()) as $name) {
                /** @var object|MappingGeneratorInterface $service */
                $service = $this->mappingGenerators->get($name);
                if (!$service instanceof MappingGeneratorInterface) {
                    throw new InvalidTypeMapperException(sprintf('Service %s not found. Class "%s" must implement "%s".', $name, $service::class, MappingGeneratorInterface::class));
                }
                $mappers[$name] = $service;
            }

            return $mappers;
        } catch (ServiceNotFoundException $e) {
            throw new InvalidTypeMapperException(sprintf('Service "%s" not found, do you implement %s', $e->getId(), MappingGeneratorInterface::class), 0, $e);
        }
    }

    public function countDataGenerator(): int
    {
        return count($this->dataGenerators->getProvidedServices()) + count($this->collectionManagers->getProvidedServices());
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $classString
     *
     * @return array<string, MappingGeneratorInterface> indexed by the mapper name
     */
    public function getEntityMappers(string $classString): array
    {
        $response = [];
        foreach (($this->entityMapping[$classString] ?? []) as $mapperName) {
            $service = $this->mappingGenerators->has($mapperName) ? $this->mappingGenerators->get($mapperName) : null;
            $service ??= $this->collectionManagers->has($mapperName) ? $this->collectionManagers->get($mapperName) : null;
            if (!$service instanceof MappingGeneratorInterface) {
                throw new InvalidTypeMapperException(sprintf('No entity mapper for entity "%s" did you implemented "%s".', $mapperName, MappingGeneratorInterface::class));
            }
            $response[$mapperName] = $service;
        }

        return $response;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $classString $classString
     */
    public function hasEntityTransformer(string $classString): bool
    {
        if (false === isset($this->entityMapping[$classString])) {
            return false;
        }
        $collections = $this->entityMapping[$classString];
        foreach ($collections as $collection) {
            if ($this->entityTransformers->has($collection) || $this->collectionManagers->has($collection)) {
                return true;
            }
        }

        return false;
    }

    public function getDataGenerator(string $shortName): DataGeneratorInterface
    {
        $service = $this->dataGenerators->has($shortName) ? $this->dataGenerators->get($shortName) : null;
        $service ??= $this->collectionManagers->has($shortName) ? $this->collectionManagers->get($shortName) : null;

        if (!$service instanceof DataGeneratorInterface) {
            throw new InvalidTypeMapperException(sprintf('No data generator found for "%s" do you implemented "%s".', $shortName, DataGeneratorInterface::class));
        }

        return $service;
    }

    public function getEntityTransformers(string $entity): array
    {
        $response = [];
        foreach (($this->entityMapping[$entity] ?? []) as $mapperName) {
            $service = null;
            if ($this->entityTransformers->has($mapperName)) {
                $service = $this->entityTransformers->get($mapperName);
            }

            if ($service === null && $this->collectionManagers->has($mapperName)) {
                $service = $this->collectionManagers->get($mapperName);
            }

            if (!$service instanceof EntityTransformerInterface) {
                throw new InvalidTypeMapperException(sprintf('No valid entity transformer found for entity "%s" do you implemented "%s".', $entity, EntityTransformerInterface::class));
            }

            $response[$mapperName] = $service;
        }

        return $response;
    }
}
