<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Mapper;

use Biblioverse\TypesenseBundle\Mapper\Converter\Exception\ValueConversionException;
use Biblioverse\TypesenseBundle\Mapper\Converter\Exception\ValueExtractorException;

interface DataGeneratorInterface
{
    /**
     * Data to index, the key is the field name.
     *
     * @return \Generator<array<string, mixed>>
     *
     * @throws ValueConversionException
     * @throws ValueExtractorException
     */
    public function getData(): \Generator;

    /**
     * How many data to index. If null, the progression is unknown.
     */
    public function getDataCount(): ?int;
}
