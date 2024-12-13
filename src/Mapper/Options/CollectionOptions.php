<?php

namespace Biblioteca\TypesenseBundle\Mapper\Options;

class CollectionOptions implements CollectionOptionsInterface
{
    /** @param string[]|null $tokenSeparators */
    /** @param string[]|null $symbolsToIndex */
    public function __construct(
        public ?array $tokenSeparators = null,
        public ?array $symbolsToIndex = null,
        public ?string $defaultSortingField = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'token_separators' => $this->tokenSeparators,
            'symbols_to_index' => $this->symbolsToIndex,
            'default_sorting_field' => $this->defaultSortingField,
        ];
    }
}
