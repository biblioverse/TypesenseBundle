<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Mapper\Converter\Exception;

class ValueExtractorException extends \Exception
{
    public function __construct(mixed $value, string $path, ?\Throwable $throwable = null)
    {
        $valueType = get_debug_type($value);
        parent::__construct(sprintf('Cannot extract %s from %s', $path, $valueType), 0, $throwable);
    }
}
