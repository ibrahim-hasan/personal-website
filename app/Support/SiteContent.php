<?php

namespace App\Support;

class SiteContent
{
    public static function home(): array
    {
        return self::localize([
            'stats' => [
                ['value' => ['ar' => 'AI', 'en' => 'AI'], 'label' => ['ar' => 'مساعدات ذكية، RAG، وأتمتة سير العمل', 'en' => 'assistants, RAG, and workflow automation']],
                ['value' => ['ar' => 'Ops', 'en' => 'Ops'], 'label' => ['ar' => 'نشر، استعادة، وفحوصات إنتاج', 'en' => 'deployment, recovery, and production checks']],
                ['value' => ['ar' => 'Admin', 'en' => 'Admin'], 'label' => ['ar' => 'Filament، لوحات متابعة، وأدوات داخلية', 'en' => 'Filament, dashboards, and internal tools']],
                ['value' => ['ar' => 'ثنائي', 'en' => 'Bilingual'], 'label' => ['ar' => 'تجارب عربية وإنجليزية للمنتجات', 'en' => 'Arabic and English product workflows']],
            ],
            'services' => self::servicesPayload(),
            'work' => array_slice(self::workPayload(), 0, 3),
            'writing' => array_slice(self::writingPayload(), 0, 3),
            'process' => self::processPayload(),
        ]);
    }

    public static function services(): array
    {
        return self::localize(self::servicesPayload());
    }

    public static function work(): array
    {
        return self::localize(self::workPayload());
    }

    public static function writing(): array
    {
        return self::localize(self::writingPayload());
    }

    public static function process(): array
    {
        return self::localize(self::processPayload());
    }

    public static function contact(): array
    {
        return self::localize([
            'email' => 'hello@ibrahimhasan.dev',
            'location' => ['ar' => 'إسطنبول / عن بعد', 'en' => 'Istanbul / remote'],
            'availability' => [
                'ar' => 'متاح لبناء المنتجات المركزة، تحسين موثوقية الذكاء الاصطناعي، واستعادة أنظمة الإنتاج.',
                'en' => 'Available for focused product builds, AI reliability work, and production recovery.',
            ],
            'channels' => [
                ['label' => ['ar' => 'البريد', 'en' => 'Email'], 'href' => 'mailto:hello@ibrahimhasan.dev', 'value' => 'hello@ibrahimhasan.dev'],
                ['label' => ['ar' => 'لينكدإن', 'en' => 'LinkedIn'], 'href' => 'https://www.linkedin.com/', 'value' => ['ar' => 'تواصل عبر لينكدإن', 'en' => 'Connect on LinkedIn']],
                ['label' => ['ar' => 'جيت هب', 'en' => 'GitHub'], 'href' => 'https://github.com/', 'value' => ['ar' => 'راجع العمل الهندسي', 'en' => 'Review engineering work']],
            ],
        ]);
    }

    public static function toolchain(): array
    {
        return self::localize([
            ['ar' => 'Laravel', 'en' => 'Laravel'],
            ['ar' => 'Livewire', 'en' => 'Livewire'],
            ['ar' => 'Filament', 'en' => 'Filament'],
            ['ar' => 'Tailwind CSS', 'en' => 'Tailwind CSS'],
            ['ar' => 'Django', 'en' => 'Django'],
            ['ar' => 'OpenAI API', 'en' => 'OpenAI API'],
            ['ar' => 'n8n', 'en' => 'n8n'],
            ['ar' => 'GitHub Actions', 'en' => 'GitHub Actions'],
            ['ar' => 'CloudPanel', 'en' => 'CloudPanel'],
            ['ar' => 'PostgreSQL', 'en' => 'PostgreSQL'],
            ['ar' => 'MySQL', 'en' => 'MySQL'],
            ['ar' => 'Redis', 'en' => 'Redis'],
        ]);
    }

