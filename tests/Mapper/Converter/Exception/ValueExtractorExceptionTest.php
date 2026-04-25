<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Tests\Mapper\Converter\Exception;

use Biblioverse\TypesenseBundle\Mapper\Converter\Exception\ValueExtractorException;

class ValueExtractorExceptionTest extends \PHPUnit\Framework\TestCase
{
    public function testException(): void
    {
        $this->expectException(ValueExtractorException::class);
        $this->expectExceptionMessage('Cannot extract string from int');

        throw new ValueExtractorException(12, 'string');
    }
}
