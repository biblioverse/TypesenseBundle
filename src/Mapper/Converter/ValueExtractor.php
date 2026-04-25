<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Mapper\Converter;

use Biblioverse\TypesenseBundle\Mapper\Converter\Exception\ValueExtractorException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ValueExtractor implements ValueExtractorInterface
{
    private readonly PropertyAccessor $propertyAccessor;

    public function __construct(?PropertyAccessor $propertyAccessor = null)
    {
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * @throws ValueExtractorException
     */
    public function getValue(object $entity, string $name): mixed
    {
        // It's a simple property, we just return the value
        if (!str_contains($name, '.')) {
            return $this->getRawValue($entity, $name);
        }

        try {
            // For a composed property, we extract it
            $parts = explode('.', $name);
            $currentValue = $entity;
            foreach ($parts as $part) {
                $currentValue = $this->getRawValue($currentValue, $part, $name);
            }
        } catch (ValueExtractorException $e) {
            throw new ValueExtractorException($entity, $name, $e);
        }

        return $currentValue;
    }

    /**
     * @throws ValueExtractorException
     */
    private function getRawValue(mixed $value, string $name, ?string $parent = null): mixed
    {
        if ($value === null) {
            return null;
        }

        $nameInternal = $name;
        if (is_array($value) && !str_starts_with($name, '[') && !str_ends_with($name, ']')) {
            $nameInternal = "[$name]"; // PropertyAccessor expects array keys to be enclosed in square brackets
        }

        if ((!is_array($value) && !is_object($value)) || false === $this->propertyAccessor->isReadable($value, $nameInternal)) {
            throw new ValueExtractorException($value, $name.($parent ? " (in $parent)" : ''));
        }

        return $this->propertyAccessor->getValue($value, $nameInternal);
    }
}