    private static function servicesPayload(): array
    {
        return [
            [
                'id' => 'ai-products',
                'name' => ['ar' => 'هندسة منتجات الذكاء الاصطناعي', 'en' => 'AI Product Engineering'],
                'summary' => [
                    'ar' => 'مساعدات محادثة، RAG، ويدجتات، حلقات جودة الإجابات، وميزات AI جاهزة للإنتاج.',
                    'en' => 'Chatbots, RAG workflows, widgets, answer quality loops, and production-grade AI features.',
                ],
                'problem' => [
                    'ar' => 'المنتج يحتاج إلى AI، لكن العروض العامة بطيئة أو غير دقيقة أو صعبة التشغيل.',
                    'en' => 'The product needs AI, but generic demos are slow, inaccurate, or hard to operate.',
                ],
                'approach' => [
                    'ar' => 'أراجع الاسترجاع، البرومبتات، التتبعات، زمن الاستجابة، حالات الفشل، وتجربة الواجهة معاً قبل الإطلاق.',
                    'en' => 'I inspect retrieval, prompts, traces, latency, fallback behavior, and UI states together before shipping.',
                ],
                'deliverables' => [
                    ['ar' => 'معمارية RAG', 'en' => 'RAG architecture'],
                    ['ar' => 'ويدجتات المساعد', 'en' => 'assistant widgets'],
                    ['ar' => 'برومبتات تقييم', 'en' => 'evaluation prompts'],
                    ['ar' => 'إسناد المصادر', 'en' => 'source attribution'],
                    ['ar' => 'فحوصات زمن الاستجابة', 'en' => 'latency checks'],
                ],
                'result' => [
                    'ar' => 'ميزات AI تجيب من المعرفة الصحيحة، تعرض دليلاً مفيداً، ويمكن تتبعها بعد الإطلاق.',
                    'en' => 'AI features that answer from the right knowledge, show useful evidence, and can be debugged after launch.',
                ],
            ],
            [
                'id' => 'platform-builds',
                'name' => ['ar' => 'منصات Laravel وDjango', 'en' => 'Laravel and Django Platforms'],
                'summary' => [
                    'ar' => 'أنظمة ويب عملية مع لوحات إدارة، تدفقات مرتبطة بالفوترة، واجهات API، ونماذج محتوى قابلة للتوسع.',
                    'en' => 'Practical web systems with admin panels, billing-aware workflows, APIs, and durable content models.',
                ],
                'problem' => [
                    'ar' => 'التطبيق يملك منطقاً مهماً، لكن سير العمل متفرق أو هش أو يصعب شرحه.',
                    'en' => 'The app has valuable domain logic, but the workflow feels scattered or fragile.',
                ],
                'approach' => [
                    'ar' => 'أربط النماذج، المسارات، موارد الإدارة، الاختبارات، وسلوك المتصفح حول مسار المشغل الحقيقي.',
                    'en' => 'I align models, routes, admin resources, tests, and browser behavior around the real operator flow.',
                ],
                'deliverables' => [
                    ['ar' => 'تطبيقات Laravel', 'en' => 'Laravel apps'],
                    ['ar' => 'تطبيقات Django', 'en' => 'Django apps'],
                    ['ar' => 'إدارة Filament', 'en' => 'Filament admin'],
                    ['ar' => 'واجهات Livewire', 'en' => 'Livewire interfaces'],
                    ['ar' => 'أسطح API', 'en' => 'API surfaces'],
                ],
                'result' => [
                    'ar' => 'منصة أسهل في التشغيل والشرح والنشر والتطوير.',
                    'en' => 'A platform that is easier to operate, explain, deploy, and extend.',
                ],
            ],
            [
                'id' => 'production-recovery',
                'name' => ['ar' => 'استعادة الإنتاج', 'en' => 'Production Recovery'],
                'summary' => [
                    'ar' => 'تشخيص الحوادث، فحوصات النشر، إعدادات الخادم، السجلات، والاستعادة من البداية للنهاية.',
                    'en' => 'Incident diagnosis, deploy checks, server configuration, logs, and end-to-end recovery.',
                ],
                'problem' => [
                    'ar' => 'هناك شيء مكسور في الإنتاج والاختبارات المحلية الخضراء لا تفسر ما يراه المستخدم.',
                    'en' => 'Something is broken in production and local green tests do not explain what users see.',
                ],
                'approach' => [
                    'ar' => 'أبدأ بقراءة دون تغيير، أقارن حالة التشغيل، أراجع السجلات ومسارات النشر، ثم أطبق إصلاحاً محدداً.',
                    'en' => 'I start read-only, compare runtime state, inspect logs, trace deployment paths, then apply a focused fix.',
                ],
                'deliverables' => [
                    ['ar' => 'تقارير سبب جذري', 'en' => 'root-cause reports'],
                    ['ar' => 'فحوصات CloudPanel', 'en' => 'CloudPanel checks'],
                    ['ar' => 'إصلاحات GitHub Actions', 'en' => 'GitHub Actions fixes'],
                    ['ar' => 'اختبارات دخان', 'en' => 'smoke tests'],
                    ['ar' => 'ملاحظات إصدار', 'en' => 'release notes'],
                ],
                'result' => [
                    'ar' => 'سطح إنتاجي ثابت مع فهم السبب، لا رقعة مؤقتة فقط.',
                    'en' => 'A fixed production surface with the cause understood, not just a temporary patch.',
                ],
            ],
            [
                'id' => 'product-ops',
                'name' => ['ar' => 'تشغيل المنتج', 'en' => 'Product Operations'],
                'summary' => [
                    'ar' => 'تجربة الإدارة، التدفقات الداخلية، المحتوى التجاري العربي، تسليمات العملاء، وربط الأتمتة.',
                    'en' => 'Admin UX, internal workflows, Arabic business content, client handoffs, and automation glue.',
                ],
                'problem' => [
                    'ar' => 'العمل موزع بين إدارة المتصفح، واتساب، مستندات، جداول، وأدوات نشر.',
                    'en' => 'The work is split between browser admin, WhatsApp, docs, spreadsheets, and deployment tools.',
                ],
                'approach' => [
                    'ar' => 'أحول العمل التشغيلي المتناثر إلى واجهات واضحة، قوائم تحقق، أتمتة، ومحتوى قابل لإعادة الاستخدام.',
                    'en' => 'I turn loose operational work into clear interfaces, checklists, automations, and reusable content.',
                ],
                'deliverables' => [
                    ['ar' => 'خرائط سير عمل', 'en' => 'workflow maps'],
                    ['ar' => 'إعادة تصميم الإدارة', 'en' => 'admin redesigns'],
                    ['ar' => 'تسليمات مؤتمتة', 'en' => 'automation handoffs'],
                    ['ar' => 'أنظمة محتوى', 'en' => 'content systems'],
                    ['ar' => 'مواد عملاء', 'en' => 'client material'],
                ],
                'result' => [
                    'ar' => 'تنسيق يدوي أقل ومسار أوضح من الطلب إلى التسليم.',
                    'en' => 'Less manual coordination and a clearer path from request to shipped work.',
                ],
            ],
        ];
    }

