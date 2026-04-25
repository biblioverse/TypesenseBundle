<?php

namespace Biblioverse\TypesenseBundle\Query;

use Biblioverse\TypesenseBundle\Type\FacetStrategyEnum;
use Biblioverse\TypesenseBundle\Type\InfixEnum;

class SearchQuery implements SearchQueryInterface
{
    private readonly ?string $infix;
    private readonly ?string $prefix;

    private readonly ?string $stopwords;
    private readonly ?string $facetStrategy;

    /**
     * Use named parameters to avoid issues. The order WILL change.
     *
     * @param string|InfixEnum[]|null $infix
     * @param bool|bool[]|null        $prefix
     * @param string[]|null           $stopwords
     */
    public function __construct(
        private readonly string $q,
        private readonly string $queryBy,
        private readonly ?string $filterBy = null,
        private readonly ?string $sortBy = null,
        // After this line, we use alphabetical order for the parameters
        ?array $stopwords = null,
        bool|array|null $prefix = null,
        private readonly string|VectorQueryInterface|null $vectorQuery = null,
        private readonly ?VoiceQueryInterface $voiceQuery = null,
        private readonly ?bool $enableHighlightV1 = null,
        private readonly ?bool $enableLazyFilter = null,
        private readonly ?bool $enableOverrides = null,
        private readonly ?bool $enableSynonyms = null,
        private readonly ?bool $enableTyposForAlphaNumericalTokens = null,
        private readonly ?bool $enableTyposForNumericalTokens = null,
        private readonly ?bool $exhaustiveSearch = null,
        private readonly ?bool $filterCuratedHits = null,
        private readonly ?bool $groupMissingValues = null,
        private readonly ?bool $preSegmentedQuery = null,
        private readonly ?bool $prioritizeExactMatch = null,
        private readonly ?bool $prioritizeNumMatchingFields = null,
        private readonly ?bool $prioritizeTokenPosition = null,
        private readonly ?bool $synonymPrefix = null,
        private readonly ?float $dropTokensThreshold = null,
        private readonly ?int $facetQueryNumTypos = null,
        private readonly ?int $facetSamplePercent = null,
        private readonly ?int $facetSampleThreshold = null,
        private readonly ?int $groupLimit = null,
        private readonly ?int $highlightAffixNumTokens = null,
        private readonly ?int $limit = null,
        private readonly ?int $limitHits = null,
        private readonly ?int $maxCandidates = null,
        private readonly ?int $maxFacetValues = null,
        private readonly ?int $minLen1Typo = null,
        private readonly ?int $minLen2Typo = null,
        private readonly ?int $numTypos = null,
        private readonly ?int $offset = null,
        private readonly ?int $page = null,
        private readonly ?int $perPage = null,
        private readonly ?int $remoteEmbeddingBatchSize = null,
        private readonly ?int $remoteEmbeddingNumTries = null,
        private readonly ?int $remoteEmbeddingTimeoutMs = null,
        private readonly ?int $searchCutoffMs = null,
        private readonly ?int $snippetThreshold = null,
        private readonly ?int $synonymNumTypos = null,
        private readonly ?int $typoTokensThreshold = null,
        private readonly ?string $dropTokensMode = null,
        private readonly ?string $excludeFields = null,
        private readonly ?string $facetBy = null,
        private readonly ?string $facetQuery = null,
        private readonly ?string $facetReturnParent = null,
        private readonly ?string $groupBy = null,
        private readonly ?string $hiddenHits = null,
        private readonly ?string $highlightEndTag = null,
        private readonly ?string $highlightFields = null,
        private readonly ?string $highlightFullFields = null,
        private readonly ?string $highlightStartTag = null,
        private readonly ?string $includeFields = null,
        private readonly ?string $overrideTags = null,
        private readonly ?string $pinnedHits = null,
        private readonly ?string $preset = null,
        private readonly ?string $queryByWeights = null,
        private readonly ?string $splitJoinTokens = null,
        private readonly ?string $textMatchType = null,
        string|FacetStrategyEnum|null $facetStrategy = null,
        string|array|null $infix = null,
    ) {
        $this->infix = $this->convertArray($infix, fn ($infix) => $infix instanceof InfixEnum ? $infix->value : $infix, InfixEnum::class);

        if (is_bool($prefix)) {
            $prefix = (array) $prefix;
        }

        $this->prefix = $prefix === [] || $prefix === null ? null :
            implode(',', array_map(fn (bool $value): string => $value ? 'true' : 'false', $prefix));
        $this->stopwords = $stopwords === null || $stopwords === [] ? null : implode(',', $stopwords);

        // Check incompatible combinations
        if ($this->vectorQuery instanceof VectorQueryInterface && $this->infix !== null) {
            throw new \InvalidArgumentException('Cannot set both infix and vectorQuery');
        }

        $this->facetStrategy = $facetStrategy instanceof FacetStrategyEnum ? $facetStrategy->value : $facetStrategy;
    }

