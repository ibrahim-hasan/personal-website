<?php

namespace App\Services\ElevenLabs;

use App\Data\ElevenLabs\SpeechSynthesisResult;
use App\Exceptions\ElevenLabsRequestException;
use App\Services\ArticleAudio\Mp3Concatenator;
use App\Services\ArticleAudio\NarrationTextChunker;
use App\Support\Ai\ElevenLabsExecutionBudget;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ElevenLabsTextToSpeech
{
    public function __construct(
        private readonly NarrationTextChunker $chunker,
        private readonly Mp3Concatenator $concatenator,
        private readonly ElevenLabsSegmentCheckpointStore $checkpoints,
    ) {}

    public function synthesize(string $text, string $locale, ?string $modelId = null): SpeechSynthesisResult
    {
        $apiKey = (string) config('services.elevenlabs.api_key');
        $voiceId = $this->voiceId();
        $modelId ??= (string) config('services.elevenlabs.model_id', 'eleven_multilingual_v2');
        $profile = $this->profile($modelId);

        if ($apiKey === '' || $voiceId === '') {
            throw new RuntimeException('ElevenLabs server configuration is incomplete.');
        }

        $chunks = $this->chunker->split($text, (int) $profile['max_characters']);

        if ($chunks === []) {
            throw new RuntimeException('The article narration is empty.');
        }

        if (count($chunks) > ElevenLabsExecutionBudget::maxSegments()) {
            throw new RuntimeException(sprintf(
                'The narration requires %d ElevenLabs segments; the configured per-job maximum is %d.',
                count($chunks),
                ElevenLabsExecutionBudget::maxSegments(),
            ));
        }

        $audioSegments = [];
        $requestIds = [];
        $outputFormat = (string) config('services.elevenlabs.output_format', 'mp3_44100_128');
        $normalization = (string) config('services.elevenlabs.text_normalization', 'on');
        $contextCharacters = (int) config('services.elevenlabs.context_characters', 400);
        $checkpointKey = $this->checkpoints->key([
            'version' => 1,
            'text_hash' => hash('sha256', $text),
            'chunk_hashes' => array_map(
                fn (string $chunk): string => hash('sha256', $chunk),
                $chunks,
            ),
            'locale' => $locale,
            'voice_id' => $voiceId,
            'model_id' => $modelId,
            'output_format' => $outputFormat,
            'voice_settings' => $profile['voice_settings'],
            'text_normalization' => $normalization,
            'context_characters' => $contextCharacters,
        ]);

        foreach ($chunks as $index => $chunk) {
            $textHash = hash('sha256', $chunk);
            $restored = $this->checkpoints->restore($checkpointKey, $index, $textHash);

            if ($restored !== null) {
                $audioSegments[] = $restored['audio'];

                if ($restored['request_id'] !== 'unavailable') {
                    $requestIds[] = $restored['request_id'];
                }

                continue;
            }

            $payload = [
                'text' => $chunk,
                'model_id' => $modelId,
                'voice_settings' => $profile['voice_settings'],
            ];

            if ($profile['supports_language_code']) {
                $payload['language_code'] = $locale === 'ar' ? 'ar' : 'en';
            }

            if (in_array($normalization, ['auto', 'on', 'off'], true)) {
                $payload['apply_text_normalization'] = $normalization;
            }

            if ($profile['supports_request_stitching']) {
                if ($index > 0) {
                    $payload['previous_text'] = mb_substr($chunks[$index - 1], -$contextCharacters);
                }

                if (isset($chunks[$index + 1])) {
                    $payload['next_text'] = mb_substr($chunks[$index + 1], 0, $contextCharacters);
                }
            }

            $response = Http::baseUrl(rtrim((string) config('services.elevenlabs.base_url'), '/'))
                ->withHeaders([
                    'xi-api-key' => $apiKey,
                    'Accept' => 'audio/mpeg',
                ])
                ->asJson()
                ->connectTimeout(ElevenLabsExecutionBudget::connectTimeout())
                ->timeout(ElevenLabsExecutionBudget::providerTimeout())
                ->post('/text-to-speech/'.rawurlencode($voiceId).'/stream?output_format='.rawurlencode($outputFormat), $payload);

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

            $audio = $response->body();
            $requestId = $this->requestId($response->headers());
            $this->checkpoints->persist($checkpointKey, $index, $textHash, $audio, $requestId);
            $audioSegments[] = $audio;

            if ($requestId !== 'unavailable') {
                $requestIds[] = $requestId;
            }
        }

        return new SpeechSynthesisResult(
            audio: $this->concatenator->concatenate($audioSegments),
            requestIds: $requestIds,
            segmentCount: count($audioSegments),
            characterCount: mb_strlen($text),
            checkpointKey: $checkpointKey,
        );
    }

    public function forgetCheckpoint(string $checkpointKey): void
    {
        $this->checkpoints->forget($checkpointKey);
    }

    public function voiceId(): string
    {
        return trim((string) config('services.elevenlabs.voice_id'));
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