    private static function workPayload(): array
    {
        return [
            [
                'category' => ['ar' => 'AI', 'en' => 'AI'],
                'title' => ['ar' => 'مساعد دعم عربي مع أدلة', 'en' => 'Arabic Support Assistant With Evidence'],
                'summary' => [
                    'ar' => 'تدفق مساعد موجه للعملاء يفصل بين الاسترجاع، حالة الإجابة، بطاقات المصادر، وسلوك الويدجت.',
                    'en' => 'A customer-facing assistant flow that separates retrieval, answer status, source cards, and widget behavior.',
                ],
                'outcome' => [
                    'ar' => 'إجابات أدق، حالات فشل أوضح، وبرومبتات اختبار لمتابعة الجودة.',
                    'en' => 'Sharper answers, clearer fallback states, and test prompts for ongoing quality checks.',
                ],
                'image' => 'images/ibrahim/rag-console.png',
                'tags' => [['ar' => 'RAG', 'en' => 'RAG'], ['ar' => 'ويدجت', 'en' => 'Widget'], ['ar' => 'تجربة عربية', 'en' => 'Arabic UX']],
            ],
            [
                'category' => ['ar' => 'منصة', 'en' => 'Platform'],
                'title' => ['ar' => 'لوحة إدارة تشغيلية', 'en' => 'Operational Admin Console'],
                'summary' => [
                    'ar' => 'تدفقات كثيفة بأسلوب Filament للمحتوى، الاستخدام، حدود الفوترة، ومسارات المراجعة الداخلية.',
                    'en' => 'Dense Filament-style workflows for content, usage, billing-aware limits, and internal review paths.',
                ],
                'outcome' => [
                    'ar' => 'سطح إداري هادئ مصمم للعمل المتكرر، لا للزخرفة التسويقية.',
                    'en' => 'A calmer admin surface built for repeated operator work rather than marketing decoration.',
                ],
                'image' => 'images/ibrahim/product-systems.png',
                'tags' => [['ar' => 'Laravel', 'en' => 'Laravel'], ['ar' => 'Filament', 'en' => 'Filament'], ['ar' => 'Livewire', 'en' => 'Livewire']],
            ],
            [
                'category' => ['ar' => 'تشغيل', 'en' => 'Ops'],
                'title' => ['ar' => 'استعادة نشر CloudPanel', 'en' => 'CloudPanel Deployment Recovery'],
                'summary' => [
                    'ar' => 'تشخيص قراءة فقط، فحص سجلات، استعادة مدير الملفات، ربط النشر، وفحوصات دخان.',
                    'en' => 'Read-only diagnosis, log inspection, file-manager recovery, deployment wiring, and smoke checks.',
                ],
                'outcome' => [
                    'ar' => 'حلقة نشر مثبتة تربط الفرع، حالة الخادم، وسلوك المتصفح.',
                    'en' => 'A proven deploy loop with branch reality, server state, and browser behavior aligned.',
                ],
                'image' => 'images/ibrahim/automation-board.png',
                'tags' => [['ar' => 'CloudPanel', 'en' => 'CloudPanel'], ['ar' => 'GitHub Actions', 'en' => 'GitHub Actions'], ['ar' => 'PHP', 'en' => 'PHP']],
            ],
            [
                'category' => ['ar' => 'أتمتة', 'en' => 'Automation'],
                'title' => ['ar' => 'تسليم من واتساب إلى التنفيذ', 'en' => 'WhatsApp-to-Delivery Handoff'],
                'summary' => [
                    'ar' => 'نمط تنسيق عملي يحول طلبات العملاء المتفرقة إلى مهام منتج منظمة.',
                    'en' => 'A practical coordination pattern that turns fragmented client requests into structured product tasks.',
                ],
                'outcome' => [
                    'ar' => 'استقبال أوضح، تفاصيل مفقودة أقل، ودورات مراجعة أسرع للعمل العربي والإنجليزي.',
                    'en' => 'Cleaner intake, fewer missed details, and faster review cycles for Arabic and English work.',
                ],
                'image' => 'images/ibrahim/workflow-map.png',
                'tags' => [['ar' => 'n8n', 'en' => 'n8n'], ['ar' => 'مستندات', 'en' => 'Docs'], ['ar' => 'تشغيل العملاء', 'en' => 'Client Ops']],
            ],
        ];
    }

