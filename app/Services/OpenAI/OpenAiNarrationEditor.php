<?php

namespace App\Services\OpenAI;

use App\Contracts\ArticleAudio\NarrationEditor;
use App\Data\ArticleAudio\NarrationDraft;
use App\Exceptions\OpenAiNarrationException;
use App\Services\ArticleAudio\NarrationDraftValidator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class OpenAiNarrationEditor implements NarrationEditor
{
    private const PROMPT_VERSION = 'arabic-editorial-v1';

    public function __construct(
        private readonly NarrationDraftValidator $validator,
    ) {}

    public function prepare(string $source, string $locale): NarrationDraft
    {
        $apiKey = (string) config('services.openai.api_key');
        $model = (string) config('services.openai.narration_model', 'gpt-5.6-terra');

        if ($apiKey === '') {
            throw new RuntimeException('OpenAI server configuration is incomplete.');
        }

        $response = Http::baseUrl(rtrim((string) config('services.openai.base_url'), '/'))
            ->withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->connectTimeout((int) config('services.openai.connect_timeout', 15))
            ->timeout((int) config('services.openai.timeout', 180))
            ->retry(
                [1500, 3500],
                when: function (Throwable $exception): bool {
                    if ($exception instanceof ConnectionException) {
                        return true;
                    }

                    return $exception instanceof RequestException
                        && in_array($exception->response->status(), [429, 500, 502, 503, 504], true);
                },
                throw: false,
            )
            ->post('/responses', [
                'model' => $model,
                'store' => false,
                'instructions' => $this->instructions($locale),
                'input' => $this->input($source, $locale),
                'max_output_tokens' => (int) config('services.openai.narration_max_output_tokens', 20000),
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => 'article_narration',
                        'strict' => true,
                        'schema' => $this->schema(),
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw OpenAiNarrationException::fromResponse($response);
        }

        $payload = json_decode($this->outputText((array) $response->json()), true);

        if (! is_array($payload)) {
            throw new RuntimeException('OpenAI returned an invalid narration response.');
        }

        $script = trim((string) ($payload['script'] ?? ''));
        $this->validator->validate($script, $source);

        return new NarrationDraft(
            script: $script,
            notes: $this->stringList($payload['notes'] ?? []),
            pronunciationNotes: $this->stringList($payload['pronunciation_notes'] ?? []),
            model: $model,
            promptVersion: self::PROMPT_VERSION,
        );
    }

    private function instructions(string $locale): string
    {
        $language = $locale === 'ar' ? 'Arabic' : 'English';

        return <<<PROMPT
You are a senior {$language} audiobook editor preparing a business article for calm, premium text-to-speech narration.

Preserve every fact, qualification, example, argument, and the original order. Do not summarize, remove ideas, invent claims, add an introduction, or add a conclusion. Improve only spoken delivery: sentence boundaries, punctuation, natural pauses, pronunciation clarity, and the spoken form of numbers or abbreviations.

For Arabic, use Modern Standard Arabic with a warm editorial rhythm. Add diacritics only where they resolve genuine ambiguity; never fully vocalize the article. Keep established English product or company names intact unless a natural Arabic reading is clearly better.

Allowed audio tags are exactly [thoughtful], [short pause], and [long pause]. Use them sparingly. Do not use HTML, SSML, Markdown fences, headings invented by you, stage directions, or any other square-bracket tag.

Return only the required structured response. The script must remain close to the source length and must be suitable for human review before synthesis.
PROMPT;
    }

    private function input(string $source, string $locale): string
    {
        return "Target locale: {$locale}\n\nSOURCE ARTICLE:\n{$source}";
    }

    /** @return array<string, mixed> */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'script' => ['type' => 'string'],
                'notes' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'maxItems' => 8,
                ],
                'pronunciation_notes' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'maxItems' => 12,
                ],
            ],
            'required' => ['script', 'notes', 'pronunciation_notes'],
        ];
    }

    /** @param array<string, mixed> $response */
    private function outputText(array $response): string
    {
        foreach ((array) ($response['output'] ?? []) as $output) {
            if (! is_array($output)) {
                continue;
            }

            foreach ((array) ($output['content'] ?? []) as $content) {
                if (is_array($content) && ($content['type'] ?? null) === 'output_text') {
                    return (string) ($content['text'] ?? '');
                }
            }
        }

        return '';
    }

    /** @return list<string> */
    private function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (mixed $item): string => trim(is_scalar($item) ? (string) $item : ''),
            $value,
        )));
    }
}