    private function convertArray(mixed $values, callable $convert, ?string $className = null): ?string
    {
        if (!is_array($values)) {
            if ($values === null) {
                return null;
            }
            $values = $convert($values);
            if ($values === null) {
                return null;
            }
            if (!is_scalar($values)) {
                throw new \InvalidArgumentException('Expected scalar value');
            }

            return (string) $values;
        }
        foreach ($values as $value) {
            if ($className !== null && !$value instanceof $className) {
                throw new \InvalidArgumentException(sprintf('Expected type %s, got %s', $className, get_debug_type($value)));
            }
        }

        return $values === [] ? null : implode(',', array_map($convert, $values)); // @phpstan-ignore argument.type
    }

    public function toArray(): array
    {
        return array_filter([
            'drop_tokens_mode' => $this->dropTokensMode,
            'drop_tokens_threshold' => $this->dropTokensThreshold,
            'enable_highlight_v1' => $this->enableHighlightV1,
            'enable_lazy_filter' => $this->enableLazyFilter,
            'enable_overrides' => $this->enableOverrides,
            'enable_synonyms' => $this->enableSynonyms,
            'enable_typos_for_alpha_numerical_tokens' => $this->enableTyposForAlphaNumericalTokens,
            'enable_typos_for_numerical_tokens' => $this->enableTyposForNumericalTokens,
            'exclude_fields' => $this->excludeFields,
            'exhaustive_search' => $this->exhaustiveSearch,
            'facet_by' => $this->facetBy,
            'facet_query' => $this->facetQuery,
            'facet_query_num_typos' => $this->facetQueryNumTypos,
            'facet_return_parent' => $this->facetReturnParent,
            'facet_sample_percent' => $this->facetSamplePercent,
            'facet_sample_threshold' => $this->facetSampleThreshold,
            'facet_strategy' => $this->facetStrategy,
            'filter_by' => $this->filterBy,
            'filter_curated_hits' => $this->filterCuratedHits,
            'group_by' => $this->groupBy,
            'group_limit' => $this->groupLimit,
            'group_missing_values' => $this->groupMissingValues,
            'hidden_hits' => $this->hiddenHits,
            'highlight_affix_num_tokens' => $this->highlightAffixNumTokens,
            'highlight_end_tag' => $this->highlightEndTag,
            'highlight_fields' => $this->highlightFields,
            'highlight_full_fields' => $this->highlightFullFields,
            'highlight_start_tag' => $this->highlightStartTag,
            'include_fields' => $this->includeFields,
            'infix' => $this->infix,
            'limit' => $this->limit,
            'limit_hits' => $this->limitHits,
            'max_candidates' => $this->maxCandidates,
            'max_facet_values' => $this->maxFacetValues,
            'min_len_1typo' => $this->minLen1Typo,
            'min_len_2typo' => $this->minLen2Typo,
            'num_typos' => $this->numTypos,
            'offset' => $this->offset,
            'override_tags' => $this->overrideTags,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'pinned_hits' => $this->pinnedHits,
            'pre_segmented_query' => $this->preSegmentedQuery,
            'prefix' => $this->prefix,
            'preset' => $this->preset,
            'prioritize_exact_match' => $this->prioritizeExactMatch,
            'prioritize_num_matching_fields' => $this->prioritizeNumMatchingFields,
            'prioritize_token_position' => $this->prioritizeTokenPosition,
            'q' => $this->q,
            'query_by' => $this->queryBy,
            'query_by_weights' => $this->queryByWeights,
            'remote_embedding_batch_size' => $this->remoteEmbeddingBatchSize,
            'remote_embedding_num_tries' => $this->remoteEmbeddingNumTries,
            'remote_embedding_timeout_ms' => $this->remoteEmbeddingTimeoutMs,
            'search_cutoff_ms' => $this->searchCutoffMs,
            'snippet_threshold' => $this->snippetThreshold,
            'sort_by' => $this->sortBy,
            'split_join_tokens' => $this->splitJoinTokens,
            'stopwords' => $this->stopwords,
            'synonym_num_typos' => $this->synonymNumTypos,
            'synonym_prefix' => $this->synonymPrefix,
            'text_match_type' => $this->textMatchType,
            'typo_tokens_threshold' => $this->typoTokensThreshold,
            'vector_query' => $this->vectorQuery instanceof VectorQueryInterface ? (string) $this->vectorQuery : $this->vectorQuery,
            'voice_query' => $this->voiceQuery instanceof VoiceQueryInterface ? (string) $this->voiceQuery : null,
        ], fn (mixed $value): bool => !is_null($value));
    }

    public function getParameter(string $name): mixed
    {
        return $this->toArray()[$name] ?? null;
    }

    public function getQ(): string
    {
        return $this->q;
    }

    public function getQueryBy(): string
    {
        return $this->queryBy;
    }

    public function getFilterBy(): ?string
    {
        return $this->filterBy;
    }

    public function getSortBy(): ?string
    {
        return $this->sortBy;
    }
}