    private static function writingPayload(): array
    {
        return [
            [
                'title' => ['ar' => 'كيف أشخص جودة إجابات الذكاء الاصطناعي', 'en' => 'How I Diagnose AI Answer Quality'],
                'type' => ['ar' => 'ملاحظة ميدانية', 'en' => 'Field note'],
                'summary' => [
                    'ar' => 'قائمة عملية للفصل بين فشل الاسترجاع، فشل البرومبت، المحتوى القديم، وتجربة الويدجت.',
                    'en' => 'A practical checklist for separating retrieval failure, prompt failure, stale content, and widget UX.',
                ],
                'read_time' => ['ar' => '6 دقائق', 'en' => '6 min'],
            ],
            [
                'title' => ['ar' => 'لوحات الإدارة يجب أن تشبه طاولة العمل', 'en' => 'Admin Panels Should Feel Like Workbenches'],
                'type' => ['ar' => 'رأي منتج', 'en' => 'Product opinion'],
                'summary' => [
                    'ar' => 'لماذا تحتاج الأدوات الداخلية إلى كثافة، وضوح حالة، قراءة سريعة، وزخرفة أقل.',
                    'en' => 'Why internal tools need density, state clarity, fast scanning, and fewer decorative surfaces.',
                ],
                'read_time' => ['ar' => '4 دقائق', 'en' => '4 min'],
            ],
            [
                'title' => ['ar' => 'إصلاحات الإنتاج تحتاج إلى حقيقة المتصفح', 'en' => 'Production Fixes Need Browser Truth'],
                'type' => ['ar' => 'ملاحظة تشغيل', 'en' => 'Operations note'],
                'summary' => [
                    'ar' => 'الاختبارات المحلية مهمة، لكن رابط الإنتاج والسجلات وتاريخ النشر وسلوك المستخدم تغلق الحلقة.',
                    'en' => 'Local tests matter, but live URL checks, logs, deployment history, and user-visible behavior close the loop.',
                ],
                'read_time' => ['ar' => '5 دقائق', 'en' => '5 min'],
            ],
        ];
    }

