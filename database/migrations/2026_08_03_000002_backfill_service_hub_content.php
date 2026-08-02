<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('services')
            || ! Schema::hasColumn('services', 'fit_signals')
            || ! Schema::hasColumn('services', 'engagement_note')) {
            return;
        }

        $defaults = [
            'transformation' => [
                'fit_signals' => [
                    'ar' => [
                        'تتوزّع المبادرات الرقمية بين أدوات وقرارات من دون ترتيب واضح للأولويات.',
                        'تُشترى الأدوات قبل فهم العملية والمشكلة التي يُفترض أن تعالجها.',
                        'يصعب قياس نتائج الحلول الحالية أو صيانتها.',
                    ],
                    'en' => [
                        'Several digital initiatives are moving independently, without a clear impact-based priority.',
                        'Technology choices are being made before the underlying process is understood.',
                        'Current solutions are difficult to measure or maintain.',
                    ],
                ],
                'engagement_note' => [
                    'ar' => 'أبدأ بفهم العملية والقرار والمخاطر، ثم أحدد أين تضيف الرقمنة قيمة فعلية قبل اقتراح أي تقنية.',
                    'en' => 'I begin with the process, the decision, and the risks, then identify where digitization creates real value before proposing technology.',
                ],
            ],
            'ai-adoption' => [
                'fit_signals' => [
                    'ar' => [
                        'تبدو تجربة الذكاء الاصطناعي مقنعة في العرض، لكن دقتها أو سلامتها لا تكفي للاعتماد التشغيلي.',
                        'لا تستند المخرجات إلى معرفة مؤسسية معتمدة يمكن إسنادها إلى مصادرها.',
                        'لا توجد طريقة واضحة لتقييم المخرجات أو تحديد مواضع المراجعة البشرية.',
                    ],
                    'en' => [
                        'The AI demo is persuasive, but its accuracy or safety is not dependable enough for operational use.',
                        'Answers are not grounded in approved organizational knowledge or attributable sources.',
                        'There is no clear loop for evaluating outputs and deciding where human review is required.',
                    ],
                ],
                'engagement_note' => [
                    'ar' => 'أبدأ بتحديد المعرفة المؤسسية المعتمدة ومواضع المراجعة البشرية، ثم أبني حلقة واضحة لتقييم المخرجات وضبط المخاطر.',
                    'en' => 'I begin with the approved knowledge and required points of human review, then build the evaluation and risk controls around them.',
                ],
            ],
            'data-governance' => [
                'fit_signals' => [
                    'ar' => [
                        'تتوزع البيانات بين مصادر لا يظهر بينها تدفق واضح.',
                        'ملكية البيانات غامضة، والصلاحيات غير محددة بما يكفي.',
                        'جودة البيانات وأسس الخصوصية لا تكفيان لاتخاذ قرار موثوق أو بدء مبادرة ذكاء اصطناعي.',
                    ],
                    'en' => [
                        'Data is scattered across sources, with no clear view of its flow.',
                        'Ownership is ambiguous and permissions are not clearly defined.',
                        'Quality and privacy foundations are not yet sufficient for reliable decisions or AI adoption.',
                    ],
                ],
                'engagement_note' => [
                    'ar' => 'أبدأ برسم تدفق البيانات وتحديد الملكية والصلاحيات، ثم أضع أسس الجودة والخصوصية قبل أي مبادرة ذكاء اصطناعي.',
                    'en' => 'I begin by mapping data flows and clarifying ownership and permissions, then establish the quality and privacy foundations needed before an AI initiative.',
                ],
            ],
            'systems' => [
                'fit_signals' => [
                    'ar' => [
                        'يتوزع العمل بين أدوات وجداول ورسائل لا يجمعها مسار تشغيلي واضح.',
                        'تعتمد التدفقات على تنسيق يدوي وهش يصعب شرحه أو تطويره.',
                        'يصعب قياس المسار من الطلب إلى التسليم أو صيانته.',
                    ],
                    'en' => [
                        'Work is spread across tools, spreadsheets, and messages without a clear operating flow.',
                        'Fragile workflows depend on manual coordination and are difficult to explain or evolve.',
                        'The path from request to delivery is difficult to measure or maintain.',
                    ],
                ],
                'engagement_note' => [
                    'ar' => 'أبدأ من العمل التشغيلي المتناثر، وأحدد أين يحتاج إلى نظام أو ربط أو أتمتة ليصبح أوضح وقابلاً للقياس والصيانة.',
                    'en' => 'I begin with the scattered operational work and identify where a system, integration, or automation can make the flow clearer, measurable, and maintainable.',
                ],
            ],
        ];

        DB::table('services')
            ->select(['id', 'key', 'fit_signals', 'engagement_note'])
            ->orderBy('id')
            ->each(function (object $service) use ($defaults): void {
                $default = $defaults[$service->key] ?? null;

                if (! is_array($default)) {
                    return;
                }

                $updates = [];

                if ($this->isBlankTranslations($service->fit_signals)) {
                    $updates['fit_signals'] = json_encode($default['fit_signals'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                }

                if ($this->isBlankTranslations($service->engagement_note)) {
                    $updates['engagement_note'] = json_encode($default['engagement_note'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                }

                if ($updates !== []) {
                    DB::table('services')->where('id', $service->id)->update($updates);
                }
            });
    }

    public function down(): void {}

    private function isBlankTranslations(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_array($value)) {
            return $value === [];
        }

        if (! is_string($value) || trim($value) === '') {
            return true;
        }

        try {
            $decoded = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return false;
        }

        return ! is_array($decoded) || $decoded === [];
    }
};
