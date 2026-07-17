<?php

namespace App\Support;

use App\Models\Service;
use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

class SiteContent
{
    public static function home(): array
    {
        $content = self::localize([
            'focus' => [
                ['value' => ['ar' => 'التحول الرقمي', 'en' => 'Digital Transformation'], 'label' => ['ar' => 'ما الذي يُرقمن، وما الذي يؤتمت، وبأي ترتيب', 'en' => 'What to digitize, what to automate, and in what order']],
                ['value' => ['ar' => 'الذكاء الاصطناعي', 'en' => 'AI Adoption'], 'label' => ['ar' => 'مساعدون، وكلاء، تقييم، وتقليل مخاطر الهلوسة', 'en' => 'Assistants, agents, evaluation, and hallucination risk']],
                ['value' => ['ar' => 'حوكمة البيانات', 'en' => 'Data Governance'], 'label' => ['ar' => 'ملكية، صلاحيات، جودة، واستعداد البيانات', 'en' => 'Ownership, permissions, quality, and readiness']],
                ['value' => ['ar' => 'الأنظمة والأتمتة', 'en' => 'Systems & Automation'], 'label' => ['ar' => 'منصات قابلة للصيانة والتوسع والقياس', 'en' => 'Maintainable, scalable, measurable platforms']],
            ],
            'services' => self::serviceDefaults(),
            'work' => array_slice(self::workPayload(), 0, 3),
            'writing' => array_slice(self::writingPayload(), 0, 3),
            'process' => self::processPayload(),
        ]);

        $content['services'] = self::services();

        return $content;
    }

    public static function services(): array
    {
        if (Schema::hasTable('services') && Schema::hasColumn('services', 'slug')) {
            return Service::query()
                ->posted()
                ->orderBy('order')
                ->get()
                ->map(fn (Service $service): array => $service->toPublicArray(app()->getLocale()))
                ->all();
        }

        return self::localize(self::serviceDefaults());
    }

    public static function aboutBiography(): string
    {
        $fallback = (string) __('site.about.body');

        if (! Schema::hasTable('settings')) {
            return $fallback;
        }

        $stored = Setting::getValue('about_biography', 'website_content');

        if (is_string($stored) && $stored !== '') {
            $decoded = json_decode($stored, true);
            $stored = is_array($decoded) ? $decoded : $stored;
        }

        if (is_array($stored)) {
            $stored = $stored[app()->getLocale()] ?? $stored['en'] ?? $stored['ar'] ?? '';
        }

        $biography = trim(strip_tags(is_string($stored) ? $stored : ''));

        return $biography !== '' ? $biography : $fallback;
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
        $settings = self::contactSettings();
        $configuredEmail = trim((string) ($settings['contact_email'] ?? ''));
        $email = filter_var($configuredEmail, FILTER_VALIDATE_EMAIL)
            ? $configuredEmail
            : 'hello@ibrahimhasan.net';
        $channels = [
            [
                'label' => ['ar' => 'البريد', 'en' => 'Email'],
                'href' => 'mailto:'.$email,
                'value' => $email,
                'value_direction' => 'ltr',
            ],
        ];

        $linkedin = self::socialUrls()['linkedin'] ?? null;

        if (is_string($linkedin) && trim($linkedin) !== '') {
            $channels[] = [
                'label' => ['ar' => 'لينكدإن', 'en' => 'LinkedIn'],
                'href' => $linkedin,
                'value' => ['ar' => 'تواصل عبر لينكدإن', 'en' => 'Connect on LinkedIn'],
            ];
        }

        $phone = trim((string) ($settings['contact_phone'] ?? ''));

        if ($phone !== '') {
            $channels[] = [
                'label' => ['ar' => 'الهاتف', 'en' => 'Phone'],
                'href' => 'tel:'.preg_replace('/[^+\d]/', '', $phone),
                'value' => $phone,
                'value_direction' => 'ltr',
            ];
        }

        $whatsapp = trim((string) ($settings['whatsapp_number'] ?? ''));

        if ($whatsapp !== '') {
            $channels[] = [
                'label' => ['ar' => 'واتساب', 'en' => 'WhatsApp'],
                'href' => 'https://wa.me/'.preg_replace('/\D/', '', $whatsapp),
                'value' => ['ar' => 'ابدأ محادثة', 'en' => 'Start a conversation'],
            ];
        }

        $channels[] = [
            'label' => ['ar' => 'وقت الرد', 'en' => 'Response time'],
            'href' => null,
            'value' => [
                'ar' => 'عادةً خلال يوم عمل واحد',
                'en' => 'Usually within one business day',
            ],
        ];

        return self::localize([
            'email' => $email,
            'availability' => [
                'ar' => 'أعمل مع شركات تريد تحويل مشاكل التشغيل والنمو إلى أنظمة رقمية وحلول ذكاء اصطناعي عملية، مبنية على فهم واضح للعمليات والبيانات والمخاطر.',
                'en' => 'I work with companies that want to turn operational and growth challenges into digital systems and practical AI solutions, built on a clear understanding of processes, data, and risk.',
            ],
            'channels' => $channels,
        ]);
    }

