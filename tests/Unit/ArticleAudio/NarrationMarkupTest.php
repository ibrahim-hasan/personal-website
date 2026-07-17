<?php

namespace Tests\Unit\ArticleAudio;

use App\Services\ArticleAudio\NarrationDraftValidator;
use App\Services\ArticleAudio\NarrationMarkup;
use App\Services\ArticleAudio\NarrationSampleExcerpt;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class NarrationMarkupTest extends TestCase
{
    public function test_v3_keeps_expressive_tags_while_v2_uses_supported_break_markup(): void
    {
        $script = '[thoughtful] بداية هادئة. [short pause] ثم فكرة ثانية. [exhales] [long pause] وأخيراً الخلاصة.';
        $markup = new NarrationMarkup;

        $v3 = $markup->forModel($script, 'eleven_v3');
        $v2 = $markup->forModel($script, 'eleven_multilingual_v2');

        $this->assertSame($script, $v3);
        $this->assertStringNotContainsString('[thoughtful]', $v2);
        $this->assertStringNotContainsString('[short pause]', $v2);
        $this->assertStringNotContainsString('[exhales]', $v2);
        $this->assertStringContainsString('<break time="0.7s" />', $v2);
        $this->assertStringContainsString('<break time="1.4s" />', $v2);
    }

    public function test_sample_excerpt_prefers_a_complete_sentence_boundary(): void
    {
        $script = str_repeat('جملة قصيرة مكتملة. ', 30).'نهاية بعيدة.';
        $excerpt = (new NarrationSampleExcerpt)->make($script, 220);

        $this->assertLessThanOrEqual(220, mb_strlen($excerpt));
        $this->assertStringEndsWith('.', $excerpt);
    }

    public function test_validator_rejects_unknown_tags_and_substantial_summarization(): void
    {
        $validator = new NarrationDraftValidator;
        $source = str_repeat('هذه حقيقة يجب الحفاظ عليها. ', 30);

        try {
            $validator->validate($source.' [whisper]', $source);
            $this->fail('Unknown audio tags must be rejected.');
        } catch (UnexpectedValueException $exception) {
            $this->assertStringContainsString('unsupported audio tag', $exception->getMessage());
        }

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('source length');

        $validator->validate('مُلَخَّصٌ قَصِيرٌ جِدًّا.', $source);
    }

    public function test_validator_requires_pronunciation_focused_diacritics_for_arabic_only(): void
    {
        $validator = new NarrationDraftValidator;
        $source = str_repeat('هذه جملة عربية تحافظ على المعنى الأصلي. ', 20);

        $validator->validateGenerated(
            str_repeat('هَذِهِ جُمْلَةٌ عَرَبِيَّةٌ تُحَافِظُ عَلَى المَعْنَى الأَصْلِيِّ. ', 20),
            $source,
            'ar',
        );

        $validator->validateGenerated(
            'هَذَا نَصٌّ عَرَبِيٌّ وَاضِحٌ عَنْ OpenAI وَ ElevenLabs.',
            'هذا نص عربي واضح عن OpenAI وElevenLabs.',
            'ar',
        );

        $validator->validateGenerated(
            str_repeat('This English sentence preserves the original meaning. ', 20),
            str_repeat('This English sentence preserves the original meaning. ', 20),
            'en',
        );

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('pronunciation-focused diacritics');

        $validator->validateGenerated($source, $source, 'ar');
    }

    public function test_validator_rejects_a_non_arabic_generated_script_for_the_arabic_locale(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('not predominantly Arabic');

        (new NarrationDraftValidator)->validateGenerated(
            'This response is written entirely in English.',
            'هذه استجابة عربية قصيرة.',
            'ar',
        );
    }
}
