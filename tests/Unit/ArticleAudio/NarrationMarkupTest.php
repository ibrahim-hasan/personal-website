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

    public function test_validator_rejects_every_change_except_arabic_diacritics_and_approved_audio_tags(): void
    {
        $validator = new NarrationDraftValidator;
        $source = str_repeat('هذه حقيقة يجب الحفاظ عليها. ', 30);

        try {
            $validator->validate(str_replace('الحفاظ', 'تغيير', $source), $source);
            $this->fail('Changed source text must be rejected.');
        } catch (UnexpectedValueException $exception) {
            $this->assertStringContainsString('only add Arabic diacritics', $exception->getMessage());
        }

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('only add Arabic diacritics');

        $validator->validate('ملخص قصير جداً.', $source);
    }

    public function test_validator_allows_arabic_without_full_vocalization(): void
    {
        $validator = new NarrationDraftValidator;
        $source = str_repeat('هذه جملة عربية تحافظ على المعنى الأصلي. ', 20);

        $validator->validateGenerated(
            str_repeat('هذه جملة عربية تحافظ على المعنى الأصلي. ', 20),
            $source,
            'ar',
        );

        $validator->validateGenerated(
            'هٰذِهِ جُمْلَةٌ عَرَبِيَّةٌ تُحافِظُ عَلَى المَعْنَى الأَصْلِيِّ.',
            'هذه جملة عربية تحافظ على المعنى الأصلي.',
            'ar',
        );

        $validator->validateGenerated(
            'هذا نص عربي واضح عن OpenAI وElevenLabs.',
            'هذا نص عربي واضح عن OpenAI وElevenLabs.',
            'ar',
        );

        $validator->validateGenerated(
            str_repeat('This English sentence preserves the original meaning. ', 20),
            str_repeat('This English sentence preserves the original meaning. ', 20),
            'en',
        );

        $this->addToAssertionCount(1);

        $validator->validateGenerated(
            '[thoughtful] هذه جملة عربية. [short pause] وهذه جملة ثانية. [long pause] الخلاصة. [exhales]',
            'هذه جملة عربية. وهذه جملة ثانية. الخلاصة.',
            'ar',
        );
    }

    public function test_validator_allows_browser_line_endings_without_relaxing_the_source_contract(): void
    {
        $validator = new NarrationDraftValidator;
        $source = "عنوان المقال؟\n\nهذه فقرة عربية تحافظ على النص الأصلي.\n\nوهذه فقرة ثانية.";

        $validator->validate(
            str_replace("\n", "\r\n", $source),
            $source,
        );

        $this->addToAssertionCount(1);
    }

    public function test_validator_allows_an_approved_audio_tag_on_its_own_paragraph_line(): void
    {
        (new NarrationDraftValidator)->validate(
            "الفكرة الأولى.\n\n[short pause]\n\nالفكرة الثانية.",
            "الفكرة الأولى.\n\nالفكرة الثانية.",
        );

        $this->addToAssertionCount(1);
    }

    public function test_validator_rejects_an_approved_audio_tag_that_changes_paragraph_spacing(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('only add Arabic diacritics');

        (new NarrationDraftValidator)->validate(
            "الفكرة الأولى.\n[short pause]\n\nالفكرة الثانية.",
            "الفكرة الأولى.\n\nالفكرة الثانية.",
        );
    }

    public function test_validator_rejects_an_approved_tag_inside_a_word(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('inside a word or name');

        (new NarrationDraftValidator)->validate(
            'هذه[short pause]جملة عربية.',
            'هذه جملة عربية.',
        );
    }

    public function test_validator_rejects_unapproved_audio_tags(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('unsupported audio tag');

        (new NarrationDraftValidator)->validate(
            '[laughs] هذه جملة عربية.',
            'هذه جملة عربية.',
        );
    }

    public function test_validator_rejects_a_non_arabic_generated_script_for_the_arabic_locale(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('only add Arabic diacritics');

        (new NarrationDraftValidator)->validateGenerated(
            'This response is written entirely in English.',
            'هذه استجابة عربية قصيرة.',
            'ar',
        );
    }
}
