<?php

namespace Biblioverse\TypesenseBundle\Tests\Mapper\Entity;

use Biblioverse\TypesenseBundle\Mapper\Entity\EntityDataGenerator;
use Biblioverse\TypesenseBundle\Mapper\Entity\EntityTransformer;
use Biblioverse\TypesenseBundle\Mapper\Mapping\Mapping;
use Biblioverse\TypesenseBundle\Mapper\Mapping\MappingInterface;
use Biblioverse\TypesenseBundle\Mapper\MappingGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(EntityDataGenerator::class)]
class EntityDataGeneratorTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testTransform(): void
    {
        $className = \stdClass::class;
        $data = [
            (object) ['id' => 1, 'name' => 'Entity1'],
            (object) ['id' => 2, 'name' => 'Entity2'],
        ];

        $entityManager = $this->getEntityManager($className, $data);
        $entityDataGenerator = $this->getEntityDataGenerator($entityManager, (new Mapping())
            ->add('id', 'string')
            ->add('name', 'string'), $className);

        $this->assertSame(2, $entityDataGenerator->getDataCount());
        $this->assertTrue($entityDataGenerator->support($data[0]));
        $this->assertSame([
            ['id' => '1', 'name' => 'Entity1'],
            ['id' => '2', 'name' => 'Entity2'],
        ], iterator_to_array($entityDataGenerator->getData()));
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return EntityDataGenerator<T>
     */
    private function getEntityDataGenerator(EntityManagerInterface $entityManager, Mapping $mapping, string $className): EntityDataGenerator
    {
        /**
         * @var EntityTransformer<T> $entityTransformer
         */
        $entityTransformer = new EntityTransformer(new class($mapping) implements MappingGeneratorInterface {
            public function __construct(private readonly Mapping $mapping)
            {
            }

            public function getMapping(): MappingInterface
            {
                return $this->mapping;
            }
        });

        return new EntityDataGenerator($entityManager, $entityTransformer, $className);
    }

    /**
     * @template T
     *
     * @param class-string<T> $entityClass
     * @param array<int, T>   $mockData
     *
     * @throws Exception
     */
    private function getEntityManager(string $entityClass, array $mockData): EntityManagerInterface
    {
        $identifiers = ['id'];

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects($this->any())->method('getIdentifier')->willReturn($identifiers);
        $entityManager->expects($this->any())->method('getClassMetadata')->with($entityClass)->willReturn($classMetadata);

        $repository = $this->createMock(EntityRepository::class);
        $entityManager->expects($this->any())->method('getRepository')->with($entityClass)->willReturn($repository);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->any())->method('select')->willReturnSelf();

        $dataQuery = $this->createMock(Query::class);

        $dataQuery->expects($this->any())->method('toIterable')->willReturnCallback(fn () => new \ArrayIterator($mockData));
        $dataQuery->expects($this->any())->method('getSingleScalarResult')->willReturn(count($mockData));
        $repository->expects($this->any())->method('createQueryBuilder')->with('entity')->willReturn($queryBuilder);
        $queryBuilder->expects($this->any())->method('getQuery')->willReturn($dataQuery);

        return $entityManager;
    }
}
