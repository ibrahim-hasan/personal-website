<?php

namespace Tests\Unit\Support;

use App\Services\ArticleAudio\ArticleNarrationScript;
use PHPUnit\Framework\TestCase;

class ArticleNarrationScriptTest extends TestCase
{
    public function test_it_builds_a_clean_structured_arabic_narration(): void
    {
        $script = (new ArticleNarrationScript)->fromLocalized([
            'title' => '<strong>عنوان المقال</strong>',
            'lead' => "مقدمة&nbsp;مفيدة\u{200B}",
            'sections' => [
                [
                    'heading' => 'القسم الأول',
                    'paragraphs' => [
                        '<p>فقرة <em>مهمة</em></p><script>سر غير مقروء</script>',
                    ],
                    'points' => ['نقطة عملية'],
                    'note' => '<b>تنبيه هادئ</b>',
                ],
            ],
            'closing' => 'الخلاصة',
        ], 'ar');

        $this->assertStringContainsString('عنوان المقال.', $script);
        $this->assertStringContainsString('مقدمة مفيدة.', $script);
        $this->assertStringContainsString('فقرة مهمة.', $script);
        $this->assertStringContainsString('نقطة عملية.', $script);
        $this->assertStringContainsString('ملاحظة. تنبيه هادئ.', $script);
        $this->assertStringEndsWith('الخلاصة.', $script);
        $this->assertStringNotContainsString('<', $script);
        $this->assertStringNotContainsString('سر غير مقروء', $script);
        $this->assertStringNotContainsString("\u{200B}", $script);
    }

    public function test_it_uses_an_english_note_cue_for_english_narration(): void
    {
        $script = (new ArticleNarrationScript)->fromLocalized([
            'title' => 'A useful title',
            'sections' => [[
                'heading' => 'A section',
                'paragraphs' => [],
                'note' => 'Listen carefully',
            ]],
        ], 'en');

        $this->assertStringContainsString('Note. Listen carefully.', $script);
    }
}
