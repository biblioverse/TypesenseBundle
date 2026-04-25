<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Query;

class VectorQuery implements VectorQueryInterface
{
    /**
     * @param array<float> $queryVector e.g., [0.1, 0.2, 0.3]
     * @param string[]     $queries
     */
    public function __construct(
        private readonly string $fieldName,
        private readonly array $queryVector, // e.g., [0.1, 0.2, 0.3]
        private readonly ?int $numCandidates = null, // e.g., 100
        private readonly ?int $k = null,
        private readonly ?string $id = null,
        private readonly ?float $weights = null, // Optional weight
        private readonly ?float $alpha = null,
        private readonly ?float $distanceThreshold = null,
        private readonly ?int $flatSearchCutoff = null,
        private readonly ?array $queries = null,
        private readonly ?int $ef = null,
    ) {
    }

    /**
     * @return array<string,int|string|float|float[]|string[]|bool>
     */
    private function toArray(): array
    {
        return array_filter([
            'alpha' => $this->alpha,
            'id' => $this->id,
            'k' => $this->k,
            'num_candidates' => $this->numCandidates,
            'query_vector' => $this->queryVector,
            'distance_threshold' => $this->distanceThreshold,
            'queries' => $this->queries,
            'query_weights' => $this->weights,
            'flat_search_cutoff' => $this->flatSearchCutoff,
            'ef' => $this->ef,
        ], fn ($value): bool => !is_null($value));
    }

    public function __toString(): string
    {
        // Convert embedding array to a comma-separated string
        $embeddingString = implode(', ', $this->queryVector);

        // Return the query string in the required format
        $result = $this->fieldName.":([$embeddingString]";

        $values = $this->toArray();
        unset($values['query_vector']);
        foreach ($values as $name => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $result .= ", $name:$value";
        }

        return $result.')';
    }
}