    /**
     * @return list<array{platform: string, label: string, href: string}>
     */
    public static function socialProfiles(): array
    {
        $socialUrls = self::socialUrls();
        $profiles = [
            ['platform' => 'linkedin', 'label' => 'LinkedIn', 'href' => $socialUrls['linkedin'] ?? null],
            ['platform' => 'facebook', 'label' => 'Facebook', 'href' => $socialUrls['facebook'] ?? null],
            ['platform' => 'twitter', 'label' => 'X', 'href' => $socialUrls['twitter'] ?? null],
            ['platform' => 'instagram', 'label' => 'Instagram', 'href' => $socialUrls['instagram'] ?? null],
            ['platform' => 'youtube', 'label' => 'YouTube', 'href' => $socialUrls['youtube'] ?? null],
        ];

        return array_values(array_filter(
            $profiles,
            fn (array $profile): bool => is_string($profile['href']) && trim($profile['href']) !== '',
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private static function socialUrls(): array
    {
        $socialUrls = config('services.social', []);
        $defaultConnection = (string) config('database.default');
        $databaseName = config("database.connections.{$defaultConnection}.database");

        if (! is_string($databaseName) || trim($databaseName) === '' || ! Schema::hasTable('settings')) {
            return $socialUrls;
        }

        $storedUrls = Setting::query()
            ->where('group', 'social')
            ->whereIn('key', ['social_linkedin', 'social_facebook', 'social_twitter', 'social_instagram', 'social_youtube'])
            ->pluck(Setting::valueColumn(), 'key');

        foreach ([
            'linkedin' => 'social_linkedin',
            'facebook' => 'social_facebook',
            'twitter' => 'social_twitter',
            'instagram' => 'social_instagram',
            'youtube' => 'social_youtube',
        ] as $platform => $settingKey) {
            $storedUrl = $storedUrls->get($settingKey);

            if (is_string($storedUrl) && trim($storedUrl) !== '') {
                $socialUrls[$platform] = self::safeExternalUrl($storedUrl);
            }
        }

        return collect($socialUrls)
            ->map(fn (mixed $url): ?string => self::safeExternalUrl($url))
            ->filter()
            ->all();
    }

    /** @return array<string, string|null> */
    private static function contactSettings(): array
    {
        $settings = [
            'contact_email' => 'hello@ibrahimhasan.net',
            'contact_phone' => null,
            'whatsapp_number' => null,
        ];

        if (! Schema::hasTable('settings')) {
            return $settings;
        }

        return array_replace($settings, Setting::query()
            ->where('group', 'contact')
            ->whereIn('key', array_keys($settings))
            ->pluck(Setting::valueColumn(), 'key')
            ->all());
    }

    private static function safeExternalUrl(mixed $url): ?string
    {
        if (! is_string($url)) {
            return null;
        }

        $url = trim($url);
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if ($url === '' || ! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
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
            ['ar' => 'PostgreSQL', 'en' => 'PostgreSQL'],
            ['ar' => 'MySQL', 'en' => 'MySQL'],
            ['ar' => 'Redis', 'en' => 'Redis'],
            ['ar' => 'السحابة وFinOps', 'en' => 'Cloud & FinOps'],
        ]);
    }

    public static function serviceDefaults(): array
    {
        return [
            [
                'id' => 'transformation',
                'name' => ['ar' => 'استراتيجية التحول الرقمي', 'en' => 'Digital Transformation Strategy'],
                'summary' => [
                    'ar' => 'تشخيص ما يجب رقمنته، وما يجب أتمتته، وما يمكن دعمه بالذكاء الاصطناعي، بترتيب أولويات مرتبط بالأثر التشغيلي والتجاري.',
                    'en' => 'Diagnosing what to digitize, what to automate, and what to support with AI, prioritized by operational and business impact.',
                ],
                'problem' => [
                    'ar' => 'مبادرات رقمية متفرقة، أدوات تُشترى قبل فهم العملية، ونتائج يصعب قياسها أو صيانتها.',
                    'en' => 'Scattered digital initiatives, tools bought before the process is understood, and results that are hard to measure or maintain.',
                ],
                'approach' => [
                    'ar' => 'أبدأ من العملية والقرار والمخاطر، ثم أحدد أين تكمن قيمة الرقمنة الحقيقية قبل اقتراح أي تقنية.',
                    'en' => 'I start from the process, the decision, and the risk, then identify where digitization creates real value before proposing any technology.',
                ],
                'deliverables' => [
                    ['ar' => 'خارطة رقمنة مرحلية', 'en' => 'Phased digitization roadmap'],
                    ['ar' => 'تحليل فجوات العملية', 'en' => 'Process gap analysis'],
                    ['ar' => 'مصفوفة أولويات بالأثر', 'en' => 'Impact-priority matrix'],
                    ['ar' => 'معايير قياس النجاح', 'en' => 'Success metrics'],
                ],
                'result' => [
                    'ar' => 'قرارات تقنية أوضح، إنفاق أكثر تركيزاً، ونظام رقمي يخدم العمل لا العكس.',
                    'en' => 'Clearer technology decisions, more focused spending, and a digital system that serves the business, not the other way around.',
                ],
            ],
            [
                'id' => 'ai-adoption',
                'name' => ['ar' => 'هندسة تبنّي الذكاء الاصطناعي', 'en' => 'AI Adoption Engineering'],
                'summary' => [
                    'ar' => 'مساعدون داخليون يستندون إلى المعرفة المعتمدة، مع مراجعة بشرية وضوابط واضحة للجودة والمخاطر.',
                    'en' => 'Internal assistants grounded in approved knowledge, with human review and clear controls for quality and risk.',
                ],
                'problem' => [
                    'ar' => 'تجارب ذكاء اصطناعي تبدو مبهرة في العرض لكنها غير دقيقة أو غير آمنة أو يصعب الاعتماد عليها في التشغيل.',
                    'en' => 'AI demos that look impressive but are inaccurate, unsafe, or hard to rely on in real operations.',
                ],
                'approach' => [
                    'ar' => 'أربط الذكاء الاصطناعي بالمعرفة المؤسسية الصحيحة، وأبني حلقات تقييم، وأجعل الإنسان في الحلقة حيثما يلزم.',
                    'en' => 'I ground AI in the right organizational knowledge, build evaluation loops, and keep a human in the loop wherever it matters.',
                ],
                'deliverables' => [
                    ['ar' => 'مساعدون مدعومون بالمعرفة', 'en' => 'Knowledge-grounded assistants'],
                    ['ar' => 'حلقات تقييم المخرجات', 'en' => 'Output evaluation loops'],
                    ['ar' => 'إسناد المصادر', 'en' => 'Source attribution'],
                    ['ar' => 'إدارة المخاطر والاعتمادية', 'en' => 'Risk and reliability controls'],
                ],
                'result' => [
                    'ar' => 'ذكاء اصطناعي يعمل كمضاعِف للفريق، لا كبديل سحري، وبمخاطر مفهومة ومحكومة.',
                    'en' => 'AI that works as a multiplier for the team, not a magic replacement, with risks understood and controlled.',
                ],
            ],
            [
                'id' => 'data-governance',
                'name' => ['ar' => 'حوكمة البيانات واستراتيجيتها', 'en' => 'Data Governance & Strategy'],
                'summary' => [
                    'ar' => 'أغلب مشاكل الذكاء الاصطناعي لا تأتي من ضعف النموذج، بل من بيانات ضعيفة أو غير منظمة أو بدون ملكية واضحة.',
                    'en' => 'Most AI problems do not come from a weak model, but from weak, disorganized data without clear ownership.',
                ],
                'problem' => [
                    'ar' => 'بيانات متناثرة، ملكية غامضة، صلاحيات غير واضحة، وجودة لا تكفي لاتخاذ قرارات موثوقة.',
                    'en' => 'Scattered data, ambiguous ownership, unclear permissions, and quality that is not enough for reliable decisions.',
                ],
                'approach' => [
                    'ar' => 'أرسم تدفق البيانات، أحدد الملكية والصلاحيات، وأضع أسس الجودة والخصوصية قبل أي مبادرة ذكاء اصطناعي.',
                    'en' => 'I map the data flow, define ownership and permissions, and set quality and privacy foundations before any AI initiative.',
                ],
                'deliverables' => [
                    ['ar' => 'خارطة تدفق البيانات', 'en' => 'Data flow maps'],
                    ['ar' => 'نموذج الملكية والصلاحيات', 'en' => 'Ownership and permissions model'],
                    ['ar' => 'معايير الجودة والخصوصية', 'en' => 'Quality and privacy standards'],
                    ['ar' => 'تقييم استعداد البيانات', 'en' => 'Data readiness assessment'],
                ],
                'result' => [
                    'ar' => 'بيانات جاهزة تدعم قرارات موثوقة وتبنّي ذكاء اصطناعي بأقل مخاطر.',
                    'en' => 'Ready data that supports reliable decisions and AI adoption with lower risk.',
                ],
            ],
            [
                'id' => 'systems',
                'name' => ['ar' => 'هندسة الأنظمة والأتمتة', 'en' => 'Systems & Automation Architecture'],
                'summary' => [
                    'ar' => 'منصات تشغيلية داخلية، وربط بين الأنظمة، وأتمتة لمسارات العمل بطريقة قابلة للصيانة والتوسع.',
                    'en' => 'Internal operating platforms, connected systems, and workflow automation designed to remain maintainable as the work grows.',
                ],
                'problem' => [
                    'ar' => 'عمل موزع بين أدوات متفرقة، جداول، ورسائل، مع تدفقات هشة يصعب شرحها أو تطويرها.',
                    'en' => 'Work spread across scattered tools, spreadsheets, and messages, with fragile flows that are hard to explain or evolve.',
                ],
                'approach' => [
                    'ar' => 'أحوّل العمل التشغيلي المتناثر إلى أنظمة واضحة، وأتمتة موثوقة، وتدفقات يمكن قياسها وصيانتها.',
                    'en' => 'I turn scattered operational work into clear systems, reliable automation, and flows that can be measured and maintained.',
                ],
                'deliverables' => [
                    ['ar' => 'أنظمة تشغيل داخلية', 'en' => 'Internal operations systems'],
                    ['ar' => 'ربط مسارات العمل وأتمتتها', 'en' => 'Connected and automated workflows'],
                    ['ar' => 'لوحات متابعة', 'en' => 'Monitoring dashboards'],
                    ['ar' => 'توثيق قابل للتسليم', 'en' => 'Handoff-ready documentation'],
                ],
                'result' => [
                    'ar' => 'تنسيق يدوي أقل، تدفقات أوضح، ومسار موثوق من الطلب إلى التسليم.',
                    'en' => 'Less manual coordination, clearer flows, and a reliable path from request to delivery.',
                ],
            ],
        ];
    }

    private static function workPayload(): array
    {
        return [
            [
                'category' => ['ar' => 'ذكاء اصطناعي', 'en' => 'AI'],
                'title' => ['ar' => 'مساعد داخلي مدعوم بالمعرفة المؤسسية', 'en' => 'Knowledge-Grounded Internal Assistant'],
                'summary' => [
                    'ar' => 'مساعد يجيب من قاعدة معرفة منظمة، يعرض مصادره، ويفصل بين حالة الإجابة وحالة الفشل بوضوح.',
                    'en' => 'An assistant that answers from an organized knowledge base, shows its sources, and separates answer and failure states clearly.',
                ],
                'outcome' => [
                    'ar' => 'إجابات أدق، مخاطر هلوسة أقل، وثقة أعلى في الاعتماد التشغيلي.',
                    'en' => 'More accurate answers, lower hallucination risk, and higher confidence in operational use.',
                ],
                'image' => 'images/ibrahim/rag-console.png',
                'tags' => [['ar' => 'RAG', 'en' => 'RAG'], ['ar' => 'تقييم', 'en' => 'Evaluation'], ['ar' => 'إسناد المصادر', 'en' => 'Attribution']],
            ],
            [
                'category' => ['ar' => 'أنظمة', 'en' => 'Systems'],
                'title' => ['ar' => 'نظام تشغيل داخلي', 'en' => 'Internal Operations System'],
                'summary' => [
                    'ar' => 'تدفقات تشغيلية للإدارة، المحتوى، الصلاحيات، والمتابعة الداخلية، مبنية للعمل المتكرر لا للزخرفة.',
                    'en' => 'Operational flows for management, content, permissions, and internal follow-up, built for repeated work, not decoration.',
                ],
                'outcome' => [
                    'ar' => 'سطح تشغيلي هادئ، يقلل التنسيق اليدوي ويسرّع القرارات.',
                    'en' => 'A calm operational surface that reduces manual coordination and speeds up decisions.',
                ],
                'image' => 'images/ibrahim/product-systems.png',
                'tags' => [['ar' => 'Laravel', 'en' => 'Laravel'], ['ar' => 'إدارة', 'en' => 'Admin'], ['ar' => 'صلاحيات', 'en' => 'Permissions']],
            ],
            [
                'category' => ['ar' => 'بيانات', 'en' => 'Data'],
                'title' => ['ar' => 'استعداد البيانات قبل تبنّي الذكاء الاصطناعي', 'en' => 'Data Readiness Before AI'],
                'summary' => [
                    'ar' => 'تقييم جودة البيانات، الملكية، الصلاحيات، والخصوصية كأساس قبل أي مبادرة ذكاء اصطناعي.',
                    'en' => 'Assessing data quality, ownership, permissions, and privacy as a foundation before any AI initiative.',
                ],
                'outcome' => [
                    'ar' => 'تبنٍّ أقل مخاطر، وقرارات مبنية على بيانات يمكن الوثوق بها.',
                    'en' => 'Lower-risk adoption and decisions built on data that can be trusted.',
                ],
                'image' => 'images/ibrahim/automation-board.png',
                'tags' => [['ar' => 'حوكمة', 'en' => 'Governance'], ['ar' => 'جودة', 'en' => 'Quality'], ['ar' => 'خصوصية', 'en' => 'Privacy']],
            ],
            [
                'category' => ['ar' => 'أتمتة', 'en' => 'Automation'],
                'title' => ['ar' => 'أتمتة التدفق من الطلب إلى التسليم', 'en' => 'Request-to-Delivery Automation'],
                'summary' => [
                    'ar' => 'نمط تنسيق عملي يحوّل الطلبات المتفرقة إلى مهام منظمة، مع متابعة وتسليم أوضح.',
                    'en' => 'A practical coordination pattern that turns scattered requests into structured tasks, with clearer follow-up and delivery.',
                ],
                'outcome' => [
                    'ar' => 'استقبال أوضح، تفاصيل مفقودة أقل، ودورات عمل أسرع.',
                    'en' => 'Clearer intake, fewer missed details, and faster work cycles.',
                ],
                'image' => 'images/ibrahim/workflow-map.png',
                'tags' => [['ar' => 'تنسيق', 'en' => 'Coordination'], ['ar' => 'تدفقات', 'en' => 'Workflows'], ['ar' => 'تسليم', 'en' => 'Delivery']],
            ],
        ];
    }

    private static function writingPayload(): array
    {
        return [
            [
                'title' => ['ar' => 'متى نستخدم الذكاء الاصطناعي ومتى لا نستخدمه', 'en' => 'When to Use AI and When Not To'],
                'type' => ['ar' => 'رأي عملي', 'en' => 'Practical opinion'],
                'summary' => [
                    'ar' => 'ليست كل مشكلة تحتاج ذكاءً اصطناعياً. أحياناً نظام أفضل أو بيانات أنظف تحل المشكلة بكلفة أقل.',
                    'en' => 'Not every problem needs AI. Sometimes a better system or cleaner data solves it at lower cost.',
                ],
                'read_time' => ['ar' => '6 دقائق', 'en' => '6 min'],
            ],
            [
                'title' => ['ar' => 'لماذا تفشل مشاريع التحول الرقمي', 'en' => 'Why Digital Transformation Projects Fail'],
                'type' => ['ar' => 'ملاحظة ميدانية', 'en' => 'Field note'],
                'summary' => [
                    'ar' => 'أغلب الفشل يبدأ قبل الكود: غياب فهم العملية، أهداف غير واضحة، أو أدوات تُشترى قبل السؤال الصحيح.',
                    'en' => 'Most failure starts before code: no process understanding, unclear goals, or tools bought before the right question.',
                ],
                'read_time' => ['ar' => '5 دقائق', 'en' => '5 min'],
            ],
            [
                'title' => ['ar' => 'البيانات قبل الذكاء الاصطناعي', 'en' => 'Data Before AI'],
                'type' => ['ar' => 'إطار عمل', 'en' => 'Framework'],
                'summary' => [
                    'ar' => 'لماذا تبدأ مشاريع الذكاء الاصطناعي الناجحة من جودة البيانات وملكيتها وحوكمتها، لا من النموذج.',
                    'en' => 'Why successful AI projects start from data quality, ownership, and governance, not from the model.',
                ],
                'read_time' => ['ar' => '4 دقائق', 'en' => '4 min'],
            ],
        ];
    }

    private static function processPayload(): array
    {
        return [
            ['step' => '01', 'title' => ['ar' => 'فهم المشكلة قبل الحل', 'en' => 'Understand the problem first'], 'body' => ['ar' => 'ما المشكلة التشغيلية أو التجارية؟ أين الهدر أو التعطل؟ ما القرار المطلوب تحسينه؟', 'en' => 'What is the operational or business problem? Where is the waste or friction? What decision needs improving?']],
            ['step' => '02', 'title' => ['ar' => 'تحليل العملية والبيانات', 'en' => 'Map the process and the data'], 'body' => ['ar' => 'أرسم العملية الحالية، تدفق البيانات، نقاط القرار، والمخاطر قبل التفكير في أي تقنية.', 'en' => 'I map the current process, data flow, decision points, and risks before thinking about any technology.']],
            ['step' => '03', 'title' => ['ar' => 'تحديد ما يُرقمن ويؤتمت ويدعم بالذكاء الاصطناعي', 'en' => 'Decide what to digitize, automate, or support with AI'], 'body' => ['ar' => 'هل نحتاج ذكاءً اصطناعياً، أم نظاماً أفضل، أم بيانات أنظف؟ الترتيب يُبنى على الأثر والكلفة والمخاطر.', 'en' => 'Do we need AI, a better system, or cleaner data? The order is built on impact, cost, and risk.']],
            ['step' => '04', 'title' => ['ar' => 'بناء حل قابل للصيانة والقياس', 'en' => 'Build maintainable and measurable'], 'body' => ['ar' => 'أبني حلاً يمكن صيانته وتوسعته وقياس أثره، مع توثيق واضح وتسليم يمكن لفريقك متابعته.', 'en' => 'I build a solution that can be maintained, scaled, and measured, with clear documentation your team can follow.']],
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
