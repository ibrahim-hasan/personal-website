<?php

namespace App\Support;

use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

final class PortfolioAtlas
{
    /**
     * @return list<array{id: string, title: string, sector: string, summary: string, challenge: string, response: string, outcome: string, lens: string, image: string, alt: string, logo: string, logo_alt: string, tags: list<string>}>
     */
    public static function homepageProjects(int $limit = 6): array
    {
        if ($limit <= 0) {
            return [];
        }

        return array_slice(self::projects(), 0, $limit);
    }

    /**
     * @return list<array{id: string, title: string, sector: string, summary: string, challenge: string, response: string, outcome: string, lens: string, image: string, alt: string, logo: string, logo_alt: string, tags: list<string>}>
     */
    public static function featuredProjects(?string $lens = null, int $limit = 4): array
    {
        if ($limit <= 0) {
            return [];
        }

        if (self::usesStoredProjects()) {
            return Project::query()
                ->published()
                ->where('featured', true)
                ->when($lens !== null, fn (Builder $query): Builder => $query->where('lens', $lens))
                ->orderBy('sort_order')
                ->limit($limit)
                ->get()
                ->map(fn (Project $project): array => $project->toPortfolioArray(app()->getLocale()))
                ->all();
        }

        $projects = self::projects();

        if ($lens !== null) {
            $projects = array_values(array_filter(
                $projects,
                fn (array $project): bool => $project['lens'] === $lens,
            ));
        }

        return array_slice($projects, 0, $limit);
    }

