<?php

namespace Biblioverse\TypesenseBundle\Mapper\Converter;

use Biblioverse\TypesenseBundle\Mapper\Converter\Exception\ValueConversionException;
use Biblioverse\TypesenseBundle\Type\DataTypeEnum;

class ValueConverter implements ValueConverterInterface
{
    /**
     * @return array<mixed, mixed>|null
     *
     * @throws ValueConversionException
     */
    private function toObject(mixed $v, bool $optional): ?array
    {
        if ($v === null) {
            return $optional ? null : [];
        }

        if ($v instanceof ToTypesenseInterface) {
            return $v->toTypesense();
        }

        if ($v instanceof \JsonSerializable) {
            /** @var array<mixed,mixed> $return */
            $return = (array) $v->jsonSerialize();

            return $return;
        }

        if (is_array($v)) {
            /** @var array<mixed,mixed> $return */
            $return = $v;

            return $return;
        }

        if ($v instanceof \Traversable) {
            /** @var array<mixed,mixed> $return */
            $return = iterator_to_array($v);

            return $return;
        }

        throw ValueConversionException::fromType($v, DataTypeEnum::OBJECT->value);
    }

    /**
     * @param mixed|array{0:float|float|string|int, 1:float|float|string|int} $value
     *
     * @return ?array{0: float, 1: float}
     */
    private function toGeoPoint(mixed $value): ?array
    {
        if (in_array($value, [null, '', []], true)) {
            return null;
        }

        if (!is_array($value) || count($value) !== 2) {
            throw new \InvalidArgumentException('GeoPoint must be an array with 2 values');
        }

        if (!is_scalar($value[0]) || !is_scalar($value[1])) {
            throw new \InvalidArgumentException('GeoPoint must be an array with scalar values');
        }

        return [(float) $value[0], (float) $value[1]];
    }

    /**
     * @throws ValueConversionException
     */
    public function convert(mixed $value, string $type, bool $optional = true): mixed
    {
        // Skip optional values
        if ($value === null && $optional) {
            return null;
        }

        // Dates need to be converted into Unix timestamps and stored as int64 fields in Typesense.
        if ($value instanceof \DateTimeInterface && $type === DataTypeEnum::INT64->value) {
            return $value->getTimestamp();
        }

        // Convert SplFileInfo images to base64
        if ($value instanceof \SplFileInfo && $type === DataTypeEnum::IMAGE->value) {
            return $this->toBase64($value);
        }

        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        if ($value instanceof \UnitEnum) {
            if ($type !== DataTypeEnum::STRING->value) {
                throw ValueConversionException::fromType($value, $type);
            }
            $value = $value->name;
        }

        if (is_resource($value)) {
            throw ValueConversionException::fromType($value, $type);
        }

        try {
            return match (DataTypeEnum::tryFrom($type)) {
                DataTypeEnum::STRING => strval($value), // @phpstan-ignore argument.type
                DataTypeEnum::STRING_ARRAY => array_map(strval(...), (array) $value),  // @phpstan-ignore argument.type
                DataTypeEnum::INT32, DataTypeEnum::INT64 => (int) $value,  // @phpstan-ignore cast.int
                DataTypeEnum::INT32_ARRAY, DataTypeEnum::INT64_ARRAY => array_map(fn ($v) => (int) $v, (array) $value), // @phpstan-ignore cast.int
                DataTypeEnum::FLOAT => (float) $value, // @phpstan-ignore cast.double
                DataTypeEnum::FLOAT_ARRAY => array_map(fn ($v) => (float) $v, (array) $value),  // @phpstan-ignore cast.double
                DataTypeEnum::BOOL => (bool) $value,
                DataTypeEnum::BOOL_ARRAY => array_map(fn ($v) => (bool) $v, (array) $value),
                DataTypeEnum::GEOPOINT => $this->toGeoPoint($value),
                DataTypeEnum::GEOPOINT_ARRAY => array_map($this->toGeoPoint(...), (array) $value),
                DataTypeEnum::OBJECT => $this->toObject($value, $optional),
                DataTypeEnum::OBJECT_ARRAY => array_map(fn ($v) => $this->toObject($v, $optional), (array) $value),
                DataTypeEnum::STRING_CONVERTIBLE, DataTypeEnum::AUTO, DataTypeEnum::IMAGE => $value, // Automatically detect type
                default => $value === null ? null : ((string) $value),  // @phpstan-ignore cast.string
            };
        } catch (\Throwable $e) {
            throw ValueConversionException::fromType($value, $type, $e);
        }
    }

    private function toBase64(\SplFileInfo $value): string
    {
        if (!$value->isFile()) {
            throw new \InvalidArgumentException('The provided SplFileInfo object is not a valid file.');
        }

        $fileContent = file_get_contents($value->getRealPath());
        if ($fileContent === false) {
            throw new \RuntimeException('Failed to read the file: '.$value->getRealPath());
        }

        return base64_encode($fileContent);
    }
}
