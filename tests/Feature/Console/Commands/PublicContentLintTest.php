<?php

namespace Tests\Feature\Console\Commands;

use App\Support\ContentLint\PublicContentLinter;
use App\Support\ContentLint\PublicContentLintFinding;
use App\Support\ContentLint\PublicContentLintReport;
use Illuminate\Filesystem\Filesystem;
use Mockery\MockInterface;
use Tests\TestCase;

class PublicContentLintTest extends TestCase
{
    public function test_it_flags_banned_public_copy_and_unresolved_literal_translation_keys(): void
    {
        $report = $this->linter()->inspect(
            [
                'resources/views/website/example.blade.php' => <<<'BLADE'
                    <p>جلسة تشخيصية</p>
                    <p>بعد الفقرة الأخيرة</p>
                    <p>After the last paragraph</p>
                    <p>[[TODO]]</p>
                    <p>[Editorial note]</p>
                    {{ __('site.existing') }}
                    {{ __('site.missing') }}
                    BLADE,
            ],
            $this->translations(),
        );

        $rules = $this->rules($report);

        $this->assertContains('banned_sales_phrase', $rules);
        $this->assertContains('banned_discussion_label', $rules);
        $this->assertContains('placeholder_marker', $rules);
        $this->assertContains('editorial_annotation', $rules);
        $this->assertContains('unresolved_translation_key', $rules);
        $this->assertContains("Missing ar translation for 'site.missing'.", $this->messages($report));
        $this->assertContains("Missing en translation for 'site.missing'.", $this->messages($report));
    }

    public function test_it_lints_public_translation_values_without_treating_technical_identifiers_as_copy(): void
    {
        $report = $this->linter()->inspect(
            [
                'resources/views/website/example.blade.php' => <<<'BLADE'
                    {{ __('site.consultation.body') }}
                    {{ __('site.empty_section.heading') }}
                    {{ __('site.raw_key.label') }}
                    {{ __('site.role.label') }}
                    {{ __('community.kicker') }}
                    BLADE,
            ],
            [
                'ar' => [
                    'site' => [
                        'consultation' => ['body' => 'جلسة تشخيصية'],
                        'empty_section' => ['heading' => '   '],
                        'raw_key' => ['label' => 'site.footer.title'],
                        'role' => ['label' => 'Role: Founder'],
                        'media' => ['label' => 'مساحة الصورة تُبنى لاحقاً'],
                        'annotation' => ['label' => '[ملاحظة تحريرية]'],
                    ],
                    'community' => ['kicker' => 'بعد الفقرة الأخيرة'],
                ],
                'en' => [
                    'site' => [
                        'consultation' => ['body' => 'A focused consultation'],
                        'empty_section' => ['heading' => 'A clear heading'],
                        'raw_key' => ['label' => 'A useful label'],
                        'role' => ['label' => 'A clear label'],
                        'media' => ['label' => 'A prepared image'],
                        'annotation' => ['label' => 'A useful note'],
                    ],
                    'community' => ['kicker' => 'Discussion'],
                ],
            ],
        );

        $rules = $this->rules($report);

        $this->assertContains('banned_sales_phrase', $rules);
        $this->assertContains('banned_discussion_label', $rules);
        $this->assertContains('empty_semantic_section', $rules);
        $this->assertContains('unresolved_translation_key', $rules);
        $this->assertContains('english_ui_prefix', $rules);
        $this->assertContains('placeholder_marker', $rules);
        $this->assertContains('editorial_annotation', $rules);
    }

    public function test_it_does_not_ban_legitimate_editorial_or_legal_uses_of_the_diagnosis_root(): void
    {
        $report = $this->linter()->inspect(
            [
                'resources/views/website/example.blade.php' => "{{ __('site.editorial.note') }}",
            ],
            [
                'ar' => ['site' => ['editorial' => ['note' => 'يتناول النص تشخيص المشكلة في سياق تحريري.']]],
                'en' => ['site' => ['editorial' => ['note' => 'The text discusses diagnosis in an editorial context.']]],
            ],
        );

        $this->assertNotContains('banned_sales_phrase', $this->rules($report));
    }

    public function test_it_requires_every_arabic_plural_interval_for_public_choice_copy(): void
    {
        $report = $this->linter()->inspect(
            [
                'resources/views/website/example.blade.php' => "{{ trans_choice('site.work.result_count', \$count) }}",
            ],
            [
                'ar' => [
                    'site' => [
                        'work' => [
                            'result_count' => '{0} صفر|{1} واحد|{2} اثنان|[3,10] قليل|[11,*] كثير',
                        ],
                    ],
                ],
                'en' => [
                    'site' => [
                        'work' => [
                            'result_count' => '{0} none|{1} one|[2,*] many',
                        ],
                    ],
                ],
            ],
        );

        $messages = $this->messages($report);

        $this->assertContains("Arabic plural copy for 'site.work.result_count' is missing the many category.", $messages);
        $this->assertContains("Arabic plural copy for 'site.work.result_count' is missing the other category.", $messages);
    }

    public function test_it_accepts_complete_arabic_plural_intervals(): void
    {
        $report = $this->linter()->inspect(
            [
                'resources/views/website/example.blade.php' => "{{ trans_choice('site.work.result_count', \$count) }}",
            ],
            [
                'ar' => [
                    'site' => [
                        'work' => [
                            'result_count' => '{0} لا شيء|{1} واحد|{2} اثنان|[3,10] قليل|[11,99] كثير|[100,*] كثير جداً',
                        ],
                    ],
                ],
                'en' => [
                    'site' => [
                        'work' => [
                            'result_count' => '{0} none|{1} one|[2,*] many',
                        ],
                    ],
                ],
            ],
        );

        $this->assertFalse($report->hasFailures());
    }

    public function test_the_command_reports_a_clean_result_without_writing_content(): void
    {
        $this->mock(PublicContentLinter::class, function (MockInterface $mock): void {
            $mock->shouldReceive('inspectApplication')
                ->once()
                ->andReturn(new PublicContentLintReport([], 4, 7, 1));
        });

        $this->artisan('content:lint')
            ->expectsOutputToContain('Checked 4 scoped public sources, 7 literal translation keys, and 1 Arabic plural strings.')
            ->expectsOutputToContain('Public content lint passed.')
            ->assertExitCode(0);
    }

    private function linter(): PublicContentLinter
    {
        return new PublicContentLinter(app(Filesystem::class));
    }

    /**
     * @return array{ar: array<string, mixed>, en: array<string, mixed>}
     */
    private function translations(): array
    {
        return [
            'ar' => ['site' => ['existing' => 'نص واضح']],
            'en' => ['site' => ['existing' => 'Clear copy']],
        ];
    }

    /**
     * @return list<string>
     */
    private function rules(PublicContentLintReport $report): array
    {
        return array_map(
            fn (PublicContentLintFinding $finding): string => $finding->rule,
            $report->findings,
        );
    }

    /**
     * @return list<string>
     */
    private function messages(PublicContentLintReport $report): array
    {
        return array_map(
            fn (PublicContentLintFinding $finding): string => $finding->message,
            $report->findings,
        );
    }
}
