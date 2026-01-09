<?php

namespace Biblioverse\TypesenseBundle\Tests\Mapper\Entity;

use Biblioverse\TypesenseBundle\Mapper\Converter\Exception\ValueConversionException;
use Biblioverse\TypesenseBundle\Mapper\Converter\Exception\ValueExtractorException;
use Biblioverse\TypesenseBundle\Mapper\Converter\Field\FieldConverterInterface;
use Biblioverse\TypesenseBundle\Mapper\Converter\ValueConverterInterface;
use Biblioverse\TypesenseBundle\Mapper\Entity\EntityTransformer;
use Biblioverse\TypesenseBundle\Mapper\Fields\FieldMapping;
use Biblioverse\TypesenseBundle\Mapper\Fields\FieldMappingInterface;
use Biblioverse\TypesenseBundle\Mapper\Mapping\Mapping;
use Biblioverse\TypesenseBundle\Mapper\Mapping\MappingInterface;
use Biblioverse\TypesenseBundle\Mapper\MappingGeneratorInterface;
use Biblioverse\TypesenseBundle\Mapper\Options\CollectionOptions;
use Biblioverse\TypesenseBundle\Type\DataTypeEnum;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EntityTransformer::class)]
class EntityTransformerTest extends TestCase
{
    /**
     * @throws ValueExtractorException
     * @throws ValueConversionException
     */
    public function testTransform(): void
    {
        $entity = $this->getEntity();
        $entityTransformer = $this->getTransformer();
        $this->assertSame([
            'id' => '42',
            'title' => 'My Title',
            'composed' => 'my composition',
        ], $entityTransformer->transform($entity));
    }

    /**
     * @throws ValueConversionException
     * @throws ValueExtractorException
     */
    public function testTransformWithFieldConverter(): void
    {
        $entity = $this->getEntity();

        $fieldMapping = new FieldMapping(name: 'phone', type: 'string');
        $mapping = new Mapping();
        $mapping->add('id', 'string');
        $mapping->addField($fieldMapping);

        $fieldMapping->setFieldConverter(new class implements FieldConverterInterface {
            public function convert(object $entity, mixed $value, FieldMappingInterface $fieldMapping): mixed
            {
                // @phpstan-ignore-next-line
                return sprintf('[phone] %s', $entity->phone);
            }
        });

        $entityTransformer = $this->getTransformer($this->createMappingGenerator($mapping));
        $this->assertSame([
            'id' => '42',
            'phone' => '[phone] 118',
        ], $entityTransformer->transform($entity));
    }

    public function testSupport(): void
    {
        $entity = $this->getEntity();
        $entityTransformer = $this->getTransformer();
        $this->assertTrue($entityTransformer->support($entity));
    }

    public function getEntity(): object
    {
        return new class {
            public int $id = 42;
            public string $title = 'My Title';
            public ?string $phone = '118';

            public function composed(): string
            {
                return 'my composition';
            }
        };
    }

    /**
     * @return EntityTransformer<object>
     */
    private function getTransformer(?MappingGeneratorInterface $mappingGenerator = null): EntityTransformer
    {
        $this->createStub(EntityManagerInterface::class);
        $mappingGenerator ??= $this->createMappingGenerator(null);
        $valueConverter = $this->createValueConverter();

        return new EntityTransformer($mappingGenerator, $valueConverter);
    }

    private function createMappingGenerator(?Mapping $mapping): MappingGeneratorInterface
    {
        $defaultMapping = new Mapping(collectionOptions: new CollectionOptions(
            tokenSeparators: [' ', '-', "'"],
            symbolsToIndex: ['+', '#', '@', '_'],
            defaultSortingField: 'sortable_id'
        ));

        $defaultMapping->
        add(
            name: 'id',
            type: DataTypeEnum::STRING
        )
        ->add(
            name: 'title',
            type: DataTypeEnum::STRING
        );

        $defaultMapping->addField(new FieldMapping(name: 'composed', type: 'string'));

        return new class($mapping ?? $defaultMapping) implements MappingGeneratorInterface {
            public function __construct(protected Mapping $mapping)
            {
            }

            public function getMapping(): MappingInterface
            {
                return $this->mapping;
            }
        };
    }

    private function createValueConverter(): ValueConverterInterface
    {
        return new class implements ValueConverterInterface {
            public function convert(mixed $value, string $type, bool $optional = true): mixed
            {
                return is_scalar($value) ? (string) $value : '[not-scalar]';
            }
        };
    }
}
