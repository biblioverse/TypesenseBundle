<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Tests\Mapper\Converter\Exception;

use Biblioverse\TypesenseBundle\Mapper\Converter\Exception\ValueConversionException;

class ValueConversionExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testException(): void
    {
        $this->expectException(ValueConversionException::class);
        $this->expectExceptionMessage('Cannot convert int to string');

        throw ValueConversionException::fromType(12, 'string');
    }
}
