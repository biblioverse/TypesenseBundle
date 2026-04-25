<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Mapper\Entity;

use Biblioverse\TypesenseBundle\Mapper\Converter\Exception\ValueConversionException;
use Biblioverse\TypesenseBundle\Mapper\Converter\Exception\ValueExtractorException;

/**
 * @template T of object
 */
interface EntityTransformerInterface
{
    /**
     * @param object&T $entity
     */
    public function support(object $entity): bool;

    /**
     * @param object&T $entity
     *
     * @return array<string, mixed>
     *
     * @throws ValueExtractorException
     * @throws ValueConversionException
     */
    public function transform(object $entity): array;
}
