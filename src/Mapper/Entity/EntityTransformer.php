<?php

namespace Biblioverse\TypesenseBundle\Mapper\Entity;

use Biblioverse\TypesenseBundle\Mapper\Converter\Field\FieldConverterInterface;
use Biblioverse\TypesenseBundle\Mapper\Converter\ValueConverter;
use Biblioverse\TypesenseBundle\Mapper\Converter\ValueConverterInterface;
use Biblioverse\TypesenseBundle\Mapper\Converter\ValueExtractor;
use Biblioverse\TypesenseBundle\Mapper\Converter\ValueExtractorInterface;
use Biblioverse\TypesenseBundle\Mapper\MappingGeneratorInterface;

/**
 * @template T of Object
 *
 * @implements EntityTransformerInterface<T>
 */
final class EntityTransformer implements EntityTransformerInterface
{
    public function __construct(
        private readonly MappingGeneratorInterface $mappingGenerator,
        private readonly ValueConverterInterface $valueConverter = new ValueConverter(),
        private readonly ValueExtractorInterface $valueExtractor = new ValueExtractor(),
    ) {
    }

    public function transform(object $entity): array
    {
        $data = [];

        foreach ($this->mappingGenerator->getMapping()->getFields() as $fieldMapping) {
            $fieldName = $fieldMapping->getEntityAttribute() ?? $fieldMapping->getName();
            $value = $this->valueExtractor->getValue($entity, $fieldName);

            if ($fieldMapping->getFieldConverter() instanceof FieldConverterInterface) {
                $value = $fieldMapping->getFieldConverter()->convert($entity, $value, $fieldMapping);
            }

            $data[$fieldMapping->getName()] = $this->valueConverter->convert($value, $fieldMapping->getType(), $fieldMapping->isOptional());
        }

        return $data;
    }

    public function support(object $entity): bool
    {
        return true;
    }
}
