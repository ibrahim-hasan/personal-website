<?php

namespace Database\Seeders;

use App\Enums\IntellectualLibraryType;
use App\Models\Author;
use App\Models\Guide;
use App\Models\GuideDownloader;
use App\Models\IntellectualLibrary;
use App\Models\Newsletter;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Tags\Tag;

class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSettings();
        $authors = $this->seedAuthors();
        $this->seedServices();
        $guides = $this->seedGuides();
        $this->seedIntellectualLibrary($authors);
        $this->seedGuideDownloaders($guides);
        $this->seedNewsletters();
    }

    protected function seedSettings(): void
    {
        $settings = [
            ['group' => 'site', 'key' => 'site_name_ar', 'label' => 'Site Name (AR)', 'value' => 'إبراهيم حسن', 'type' => 'text'],
            ['group' => 'site', 'key' => 'site_name_en', 'label' => 'Site Name (EN)', 'value' => 'Ibrahim Hasan', 'type' => 'text'],
            ['group' => 'site', 'key' => 'site_description_ar', 'label' => 'Site Description (AR)', 'value' => 'استوديو هندسي عملي لبناء منتجات الذكاء الاصطناعي ولوحات الإدارة ومسارات التشغيل.', 'type' => 'textarea'],
            ['group' => 'site', 'key' => 'site_description_en', 'label' => 'Site Description (EN)', 'value' => 'A practical engineering studio for AI products, admin systems, automation, and production recovery.', 'type' => 'textarea'],
            ['group' => 'contact', 'key' => 'contact_address', 'label' => 'Contact Address', 'value' => 'Istanbul / remote', 'type' => 'text'],
            ['group' => 'contact', 'key' => 'address_url', 'label' => 'Address URL', 'value' => 'https://maps.google.com/?q=Istanbul', 'type' => 'text'],
            ['group' => 'contact', 'key' => 'contact_phone', 'label' => 'Contact Phone', 'value' => '', 'type' => 'text'],
            ['group' => 'contact', 'key' => 'contact_email', 'label' => 'Contact Email', 'value' => 'hello@ibrahimhasan.net', 'type' => 'text'],
            ['group' => 'social', 'key' => 'social_facebook', 'label' => 'Social Facebook', 'value' => '', 'type' => 'text'],
            ['group' => 'social', 'key' => 'social_instagram', 'label' => 'Social Instagram', 'value' => '', 'type' => 'text'],
            ['group' => 'social', 'key' => 'social_linkedin', 'label' => 'Social LinkedIn', 'value' => 'https://sa.linkedin.com/in/i-hasan', 'type' => 'text'],
            ['group' => 'social', 'key' => 'social_twitter', 'label' => 'Social X', 'value' => '', 'type' => 'text'],
            ['group' => 'social', 'key' => 'social_youtube', 'label' => 'Social YouTube', 'value' => '', 'type' => 'text'],
            ['group' => 'stats', 'key' => 'home_stats_total_clients', 'label' => 'Homepage Total Clients', 'value' => '180+', 'type' => 'text'],
            ['group' => 'stats', 'key' => 'home_stats_total_consultations', 'label' => 'Homepage Total Consultations', 'value' => '1,250+', 'type' => 'text'],
            ['group' => 'stats', 'key' => 'home_stats_training_hours', 'label' => 'Training Hours', 'value' => '7,400+', 'type' => 'text'],
            ['group' => 'stats', 'key' => 'home_stats_corporate_experience', 'label' => 'Corporate Experience', 'value' => '22 years', 'type' => 'text'],
            ['group' => 'stats', 'key' => 'home_stats_government_private_years', 'label' => 'Government And Private Institution Years', 'value' => '16+', 'type' => 'text'],
            ['group' => 'stats', 'key' => 'home_stats_trainees', 'label' => 'Verified Workflows', 'value' => '120+', 'type' => 'text'],
            ['group' => 'guide', 'key' => 'guide_download_file_reference', 'label' => 'Guide Download File Reference', 'value' => 'guides/ibrahim-product-systems-checklist.pdf', 'type' => 'file'],
            ['group' => 'seo', 'key' => 'default_seo_title', 'label' => 'Default SEO Title', 'value' => json_encode(['ar' => 'إبراهيم حسن | هندسة منتجات الذكاء الاصطناعي', 'en' => 'Ibrahim Hasan | AI Product Engineering'], JSON_UNESCAPED_UNICODE), 'type' => 'json'],
            ['group' => 'seo', 'key' => 'default_seo_description', 'label' => 'Default SEO Description', 'value' => json_encode(['ar' => 'بناء وإصلاح منصات Laravel وDjango ومساعدات الذكاء الاصطناعي ومسارات التشغيل.', 'en' => 'Building and repairing Laravel, Django, AI assistant, automation, and production systems.'], JSON_UNESCAPED_UNICODE), 'type' => 'json'],
            ['group' => 'home', 'key' => 'home_layers', 'label' => 'Home Layers', 'value' => json_encode($this->homeLayers(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'type' => 'textarea'],
        ];

        foreach ($settings as $index => $setting) {
            Setting::updateOrCreate(
                ['group' => $setting['group'], 'key' => $setting['key']],
                [
                    'label' => $setting['label'],
                    'group' => $setting['group'],
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'order' => $index + 1,
                ],
            );
        }
    }

    /**
     * @return array<int, array{title: array{ar: string, en: string}, description: array{ar: string, en: string}}>
     */
    protected function homeLayers(): array
    {
        return [
            [
                'title' => ['ar' => 'طبقة الوضوح الاستراتيجي', 'en' => 'Strategic Clarity Layer'],
                'description' => ['ar' => 'تحويل الرؤية إلى أولويات تنفيذية مفهومة لكل فريق وصاحب قرار.', 'en' => 'Translate vision into execution priorities that every team and decision-maker can use.'],
            ],
            [
                'title' => ['ar' => 'طبقة التشغيل العملي', 'en' => 'Practical Operations Layer'],
                'description' => ['ar' => 'ربط قرارات المنتج بمسارات واضحة ومؤشرات يمكن مراجعتها دون ضجيج.', 'en' => 'Connect product decisions to clear workflows and metrics that can be reviewed without noise.'],
            ],
            [
                'title' => ['ar' => 'طبقة تمكين الفرق', 'en' => 'Team Enablement Layer'],
                'description' => ['ar' => 'تصميم أدوات متابعة ومسؤوليات واضحة تساعد الفرق على الإنجاز دون تعقيد.', 'en' => 'Design practical tools and clear responsibilities that help teams deliver without friction.'],
            ],
            [
                'title' => ['ar' => 'طبقة قياس الأثر', 'en' => 'Impact Measurement Layer'],
                'description' => ['ar' => 'ربط المبادرات بنتائج قابلة للقياس حتى تصبح القيمة مرئية ومتكررة.', 'en' => 'Connect initiatives to measurable results so value becomes visible and repeatable.'],
            ],
        ];
    }

    /**
     * @return array<string, Author>
     */
    protected function seedAuthors(): array
    {
        $authors = [
            'ibrahim' => [
                'name' => ['ar' => 'إبراهيم حسن', 'en' => 'Ibrahim Hasan'],
                'position' => ['ar' => 'مهندس منتجات الذكاء الاصطناعي والمنصات', 'en' => 'AI Product And Platform Engineer'],
                'description' => [
                    'ar' => 'يبني ويصلح منصات Laravel وDjango ومساعدات الذكاء الاصطناعي ولوحات الإدارة ومسارات النشر.',
                    'en' => 'Builds and repairs Laravel, Django, AI assistant, admin, automation, and deployment systems.',
                ],
                'image' => 'ibrahim/ibrahim-hasan-portrait.png',
            ],
            'mariam' => [
                'name' => ['ar' => 'مريم الخطيب', 'en' => 'Mariam Alkhateeb'],
                'position' => ['ar' => 'باحثة في تشغيل المنتجات', 'en' => 'Product Operations Researcher'],
                'description' => [
                    'ar' => 'تركز على تحويل ملاحظات الدعم والعملاء إلى متطلبات ومواد تشغيلية قابلة للتنفيذ.',
                    'en' => 'Focuses on turning support and client feedback into actionable requirements and operating material.',
                ],
                'image' => 'about-video.png',
            ],
        ];

        return collect($authors)
            ->map(function (array $author): Author {
                $record = Author::updateOrCreate(
                    ['name->en' => $author['name']['en']],
                    [
                        'name' => $author['name'],
                        'position' => $author['position'],
                        'description' => $author['description'],
                        'is_draft' => false,
                        'is_active' => true,
                    ],
                );

                $this->attachMedia($record, 'avatar', $author['image']);

                return $record;
            })
            ->all();
    }

    protected function seedServices(): void
    {
        $services = [
            [
                'name' => ['ar' => 'موثوقية مساعدات الذكاء الاصطناعي', 'en' => 'AI Assistant Reliability'],
                'problems_you_are_facing' => ['ar' => 'المساعد يجيب بثقة لكنه يخطئ، يتأخر، أو لا يوضح مصادره.', 'en' => 'The assistant answers confidently but is wrong, slow, or unclear about its sources.'],
                'how_can_we_help' => ['ar' => 'أفحص الاسترجاع والأدلة والتدفق المرئي ثم أصلح الطبقة التي تسبب الخلل.', 'en' => 'I inspect retrieval, evidence, and visible flow, then fix the layer causing the failure.'],
                'type_of_intervention' => ['ar' => 'تتبع RAG، تحسين الاسترجاع، اختبار الواجهة، وتوثيق الحالات.', 'en' => 'RAG tracing, retrieval tuning, widget testing, and scenario documentation.'],
                'results' => ['ar' => 'إجابات أدق، مصادر أوضح، وزمن استجابة يمكن قياسه.', 'en' => 'More accurate answers, clearer sources, and measurable response time.'],
            ],
            [
                'name' => ['ar' => 'منصات Laravel وDjango', 'en' => 'Laravel And Django Platforms'],
                'problems_you_are_facing' => ['ar' => 'النظام يعمل جزئياً لكن العقود، الصلاحيات، أو النشر أصبحت صعبة التتبع.', 'en' => 'The system partially works, but contracts, permissions, or releases are hard to trace.'],
                'how_can_we_help' => ['ar' => 'أعيد ترتيب المسارات والنماذج والاختبارات حول سلوك واضح يمكن صيانته.', 'en' => 'I align routes, models, and tests around clear behavior that can be maintained.'],
                'type_of_intervention' => ['ar' => 'إصلاح جذري، بناء ميزات، مراجعة صلاحيات، وتنظيف تدفقات الإدارة.', 'en' => 'Root-cause fixes, feature work, permission reviews, and admin-flow cleanup.'],
                'results' => ['ar' => 'منصة أكثر وضوحاً للفريق وأقل هشاشة عند الإطلاق التالي.', 'en' => 'A clearer platform for the team and less fragility in the next release.'],
            ],
            [
                'name' => ['ar' => 'لوحات الإدارة والتشغيل', 'en' => 'Admin And Operations UX'],
                'problems_you_are_facing' => ['ar' => 'لوحة الإدارة موجودة لكنها بطيئة، مربكة، أو لا تناسب سير العمل اليومي.', 'en' => 'The admin panel exists, but it is slow, confusing, or mismatched to daily work.'],
                'how_can_we_help' => ['ar' => 'أعيد تصميم الموارد والنماذج والجداول حول المهام الحقيقية للمستخدمين.', 'en' => 'I redesign resources, forms, and tables around the real tasks users repeat.'],
                'type_of_intervention' => ['ar' => 'Filament، صلاحيات، ترجمات، فلاتر، إجراءات جماعية، وتبسيط الواجهات.', 'en' => 'Filament resources, permissions, translations, filters, bulk actions, and interface simplification.'],
                'results' => ['ar' => 'إدارة أسرع، أخطاء أقل، وتجربة واضحة للفرق غير التقنية.', 'en' => 'Faster administration, fewer mistakes, and a clearer experience for non-technical teams.'],
            ],
            [
                'name' => ['ar' => 'التشخيص واستعادة الإنتاج', 'en' => 'Production Diagnosis And Recovery'],
                'problems_you_are_facing' => ['ar' => 'العطل ظهر في الإنتاج ولا يكفي أن يكون الاختبار المحلي أخضر.', 'en' => 'The failure appeared in production, and a green local test is not enough.'],
                'how_can_we_help' => ['ar' => 'أربط السجلات، المتصفح، النشر، وبيانات المستخدم للوصول إلى السبب الحقيقي.', 'en' => 'I connect logs, browser behavior, deployment state, and user data to find the real cause.'],
                'type_of_intervention' => ['ar' => 'تشخيص حذر، إصلاح، بناء اختبارات، والتحقق من المسار الحي.', 'en' => 'Careful diagnosis, repair, test coverage, and live-path verification.'],
                'results' => ['ar' => 'استعادة موثوقة مع تفسير واضح لما حدث وكيف تم منعه.', 'en' => 'Reliable recovery with a clear explanation of what happened and how it was prevented.'],
            ],
        ];

        foreach ($services as $index => $service) {
            Service::updateOrCreate(
                ['name->en' => $service['name']['en']],
                [
                    ...$service,
                    'order' => $index + 1,
                    'is_draft' => false,
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @return Collection<int, Guide>
     */
    protected function seedGuides()
    {
        $guides = collect([
            [
                'title' => ['ar' => 'قائمة فحص موثوقية مساعد الذكاء الاصطناعي', 'en' => 'AI Assistant Reliability Checklist'],
                'description' => ['ar' => 'دليل عملي لاختبار الاسترجاع، الأدلة، زمن الاستجابة، وسلوك الواجهة قبل الإطلاق.', 'en' => 'A practical guide for testing retrieval, evidence, response time, and widget behavior before release.'],
                'image' => 'bg-cover-2.png',
            ],
            [
                'title' => ['ar' => 'قالب تشخيص أعطال الإنتاج', 'en' => 'Production Incident Diagnosis Template'],
                'description' => ['ar' => 'قالب يجمع السجلات، المسارات، لقطات المتصفح، وفرضيات السبب الجذري في مكان واحد.', 'en' => 'A template for collecting logs, paths, browser screenshots, and root-cause hypotheses in one place.'],
                'image' => 'ibrahim/product-systems.png',
            ],
            [
                'title' => ['ar' => 'خريطة تحسين لوحة الإدارة', 'en' => 'Admin Workflow Improvement Map'],
                'description' => ['ar' => 'أسئلة قصيرة لتحويل لوحة الإدارة من مجموعة حقول إلى سير عمل واضح للفريق.', 'en' => 'Short prompts for turning an admin panel from a set of fields into a clear team workflow.'],
                'image' => 'bg-cover-2.png',
            ],
        ])->map(function (array $guide, int $index): Guide {
            $record = Guide::updateOrCreate(
                ['title->en' => $guide['title']['en']],
                [
                    'title' => $guide['title'],
                    'description' => $guide['description'],
                    'sort_order' => $index + 1,
                    'is_draft' => false,
                    'is_active' => true,
                ],
            );

            $this->attachMedia($record, 'cover_image', $guide['image']);
            $this->attachPdf($record, 'guide_file', Str::slug($guide['title']['en']).'.pdf');

            return $record;
        });

        return $guides->values();
    }

    /**
     * @param  array<string, Author>  $authors
     */
    protected function seedIntellectualLibrary(array $authors): void
    {
        $items = [
            [IntellectualLibraryType::Article, 'How To Audit A RAG Answer', 'كيف تراجع إجابة RAG', 'ai', 11, 'ibrahim/rag-console.png'],
            [IntellectualLibraryType::Article, 'Debugging Production From The Browser Backward', 'تشخيص الإنتاج من المتصفح إلى الخلف', 'production', 8, 'bg-cover-2.png'],
            [IntellectualLibraryType::Article, 'From Admin Fields To Admin Workflows', 'من حقول الإدارة إلى سير العمل', 'admin', 6, 'ntellectual-biography-cover.jpg'],
            [IntellectualLibraryType::Video, 'What Makes An AI Widget Feel Slow', 'لماذا يشعر المستخدم أن ودجت الذكاء الاصطناعي بطيء', 'ai', 14, 'about-video.png'],
            [IntellectualLibraryType::Video, 'Five Checks Before Shipping A Laravel Admin Feature', 'خمس فحوصات قبل إطلاق ميزة Laravel إدارية', 'laravel', 19, 'ibrahim/product-systems.png'],
            [IntellectualLibraryType::Podcast, 'The Hidden Cost Of Partial Fixes', 'التكلفة الخفية للإصلاحات الجزئية', 'production', 31, 'bg-cover-2.png'],
            [IntellectualLibraryType::Podcast, 'What To Verify Before Calling A Fix Done', 'ما الذي يجب التحقق منه قبل اعتبار الإصلاح منتهياً', 'verification', 24, 'about-video.png'],
            [IntellectualLibraryType::Tool, 'RAG Evidence Review Checklist', 'قائمة مراجعة أدلة RAG', 'tool', 17, 'bg-cover-2.png'],
            [IntellectualLibraryType::Tool, 'Production Incident Notes Template', 'قالب ملاحظات أعطال الإنتاج', 'tool', 13, 'ibrahim/workflow-map.png'],
            [IntellectualLibraryType::Article, 'Designing Admin Filters People Actually Use', 'تصميم فلاتر إدارة يستخدمها الناس فعلاً', 'admin', 21, 'bg-cover-2.png'],
            [IntellectualLibraryType::Video, 'The Product Recovery Map', 'خريطة استعادة المنتج', 'production', 10, 'ntellectual-biography-cover.jpg'],
            [IntellectualLibraryType::Article, 'Turning Support Questions Into Product Signals', 'تحويل أسئلة الدعم إلى إشارات منتج', 'operations', 7, 'about-video.png'],
        ];

        foreach ($items as $index => [$type, $titleEn, $titleAr, $tag, $views, $image]) {
            $author = $index % 3 === 0 ? $authors['mariam'] : $authors['ibrahim'];
            $record = IntellectualLibrary::updateOrCreate(
                ['name->en' => $titleEn],
                [
                    'name' => ['ar' => $titleAr, 'en' => $titleEn],
                    'slug' => ['ar' => Str::slug($titleAr), 'en' => Str::slug($titleEn)],
                    'excert' => [
                        'ar' => "ملخص عملي يساعدك على تطبيق {$titleAr} داخل بيئة العمل اليومية.",
                        'en' => "A practical brief to help you apply {$titleEn} inside real team routines.",
                    ],
                    'content' => $this->libraryContent($titleAr, $titleEn, $type),
                    'type' => $type,
                    'author_id' => $author->id,
                    'reading_time' => $type === IntellectualLibraryType::Tool ? null : 5 + ($index % 7),
                    'video_length' => in_array($type, [IntellectualLibraryType::Video, IntellectualLibraryType::Podcast], true) ? sprintf('%02d:%02d', 12 + $index, 15 + $index) : null,
                    'youtube_url' => in_array($type, [IntellectualLibraryType::Video, IntellectualLibraryType::Podcast], true) ? 'https://www.youtube.com/watch?v=jD9VdUmdVVc' : null,
                    'seo_title' => ['ar' => $titleAr, 'en' => $titleEn],
                    'seo_description' => [
                        'ar' => "محتوى تطبيقي من موقع إبراهيم حسن حول {$titleAr}.",
                        'en' => "Applied Ibrahim Hasan site content about {$titleEn}.",
                    ],
                    'views' => $views,
                    'scheduled_at' => Carbon::now()->subDays(20 - $index),
                    'is_draft' => false,
                    'is_active' => true,
                ],
            );

            $record->syncTagIds([
                $this->tag(['ar' => 'مميز', 'en' => 'featured'])->id,
                $this->tag($this->typeTag($type))->id,
                $this->tag($this->topicTag($tag))->id,
            ], 'intellectual_library');

            $this->attachMedia($record, 'featured_image', $image);
            $this->attachMedia($record, 'cover_image', $image);
            $this->attachMedia($record, 'og_image', $image);

            if ($type === IntellectualLibraryType::Tool) {
                $this->attachPdf($record, 'tool_file', Str::slug($titleEn).'.pdf');
            }
        }
    }

    /**
     * @return array{ar: string, en: string}
     */
    protected function libraryContent(string $titleAr, string $titleEn, IntellectualLibraryType $type): array
    {
        $formatEn = match ($type) {
            IntellectualLibraryType::Article => 'article',
            IntellectualLibraryType::Video => 'video lesson',
            IntellectualLibraryType::Podcast => 'podcast conversation',
            IntellectualLibraryType::Tool => 'downloadable tool',
        };
        $formatAr = match ($type) {
            IntellectualLibraryType::Article => 'مقال',
            IntellectualLibraryType::Video => 'درس مرئي',
            IntellectualLibraryType::Podcast => 'حوار صوتي',
            IntellectualLibraryType::Tool => 'أداة قابلة للتحميل',
        };

        return [
            'en' => "<h2>{$titleEn}</h2><p>This {$formatEn} is written for product teams that need a real system to behave better, not just look better in a ticket. It starts with the visible symptom, then works back to code, data, and release state.</p><h3>What to apply this week</h3><ul><li>Capture the exact user-facing behavior before changing code.</li><li>Name the evidence, route, model, prompt, or deployment layer involved.</li><li>Verify the result in the same surface where the issue appeared.</li></ul><blockquote>A fix is complete when the product surface proves it.</blockquote><p>Use this demo content to test article pages, previews, SEO summaries, related content, reading time, and rich text spacing.</p>",
            'ar' => "<h2>{$titleAr}</h2><p>هذا {$formatAr} مخصص لفرق المنتجات التي تحتاج أن يتصرف النظام بشكل أفضل، لا أن يبدو التذكرة فقط أفضل. يبدأ من العرض الذي يراه المستخدم ثم يعود إلى الكود والبيانات وحالة النشر.</p><h3>ما الذي يمكن تطبيقه هذا الأسبوع؟</h3><ul><li>وثق السلوك الذي يراه المستخدم قبل تعديل الكود.</li><li>حدد الدليل أو المسار أو النموذج أو البرومبت أو طبقة النشر المرتبطة بالمشكلة.</li><li>تحقق من النتيجة في نفس السطح الذي ظهرت فيه المشكلة.</li></ul><blockquote>ينتهي الإصلاح عندما يثبت سطح المنتج ذلك.</blockquote><p>يستخدم هذا المحتوى التجريبي لاختبار صفحات المقالات والمعاينات وملخصات السيو والمحتوى المرتبط والمسافات الطباعية.</p>",
        ];
    }

    protected function seedGuideDownloaders($guides): void
    {
        $domains = ['fromscratch.solutions', 'productops.dev', 'laravel-studio.test', 'example.com'];

        for ($index = 1; $index <= 36; $index++) {
            $guide = $guides[($index - 1) % $guides->count()];

            GuideDownloader::updateOrCreate(
                [
                    'email' => "builder{$index}@".$domains[$index % count($domains)],
                    'guide_id' => $guide->id,
                ],
                [
                    'is_mail_sent' => $index % 6 !== 0,
                    'download_token' => Str::random(32),
                    'token_expires_at' => now()->addHours(24),
                    'token_used_at' => $index % 5 === 0 ? now()->subHours(2) : null,
                    'created_at' => now()->subDays((int) floor($index / 2)),
                    'updated_at' => now(),
                ],
            );
        }
    }

    protected function seedNewsletters(): void
    {
        $domains = ['fromscratch.solutions', 'productops.dev', 'ai-workflows.test', 'example.com'];

        for ($index = 1; $index <= 48; $index++) {
            Newsletter::updateOrCreate(
                ['email' => "subscriber{$index}@".$domains[$index % count($domains)]],
                [
                    'is_disabled' => $index % 10 === 0,
                    'unsubscribe_token' => Str::random(64),
                    'created_at' => now()->subDays($index),
                    'updated_at' => now(),
                ],
            );
        }
    }

    /**
     * @param  array{ar: string, en: string}  $names
     */
    protected function tag(array $names): Tag
    {
        $tag = Tag::findOrCreate($names['en'], 'intellectual_library', 'en');
        $tag->setTranslations('name', $names);
        $tag->setTranslations('slug', [
            'ar' => Str::slug($names['ar']),
            'en' => Str::slug($names['en']),
        ]);
        $tag->save();

        return $tag;
    }

    /**
     * @return array{ar: string, en: string}
     */
    protected function typeTag(IntellectualLibraryType $type): array
    {
        return match ($type) {
            IntellectualLibraryType::Article => ['ar' => 'مقالات', 'en' => 'articles'],
            IntellectualLibraryType::Video => ['ar' => 'فيديو', 'en' => 'videos'],
            IntellectualLibraryType::Podcast => ['ar' => 'بودكاست', 'en' => 'podcasts'],
            IntellectualLibraryType::Tool => ['ar' => 'أدوات', 'en' => 'tools'],
        };
    }

    /**
     * @return array{ar: string, en: string}
     */
    protected function topicTag(string $tag): array
    {
        return match ($tag) {
            'ai' => ['ar' => 'ذكاء اصطناعي', 'en' => 'ai'],
            'production' => ['ar' => 'الإنتاج', 'en' => 'production'],
            'admin' => ['ar' => 'لوحات الإدارة', 'en' => 'admin'],
            'laravel' => ['ar' => 'Laravel', 'en' => 'laravel'],
            'verification' => ['ar' => 'التحقق', 'en' => 'verification'],
            'operations' => ['ar' => 'تشغيل المنتج', 'en' => 'product operations'],
            'tool' => ['ar' => 'أدوات عملية', 'en' => 'practical tools'],
            default => ['ar' => 'مميز', 'en' => 'featured'],
        };
    }

    protected function attachMedia(mixed $model, string $collection, string $imageName): void
    {
        $path = $this->imagePath($imageName);

        if ($path === null) {
            return;
        }

        $model->clearMediaCollection($collection);
        $model->addMedia($path)
            ->preservingOriginal()
            ->usingFileName(basename($path))
            ->toMediaCollection($collection);
    }

    protected function attachPdf(mixed $model, string $collection, string $fileName): void
    {
        $model->clearMediaCollection($collection);
        $model->addMediaFromString("%PDF-1.4\n% Ibrahim Hasan demo {$collection}\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF")
            ->usingFileName($fileName)
            ->toMediaCollection($collection);
    }

    protected function imagePath(string $imageName): ?string
    {
        $candidates = [
            public_path("images/{$imageName}"),
            public_path("images/objects/{$imageName}"),
            public_path('images/ibrahim/rag-console.png'),
            public_path('images/bg-cover-2.png'),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
