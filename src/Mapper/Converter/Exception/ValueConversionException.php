<?php

namespace Biblioverse\TypesenseBundle\Mapper\Converter\Exception;

class ValueConversionException extends \Exception
{
    public function __construct(string $message, int $code = 0, ?\Throwable $throwable = null)
    {
        parent::__construct($message, $code, $throwable);
    }

    public static function fromType(mixed $value, string $type, ?\Throwable $throwable = null): self
    {
        $valueType = get_debug_type($value);

        return new self(sprintf('Cannot convert %s to %s', $valueType, $type), 0, $throwable);
    }

    public static function fromCallable(mixed $value, string $callableString, ?\Throwable $throwable = null): self
    {
        $valueType = get_debug_type($value);

        return new self(sprintf('Cannot convert %s via %s', $valueType, $callableString), 1, $throwable);
    }
}
