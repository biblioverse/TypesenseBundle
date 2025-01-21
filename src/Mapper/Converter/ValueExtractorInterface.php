<?php

namespace Biblioverse\TypesenseBundle\Mapper\Converter;

use Biblioverse\TypesenseBundle\Mapper\Converter\Exception\ValueExtractorException;

interface ValueExtractorInterface
{
    /**
     * @throws ValueExtractorException
     */
    public function getValue(object $entity, string $name): mixed;
}