    private static function processPayload(): array
    {
        return [
            ['step' => '01', 'title' => ['ar' => 'قراءة النظام الحقيقي', 'en' => 'Read the real system'], 'body' => ['ar' => 'أتحقق من المستودع، الفرع، بيئة التشغيل، الرابط، شكل البيانات، وسلوك المتصفح قبل تغيير الكود.', 'en' => 'I verify the repo, branch, runtime, URL, database shape, and browser behavior before changing code.']],
            ['step' => '02', 'title' => ['ar' => 'تحديد السبب الجذري', 'en' => 'Find the root cause'], 'body' => ['ar' => 'أتتبع الفشل الظاهر للمستخدم عبر الكود، البيانات، الطوابير، نداءات المزود، وحالة النشر.', 'en' => 'I trace the user-visible failure back through code, data, queues, provider calls, and deployment state.']],
            ['step' => '03', 'title' => ['ar' => 'شحن الإصلاح المركز', 'en' => 'Ship the focused fix'], 'body' => ['ar' => 'أبقي التغييرات قريبة من السطح المتأثر وأتبع أنماط الإطار الموجودة في المشروع.', 'en' => 'I keep edits close to the affected surface and match the framework patterns already in the project.']],
            ['step' => '04', 'title' => ['ar' => 'إثبات أنه يعمل', 'en' => 'Prove it works'], 'body' => ['ar' => 'أشغل الاختبارات المناسبة، أبني الأصول، أتحقق في المتصفح، وأوضح ما تم التحقق منه وما بقي.', 'en' => 'I run targeted tests, build assets, check the browser, and report what was verified and what remains.']],
        ];
    }

    private static function localize(mixed $value, ?string $locale = null): mixed
    {
        $locale ??= app()->getLocale();

        if (! is_array($value)) {
            return $value;
        }

        if (self::isLocalizedString($value)) {
            return (string) ($value[$locale] ?? $value['ar'] ?? $value['en'] ?? '');
        }

        return array_map(fn (mixed $item): mixed => self::localize($item, $locale), $value);
    }

    private static function isLocalizedString(array $value): bool
    {
        if (! array_key_exists('ar', $value) && ! array_key_exists('en', $value)) {
            return false;
        }

        foreach ($value as $item) {
            if (is_array($item)) {
                return false;
            }
        }

        return true;
    }
}
