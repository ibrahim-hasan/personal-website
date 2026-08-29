<?php

namespace App\Support\Editorial;

final class ArticleTopicClusters
{
    public const string AI_ADOPTION_GOVERNANCE = 'ai-adoption-governance';

    public const string DATA_KNOWLEDGE_SYSTEMS = 'data-knowledge-systems';

    public const string DIGITAL_TRANSFORMATION_OPERATIONS = 'digital-transformation-operations';

    public const string PRODUCT_STRATEGY_MEASUREMENT = 'product-strategy-measurement';

    /** @var array<string, string> */
    private const array CLUSTER_BY_TOPIC = [
        self::AI_ADOPTION_GOVERNANCE => self::AI_ADOPTION_GOVERNANCE,
        'ai-adoption' => self::AI_ADOPTION_GOVERNANCE,
        'ai-products' => self::AI_ADOPTION_GOVERNANCE,
        'ai-governance' => self::AI_ADOPTION_GOVERNANCE,
        'ai_strategy' => self::AI_ADOPTION_GOVERNANCE,
        'artificial-intelligence' => self::AI_ADOPTION_GOVERNANCE,
        'governance' => self::AI_ADOPTION_GOVERNANCE,
        'human-in-loop' => self::AI_ADOPTION_GOVERNANCE,
        'responsible-ai' => self::AI_ADOPTION_GOVERNANCE,
        self::DATA_KNOWLEDGE_SYSTEMS => self::DATA_KNOWLEDGE_SYSTEMS,
        'data' => self::DATA_KNOWLEDGE_SYSTEMS,
        'data-governance' => self::DATA_KNOWLEDGE_SYSTEMS,
        'knowledge-management' => self::DATA_KNOWLEDGE_SYSTEMS,
        'knowledge-systems' => self::DATA_KNOWLEDGE_SYSTEMS,
        'rag' => self::DATA_KNOWLEDGE_SYSTEMS,
        self::DIGITAL_TRANSFORMATION_OPERATIONS => self::DIGITAL_TRANSFORMATION_OPERATIONS,
        'automation' => self::DIGITAL_TRANSFORMATION_OPERATIONS,
        'digital-transformation' => self::DIGITAL_TRANSFORMATION_OPERATIONS,
        'operations' => self::DIGITAL_TRANSFORMATION_OPERATIONS,
        'systems' => self::DIGITAL_TRANSFORMATION_OPERATIONS,
        'transformation' => self::DIGITAL_TRANSFORMATION_OPERATIONS,
        self::PRODUCT_STRATEGY_MEASUREMENT => self::PRODUCT_STRATEGY_MEASUREMENT,
        'competitive-advantage' => self::PRODUCT_STRATEGY_MEASUREMENT,
        'leadership' => self::PRODUCT_STRATEGY_MEASUREMENT,
        'measurement' => self::PRODUCT_STRATEGY_MEASUREMENT,
        'product' => self::PRODUCT_STRATEGY_MEASUREMENT,
        'product-strategy' => self::PRODUCT_STRATEGY_MEASUREMENT,
        'products' => self::PRODUCT_STRATEGY_MEASUREMENT,
        'saas' => self::PRODUCT_STRATEGY_MEASUREMENT,
        'strategy' => self::PRODUCT_STRATEGY_MEASUREMENT,
    ];

    /**
     * @param  list<string>  $topicKeys
     * @return list<string>
     */
    public static function forTopicKeys(array $topicKeys): array
    {
        return collect($topicKeys)
            ->map(fn (mixed $topicKey): ?string => is_string($topicKey)
                ? (self::CLUSTER_BY_TOPIC[$topicKey] ?? null)
                : null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** @param array<array-key, mixed> $topicKeys */
    public static function allRecognized(array $topicKeys): bool
    {
        return $topicKeys !== [] && collect($topicKeys)->every(
            fn (mixed $topicKey): bool => is_string($topicKey)
                && array_key_exists($topicKey, self::CLUSTER_BY_TOPIC),
        );
    }
}
