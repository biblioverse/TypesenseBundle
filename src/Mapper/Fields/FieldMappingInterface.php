<?php

namespace Biblioverse\TypesenseBundle\Mapper\Fields;

use Biblioverse\TypesenseBundle\Mapper\Converter\Field\FieldConverterInterface;
use Biblioverse\TypesenseBundle\Type\DataTypeEnum;

interface FieldMappingInterface
{
    /**
     * Field options and value. This is the raw data that will be sent to Typesense.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array;

    /**
     * @see DataTypeEnum
     */
    public function getType(): string;

    /**
     * Field name.
     */
    public function getName(): string;

    /**
     * Name of the field in the entity. Can be composed (Ex: `user.name`).
     * You should use `name` if not set.
     */
    public function getEntityAttribute(): ?string;

    /**
     * Dedicated service to convert the entity value to a single field value.
     */
    public function getFieldConverter(): ?FieldConverterInterface;

    public function isOptional(): bool;
}
