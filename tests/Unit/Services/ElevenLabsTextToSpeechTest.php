<?php

namespace Tests\Unit\Services;

use App\Exceptions\ElevenLabsRequestException;
use App\Services\ArticleAudio\Mp3Concatenator;
use App\Services\ElevenLabs\ElevenLabsTextToSpeech;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ElevenLabsTextToSpeechTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config()->set('services.elevenlabs', [
            'api_key' => 'server-secret',
            'base_url' => 'https://api.elevenlabs.test/v1',
            'voice_id' => 'calm-arabic-voice',
            'model_id' => 'eleven_multilingual_v2',
            'voice_ids' => [
                'ar' => 'calm-arabic-voice',
                'en' => 'american-english-voice',
            ],
            'output_format' => 'mp3_44100_128',
            'max_characters' => 100,
            'context_characters' => 30,
            'timeout' => 10,
            'connect_timeout' => 2,
            'checkpoint_disk' => 'local',
            'voice_settings' => [
                'stability' => 0.72,
                'similarity_boost' => 0.78,
                'style' => 0.08,
                'speed' => 0.96,
                'use_speaker_boost' => true,
            ],
            'text_normalization' => 'on',
            'models' => [
                'eleven_v3' => [
                    'label' => 'Eleven v3',
                    'max_characters' => 100,
                    'supports_language_code' => true,
                    'supports_request_stitching' => false,
                    'voice_settings' => [
                        'stability' => 0.50,
                        'similarity_boost' => 0.78,
                        'style' => 0.0,
                        'speed' => 0.90,
                        'use_speaker_boost' => false,
                    ],
                ],
                'eleven_multilingual_v2' => [
                    'label' => 'Multilingual v2',
                    'max_characters' => 100,
                    'supports_language_code' => false,
                    'supports_request_stitching' => true,
                    'voice_settings' => [
                        'stability' => 0.72,
                        'similarity_boost' => 0.78,
                        'style' => 0.08,
                        'speed' => 0.96,
                        'use_speaker_boost' => true,
                    ],
                ],
            ],
        ]);
    }

    public function test_it_sends_chunked_arabic_requests_with_context_and_returns_one_mp3(): void
    {
        $requests = [];
        $segment = "\xFF\xFB".str_repeat('A', 250);

        Http::preventStrayRequests();
        Http::fake(function (Request $request) use (&$requests, $segment) {
            $requests[] = $request;

            return Http::response($segment, 200, [
                'Content-Type' => 'audio/mpeg',
                'request-id' => 'request-'.count($requests),
            ]);
        });

        $text = implode("\n\n", [
            str_repeat('أ', 80).'.',
            str_repeat('ب', 80).'.',
            str_repeat('ت', 80).'.',
        ]);
        $result = app(ElevenLabsTextToSpeech::class)->synthesize($text, 'ar');

        $this->assertCount(3, $requests);
        $this->assertTrue(collect($requests)->every(
            fn (Request $request): bool => mb_strlen((string) $request['text']) <= 100,
        ));
        $this->assertSame(3, $result->segmentCount);
        $this->assertSame(mb_strlen($text), $result->characterCount);
        $this->assertSame($segment.$segment.$segment, $result->audio);
        $this->assertSame(['request-1', 'request-2', 'request-3'], $result->requestIds);
        $this->assertTrue($requests[0]->hasHeader('xi-api-key', 'server-secret'));
        $this->assertArrayNotHasKey('language_code', $requests[0]->data());
        $this->assertSame('eleven_multilingual_v2', $requests[0]['model_id']);
        $this->assertSame('on', $requests[0]['apply_text_normalization']);
        $this->assertSame(0.96, $requests[0]['voice_settings']['speed']);
        $this->assertArrayHasKey('next_text', $requests[0]->data());
        $this->assertArrayNotHasKey('previous_text', $requests[0]->data());
        $this->assertArrayHasKey('previous_text', $requests[1]->data());
        $this->assertArrayHasKey('next_text', $requests[1]->data());
        $this->assertStringContainsString(
            '/text-to-speech/calm-arabic-voice/stream?output_format=mp3_44100_128',
            $requests[0]->url(),
        );
    }

    public function test_v3_sends_the_arabic_language_code_and_uses_the_slower_expressive_profile(): void
    {
        $request = null;
        $segment = "\xFF\xFB".str_repeat('A', 250);

        Http::preventStrayRequests();
        Http::fake(function (Request $sent) use (&$request, $segment) {
            $request = $sent;

            return Http::response($segment, 200, ['Content-Type' => 'audio/mpeg']);
        });

        app(ElevenLabsTextToSpeech::class)->synthesize('نص عربي هادئ.', 'ar', 'eleven_v3');

        $this->assertNotNull($request);
        $this->assertSame('eleven_v3', $request['model_id']);
        $this->assertSame('ar', $request['language_code']);
        $this->assertSame(0.90, $request['voice_settings']['speed']);
        $this->assertSame(0.50, $request['voice_settings']['stability']);
    }

    public function test_it_uses_the_configured_american_english_voice_for_english(): void
    {
        $request = null;
        $segment = "\xFF\xFB".str_repeat('A', 250);

        Http::preventStrayRequests();
        Http::fake(function (Request $sent) use (&$request, $segment) {
            $request = $sent;

            return Http::response($segment, 200, ['Content-Type' => 'audio/mpeg']);
        });

        app(ElevenLabsTextToSpeech::class)->synthesize('An American English article.', 'en', 'eleven_v3');

        $this->assertNotNull($request);
        $this->assertStringContainsString(
            '/text-to-speech/american-english-voice/stream?output_format=mp3_44100_128',
            $request->url(),
        );
        $this->assertSame('eleven_v3', $request['model_id']);
        $this->assertSame('en', $request['language_code']);
    }

    public function test_v3_omits_request_stitching_fields_when_an_article_needs_multiple_chunks(): void
    {
        $requests = [];
        $segment = "\xFF\xFB".str_repeat('A', 250);

        Http::preventStrayRequests();
        Http::fake(function (Request $request) use (&$requests, $segment) {
            $requests[] = $request;

            return Http::response($segment, 200, [
                'Content-Type' => 'audio/mpeg',
                'request-id' => 'request-'.count($requests),
            ]);
        });

        $text = implode("\n\n", [
            str_repeat('أ', 80).'.',
            str_repeat('ب', 80).'.',
            str_repeat('ت', 80).'.',
        ]);

        $result = app(ElevenLabsTextToSpeech::class)->synthesize($text, 'ar', 'eleven_v3');

        $this->assertCount(3, $requests);
        $this->assertSame(3, $result->segmentCount);
        $this->assertTrue(collect($requests)->every(
            fn (Request $request): bool => $request['language_code'] === 'ar'
                && ! array_key_exists('previous_text', $request->data())
                && ! array_key_exists('next_text', $request->data())
                && ! array_key_exists('previous_request_ids', $request->data())
                && ! array_key_exists('next_request_ids', $request->data()),
        ));
    }

    public function test_it_refuses_to_send_a_request_when_server_credentials_are_missing(): void
    {
        config()->set('services.elevenlabs.api_key', null);
        Http::preventStrayRequests();
        Http::fake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('configuration is incomplete');

        try {
            app(ElevenLabsTextToSpeech::class)->synthesize('نص صالح للتوليد.', 'ar');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_it_rejects_narration_beyond_the_bounded_segment_budget_before_spending_credits(): void
    {
        config()->set('services.elevenlabs.max_segments', 2);
        Http::preventStrayRequests();
        Http::fake();

        $text = implode("\n\n", [
            str_repeat('أ', 80).'.',
            str_repeat('ب', 80).'.',
            str_repeat('ت', 80).'.',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('requires 3 ElevenLabs segments');

        try {
            app(ElevenLabsTextToSpeech::class)->synthesize($text, 'ar');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_it_reports_a_sanitized_payment_error_without_retrying(): void
    {
        $requests = 0;

        Http::preventStrayRequests();
        Http::fake(function () use (&$requests) {
            $requests++;

            return Http::response([
                'detail' => [
                    'type' => 'payment_required',
                    'code' => 'insufficient_credits',
                    'message' => 'Private provider detail containing sensitive article text.',
                    'request_id' => 'request-payment-402',
                ],
            ], 402);
        });

        try {
            app(ElevenLabsTextToSpeech::class)->synthesize('نص صالح للتوليد.', 'ar');
            $this->fail('The payment failure should throw an exception.');
        } catch (ElevenLabsRequestException $exception) {
            $this->assertSame(402, $exception->httpStatus);
            $this->assertSame('insufficient_credits', $exception->providerCode);
            $this->assertSame('request-payment-402', $exception->providerRequestId);
            $this->assertStringContainsString('credit quota', $exception->getMessage());
            $this->assertStringContainsString('request-payment-402', $exception->getMessage());
            $this->assertStringNotContainsString('sensitive article text', $exception->getMessage());
        }

        $this->assertSame(1, $requests);
    }

    public function test_it_does_not_retry_a_provider_failure_for_a_paid_generation(): void
    {
        $requests = 0;

        Http::preventStrayRequests();
        Http::fake(function () use (&$requests) {
            $requests++;

            return Http::response([
                'detail' => [
                    'code' => 'provider_unavailable',
                    'request_id' => 'request-unavailable-503',
                ],
            ], 503);
        });

        try {
            app(ElevenLabsTextToSpeech::class)->synthesize('نص صالح للتوليد.', 'ar');
            $this->fail('The provider failure should throw an exception.');
        } catch (ElevenLabsRequestException $exception) {
            $this->assertSame(503, $exception->httpStatus);
        }

        $this->assertSame(1, $requests);
    }

    public function test_it_does_not_retry_an_ambiguous_generation_timeout(): void
    {
        Http::preventStrayRequests();
        Http::fake(Http::failedConnection(
            'cURL error 28: Operation timed out after 420001 milliseconds with 0 bytes received',
        ));

        try {
            app(ElevenLabsTextToSpeech::class)->synthesize('نص صالح للتوليد.', 'ar');
            $this->fail('The timed-out generation should throw a connection exception.');
        } catch (ConnectionException $exception) {
            $this->assertStringContainsString('cURL error 28', $exception->getMessage());
        }

        Http::assertSentCount(1);
    }

    public function test_it_resumes_from_persisted_segments_after_a_later_segment_times_out(): void
    {
        config()->set('services.elevenlabs.ffmpeg_binary', '');
        $firstSegment = "\xFF\xFB".str_repeat('A', 250);
        $secondSegment = "\xFF\xFB".str_repeat('B', 250);
        $thirdSegment = "\xFF\xFB".str_repeat('C', 250);
        $text = implode("\n\n", [
            str_repeat('أ', 80).'.',
            str_repeat('ب', 80).'.',
            str_repeat('ت', 80).'.',
        ]);

        $providerRequests = 0;

        Http::preventStrayRequests();
        Http::fake(function () use (&$providerRequests, $firstSegment, $secondSegment, $thirdSegment) {
            $providerRequests++;

            if ($providerRequests === 2) {
                return Http::failedConnection('The second segment timed out.');
            }

            $audio = match ($providerRequests) {
                1 => $firstSegment,
                3 => $secondSegment,
                default => $thirdSegment,
            };

            return Http::response($audio, 200, [
                'Content-Type' => 'audio/mpeg',
                'request-id' => 'request-'.($providerRequests === 1 ? 1 : $providerRequests - 1),
            ]);
        });

        try {
            app(ElevenLabsTextToSpeech::class)->synthesize($text, 'ar');
            $this->fail('The interrupted generation should throw a connection exception.');
        } catch (ConnectionException) {
            $this->assertCount(2, Storage::disk('local')->allFiles('article-audio/checkpoints'));
        }

        $result = app(ElevenLabsTextToSpeech::class)->synthesize($text, 'ar');

        $this->assertSame(4, $providerRequests);
        $this->assertSame($firstSegment.$secondSegment.$thirdSegment, $result->audio);
        $this->assertSame(['request-1', 'request-2', 'request-3'], $result->requestIds);
        $this->assertSame(3, $result->segmentCount);
        $this->assertCount(6, Storage::disk('local')->allFiles('article-audio/checkpoints'));

        app(ElevenLabsTextToSpeech::class)->forgetCheckpoint($result->checkpointKey);

        $this->assertSame([], Storage::disk('local')->allFiles('article-audio/checkpoints'));
    }

    public function test_it_reports_a_key_quota_error_as_quota_instead_of_invalid_credentials(): void
    {
        Http::preventStrayRequests();
        Http::fake(fn () => Http::response([
            'detail' => [
                'type' => 'authentication_error',
                'code' => 'quota_exceeded',
                'message' => 'Provider detail that must not be exposed.',
                'request_id' => 'request-quota-401',
            ],
        ], 401));

        try {
            app(ElevenLabsTextToSpeech::class)->synthesize('نص صالح للتوليد.', 'ar');
            $this->fail('The quota failure should throw an exception.');
        } catch (ElevenLabsRequestException $exception) {
            $this->assertSame(401, $exception->httpStatus);
            $this->assertSame('quota_exceeded', $exception->providerCode);
            $this->assertStringContainsString('credit quota', $exception->getMessage());
            $this->assertStringNotContainsString('rejected the API key', $exception->getMessage());
            $this->assertStringNotContainsString('must not be exposed', $exception->getMessage());
        }
    }

    public function test_mp3_concatenation_keeps_only_the_first_id3_header(): void
    {
        $id3 = 'ID3'."\x04\x00\x00\x00\x00\x00\x04".'META';
        $firstFrames = "\xFF\xFB".str_repeat('A', 40);
        $secondFrames = "\xFF\xFB".str_repeat('B', 40);

        $combined = (new Mp3Concatenator)->concatenate([
            $id3.$firstFrames,
            $id3.$secondFrames,
        ]);

        $this->assertSame($id3.$firstFrames.$secondFrames, $combined);
        $this->assertSame(1, substr_count($combined, 'ID3'));
    }

    public function test_mp3_concatenation_explicitly_selects_the_mp3_output_format_for_ffmpeg(): void
    {
        $binary = tempnam(sys_get_temp_dir(), 'fake-ffmpeg-');

        $this->assertNotFalse($binary);

        file_put_contents($binary, <<<'PHP'
#!/usr/bin/env php
<?php

$arguments = array_slice($argv, 1);
$inputFlag = array_search('-i', $arguments, true);
$formatFlag = array_search('-f', $arguments, true);
$output = $arguments[array_key_last($arguments)] ?? null;

if ($inputFlag === false || $formatFlag === false || ($arguments[$formatFlag + 1] ?? null) !== 'mp3' || ! is_string($output)) {
    exit(2);
}

file_put_contents($output, 'normalized:'.file_get_contents($arguments[$inputFlag + 1]));
PHP);
        chmod($binary, 0755);
        config()->set('services.elevenlabs.ffmpeg_binary', $binary);

        try {
            $combined = (new Mp3Concatenator)->concatenate(['first-segment', 'second-segment']);

            $this->assertSame('normalized:first-segmentsecond-segment', $combined);
        } finally {
            @unlink($binary);
        }
    }
}
