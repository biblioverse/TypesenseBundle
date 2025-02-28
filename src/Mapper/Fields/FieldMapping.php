<?php

namespace Biblioverse\TypesenseBundle\Mapper\Fields;

use Biblioverse\TypesenseBundle\Mapper\Converter\Field\FieldConverterInterface;
use Biblioverse\TypesenseBundle\Type\DataTypeEnum;

/**
 * @phpstan-type FieldMappingArray array{
 *  'name'?: string,
 *  'type'?: string,
 *  'facet'?: bool|null,
 *  'optional'?:bool|null,
 *  'drop'?:bool|null,
 *  'index'?:bool|null,
 *  'infix'?:bool|null,
 *  'rangeIndex'?:bool|null,
 *  'sort'?:bool|null,
 *  'stem'?:bool|null,
 *  'store'?:bool|null,
 *  'numDim'?:int|null,
 *  'locale'?:string|null,
 *  'reference'?:string|null,
 *  'entity_attribute'?:string|null,
 *  'vecDist'?:string|null,
 *  'embed'?:null|FieldMappingEmbedArray,
 *  'mapped'?:bool
 * }
 * @phpstan-type FieldMappingEmbedArray array{
 *   'from':string,
 *   'model_config'?:FieldMappingEmbedModelValueArray,
 * }
 * @phpstan-type FieldMappingEmbedModelValueArray array{
 *   'model_name':string,
 *   'api_key'?:string,
 *   'url'?:string
 * }
 */
class FieldMapping implements FieldMappingInterface
{
    private readonly string $type;

    private ?string $entityAttribute = null;

    private ?FieldConverterInterface $fieldConverter = null;

    /**
     * @param FieldMappingEmbedArray $embed
     */
    public function __construct(
        public string $name,
        DataTypeEnum|string $type,
        public ?bool $facet = null,
        public ?bool $optional = null,
        public ?bool $drop = null,
        public ?bool $index = null,
        public ?bool $infix = null,
        public ?bool $rangeIndex = null,
        public ?bool $sort = null, // Default depends on the type; not assigned here
        public ?bool $stem = null,
        public ?bool $store = null,
        public ?int $numDim = null,
        public ?string $locale = null,
        public ?string $reference = null,
        public ?string $vecDist = null,
        public ?array $embed = null,
        public bool $mapped = true,
    ) {
        $this->type = $type instanceof DataTypeEnum ? $type->value : $type;
    }

    /**
     * @template TKey
     * @template TValue
     *
     * @param array<TKey, TValue> $array
     *
     * @return array<TKey, TValue>
     */
    protected static function array_filter_recursive(array $array, ?callable $callback = null): array
    {
        $array = is_callable($callback) ? array_filter($array, $callback) : array_filter($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = self::array_filter_recursive($value, $callback);
            }
        }

        return $array;
    }

    public function toArray(): array
    {
        // Make sure to "ksort" values to keep the order consistent
        return self::array_filter_recursive([
            'drop' => $this->drop,
            'facet' => $this->facet,
            'index' => $this->index,
            'infix' => $this->infix,
            'locale' => $this->locale,
            'name' => $this->name,
            'num_dims' => $this->numDim,
            'optional' => $this->optional,
            'range_index' => $this->rangeIndex,
            'reference' => $this->reference,
            'sort' => $this->sort,
            'stem' => $this->stem,
            'store' => $this->store,
            'type' => $this->type,
            'vec_dist' => $this->vecDist,
            'embed' => $this->embed,
        ], fn ($value) => $value !== null);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isMapped(): bool
    {
        return $this->mapped;
    }

    public function getEntityAttribute(): ?string
    {
        return $this->entityAttribute;
    }

    /**
     * @param FieldMappingArray $config
     */
    public static function fromArray(array $config): self
    {
        $result = new self(
            $config['name'] ?? throw new \InvalidArgumentException('Name is required'),
            $config['type'] ?? throw new \InvalidArgumentException('Type is required'),
            $config['facet'] ?? null,
            $config['optional'] ?? null,
            $config['drop'] ?? null,
            $config['index'] ?? null,
            $config['infix'] ?? null,
            $config['rangeIndex'] ?? null,
            $config['sort'] ?? null,
            $config['stem'] ?? null,
            $config['store'] ?? null,
            $config['numDim'] ?? null,
            $config['locale'] ?? null,
            $config['reference'] ?? null,
            $config['vecDist'] ?? null,
            $config['embed'] ?? null,
            $config['mapped'] ?? true,
        );

        $result->entityAttribute = $config['entity_attribute'] ?? null;

        return $result;
    }

    public function isOptional(): bool
    {
        return $this->optional ?? true;
    }

    public function getFieldConverter(): ?FieldConverterInterface
    {
        return $this->fieldConverter;
    }

    public function setFieldConverter(?FieldConverterInterface $fieldConverter): void
    {
        $this->fieldConverter = $fieldConverter;
    }

    public function setEntityAttribute(?string $entityAttribute): self
    {
        $this->entityAttribute = $entityAttribute;

        return $this;
    }
}