    /**
     * @return list<array{id: string, title: string, sector: string, summary: string, challenge: string, response: string, outcome: string, lens: string, image: string, alt: string, logo: string, logo_alt: string, tags: list<string>}>
     */
    public static function projects(): array
    {
        if (self::usesStoredProjects()) {
            return Project::query()
                ->published()
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Project $project): array => $project->toPortfolioArray(app()->getLocale()))
                ->all();
        }

        return self::localize(self::projectDefaults());
    }

    /**
     * @return list<array{id: string, name: string, relationship: string, summary: string, logo_on_light: string, logo_on_dark: string, logo_alt: string, logo_on_light_width: int, logo_on_light_height: int, logo_on_dark_width: int, logo_on_dark_height: int, focus: list<string>}>
     */
    public static function companies(): array
    {
        return self::localize(self::companiesPayload());
    }

    /**
     * @return list<array{id: string, step: string, title: string, summary: string}>
     */
    public static function experience(): array
    {
        return self::localize(self::experiencePayload());
    }

    /**
     * @return list<array{id: string, label: string, description: string, question: string}>
     */
    public static function lenses(): array
    {
        return self::localize(self::lensesPayload());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function companiesPayload(): array
    {
        $companies = [
            [
                'id' => 'code-moments',
                'name' => ['ar' => 'كود مومنتس', 'en' => 'Code Moments'],
                'relationship' => [
                    'ar' => 'الرئيس التنفيذي',
                    'en' => 'Chief Executive Officer',
                ],
                'summary' => [
                    'ar' => 'في كود مومنتس أقود الشركة من فهم احتياج العمل إلى بناء منتجات وأنظمة رقمية متقنة، مع إبقاء القرار التجاري وجودة التنفيذ والتطوير المستمر في مسار واحد.',
                    'en' => 'At Code Moments, I lead the company from understanding the business need through building polished digital products and systems, keeping commercial judgment, delivery quality, and continuous improvement on one path.',
                ],
                'logo_on_light' => 'images/brands/companies/code-moments-on-light.svg',
                'logo_on_dark' => 'images/brands/companies/code-moments-on-dark.svg',
                'logo_alt' => ['ar' => 'شعار كود مومنتس', 'en' => 'Code Moments logo'],
                'logo_on_light_width' => 94,
                'logo_on_light_height' => 34,
                'logo_on_dark_width' => 133,
                'logo_on_dark_height' => 48,
                'focus' => [
                    ['ar' => 'تحويل الاحتياج إلى خارطة منتج', 'en' => 'Turn needs into a product roadmap'],
                    ['ar' => 'مواءمة القرار التجاري والتنفيذ', 'en' => 'Align commercial decisions and delivery'],
                    ['ar' => 'بناء نظام جودة وتحسين مستمر', 'en' => 'Build a system for quality and iteration'],
                ],
            ],
            [
                'id' => 'from-scratch',
                'name' => ['ar' => 'فروم سكراتش', 'en' => 'From Scratch'],
                'relationship' => [
                    'ar' => 'الشريك المؤسس والرئيس التنفيذي',
                    'en' => 'Co-founder & Chief Executive Officer',
                ],
                'summary' => [
                    'ar' => 'شاركت في تأسيس فروم سكراتش وقيادة نموها وتسليم منتجات رقمية لقطاعات متعددة؛ تجربة رسّخت لدي أن التقنية الجيدة تبدأ بفهم التشغيل وتنتهي بنظام يستطيع الفريق الاعتماد عليه.',
                    'en' => 'I co-founded From Scratch and led its growth and delivery across digital products in multiple sectors—an experience that reinforced a simple principle: good technology starts with understanding operations and ends with a system the team can rely on.',
                ],
                'logo_on_light' => 'images/brands/companies/from-scratch-on-light.svg',
                'logo_on_dark' => 'images/brands/companies/from-scratch-on-dark.svg',
                'logo_alt' => ['ar' => 'شعار فروم سكراتش', 'en' => 'From Scratch logo'],
                'logo_on_light_width' => 259,
                'logo_on_light_height' => 140,
                'logo_on_dark_width' => 136,
                'logo_on_dark_height' => 74,
                'focus' => [
                    ['ar' => 'بناء نموذج تشغيل قابل للتوسع', 'en' => 'Build a scalable operating model'],
                    ['ar' => 'تسليم منتجات عبر قطاعات متعددة', 'en' => 'Deliver products across multiple sectors'],
                    ['ar' => 'تحويل الخبرة إلى أنظمة يعتمد عليها', 'en' => 'Turn experience into dependable systems'],
                ],
            ],
            [
                'id' => 'independent-strategic-practice',
                'name' => ['ar' => 'ممارسة إبراهيم الاستراتيجية المستقلة', 'en' => 'Ibrahim’s Independent Strategic Practice'],
                'relationship' => [
                    'ar' => 'ممارسة استراتيجية مستقلة',
                    'en' => 'Independent strategic practice',
                ],
                'summary' => [
                    'ar' => 'أعمل مباشرة مع أصحاب القرار من موقع معماري حلول للتحول الرقمي والذكاء الاصطناعي: أبدأ من العملية والقرار والبيانات والمخاطر، ثم أحدد التقنية التي تستحق البناء والقياس.',
                    'en' => 'I work directly with decision-makers as an AI and digital transformation architect: starting with the process, decision, data, and risk, then identifying the technology worth building and measuring.',
                ],
                'logo_on_light' => '',
                'logo_on_dark' => '',
                'logo_alt' => '',
                'logo_on_light_width' => 0,
                'logo_on_light_height' => 0,
                'logo_on_dark_width' => 0,
                'logo_on_dark_height' => 0,
                'focus' => [
                    ['ar' => 'تحديد القرار قبل اختيار التقنية', 'en' => 'Define the decision before the technology'],
                    ['ar' => 'ضبط مخاطر البيانات والذكاء الاصطناعي', 'en' => 'Control data and AI risk'],
                    ['ar' => 'ربط الاستثمار بأثر قابل للقياس', 'en' => 'Connect investment to measurable impact'],
                ],
            ],
        ];

        $order = ['from-scratch' => 0, 'code-moments' => 1, 'independent-strategic-practice' => 2];

        usort(
            $companies,
            static fn (array $first, array $second): int => $order[$first['id']] <=> $order[$second['id']],
        );

        return $companies;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function experiencePayload(): array
    {
        return [
            [
                'id' => 'understand-operations',
                'step' => '01',
                'title' => ['ar' => 'فهم التشغيل من الداخل', 'en' => 'Understand the operation'],
                'summary' => [
                    'ar' => 'بدأ المنهج من الاقتراب من العمل اليومي: كيف تنتقل الطلبات، أين تتعطل القرارات، وما الذي يحتاجه الفريق فعلاً قبل اقتراح أي حل.',
                    'en' => 'The approach began close to daily work: how requests move, where decisions stall, and what teams actually need before any solution is proposed.',
                ],
            ],
            [
                'id' => 'lead-delivery',
                'step' => '02',
                'title' => ['ar' => 'قيادة التسليم وسط الغموض', 'en' => 'Lead delivery through ambiguity'],
                'summary' => [
                    'ar' => 'تحولت المتطلبات غير الواضحة إلى أولويات ومسؤوليات ومسارات تسليم مفهومة، مع إبقاء القرار التجاري والعمل التقني على المسار نفسه.',
                    'en' => 'Unclear requirements became priorities, ownership, and understandable delivery paths, keeping the business decision and the technical work aligned.',
                ],
            ],
            [
                'id' => 'build-reusable-systems',
                'step' => '03',
                'title' => ['ar' => 'بناء أنظمة قابلة لإعادة الاستخدام', 'en' => 'Build reusable systems'],
                'summary' => [
                    'ar' => 'انتقل التركيز من إنجازات منفردة إلى أنظمة وأنماط عمل يمكن صيانتها وتطويرها وتسليمها لفرق أخرى بثقة.',
                    'en' => 'The focus moved from one-off outputs to systems and working patterns that can be maintained, extended, and handed to other teams with confidence.',
                ],
            ],
            [
                'id' => 'lead-ai-transformation',
                'step' => '04',
                'title' => ['ar' => 'قيادة التحول الرقمي وتبنّي الذكاء الاصطناعي', 'en' => 'Lead AI and digital transformation'],
                'summary' => [
                    'ar' => 'تجتمع الخبرة اليوم في قرار واحد متكامل: ما الذي يُرقمن، وما الذي يُؤتمت، وأين يضيف الذكاء الاصطناعي قيمة حقيقية ضمن مخاطر مفهومة.',
                    'en' => 'The experience now comes together in one integrated decision: what to digitize, what to automate, and where AI creates real value with understood risk.',
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function projectDefaults(): array
    {
        return [
            [
                'id' => 'digi-pedia',
                'title' => ['ar' => 'ديجي بيديا', 'en' => 'Digi-Pedia'],
                'sector' => ['ar' => 'تعليم الذكاء الاصطناعي والمعرفة', 'en' => 'AI education & knowledge'],
                'summary' => [
                    'ar' => 'منصة معرفية عربية تساعد المهنيين وصنّاع المحتوى ورواد الأعمال على فهم أدوات الذكاء الاصطناعي واستخدامها بصورة عملية.',
                    'en' => 'An Arabic knowledge platform helping professionals, creators, and entrepreneurs understand AI tools and put them to practical use.',
                ],
                'challenge' => [
                    'ar' => 'احتاج الجمهور العربي إلى مسار منظم وحديث ينتقل به من اكتشاف الأدوات إلى فهمها واختيار ما يناسب عمله.',
                    'en' => 'Arabic-speaking audiences needed an organized, current path from discovering tools to understanding them and choosing what fits their work.',
                ],
                'response' => [
                    'ar' => 'نُظمت التجربة حول دليل منتقى للأدوات، ومسارات تعلم عملية، ومشاركة مجتمعية، ونموذج وصول قابل للاستمرار.',
                    'en' => 'The experience was organized around a curated tool directory, practical learning paths, community participation, and a sustainable access model.',
                ],
                'outcome' => [
                    'ar' => 'مسار أوضح يساعد الجمهور العربي على اكتشاف أدوات الذكاء الاصطناعي ومقارنتها وتطبيقها في سياقات عملية.',
                    'en' => 'A clearer path for Arabic-speaking audiences to discover, compare, and apply AI tools in practical contexts.',
                ],
                'lens' => 'ai-adoption',
                'image' => 'images/projects/atlas/digi-pedia-ai-learning.webp',
                'alt' => ['ar' => 'تجربة ديجي بيديا لتعلّم الذكاء الاصطناعي بالعربية', 'en' => 'Digi-Pedia Arabic AI learning experience'],
                'logo' => 'images/brands/projects/digi-pedia.webp',
                'logo_alt' => ['ar' => 'شعار ديجي بيديا', 'en' => 'Digi-Pedia logo'],
                'tags' => [
                    ['ar' => 'ثقافة الذكاء الاصطناعي', 'en' => 'AI literacy'],
                    ['ar' => 'إتاحة المعرفة', 'en' => 'Knowledge access'],
                    ['ar' => 'مجتمع تعلّم', 'en' => 'Learning community'],
                ],
            ],
            [
                'id' => 'wafaa',
                'title' => ['ar' => 'منظومة وفاء الأجيال', 'en' => 'Wafaa Ecosystem'],
                'sector' => ['ar' => 'التعليم والعمل غير الربحي', 'en' => 'Education & nonprofit'],
                'summary' => [
                    'ar' => 'منظومة تشغيل مترابطة تجمع الإدارة والتعليم والشؤون المالية والتبرعات لمؤسسة تعليمية غير ربحية.',
                    'en' => 'A connected operating ecosystem bringing together administration, learning, finance, and giving for a nonprofit education organization.',
                ],
                'challenge' => [
                    'ar' => 'اعتمدت أعمال الطلاب والموظفين والشؤون المالية والتبرعات على سجلات يدوية ومسارات منفصلة يصعب تنسيقها.',
                    'en' => 'Student, staff, finance, and donation work depended on manual records and separate paths that were difficult to coordinate.',
                ],
                'response' => [
                    'ar' => 'رُبطت المسارات الإدارية والتعليمية والمالية والتبرعية في بيئة رقمية واحدة، بعد رسم العلاقات ونقاط التسليم بينها.',
                    'en' => 'Administrative, learning, financial, and donation journeys were connected in one digital environment after mapping their relationships and handoffs.',
                ],
                'outcome' => [
                    'ar' => 'نموذج تشغيل أكثر ترابطاً، بمسؤوليات أوضح واعتماد أقل على الأوراق والملفات المتفرقة.',
                    'en' => 'A more coherent operating model with clearer responsibilities and less dependence on fragmented paperwork.',
                ],
                'lens' => 'transformation',
                'image' => 'images/projects/atlas/wafaa-education-transformation.webp',
                'alt' => ['ar' => 'منظومة وفاء لإدارة التعليم والعمل غير الربحي', 'en' => 'Wafaa education and nonprofit operating ecosystem'],
                'logo' => 'images/brands/projects/wafaa.webp',
                'logo_alt' => ['ar' => 'شعار وفاء الأجيال', 'en' => 'Wafaa logo'],
                'tags' => [
                    ['ar' => 'تشغيل التعليم', 'en' => 'Education operations'],
                    ['ar' => 'تحول غير ربحي', 'en' => 'Nonprofit transformation'],
                    ['ar' => 'تنسيق الخدمات', 'en' => 'Service coordination'],
                ],
            ],
            [
                'id' => 'rannan',
                'title' => ['ar' => 'رنان', 'en' => 'Rannan'],
                'sector' => ['ar' => 'الثقة بالمتصل والاتصالات', 'en' => 'Caller trust & communications'],
                'summary' => [
                    'ar' => 'تجربة عربية للتعرّف على هوية المتصل والحد من المكالمات المزعجة مع مساهمة مجتمعية تراعي الخصوصية.',
                    'en' => 'An Arabic caller-identification and unwanted-call protection experience with privacy-conscious community participation.',
                ],
                'challenge' => [
                    'ar' => 'كان على المنتج الجمع بين سرعة البحث عن الأرقام، وجودة المعلومات، والخصوصية، مع إتاحة تصحيح الأسماء بمسؤولية.',
                    'en' => 'The product had to balance fast number lookup, information quality, privacy, and responsible community name corrections.',
                ],
                'response' => [
                    'ar' => 'صُممت الرحلة حول بحث مباشر، ومساهمات موثوقة، ومسارات واضحة للتصحيح والإبلاغ، وضوابط تحمي المستخدم.',
                    'en' => 'The journey was shaped around direct lookup, trusted contributions, clear correction and reporting paths, and safeguards for users.',
                ],
                'outcome' => [
                    'ar' => 'سياق أوضح حول هوية المتصل وتجربة ثقة يشارك المجتمع في تحسينها دون التفريط بالخصوصية.',
                    'en' => 'Clearer caller context and a community-supported trust experience without giving up privacy.',
                ],
                'lens' => 'product',
                'image' => 'images/projects/atlas/rannan-caller-trust.webp',
                'alt' => ['ar' => 'تجربة رنان للتعرّف على هوية المتصل', 'en' => 'Rannan caller-identification experience'],
                'logo' => 'images/brands/projects/rannan.webp',
                'logo_alt' => ['ar' => 'شعار رنان', 'en' => 'Rannan logo'],
                'tags' => [
                    ['ar' => 'الثقة والسلامة', 'en' => 'Trust & safety'],
                    ['ar' => 'خدمة المستهلك', 'en' => 'Consumer utility'],
                    ['ar' => 'جودة مجتمعية', 'en' => 'Community quality'],
                ],
            ],
            [
                'id' => 'maazim',
                'title' => ['ar' => 'معازيم', 'en' => 'Maazim'],
                'sector' => ['ar' => 'تجارة الهدايا والتوصيل', 'en' => 'Gifting commerce & delivery'],
                'summary' => [
                    'ar' => 'تجربة متكاملة لاختيار الهدايا وتخصيص طريقة تقديمها وتنسيق شرائها وتجهيزها وتوصيلها.',
                    'en' => 'An integrated experience for choosing gifts, tailoring their presentation, and coordinating purchase, preparation, and delivery.',
                ],
                'challenge' => [
                    'ar' => 'كان المطلوب جمع تنوع المنتجات وخيارات الدفع والتجهيز والتوصيل في رحلة واضحة تحافظ على طابع الخدمة الفاخر.',
                    'en' => 'The challenge was to bring product choice, payment, preparation, and delivery into one clear journey without losing the service’s premium character.',
                ],
                'response' => [
                    'ar' => 'نُظمت الخدمة حول الاكتشاف والتخصيص والشراء والتنفيذ، مع مسارات تشغيل تدعم الطلبات والمخزون وتنسيق التوصيل.',
                    'en' => 'The service was structured around discovery, customization, purchase, and fulfilment, with operating flows for orders, inventory, and delivery coordination.',
                ],
                'outcome' => [
                    'ar' => 'تجربة تجارة موحدة تجعل الهدية الفاخرة أسهل في الطلب وأكثر انتظاماً في التجهيز والتوصيل.',
                    'en' => 'A unified commerce experience that makes premium gifting easier to order and more orderly to prepare and deliver.',
                ],
                'lens' => 'operations',
                'image' => 'images/projects/atlas/maazim-gifting-operations.webp',
                'alt' => ['ar' => 'تجربة معازيم لطلب الهدايا وتوصيلها', 'en' => 'Maazim gift ordering and delivery experience'],
                'logo' => 'images/brands/projects/maazim.webp',
                'logo_alt' => ['ar' => 'شعار معازيم', 'en' => 'Maazim logo'],
                'tags' => [
                    ['ar' => 'تشغيل التجارة', 'en' => 'Commerce operations'],
                    ['ar' => 'تنسيق الطلبات', 'en' => 'Order orchestration'],
                    ['ar' => 'تجربة العميل', 'en' => 'Customer experience'],
                ],
            ],
            [
                'id' => 'rafid-360',
                'title' => ['ar' => 'رافد 360', 'en' => 'Rafid 360'],
                'sector' => ['ar' => 'التعاون الإنساني', 'en' => 'Humanitarian collaboration'],
                'summary' => [
                    'ar' => 'مساحة عمل مركزية تساعد المنظمات التنموية على التشبيك وتنسيق الموارد والتعاون ضمن بيئة تراعي حساسية المعلومات.',
                    'en' => 'A central workspace helping development organizations connect, coordinate resources, and collaborate in an environment mindful of sensitive information.',
                ],
                'challenge' => [
                    'ar' => 'توزعت جهود المنظمات ومعلوماتها بين قنوات متعددة، بينما احتاج التعاون إلى مشاركة مسؤولة وواضحة للموارد.',
                    'en' => 'Organizations’ efforts and information were spread across multiple channels while collaboration required responsible, clear resource sharing.',
                ],
                'response' => [
                    'ar' => 'بُنيت بيئة عربية أولاً تجمع التشبيك وتنسيق الموارد ومسارات التعاون مع عناية عالية بالخصوصية والوصول.',
                    'en' => 'An Arabic-first environment brought together networking, resource coordination, and collaboration paths with close attention to privacy and access.',
                ],
                'outcome' => [
                    'ar' => 'مساحة تشغيل مشتركة أوضح للتنسيق بين الجهات وتبادل المعلومات بمسؤولية.',
                    'en' => 'A clearer shared operating space for coordination across organizations and responsible information exchange.',
                ],
                'lens' => 'transformation',
                'image' => 'images/projects/atlas/rafid-humanitarian-collaboration.webp',
                'alt' => ['ar' => 'منصة رافد 360 للتعاون بين المنظمات الإنسانية', 'en' => 'Rafid 360 humanitarian organization collaboration workspace'],
                'logo' => 'images/brands/projects/rafid-360.webp',
                'logo_alt' => ['ar' => 'شعار رافد 360', 'en' => 'Rafid 360 logo'],
                'tags' => [
                    ['ar' => 'تنسيق إنساني', 'en' => 'Humanitarian coordination'],
                    ['ar' => 'تعاون آمن', 'en' => 'Secure collaboration'],
                    ['ar' => 'تجربة عربية', 'en' => 'Arabic-first experience'],
                ],
            ],
            [
                'id' => 'taifk',
                'title' => ['ar' => 'طيفك', 'en' => 'Taifk'],
                'sector' => ['ar' => 'خدمات التجميل المنزلية', 'en' => 'Home beauty services'],
                'summary' => [
                    'ar' => 'منظومة خدمات تربط العميل بمزود الخدمة وفريق التشغيل لطلب خدمات التجميل المنزلية وتنظيمها.',
                    'en' => 'A service ecosystem connecting customers, providers, and operations for requesting and organizing at-home beauty services.',
                ],
                'challenge' => [
                    'ar' => 'تطلبت الرحلة تنسيق المواعيد وتوفر المزود وتنفيذ الخدمة والدفع والولاء بين أطراف متعددة.',
                    'en' => 'The journey had to coordinate appointments, provider availability, service fulfilment, payment, and loyalty across multiple participants.',
                ],
                'response' => [
                    'ar' => 'جُمعت أدوار العميل والمزود والإدارة مع الحجز والجدولة والمحفظة والولاء ضمن نموذج خدمة واحد.',
                    'en' => 'Customer, provider, and operator roles were brought together with booking, scheduling, wallet, and loyalty journeys in one service model.',
                ],
                'outcome' => [
                    'ar' => 'حجز أسهل للعميل وتنسيق أوضح للمواعيد والتنفيذ بين المزود وفريق التشغيل.',
                    'en' => 'Easier booking for customers and clearer appointment and fulfilment coordination for providers and operators.',
                ],
                'lens' => 'operations',
                'image' => 'images/projects/atlas/taifk-service-operations.webp',
                'alt' => ['ar' => 'تجربة طيفك لحجز خدمات التجميل المنزلية', 'en' => 'Taifk at-home beauty service booking experience'],
                'logo' => 'images/brands/projects/taifk.webp',
                'logo_alt' => ['ar' => 'شعار طيفك', 'en' => 'Taifk logo'],
                'tags' => [
                    ['ar' => 'سوق خدمات', 'en' => 'Service marketplace'],
                    ['ar' => 'جدولة المواعيد', 'en' => 'Scheduling'],
                    ['ar' => 'ولاء العملاء', 'en' => 'Customer loyalty'],
                ],
            ],
            [
                'id' => 'bosalty',
                'title' => ['ar' => 'بوصلتي', 'en' => 'Bosalty'],
                'sector' => ['ar' => 'السياحة والتجارب', 'en' => 'Tourism & experiences'],
                'summary' => [
                    'ar' => 'دليل سياحي رقمي يربط زوار المملكة بمزودي التجارب، ويساعدهم على اكتشاف الوجهات وتنظيم الرحلات.',
                    'en' => 'A digital tourism platform connecting visitors in Saudi Arabia with experience providers while supporting destination discovery and trip planning.',
                ],
                'challenge' => [
                    'ar' => 'احتاج الزائر إلى جمع اكتشاف الوجهات وبناء الرحلة والتواصل مع المزود في تجربة واحدة، مع دور واضح لمقدم التجربة.',
                    'en' => 'Visitors needed destination discovery, itinerary building, and provider connection in one experience, with a clear role for experience providers.',
                ],
                'response' => [
                    'ar' => 'صُممت رحلتان مترابطتان للزائر والمزود، تدعمان البحث المنظم وتخطيط الرحلة وإدارة المشاركة في التجارب.',
                    'en' => 'Connected visitor and provider journeys supported structured discovery, trip planning, and participation in tourism experiences.',
                ],
                'outcome' => [
                    'ar' => 'مسار أوضح من الإلهام إلى رحلة منظمة، مع قناة مخصصة لمشاركة مزودي التجارب.',
                    'en' => 'A clearer path from inspiration to an organized trip, with a dedicated participation route for experience providers.',
                ],
                'lens' => 'product',
                'image' => 'images/projects/atlas/bosalty-tourism-journeys.webp',
                'alt' => ['ar' => 'تجربة بوصلتي لاكتشاف الوجهات وتخطيط الرحلات', 'en' => 'Bosalty destination discovery and trip planning experience'],
                'logo' => 'images/brands/projects/bosalty.webp',
                'logo_alt' => ['ar' => 'شعار بوصلتي', 'en' => 'Bosalty logo'],
                'tags' => [
                    ['ar' => 'تجربة سياحية', 'en' => 'Tourism experience'],
                    ['ar' => 'تخطيط الرحلات', 'en' => 'Trip planning'],
                    ['ar' => 'شبكة مزودين', 'en' => 'Provider network'],
                ],
            ],
            [
                'id' => '2060-investments',
                'title' => ['ar' => 'استثمارات عشرين ستين', 'en' => '2060 Investments'],
                'sector' => ['ar' => 'خدمات المساهمين', 'en' => 'Shareholder services'],
                'summary' => [
                    'ar' => 'بيئة رقمية منظمة لتداول الأسهم الداخلية بين المساهمين، مع متابعة الطلبات والتقارير وضبط الوصول.',
                    'en' => 'An organized digital environment for internal share trading among shareholders, with request tracking, reporting, and controlled access.',
                ],
                'challenge' => [
                    'ar' => 'احتاجت طلبات التداول الداخلية إلى شفافية أكبر، وتحقق واضح من الهوية، ومسار منضبط من الطلب إلى الإتمام.',
                    'en' => 'Internal trading requests needed greater transparency, clear identity checks, and an orderly path from request to completion.',
                ],
                'response' => [
                    'ar' => 'رُسمت دورة الطلب والتداول حول التحقق والحالات المتتابعة والمتابعة والتقارير التي تخدم المساهم وفريق التشغيل.',
                    'en' => 'The request and trading lifecycle was mapped around verification, clear statuses, follow-up, and reporting for shareholders and operators.',
                ],
                'outcome' => [
                    'ar' => 'طريقة أكثر وضوحاً وقابلية للتتبع لإدارة التعاملات الداخلية ومتابعتها من الطرفين.',
                    'en' => 'A more transparent and traceable way for shareholders and operators to manage internal transactions.',
                ],
                'lens' => 'operations',
                'image' => 'images/projects/atlas/investments-2060-shareholder-services.webp',
                'alt' => ['ar' => 'تجربة استثمارات عشرين ستين لخدمات المساهمين', 'en' => '2060 Investments shareholder services experience'],
                'logo' => 'images/brands/projects/2060.webp',
                'logo_alt' => ['ar' => 'شعار 2060', 'en' => '2060 logo'],
                'tags' => [
                    ['ar' => 'خدمات المساهمين', 'en' => 'Shareholder services'],
                    ['ar' => 'دورة التعامل', 'en' => 'Transaction workflow'],
                    ['ar' => 'وصول مضبوط', 'en' => 'Controlled access'],
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function lensesPayload(): array
    {
        return [
            [
                'id' => 'ai-adoption',
                'label' => ['ar' => 'تبنّي الذكاء الاصطناعي', 'en' => 'AI adoption'],
                'description' => [
                    'ar' => 'أين يحسّن الذكاء الاصطناعي قراراً أو سير عمل حقيقياً، وما الشروط اللازمة للثقة به.',
                    'en' => 'Where AI can improve a real decision or workflow, and what must be true for people to trust it.',
                ],
                'question' => [
                    'ar' => 'ما الذي ينبغي أن يدعمه الذكاء الاصطناعي، وأين يجب أن يبقى الإنسان مسيطراً؟',
                    'en' => 'What should AI support, and where should people stay in control?',
                ],
            ],
            [
                'id' => 'transformation',
                'label' => ['ar' => 'التحول', 'en' => 'Transformation'],
                'description' => [
                    'ar' => 'كيف يتحول العمل المتفرق إلى نموذج تشغيل رقمي مترابط وواضح المسؤوليات.',
                    'en' => 'How disconnected work becomes a coherent digital operating model with clear ownership.',
                ],
                'question' => [
                    'ar' => 'ما الذي يجب تغييره أولاً حتى يتحسن العمل كله؟',
                    'en' => 'What should change first so the whole operation improves?',
                ],
            ],
            [
                'id' => 'product',
                'label' => ['ar' => 'المنتج', 'en' => 'Product'],
                'description' => [
                    'ar' => 'كيف تتحول الخدمة إلى تجربة واضحة ومفيدة لكل طرف يشارك فيها.',
                    'en' => 'How a service becomes a clear, useful experience for every participant.',
                ],
                'question' => [
                    'ar' => 'ما الرحلة التي تجعل القيمة مفهومة وسهلة الاستخدام؟',
                    'en' => 'What journey makes the value understandable and easy to use?',
                ],
            ],
            [
                'id' => 'operations',
                'label' => ['ar' => 'التشغيل', 'en' => 'Operations'],
                'description' => [
                    'ar' => 'كيف ينتقل العمل من الطلب إلى التنفيذ باحتكاك أقل وملكية أوضح.',
                    'en' => 'How work moves from request to fulfilment with less friction and clearer ownership.',
                ],
                'question' => [
                    'ar' => 'أين يتعطل التدفق، وما الذي يجعل التنفيذ أكثر انتظاماً؟',
                    'en' => 'Where does the flow break, and what makes delivery more orderly?',
                ],
            ],
        ];
    }

    private static function usesStoredProjects(): bool
    {
        return Schema::hasTable('projects');
    }

    private static function localize(mixed $value, ?string $locale = null): mixed
    {
        $locale ??= app()->getLocale();
        $fallbackLocale = (string) config('app.fallback_locale', 'en');

        if (! is_array($value)) {
            return $value;
        }

        if (self::isLocalizedString($value)) {
            return (string) ($value[$locale] ?? $value[$fallbackLocale] ?? $value['en'] ?? $value['ar'] ?? '');
        }

        return array_map(fn (mixed $item): mixed => self::localize($item, $locale), $value);
    }

    /**
     * @param  array<array-key, mixed>  $value
     */
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
