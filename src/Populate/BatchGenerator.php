<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Populate;

/**
 * @template T
 */
class BatchGenerator
{
    private readonly int $batchSize;

    /**
     * Constructor to initialize the iterable and batch size.
     *
     * @param iterable<T> $iterable  the data source to process
     * @param int         $batchSize the number of elements in each batch
     *
     * @throws \InvalidArgumentException if batch size is not greater than 0
     */
    public function __construct(private readonly iterable $iterable, int $batchSize)
    {
        if ($batchSize <= 0) {
            throw new \InvalidArgumentException('Batch size must be greater than 0.');
        }
        $this->batchSize = $batchSize;
    }

    /**
     * Generate batches of elements from the iterable.
     *
     * @return \Generator<array<T>> yields an array of elements in each batch
     */
    public function generate(): \Generator
    {
        $batch = [];
        foreach ($this->iterable as $item) {
            $batch[] = $item;

            if (count($batch) === $this->batchSize) {
                yield $batch;
                $batch = [];
            }
        }

        // Yield remaining elements if they exist
        if ($batch !== []) {
            yield $batch;
        }
    }
}
