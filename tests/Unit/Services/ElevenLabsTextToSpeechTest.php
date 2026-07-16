<?php

namespace Tests\Unit\Services;

use App\Exceptions\ElevenLabsRequestException;
use App\Services\ArticleAudio\Mp3Concatenator;
use App\Services\ElevenLabs\ElevenLabsTextToSpeech;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use RuntimeException;
use Tests\TestCase;

class ElevenLabsTextToSpeechTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.elevenlabs', [
            'api_key' => 'server-secret',
            'base_url' => 'https://api.elevenlabs.test/v1',
            'voice_id' => 'calm-arabic-voice',
            'model_id' => 'eleven_multilingual_v2',
            'output_format' => 'mp3_44100_128',
            'max_characters' => 100,
            'context_characters' => 30,
            'timeout' => 10,
            'connect_timeout' => 2,
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
            '/text-to-speech/calm-arabic-voice?output_format=mp3_44100_128',
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

    public function test_it_uses_the_same_configured_voice_for_english(): void
    {
        $request = null;
        $segment = "\xFF\xFB".str_repeat('A', 250);

        Http::preventStrayRequests();
        Http::fake(function (Request $sent) use (&$request, $segment) {
            $request = $sent;

            return Http::response($segment, 200, ['Content-Type' => 'audio/mpeg']);
        });

        app(ElevenLabsTextToSpeech::class)->synthesize('An American English article.', 'en');

        $this->assertNotNull($request);
        $this->assertStringContainsString(
            '/text-to-speech/calm-arabic-voice?output_format=mp3_44100_128',
            $request->url(),
        );
        $this->assertSame('eleven_multilingual_v2', $request['model_id']);
        $this->assertArrayNotHasKey('language_code', $request->data());
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

    public function test_it_retries_transient_provider_failures_with_the_shared_backoff(): void
    {
        $requests = 0;
        $segment = "\xFF\xFB".str_repeat('R', 250);
        Sleep::fake();

        try {
            Http::preventStrayRequests();
            Http::fake(function () use (&$requests, $segment) {
                $requests++;

                return $requests < 3
                    ? Http::response(['detail' => ['code' => 'provider_unavailable']], 503)
                    : Http::response($segment, 200, ['Content-Type' => 'audio/mpeg']);
            });

            $result = app(ElevenLabsTextToSpeech::class)->synthesize('نص صالح للتوليد.', 'ar');

            $this->assertSame(3, $requests);
            $this->assertSame($segment, $result->audio);
            Sleep::assertSequence([
                Sleep::for(1200)->milliseconds(),
                Sleep::for(3000)->milliseconds(),
            ]);
        } finally {
            Sleep::fake(false);
        }
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
}
