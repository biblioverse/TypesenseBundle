<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Mapper\Converter;

use Biblioverse\TypesenseBundle\Mapper\Converter\Exception\ValueConversionException;

interface ValueConverterInterface
{
    /**
     * @throws ValueConversionException
     */
    public function convert(mixed $value, string $type, bool $optional = true): mixed;
}
