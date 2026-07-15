<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class SeoGenerationService
{
    public function isEnabled(string $context = 'admin'): bool
    {
        $enabled = (bool) Setting::query()
            ->where('key', 'ai_seo_enabled')
            ->value(Setting::valueColumn());

        if ($context === 'expert') {
            $expertEnabled = (bool) Setting::query()
                ->where('key', 'ai_seo_expert_enabled')
                ->value(Setting::valueColumn());

            return $enabled && $expertEnabled;
        }

        return $enabled;
    }

    /**
     * @return array{seo_title:string,seo_description:string}
     */
    public function generate(string $title, ?string $content, string $locale = 'ar', ?string $sourceLocale = null): array
    {
        $title = trim($title);
        $content = trim((string) $content);
        $sourceLocale ??= $locale;

        if ($title === '') {
            return $this->fallback($title, $content);
        }

        $apiKey = $this->getApiKey();

        if ($apiKey === '') {
            throw new RuntimeException(__('OpenAI API key is not configured in the server environment.'));
        }

        $baseUrl = $this->getBaseUrl();
        $model = $this->getModel();

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $this->systemPrompt($locale)],
                ['role' => 'user', 'content' => $this->promptForLocale($title, $content, $locale, $sourceLocale)],
            ],
            'temperature' => 0.7,
            'max_tokens' => 300,
            'response_format' => ['type' => 'json_object'],
        ];

        $response = Http::baseUrl($baseUrl)
            ->withToken($apiKey)
            ->timeout(20)
            ->post('/chat/completions', $payload)
            ->throw()
            ->json();

        $raw = (string) data_get($response, 'choices.0.message.content', '');
        $data = $this->parseJsonResponse($raw);

        return [
            'seo_title' => $this->cleanField((string) ($data['title'] ?? ''), 60, $title),
            'seo_description' => $this->cleanField((string) ($data['description'] ?? ''), 155, $content !== '' ? $content : $title),
        ];
    }

    private function getApiKey(): string
    {
        return (string) config('services.openai.api_key');
    }

    private function getModel(): string
    {
        return (string) (Setting::query()->where('key', 'openai_model')->value(Setting::valueColumn()) ?? 'gpt-4o-mini');
    }

    private function getBaseUrl(): string
    {
        return (string) (Setting::query()->where('key', 'openai_base_url')->value(Setting::valueColumn())
            ?? config('services.openai.base_url'));
    }

    /**
     * @return array{title:string,description:string}
     */
    private function fallback(string $title, string $content): array
    {
        return [
            'title' => mb_substr($title !== '' ? $title : $content, 0, 60),
            'description' => mb_substr($content !== '' ? $content : $title, 0, 155),
        ];
    }

    private function systemPrompt(string $locale): string
    {
        return $locale === 'ar'
            ? 'أنت خبير SEO. أعد الناتج كـ JSON فقط بمفتاحين title و description.'
            : 'You are an SEO expert. Return JSON only with two keys: title and description.';
    }

    private function promptForLocale(string $title, string $content, string $locale, string $sourceLocale): string
    {
        if ($locale !== $sourceLocale) {
            return "Translate and localize SEO content from {$sourceLocale} to {$locale}. Title: {$title}. Content: {$content}";
        }

        return "Generate an SEO title and description in {$locale}. Title: {$title}. Content: {$content}";
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJsonResponse(string $raw): array
    {
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function cleanField(string $value, int $max, string $fallback): string
    {
        $value = trim($value);

        if ($value === '') {
            $value = trim($fallback);
        }

        return mb_substr($value, 0, $max);
    }
}
