<?php

namespace Biblioverse\TypesenseBundle\Mapper\Converter\Field;

use Biblioverse\TypesenseBundle\Mapper\Converter\Exception\ValueConversionException;
use Biblioverse\TypesenseBundle\Mapper\Fields\FieldMappingInterface;

interface FieldConverterInterface
{
    /**
     * @throws ValueConversionException
     */
    public function convert(object $entity, mixed $value, FieldMappingInterface $fieldMapping): mixed;
}
