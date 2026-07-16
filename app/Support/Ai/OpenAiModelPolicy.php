<?php

namespace App\Support\Ai;

final class OpenAiModelPolicy
{
    /** @var array<string, string> */
    private const SEO_MODELS = [
        'gpt-4o-mini' => 'GPT-4o mini',
        'gpt-4.1-mini' => 'GPT-4.1 mini',
        'gpt-4.1' => 'GPT-4.1',
    ];

    /** @var array<string, string> */
    private const NARRATION_MODELS = [
        'gpt-4.1' => 'GPT-4.1',
    ];

    /** @return array<string, string> */
    public function seoOptions(): array
    {
        return self::SEO_MODELS;
    }

    public function seoModel(?string $candidate = null): string
    {
        return $this->resolve(
            $candidate,
            self::SEO_MODELS,
            (string) config('services.openai.seo_model', 'gpt-4o-mini'),
        );
    }

    public function narrationModel(?string $candidate = null): string
    {
        return $this->resolve(
            $candidate,
            self::NARRATION_MODELS,
            (string) config('services.openai.narration_model', 'gpt-4.1'),
        );
    }

    /**
     * @param  array<string, string>  $allowedModels
     */
    private function resolve(?string $candidate, array $allowedModels, string $configuredDefault): string
    {
        $candidate = trim((string) $candidate);

        if (array_key_exists($candidate, $allowedModels)) {
            return $candidate;
        }

        $configuredDefault = trim($configuredDefault);

        if (array_key_exists($configuredDefault, $allowedModels)) {
            return $configuredDefault;
        }

        return (string) array_key_first($allowedModels);
    }
}
