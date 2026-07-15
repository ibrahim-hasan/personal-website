<?php

namespace App\Services\ElevenLabs;

use App\Data\ElevenLabs\SpeechSynthesisResult;
use App\Exceptions\ElevenLabsRequestException;
use App\Services\ArticleAudio\Mp3Concatenator;
use App\Services\ArticleAudio\NarrationTextChunker;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class ElevenLabsTextToSpeech
{
    public function __construct(
        private readonly NarrationTextChunker $chunker,
        private readonly Mp3Concatenator $concatenator,
    ) {}

    public function synthesize(string $text, string $locale, ?string $modelId = null): SpeechSynthesisResult
    {
        $apiKey = (string) config('services.elevenlabs.api_key');
        $voiceId = (string) config('services.elevenlabs.voice_id');
        $modelId ??= (string) config('services.elevenlabs.model_id', 'eleven_multilingual_v2');
        $profile = $this->profile($modelId);

        if ($apiKey === '' || $voiceId === '') {
            throw new RuntimeException('ElevenLabs server configuration is incomplete.');
        }

        $chunks = $this->chunker->split($text, (int) $profile['max_characters']);

        if ($chunks === []) {
            throw new RuntimeException('The article narration is empty.');
        }

        $audioSegments = [];
        $requestIds = [];

        foreach ($chunks as $index => $chunk) {
            $payload = [
                'text' => $chunk,
                'model_id' => $modelId,
                'voice_settings' => $profile['voice_settings'],
            ];

            if ($profile['supports_language_code']) {
                $payload['language_code'] = $locale === 'ar' ? 'ar' : 'en';
            }

            $normalization = (string) config('services.elevenlabs.text_normalization', 'on');

            if (in_array($normalization, ['auto', 'on', 'off'], true)) {
                $payload['apply_text_normalization'] = $normalization;
            }

            if ($profile['supports_request_stitching']) {
                $contextCharacters = (int) config('services.elevenlabs.context_characters', 400);

                if ($index > 0) {
                    $payload['previous_text'] = mb_substr($chunks[$index - 1], -$contextCharacters);
                }

                if (isset($chunks[$index + 1])) {
                    $payload['next_text'] = mb_substr($chunks[$index + 1], 0, $contextCharacters);
                }
            }

            $outputFormat = (string) config('services.elevenlabs.output_format', 'mp3_44100_128');
            $response = Http::baseUrl(rtrim((string) config('services.elevenlabs.base_url'), '/'))
                ->withHeaders([
                    'xi-api-key' => $apiKey,
                    'Accept' => 'audio/mpeg',
                ])
                ->asJson()
                ->connectTimeout((int) config('services.elevenlabs.connect_timeout', 15))
                ->timeout((int) config('services.elevenlabs.timeout', 150))
                ->retry(
                    [1200, 3000],
                    when: function (Throwable $exception): bool {
                        if (! $exception instanceof RequestException) {
                            return false;
                        }

                        return in_array($exception->response->status(), [429, 500, 502, 503, 504], true);
                    },
                    throw: false,
                )
                ->post('/text-to-speech/'.rawurlencode($voiceId).'?output_format='.rawurlencode($outputFormat), $payload);

            if (! $response->successful()) {
                throw ElevenLabsRequestException::fromResponse(
                    $response,
                    $this->requestId($response->headers()),
                );
            }

            $contentType = strtolower($response->header('Content-Type', ''));

            if ($response->body() === '' || ($contentType !== '' && ! str_contains($contentType, 'audio') && ! str_contains($contentType, 'octet-stream'))) {
                throw new RuntimeException('ElevenLabs returned an invalid audio response.');
            }

            $audioSegments[] = $response->body();
            $requestId = $this->requestId($response->headers());

            if ($requestId !== 'unavailable') {
                $requestIds[] = $requestId;
            }
        }

        return new SpeechSynthesisResult(
            audio: $this->concatenator->concatenate($audioSegments),
            requestIds: $requestIds,
            segmentCount: count($audioSegments),
            characterCount: mb_strlen($text),
        );
    }

    /**
     * @return array{
     *     label: string,
     *     max_characters: int,
     *     supports_language_code: bool,
     *     supports_request_stitching: bool,
     *     voice_settings: array<string, float|bool>
     * }
     */
    public function profile(string $modelId): array
    {
        $profile = config('services.elevenlabs.models.'.$modelId);

        if (! is_array($profile)) {
            throw new RuntimeException('The selected ElevenLabs model is not supported.');
        }

        return [
            'label' => (string) ($profile['label'] ?? $modelId),
            'max_characters' => max(100, (int) ($profile['max_characters'] ?? 4500)),
            'supports_language_code' => (bool) ($profile['supports_language_code'] ?? false),
            'supports_request_stitching' => (bool) ($profile['supports_request_stitching'] ?? false),
            'voice_settings' => array_filter(
                (array) ($profile['voice_settings'] ?? []),
                fn (mixed $value): bool => $value !== null,
            ),
        ];
    }

    /** @param array<string, list<string>> $headers */
    private function requestId(array $headers): string
    {
        return $headers['request-id'][0]
            ?? $headers['Request-Id'][0]
            ?? $headers['x-request-id'][0]
            ?? $headers['X-Request-Id'][0]
            ?? 'unavailable';
    }
}
