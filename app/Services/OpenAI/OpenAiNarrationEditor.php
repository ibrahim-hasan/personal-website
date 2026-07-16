<?php

namespace App\Services\OpenAI;

use App\Ai\Agents\ArticleNarrationEditor;
use App\Contracts\ArticleAudio\NarrationEditor;
use App\Data\ArticleAudio\NarrationDraft;
use App\Exceptions\OpenAiNarrationException;
use App\Services\ArticleAudio\NarrationDraftValidator;
use App\Support\Ai\NarrationExecutionBudget;
use App\Support\Ai\OpenAiModelPolicy;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;
use Throwable;

class OpenAiNarrationEditor implements NarrationEditor
{
    private const PROMPT_VERSION = 'arabic-editorial-v2';

    public function __construct(
        private readonly NarrationDraftValidator $validator,
        private readonly OpenAiModelPolicy $models,
    ) {}

    public function prepare(string $source, string $locale): NarrationDraft
    {
        $apiKey = (string) config('ai.providers.openai.key');
        $model = $this->models->narrationModel();

        if ($apiKey === '' || $model === '') {
            throw new RuntimeException('OpenAI server configuration is incomplete.');
        }

        try {
            $response = retry(
                NarrationExecutionBudget::retryDelays(),
                fn () => ArticleNarrationEditor::make(locale: $locale)
                    ->prompt(
                        $this->input($source, $locale),
                        provider: Lab::OpenAI,
                        model: $model,
                        timeout: NarrationExecutionBudget::providerTimeout(),
                    ),
                when: fn (Throwable $exception): bool => $this->shouldRetry($exception),
            );
        } catch (Throwable $exception) {
            throw OpenAiNarrationException::fromThrowable($exception);
        }

        if (! $response instanceof StructuredAgentResponse) {
            throw new RuntimeException('OpenAI returned an invalid narration response.');
        }

        $payload = $response->toArray();

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

    private function input(string $source, string $locale): string
    {
        return "Target locale: {$locale}\n\nSOURCE ARTICLE:\n{$source}";
    }

    private function shouldRetry(Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException
            || $exception instanceof RateLimitedException
            || $exception instanceof ProviderOverloadedException) {
            return true;
        }

        return $exception instanceof RequestException
            && $exception->response !== null
            && in_array($exception->response->status(), [408, 429, 500, 502, 503, 504], true);
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
