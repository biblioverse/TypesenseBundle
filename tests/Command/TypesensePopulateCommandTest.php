<?php

namespace Biblioverse\TypesenseBundle\Tests\Command;

use Biblioverse\TypesenseBundle\CollectionAlias\CollectionAliasInterface;
use Biblioverse\TypesenseBundle\Command\TypesensePopulateCommand;
use Biblioverse\TypesenseBundle\Mapper\CollectionManagerInterface;
use Biblioverse\TypesenseBundle\Mapper\DataGeneratorInterface;
use Biblioverse\TypesenseBundle\Mapper\Entity\EntityTransformerInterface;
use Biblioverse\TypesenseBundle\Mapper\Locator\MapperLocatorInterface;
use Biblioverse\TypesenseBundle\Populate\PopulateService;
use Biblioverse\TypesenseBundle\Populate\WaitFor\AbstractWaitForService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class TypesensePopulateCommandTest extends TestCase
{
    public function testEmpty(): void
    {
        $typesensePopulateCommand = new TypesensePopulateCommand($this->createStub(PopulateService::class), $this->createStub(MapperLocatorInterface::class), $this->createStub(CollectionAliasInterface::class));
        $commandTester = new CommandTester($typesensePopulateCommand);
        $code = $commandTester->execute([]);
        $this->assertSame(0, $code);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('No data generator found', $output);
    }

    public function testOnceCollection(): void
    {
        $mapper = $this->createStub(MapperLocatorInterface::class);
        $mapper->method('countDataGenerator')->willReturn(1);
        $mapper->method('getDataGenerator')->willReturn($this->createStub(DataGeneratorInterface::class));
        $mapper->method('getEntityTransformers')->willReturnCallback(fn () => ['mapper' => $this->createStub(EntityTransformerInterface::class)]);
        $mapper->method('getMappers')->willReturnCallback(fn () => ['mapper' => $this->createStub(CollectionManagerInterface::class)]);
        $typesensePopulateCommand = new TypesensePopulateCommand($this->createStub(PopulateService::class), $mapper, $this->createStub(CollectionAliasInterface::class));
        $commandTester = new CommandTester($typesensePopulateCommand);
        $code = $commandTester->execute([]);
        $this->assertSame(0, $code);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Creating collection', $output);
    }

    public function testNoAlias(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error while populating collection');

        $mapper = $this->createStub(MapperLocatorInterface::class);
        $mapper->method('countDataGenerator')->willReturn(1);
        $mapper->method('getDataGenerator')->willReturn($this->createStub(DataGeneratorInterface::class));
        $mapper->method('getEntityTransformers')->willReturnCallback(fn () => ['mapper' => $this->createStub(EntityTransformerInterface::class)]);
        $mapper->method('getMappers')->willReturnCallback(fn () => ['mapper' => $this->createStub(CollectionManagerInterface::class)]);

        $populate = $this->createStub(PopulateService::class);
        $populate->method('fillCollection')->willThrowException(new \RuntimeException('Error while populating collection'));

        $typesensePopulateCommand = new TypesensePopulateCommand($populate, $mapper, $this->createStub(CollectionAliasInterface::class));

        $commandTester = null;
        try {
            $commandTester = new CommandTester($typesensePopulateCommand);
            $code = $commandTester->execute([]);
        } finally {
            $output = $commandTester?->getDisplay() ?? '';
            $this->assertStringContainsString('Error while populating collection', $output);
            $this->assertStringNotContainsString('Aliasing collection', $output);
        }

        $this->assertSame(0, $code);
    }

    public function testNoData(): void
    {
        $mapper = $this->createMock(MapperLocatorInterface::class);
        $mapper->method('countDataGenerator')->willReturn(1);
        $mapper->expects($this->never())->method('getDataGenerator')->willThrowException(new \RuntimeException('Should not be called'));
        $mapper->expects($this->never())->method('getEntityTransformers')->willReturnCallback(fn () => ['mapper' => $this->createStub(EntityTransformerInterface::class)]);
        $mapper->method('getMappers')->willReturnCallback(fn () => ['mapper' => $this->createStub(CollectionManagerInterface::class)]);
        $typesensePopulateCommand = new TypesensePopulateCommand($this->createStub(PopulateService::class), $mapper, $this->createStub(CollectionAliasInterface::class));
        $commandTester = new CommandTester($typesensePopulateCommand);
        $code = $commandTester->execute(['--no-data' => true]);
        $this->assertSame(0, $code);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Creating collection', $output);
    }

    public function testNoTries(): void
    {
        $dummyWaitForService = new class extends AbstractWaitForService {
            public function getName(): string
            {
                return 'dummy';
            }

            public function doCheck(): void
            {
                throw new \LogicException('Should not be called');
            }
        };
        $mapper = $this->createStub(MapperLocatorInterface::class);
        $mapper->method('countDataGenerator')->willReturn(1);
        $mapper->method('getDataGenerator')->willReturn($this->createStub(DataGeneratorInterface::class));
        $mapper->method('getEntityTransformers')->willReturnCallback(fn () => ['mapper' => $this->createStub(EntityTransformerInterface::class)]);
        $mapper->method('getMappers')->willReturnCallback(fn () => ['mapper' => $this->createStub(CollectionManagerInterface::class)]);
        $typesensePopulateCommand = new TypesensePopulateCommand($this->createStub(PopulateService::class), $mapper, $this->createStub(CollectionAliasInterface::class), [$dummyWaitForService]);
        $commandTester = new CommandTester($typesensePopulateCommand);
        $code = $commandTester->execute(['--nb-retry' => 0]);
        $this->assertSame(0, $code);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Creating collection', $output);
    }

    public function testTryingOutput(): void
    {
        $dummyWaitForService = new class extends AbstractWaitForService {
            public function getName(): string
            {
                return 'Dummy';
            }

            public function doCheck(): void
            {
                static $onlyOnce = null;
                if ($onlyOnce === null) {
                    $onlyOnce = true;
                    throw new \RuntimeException('This is an error');
                }
            }
        };
        $mapper = $this->createStub(MapperLocatorInterface::class);
        $mapper->method('countDataGenerator')->willReturn(1);
        $mapper->method('getDataGenerator')->willReturn($this->createStub(DataGeneratorInterface::class));
        $mapper->method('getEntityTransformers')->willReturnCallback(fn () => ['mapper' => $this->createStub(EntityTransformerInterface::class)]);
        $mapper->method('getMappers')->willReturnCallback(fn () => ['mapper' => $this->createStub(CollectionManagerInterface::class)]);

        $typesensePopulateCommand = new TypesensePopulateCommand($this->createStub(PopulateService::class), $mapper, $this->createStub(CollectionAliasInterface::class), [$dummyWaitForService]);
        $commandTester = new CommandTester($typesensePopulateCommand);
        $code = $commandTester->execute(['--nb-retry' => 2]);
        $this->assertSame(0, $code);
    }
}
