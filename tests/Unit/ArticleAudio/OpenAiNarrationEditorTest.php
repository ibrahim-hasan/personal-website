<?php

namespace Tests\Unit\ArticleAudio;

use App\Exceptions\OpenAiNarrationException;
use App\Services\OpenAI\OpenAiNarrationEditor;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiNarrationEditorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.openai', [
            'api_key' => 'openai-server-secret',
            'base_url' => 'https://api.openai.test/v1',
            'narration_model' => 'gpt-5.6-terra',
            'narration_max_output_tokens' => 20000,
            'timeout' => 30,
            'connect_timeout' => 2,
        ]);
    }

    public function test_it_prepares_a_structured_reviewable_narration_through_responses_api(): void
    {
        $source = str_repeat('هذه جملة عربية تحافظ على الحقائق وترتيب المقال. ', 15);
        $script = str_replace('المقال.', 'المقال. [short pause]', $source);

        Http::preventStrayRequests();
        Http::fake([
            'api.openai.test/v1/responses' => Http::response([
                'output' => [
                    ['type' => 'reasoning', 'content' => []],
                    [
                        'type' => 'message',
                        'content' => [[
                            'type' => 'output_text',
                            'text' => json_encode([
                                'script' => $script,
                                'notes' => ['Split long sentences.'],
                                'pronunciation_notes' => ['اقرأ AI: الذكاء الاصطناعي.'],
                            ], JSON_UNESCAPED_UNICODE),
                        ]],
                    ],
                ],
            ], 200, ['x-request-id' => 'openai-request-1']),
        ]);

        $draft = app(OpenAiNarrationEditor::class)->prepare($source, 'ar');

        $this->assertSame(trim($script), $draft->script);
        $this->assertSame(['Split long sentences.'], $draft->notes);
        $this->assertSame(['اقرأ AI: الذكاء الاصطناعي.'], $draft->pronunciationNotes);
        $this->assertSame('gpt-5.6-terra', $draft->model);
        $this->assertSame('arabic-editorial-v1', $draft->promptVersion);

        Http::assertSent(function (Request $request) use ($source): bool {
            return $request->url() === 'https://api.openai.test/v1/responses'
                && $request->hasHeader('Authorization', 'Bearer openai-server-secret')
                && $request['store'] === false
                && $request['text']['format']['type'] === 'json_schema'
                && $request['text']['format']['strict'] === true
                && str_contains((string) $request['input'], $source);
        });
    }

    public function test_provider_errors_are_sanitized_and_do_not_expose_article_text(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'api.openai.test/v1/responses' => Http::response([
                'error' => ['message' => 'Sensitive article content appeared in a provider error.'],
            ], 401, ['x-request-id' => 'openai-request-denied']),
        ]);

        try {
            app(OpenAiNarrationEditor::class)->prepare(str_repeat('نص حساس. ', 30), 'ar');
            $this->fail('The provider failure should throw.');
        } catch (OpenAiNarrationException $exception) {
            $this->assertSame(401, $exception->httpStatus);
            $this->assertSame('openai-request-denied', $exception->providerRequestId);
            $this->assertStringNotContainsString('Sensitive article content', $exception->getMessage());
            $this->assertStringNotContainsString('openai-server-secret', $exception->getMessage());
        }
    }
}
