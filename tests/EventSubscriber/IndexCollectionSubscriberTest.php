<?php

namespace Biblioverse\TypesenseBundle\Tests\EventSubscriber;

use Biblioverse\TypesenseBundle\EventSubscriber\IndexCollectionSubscriber;
use Biblioverse\TypesenseBundle\Mapper\Entity\EntityTransformerInterface;
use Biblioverse\TypesenseBundle\Mapper\Entity\Identifier\EntityIdentifierInterface;
use Biblioverse\TypesenseBundle\Mapper\Locator\MapperLocatorInterface;
use Biblioverse\TypesenseBundle\Populate\PopulateService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(IndexCollectionSubscriber::class)]
class IndexCollectionSubscriberTest extends TestCase
{
    /**
     * @param callable(MockObject&PopulateService):void|null $callable
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function getIndexCollectionSubscriber(?object $entity, ?callable $callable, bool $hasEntityTransformer = true, bool $supportsEntity = true): IndexCollectionSubscriber
    {
        $populateService = $this->createStub(PopulateService::class);
        if (null !== $callable) {
            $populateService = $this->createMock(PopulateService::class);
            $callable($populateService);
        }
        $mapperLocator = $this->createStub(MapperLocatorInterface::class);
        $mapperLocator->method('getEntityTransformers')->willReturn(['mapper' => new class(supportsEntity: $supportsEntity) implements EntityTransformerInterface {
            public function __construct(private readonly bool $supportsEntity = true)
            {
            }

            public function support($entity): bool
            {
                return $this->supportsEntity;
            }

            public function transform($entity): array
            {
                return ['data' => 'value'];
            }
        }]);

        $mapperLocator->method('hasEntityTransformer')->willReturn($hasEntityTransformer);

        $entityIdentifier = $this->createStub(EntityIdentifierInterface::class);
        $entityIdentifier->method('getIdentifiersValue')->willReturn(['id' => '123']);

        return new IndexCollectionSubscriber($populateService, $mapperLocator, $entityIdentifier);
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            ['onFlush', 'postFlush'],
            $this->getIndexCollectionSubscriber(null, null)->getSubscribedEvents()
        );
    }

    public function testOnFlushInsertion(): void
    {
        $entity = new \stdClass();

        $unitOfWork = $this->createStub(UnitOfWork::class);
        $unitOfWork->method('getScheduledEntityDeletions')->willReturn([]);
        $unitOfWork->method('getScheduledEntityInsertions')->willReturn([$entity]);
        $unitOfWork->method('getScheduledEntityUpdates')->willReturn([]);

        $objectManager = $this->createStub(EntityManager::class);
        $objectManager->method('getUnitOfWork')->willReturn($unitOfWork);
        $onFlushEventArgs = new OnFlushEventArgs($objectManager);

        $indexCollectionSubscriber = $this->getIndexCollectionSubscriber($entity, function (PopulateService&MockObject $populate) {
            $populate->expects($this->once())->method('fillData')->with('mapper', ['id' => '123', 'data' => 'value']);
        });
        $indexCollectionSubscriber->onFlush($onFlushEventArgs);
        $indexCollectionSubscriber->postFlush(new PostFlushEventArgs($objectManager));
    }

    public function testOnFlushUpdate(): void
    {
        $entity = new \stdClass();

        $unitOfWork = $this->createStub(UnitOfWork::class);
        $unitOfWork->method('getScheduledEntityDeletions')->willReturn([]);
        $unitOfWork->method('getScheduledEntityInsertions')->willReturn([]);
        $unitOfWork->method('getScheduledEntityUpdates')->willReturn([$entity]);

        $objectManager = $this->createStub(EntityManager::class);
        $objectManager->method('getUnitOfWork')->willReturn($unitOfWork);
        $onFlushEventArgs = new OnFlushEventArgs($objectManager);

        $indexCollectionSubscriber = $this->getIndexCollectionSubscriber($entity, function (PopulateService&MockObject $populate) {
            $populate->expects($this->once())->method('fillData')->with('mapper', ['id' => '123', 'data' => 'value']);
        });
        $indexCollectionSubscriber->onFlush($onFlushEventArgs);
        $indexCollectionSubscriber->postFlush(new PostFlushEventArgs($objectManager));
    }

    public function testOnFlushDeletion(): void
    {
        $entity = new \stdClass();

        $unitOfWork = $this->createStub(UnitOfWork::class);
        $unitOfWork->method('getScheduledEntityDeletions')->willReturn([$entity]);
        $unitOfWork->method('getScheduledEntityInsertions')->willReturn([]);
        $unitOfWork->method('getScheduledEntityUpdates')->willReturn([]);

        $objectManager = $this->createStub(EntityManager::class);
        $objectManager->method('getUnitOfWork')->willReturn($unitOfWork);
        $onFlushEventArgs = new OnFlushEventArgs($objectManager);

        $indexCollectionSubscriber = $this->getIndexCollectionSubscriber($entity, function (PopulateService&MockObject $populate) {
            $populate->expects($this->once())->method('deleteData')->with('mapper', ['id' => '123', 'data' => 'value']);
        });
        $indexCollectionSubscriber->onFlush($onFlushEventArgs);
        $indexCollectionSubscriber->postFlush(new PostFlushEventArgs($objectManager));
    }

    public function testNothingWithoutEntityTransformerOnFlush(): void
    {
        $entity = new \stdClass();

        $unitOfWork = $this->createStub(UnitOfWork::class);
        $unitOfWork->method('getScheduledEntityDeletions')->willReturn([$entity]);
        $unitOfWork->method('getScheduledEntityInsertions')->willReturn([$entity]);
        $unitOfWork->method('getScheduledEntityUpdates')->willReturn([$entity]);

        $objectManager = $this->createStub(EntityManager::class);
        $objectManager->method('getUnitOfWork')->willReturn($unitOfWork);
        $onFlushEventArgs = new OnFlushEventArgs($objectManager);

        $indexCollectionSubscriber = $this->getIndexCollectionSubscriber($entity, function (PopulateService&MockObject $populate) {
            $populate->expects($this->never())->method('fillData');
            $populate->expects($this->never())->method('deleteData');
        }, false);
        $indexCollectionSubscriber->onFlush($onFlushEventArgs);
        $indexCollectionSubscriber->postFlush(new PostFlushEventArgs($objectManager));
    }

    public function testNothingWithoutSupportOnFlush(): void
    {
        $entity = new \stdClass();

        $unitOfWork = $this->createStub(UnitOfWork::class);
        $unitOfWork->method('getScheduledEntityDeletions')->willReturn([$entity]);
        $unitOfWork->method('getScheduledEntityInsertions')->willReturn([$entity]);
        $unitOfWork->method('getScheduledEntityUpdates')->willReturn([$entity]);

        $objectManager = $this->createStub(EntityManager::class);
        $objectManager->method('getUnitOfWork')->willReturn($unitOfWork);
        $onFlushEventArgs = new OnFlushEventArgs($objectManager);

        $indexCollectionSubscriber = $this->getIndexCollectionSubscriber($entity, function (PopulateService&MockObject $populate) {
            $populate->expects($this->never())->method('fillData');
            $populate->expects($this->never())->method('deleteData');
        }, true, false);
        $indexCollectionSubscriber->onFlush($onFlushEventArgs);
        $indexCollectionSubscriber->postFlush(new PostFlushEventArgs($objectManager));
    }
}
