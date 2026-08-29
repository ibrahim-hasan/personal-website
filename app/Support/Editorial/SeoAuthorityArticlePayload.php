<?php

namespace App\Support\Editorial;

final class SeoAuthorityArticlePayload
{
    public const string MODIFIED_AT = '2026-08-29';

    public function __construct(private readonly ArticleBody $articleBody) {}

    /**
     * Return the immutable bilingual editorial payload shipped by the SEO
     * authority migration. Future edits require a new forward migration.
     *
     * @return list<array<string, mixed>>
     */
    public function records(): array
    {
        return array_map(function (array $record): array {
            $body = [
                'ar' => $this->articleBody->toDocument($record['content']['ar']),
                'en' => $this->articleBody->toDocument($record['content']['en']),
            ];

            unset($record['content']);

            return [
                ...$record,
                'body' => $body,
                'read_minutes' => [
                    'ar' => $this->articleBody->readingMinutes($body['ar'], 'ar'),
                    'en' => $this->articleBody->readingMinutes($body['en'], 'en'),
                ],
            ];
        }, $this->definitions());
    }

    public function fingerprint(): string
    {
        return hash('sha256', $this->json($this->records()));
    }

    /** @return list<array<string, mixed>> */
    private function definitions(): array
    {
        return [
            $this->aiGovernance(),
            $this->firstAiUseCase(),
            $this->dataReadiness(),
            $this->aiAdoptionRoadmap(),
            $this->aiUseCaseRegister(),
            $this->dataGovernanceBeforeAi(),
            $this->digitalTransformationRoadmap(),
            $this->workflowAudit(),
        ];
    }

    /** @return array<string, mixed> */
    private function aiGovernance(): array
    {
        return [
            'operation' => 'update',
            'key' => 'ai-governance',
            'slug' => [
                'ar' => 'نموذج-تشغيلي-لحوكمة-الذكاء-الاصطناعي',
                'en' => 'a-practical-operating-model-for-ai-governance',
            ],
            'title' => [
                'ar' => 'حوكمة الذكاء الاصطناعي في السعودية: نموذج تشغيلي عملي لعام 2026',
                'en' => 'AI Governance in Saudi Arabia: A Practical Operating Model for 2026',
            ],
            'summary' => [
                'ar' => 'نموذج يربط سجل حالات الاستخدام وملكية القرار وتصنيف المخاطر وضوابط البيانات والأمن السيبراني بالمراجعة المستمرة، مستنداً إلى المصادر السعودية الرسمية المتاحة في 2026.',
                'en' => 'An operating model that connects the use-case register, decision ownership, risk tiers, data and cybersecurity controls, and continuous review using current Saudi official guidance.',
            ],
            'seo_title' => [
                'ar' => 'حوكمة الذكاء الاصطناعي في السعودية 2026',
                'en' => 'AI Governance in Saudi Arabia 2026: Operating Model',
            ],
            'seo_description' => [
                'ar' => 'دليل عملي لحوكمة الذكاء الاصطناعي في السعودية: سجل الحالات، تصنيف المخاطر، ضوابط البيانات والأمن، والمراجعة المستمرة في 2026.',
                'en' => 'A practical 2026 AI governance model for Saudi organizations: use-case register, risk tiers, data and cybersecurity controls, and continuous review.',
            ],
            'type' => ['ar' => 'دليل حوكمة', 'en' => 'Governance guide'],
            'published_at' => '2026-05-08',
            'image' => 'images/projects/atlas/rafid-humanitarian-collaboration.webp',
            'image_alt' => [
                'ar' => 'مساحة عمل رقمية تدعم الحوكمة والتعاون بين فرق متعددة',
                'en' => 'A digital workspace supporting governance and collaboration across teams',
            ],
            'image_caption' => ['ar' => '', 'en' => ''],
            'topic_keys' => [
                ArticleTopicClusters::AI_ADOPTION_GOVERNANCE,
                ArticleTopicClusters::DATA_KNOWLEDGE_SYSTEMS,
            ],
            'modified_at' => self::MODIFIED_AT,
            'featured' => false,
            'source_url' => null,
            'is_published' => true,
            'content' => [
                'ar' => <<<'HTML'
<p><strong>آخر تحديث: 29 أغسطس 2026.</strong> يقدّم هذا الدليل نموذجاً تشغيلياً لصنّاع القرار، وليس استشارة قانونية أو تفسيراً ملزماً للأنظمة. يجب على كل مؤسسة مراجعة التزاماتها مع المختصين والجهات الرسمية بحسب قطاعها وبياناتها وحالة الاستخدام.</p>
<p>حوكمة الذكاء الاصطناعي ليست لجنة توافق على الأدوات بعد شرائها، ولا وثيقة مبادئ منفصلة عن التشغيل. هي طريقة تجعل كل حالة استخدام معروفة، ومالكها محدداً، وبياناتها مفهومة، ومخاطرها مصنفة، وقرار استمرارها قابلاً للمراجعة. تبدأ الحوكمة قبل التجربة وتستمر بعد الإطلاق.</p>
<h2>ما الذي تغيّر في السياق السعودي خلال 2026؟</h2>
<p>يضع <a href="https://www.cst.gov.sa/knowledge-center/reports/ai-adoption-guide-for-tech-companies" target="_blank">دليل تبنّي الذكاء الاصطناعي للمنشآت التقنية الصادر عن هيئة الاتصالات والفضاء والتقنية</a> خمسة أبعاد للجاهزية: السياق، والبيانات، والبنية التحتية، والمهارات والخبرات، والثقافة. وهذه الأبعاد مفيدة لأنها تنقل النقاش من «أي نموذج نشتري؟» إلى «هل تستطيع المؤسسة تشغيل الاستخدام بأمان وتحقيق قيمة منه؟».</p>
<p>كما تنشر <a href="https://sdaia.gov.sa/en/MediaCenter/KnowledgeCenter/Pages/SDAIAPublications.aspx" target="_blank">سدايا ضمن مركز المعرفة</a> إطاراً وطنياً لإدارة مخاطر الذكاء الاصطناعي يغطي التعرف على المخاطر وتقييمها ومعالجتها ومراقبتها. وتعرض <a href="https://nca.gov.sa/en/news/2354/" target="_blank">الهيئة الوطنية للأمن السيبراني</a> إرشادات للأمن السيبراني للذكاء الاصطناعي ضمن أربعة مجالات: الحوكمة، والحماية، والصمود، ومخاطر الأطراف الخارجية. انتهت مرحلة الاستطلاع المعلنة؛ لذلك ينبغي التعامل مع النسخة الرسمية المنشورة وحالتها الحالية، لا وصفها بأنها استشارة مفتوحة.</p>
<h2>النموذج التشغيلي: ستة عناصر مترابطة</h2>
<ol>
<li><strong>سجل واحد لحالات الاستخدام:</strong> يشمل المشكلة، والمالك، والمستخدمين، والبيانات، والنموذج أو المورد، والقرار المتأثر، ومؤشر القيمة، وحالة الموافقة.</li>
<li><strong>تصنيف متناسب للمخاطر:</strong> منخفض، ومتوسط، ومرتفع، وفق حساسية البيانات، وأثر الخطأ، ودرجة الاستقلالية، وقابلية التراجع.</li>
<li><strong>بوابات قرار واضحة:</strong> انتقال من الفكرة إلى تجربة محدودة، ثم تحقق، ثم تشغيل، ثم توسع؛ ولكل بوابة أدلة مطلوبة وصاحب قرار.</li>
<li><strong>ضوابط بيانات وأمن:</strong> تحديد الغرض والحد الأدنى من البيانات والصلاحيات والاحتفاظ والسجلات واختبار المورد ومسار الاستجابة للحوادث.</li>
<li><strong>رقابة بشرية محددة:</strong> من يراجع؟ متى يستطيع إيقاف النتيجة؟ ما الدليل الذي يراه؟ وكيف يُسجل الاستثناء؟</li>
<li><strong>مراقبة بعد الإطلاق:</strong> جودة المخرجات، والأخطاء، والانحيازات الملحوظة، والتكلفة، وزمن المعالجة، والشكاوى، والتغييرات في النموذج أو البيانات.</li>
</ol>
<p>يمكن البدء من <a href="/services#service-ai-adoption">قسم هندسة تبنّي الذكاء الاصطناعي</a> لتصميم هذه العناصر حول حالة تشغيلية حقيقية، ومن <a href="/writing/اختيار-أول-حالة-استخدام-للذكاء-الاصطناعي">بطاقة القيمة مقابل الجدوى</a> لاختيار الحالة الأولى قبل توسيع السجل. وتعرض <a href="/work#project-rafid-360">رافد 360 ضمن الأعمال المختارة</a> سياق منصة تعاون متعددة الجهات، من دون افتراض ضوابط أو نتائج غير منشورة.</p>
<h2>سجل المخاطر العملي</h2>
<table>
<thead><tr><th>الخطر</th><th>سؤال الاكتشاف</th><th>الضابط الأول</th><th>دليل المراجعة</th></tr></thead>
<tbody>
<tr><td>بيانات غير مناسبة</td><td>هل تدخل بيانات أكثر مما يحتاجه الغرض؟</td><td>تقليل البيانات وفصل البيئات</td><td>خريطة الحقول والصلاحيات</td></tr>
<tr><td>نتيجة غير دقيقة</td><td>ما أثر إجابة خاطئة على العميل أو القرار؟</td><td>اختبار مرجعي ومراجعة بشرية</td><td>نتائج التقييم وسجل التصحيحات</td></tr>
<tr><td>تغيّر غير ملحوظ</td><td>هل تغير النموذج أو المصدر أو التعليمات؟</td><td>إدارة إصدارات وإعادة اختبار</td><td>سجل التغيير وخط أساس الجودة</td></tr>
<tr><td>اعتماد على مورد</td><td>هل يمكن نقل البيانات أو إيقاف الخدمة بأمان؟</td><td>خطة خروج وحدود تعاقدية</td><td>اختبار الاستعادة وقائمة التبعيات</td></tr>
<tr><td>صلاحيات مفرطة</td><td>هل يستطيع النظام تنفيذ فعل لا يمكن التراجع عنه؟</td><td>أقل صلاحية وموافقة قبل التنفيذ</td><td>سجل الأفعال ومحاولات المنع</td></tr>
</tbody>
</table>
<p>لا يكفي تسجيل «احتمال» و«أثر». أضف مالك الخطر، وإجراء المعالجة، وتاريخ الاستحقاق، والمؤشر الذي يكشف التدهور، والقرار إذا تجاوز المؤشر حده. عندها يصبح السجل أداة تشغيل لا ملف امتثال.</p>
<h2>قائمة تنفيذ خلال أربعة أسابيع</h2>
<ul>
<li><strong>الأسبوع الأول:</strong> احصر الاستخدامات الرسمية وغير الرسمية، وعيّن مالكاً لكل حالة، وأوقف إدخال البيانات الحساسة في أدوات غير معتمدة.</li>
<li><strong>الأسبوع الثاني:</strong> صنّف المخاطر، وارسم تدفق البيانات والصلاحيات، وحدد متطلبات الأمن والخصوصية والاحتفاظ.</li>
<li><strong>الأسبوع الثالث:</strong> ابنِ مجموعة تقييم، وحدد نقطة المراجعة البشرية، واختبر الفشل والاسترجاع والتصعيد.</li>
<li><strong>الأسبوع الرابع:</strong> فعّل لوحة مراقبة صغيرة، واجتماع مراجعة دوري، وسجل قرارات يوضح سبب الإطلاق أو التقييد أو الإيقاف.</li>
</ul>
<p>لضوابط البيانات راجع <a href="https://dgp.sdaia.gov.sa/wps/portal/pdp/knowledgecenter/" target="_blank">مركز المعرفة لحماية البيانات الشخصية</a>، ثم استخدم <a href="/writing/جاهزية-البيانات-قبل-الذكاء-الاصطناعي">جدول قرار جاهزية البيانات</a>. أما نقطة المراجعة البشرية فتفصّلها مقالة <a href="/writing/الإنسان-داخل-حلقة-القرار">الإنسان داخل حلقة القرار</a>.</p>
<h2>متى نسمح بالتوسع؟</h2>
<p>التوسع قرار أدلة: قيمة مستقرة، وجودة ضمن الحد المتفق عليه، ومخاطر معالجة، ومسؤولية تشغيلية واضحة، وتكلفة كلية مفهومة. لا تتوسع لأن العرض التجريبي أبهر الفريق. توسع عندما تستطيع المؤسسة شرح ما يفعله النظام، وما لا يفعله، وكيف تكتشف تدهوره، ومن يملك قرار إيقافه.</p>
<p>النموذج الجيد لا يبطئ كل تجربة بالطريقة نفسها. يمنح الحالات المنخفضة المخاطر مساراً سريعاً بضوابط خفيفة، ويرفع مستوى الإثبات كلما ارتفعت حساسية البيانات أو أثر القرار أو استقلالية النظام. هذه هي الحوكمة التي تحمي سرعة التعلم بدلاً من إلغائها.</p>
HTML,
                'en' => <<<'HTML'
<p><strong>Updated 29 August 2026.</strong> This guide is an operating model for decision-makers, not legal advice or an authoritative interpretation of regulation. Each organization should confirm its obligations with qualified specialists and the relevant official authorities for its sector, data, and use case.</p>
<p>AI governance is not a committee that approves tools after procurement, nor a principles document detached from operations. It is the system that makes every use case visible, assigns an owner, explains its data, classifies its risks, and creates a reviewable decision to continue, restrict, or stop it. Governance begins before a pilot and continues after launch.</p>
<h2>What changed in the Saudi context in 2026?</h2>
<p>The <a href="https://www.cst.gov.sa/en/knowledge-center/reports/ai-adoption-guide-for-tech-companies" target="_blank">CST AI Adoption Guide for Technology Companies</a> frames readiness across five dimensions: context, data, infrastructure, skills and expertise, and culture. That framing is useful because it moves the discussion from “Which model should we buy?” to “Can this organization operate the use case safely and create value from it?”</p>
<p><a href="https://sdaia.gov.sa/en/MediaCenter/KnowledgeCenter/Pages/SDAIAPublications.aspx" target="_blank">SDAIA's knowledge publications</a> also include a National AI Risk Management Framework covering risk identification, assessment, treatment, and monitoring. The <a href="https://nca.gov.sa/en/news/2354/" target="_blank">National Cybersecurity Authority's AI Cybersecurity Guidelines announcement</a> organizes controls across governance, defense, resilience, and third-party risk, including generative and agentic AI. The announced consultation period has ended, so teams should use the currently published official material rather than describe it as open consultation.</p>
<h2>The operating model: six connected parts</h2>
<ol>
<li><strong>One use-case register:</strong> record the problem, owner, users, data, model or vendor, affected decision, value measure, and approval state.</li>
<li><strong>Proportionate risk tiers:</strong> classify low, medium, or high risk using data sensitivity, consequence of error, autonomy, and reversibility.</li>
<li><strong>Explicit decision gates:</strong> move from idea to bounded pilot, validation, operation, and scale; require named evidence and a decision owner at each gate.</li>
<li><strong>Data and security controls:</strong> define purpose, minimum data, access, retention, logging, vendor assessment, and an incident path.</li>
<li><strong>Specified human oversight:</strong> decide who reviews, when they may reject or stop an output, what evidence they see, and how exceptions are recorded.</li>
<li><strong>Post-launch monitoring:</strong> watch output quality, errors, observed bias, cost, cycle time, complaints, and changes to the model, prompts, or sources.</li>
</ol>
<p>The <a href="/en/services#service-ai-adoption">AI adoption service</a> can shape these controls around a real operational decision. Before filling the register, use the <a href="/en/writing/choosing-your-first-measurable-ai-use-case">value-versus-feasibility scorecard</a> to select a bounded first case. <a href="/en/work#project-rafid-360">Rafid 360 in selected work</a> provides public context for a multi-organization collaboration platform without implying unpublished controls or results.</p>
<h2>A practical risk register</h2>
<table>
<thead><tr><th>Risk</th><th>Discovery question</th><th>First control</th><th>Review evidence</th></tr></thead>
<tbody>
<tr><td>Unsuitable data</td><td>Does the workflow send more data than the purpose needs?</td><td>Data minimization and environment separation</td><td>Field and access map</td></tr>
<tr><td>Inaccurate output</td><td>What happens to a customer or decision when an answer is wrong?</td><td>Reference evaluation and human review</td><td>Evaluation results and correction log</td></tr>
<tr><td>Unnoticed change</td><td>Did the model, source, or instruction change?</td><td>Version control and regression testing</td><td>Change log and quality baseline</td></tr>
<tr><td>Vendor dependency</td><td>Can data be exported and service stopped safely?</td><td>Exit plan and contractual limits</td><td>Recovery test and dependency list</td></tr>
<tr><td>Excessive permissions</td><td>Can the system perform an irreversible action?</td><td>Least privilege and approval before action</td><td>Action and denial logs</td></tr>
</tbody>
</table>
<p>Probability and impact are not enough. Add the risk owner, treatment, due date, leading indicator, and the decision to take if the indicator crosses its threshold. That turns a compliance spreadsheet into an operating instrument.</p>
<h2>A four-week implementation checklist</h2>
<ul>
<li><strong>Week one:</strong> inventory sanctioned and unsanctioned uses, assign an owner to each, and stop sensitive data entering unapproved tools.</li>
<li><strong>Week two:</strong> tier the risks, map data and permissions, and define privacy, cybersecurity, retention, and vendor requirements.</li>
<li><strong>Week three:</strong> build an evaluation set, specify the human-review point, and test failure, recovery, and escalation.</li>
<li><strong>Week four:</strong> activate a small monitoring view, a recurring review, and a decision log explaining launch, restriction, or retirement.</li>
</ul>
<p>For personal-data controls, consult the <a href="https://dgp.sdaia.gov.sa/wps/portal/pdp/knowledgecenter/" target="_blank">SDAIA Personal Data Protection knowledge center</a>, then apply the <a href="/en/writing/data-readiness-before-ai">data-readiness decision table</a>. For oversight design, see <a href="/en/writing/where-human-judgment-belongs-in-ai-workflows">where human judgment belongs in AI workflows</a>.</p>
<h2>When is scaling justified?</h2>
<p>Scale is an evidence decision: stable value, quality inside an agreed threshold, treated risks, clear operating ownership, and understood total cost. Do not scale because a demonstration impressed the room. Scale when the organization can explain what the system does, what it does not do, how degradation will be detected, and who can stop it.</p>
<p>A good model does not slow every experiment equally. It gives low-risk cases a fast path with light controls and raises the evidence bar as data sensitivity, decision consequence, or autonomy increases. That is governance that protects learning speed instead of eliminating it.</p>
HTML,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function firstAiUseCase(): array
    {
        return [
            'operation' => 'update',
            'key' => 'first-ai-use-case',
            'slug' => [
                'ar' => 'اختيار-أول-حالة-استخدام-للذكاء-الاصطناعي',
                'en' => 'choosing-your-first-measurable-ai-use-case',
            ],
            'title' => [
                'ar' => 'كيف تختار أول حالة استخدام للذكاء الاصطناعي؟ بطاقة القيمة مقابل الجدوى',
                'en' => 'How to Choose Your First AI Use Case: A Value-versus-Feasibility Scorecard',
            ],
            'summary' => [
                'ar' => 'بطاقة تقييم قابلة لإعادة الاستخدام تقارن قيمة المشكلة بجاهزية العملية والبيانات والمخاطر، ثم تحوّل الخيار الأفضل إلى تجربة محدودة قابلة للقياس.',
                'en' => 'A reusable scorecard that compares problem value with workflow, data, delivery, and risk feasibility, then turns the strongest candidate into a measurable bounded pilot.',
            ],
            'seo_title' => [
                'ar' => 'كيف تختار أول حالة استخدام للذكاء الاصطناعي؟',
                'en' => 'How to Choose Your First AI Use Case',
            ],
            'seo_description' => [
                'ar' => 'استخدم بطاقة القيمة مقابل الجدوى لترتيب حالات استخدام الذكاء الاصطناعي، وتجنب التجارب الواسعة، وصمّم تجربة أولى قابلة للقياس.',
                'en' => 'Use a value-versus-feasibility scorecard to rank AI use cases, avoid oversized pilots, and design a first experiment with measurable evidence.',
            ],
            'type' => ['ar' => 'أداة قرار', 'en' => 'Decision tool'],
            'published_at' => '2026-06-04',
            'image' => 'images/projects/atlas/maazim-gifting-operations.webp',
            'image_alt' => [
                'ar' => 'تجربة تشغيل رقمية توضح مسار طلب وخدمة مترابط',
                'en' => 'A digital operating experience showing a connected request and service journey',
            ],
            'image_caption' => ['ar' => '', 'en' => ''],
            'topic_keys' => [
                ArticleTopicClusters::AI_ADOPTION_GOVERNANCE,
                ArticleTopicClusters::PRODUCT_STRATEGY_MEASUREMENT,
            ],
            'modified_at' => self::MODIFIED_AT,
            'featured' => false,
            'source_url' => null,
            'is_published' => true,
            'content' => [
                'ar' => <<<'HTML'
<p><strong>آخر تحديث: 29 أغسطس 2026.</strong> أول حالة استخدام لا ينبغي أن تكون الأكثر إثارة، بل الحالة التي تستطيع المؤسسة أن تتعلم منها بسرعة من دون تعريض قرار حساس أو بيانات مهمة لمخاطر غير مفهومة.</p>
<p>تبدأ الفرق عادة بقائمة أفكار: مساعد للموظفين، وتلخيص للمستندات، وتنبؤ، وتصنيف طلبات، ووكيل ينفذ إجراءات. المشكلة ليست نقص الأفكار؛ بل غياب طريقة متسقة للمقارنة. بطاقة القيمة مقابل الجدوى تجعل النقاش قابلاً للفحص، ثم تساعد على تحديد ما يجب اختباره وما يجب تأجيله.</p>
<h2>ابدأ بالمشكلة لا بقدرة النموذج</h2>
<p>اكتب جملة واحدة تصف العمل الحالي: من ينفذ المهمة؟ ما المدخل؟ ما القرار أو المخرج؟ كم مرة يتكرر؟ وأين يظهر الهدر أو الخطأ؟ إذا لم تستطع وصف العملية الحالية فلن تستطيع معرفة إن كان الذكاء الاصطناعي حسّنها.</p>
<p>استبعد في الجولة الأولى الحالات التي يكون فيها الخطأ غير قابل للتراجع، أو لا يوجد لها مالك تشغيلي، أو تعتمد على بيانات غير مسموح باستخدامها، أو لا تملك نتيجة قابلة للقياس. يمكن إعادة تقييمها بعد بناء قدرة الحوكمة، لكنها ليست بيئة جيدة للتعلم الأول.</p>
<h2>بطاقة القيمة مقابل الجدوى</h2>
<p>قيّم كل بُعد من 1 إلى 5، واكتب دليلاً قصيراً للدرجة. لا تستخدم المتوسط لإخفاء مانع أساسي: أي درجة 1 في مشروعية الاستخدام أو قابلية التراجع أو ملكية العملية تتطلب معالجة قبل بدء التجربة.</p>
<table>
<thead><tr><th>البعد</th><th>السؤال</th><th>الوزن</th><th>دليل الدرجة</th></tr></thead>
<tbody>
<tr><td>قيمة المشكلة</td><td>هل تؤثر في وقت أو جودة أو تكلفة أو إيراد مهم؟</td><td>25%</td><td>خط أساس موثّق</td></tr>
<tr><td>تكرار الاستخدام</td><td>هل تتكرر المهمة بما يكفي لإنتاج تعلم وقيمة؟</td><td>10%</td><td>حجم أسبوعي أو شهري</td></tr>
<tr><td>جاهزية العملية</td><td>هل الخطوات والمالك والاستثناءات معروفة؟</td><td>15%</td><td>خريطة عملية</td></tr>
<tr><td>جاهزية البيانات</td><td>هل البيانات متاحة ومناسبة ومسموحاً بها؟</td><td>20%</td><td>عينة وخريطة حقول</td></tr>
<tr><td>سهولة الدمج</td><td>هل يمكن إدخال الحل دون إعادة بناء المنظومة؟</td><td>10%</td><td>نقطة تكامل واضحة</td></tr>
<tr><td>قابلية التراجع</td><td>هل يستطيع الإنسان رفض النتيجة أو استعادة المسار؟</td><td>10%</td><td>مسار بديل واختبار فشل</td></tr>
<tr><td>قابلية القياس</td><td>هل يوجد مؤشر نجاح ومؤشر حماية؟</td><td>10%</td><td>تعريفان قابلان للحساب</td></tr>
</tbody>
</table>
<p>احسب نتيجة مرجّحة للقيمة من أول بُعدين، ونتيجة للجدوى من بقية الأبعاد. استخدم النتيجة لترتيب النقاش، لا لإلغاء الحكم المهني. الحالة ذات القيمة العالية والجدوى العالية مرشح طبيعي. القيمة العالية والجدوى المنخفضة تحتاج عملاً تمهيدياً. الجدوى العالية والقيمة المنخفضة قد تصلح لتعلّم تقني صغير، لكنها ليست حجة للاستثمار.</p>
<h2>أربعة أرباع لاتخاذ القرار</h2>
<ul>
<li><strong>قيمة عالية، جدوى عالية:</strong> صمّم تجربة محدودة وابدأ.</li>
<li><strong>قيمة عالية، جدوى منخفضة:</strong> عالج البيانات أو العملية أو التكامل أولاً.</li>
<li><strong>قيمة منخفضة، جدوى عالية:</strong> نفّذ فقط إذا كانت تكلفة التعلم صغيرة وواضحة.</li>
<li><strong>قيمة منخفضة، جدوى منخفضة:</strong> أوقفها واحتفظ بسبب القرار في السجل.</li>
</ul>
<p>تساعدك مقالة <a href="/writing/متى-لا-يكون-الذكاء-الاصطناعي-هو-الحل">متى لا يكون الذكاء الاصطناعي هو الحل</a> على تمييز تحسين العملية أو الأتمتة التقليدية عن الحاجة إلى نموذج احتمالي. وإذا كانت البيانات هي العائق، استخدم <a href="/writing/جاهزية-البيانات-قبل-الذكاء-الاصطناعي">جدول جاهزية البيانات</a> قبل رفع درجة الجدوى.</p>
<h2>حوّل الخيار إلى تجربة محدودة</h2>
<ol>
<li>اختر شريحة مستخدمين ونوع طلب واحداً، ولا تبدأ بكل المؤسسة.</li>
<li>ثبّت خط الأساس: الزمن، أو نسبة الإكمال، أو الأخطاء، أو تكلفة الحالة.</li>
<li>حدد ما يفعله النظام وما يبقى بيد الإنسان ومسار التصعيد.</li>
<li>جهّز مجموعة حالات ممثلة تشمل الاستثناءات والفشل، لا الحالات السهلة فقط.</li>
<li>حدد مدة قصيرة وحجم عينة وقراراً نهائياً: توسيع، تعديل، أو إيقاف.</li>
</ol>
<p>مثال: بدلاً من «مساعد ذكي لخدمة العملاء»، اختبر «اقتراح مسودة رد لطلبات حالة الطلب بالعربية، يراجعها الموظف قبل الإرسال». يصبح المدخل والمستخدم ونقطة المراجعة ومؤشر الزمن وحد الخطأ واضحاً. هذا النطاق أفضل للتعلم من مشروع واسع لا يستطيع الفريق تفسير نتائجه. وتعرض <a href="/work#project-maazim">معازيم ضمن الأعمال المختارة</a> سياقاً عاماً لرحلة طلب وتوصيل يمكن من خلاله ملاحظة أهمية تضييق الموقف التشغيلي، من دون ادعاء استخدام ذكاء اصطناعي أو نتيجة غير منشورة.</p>
<h2>المؤشرات التي تمنع النجاح الوهمي</h2>
<p>استخدم مؤشر قيمة ومؤشري حماية على الأقل. قد يكون مؤشر القيمة انخفاض زمن معالجة الطلب. أما الحماية فتشمل نسبة التصحيح البشري، أو الإجابات غير المدعومة، أو التصعيدات، أو التكلفة لكل حالة. لا تعتمد على رضا المستخدم وحده، ولا على عرض تجريبي اختيرت حالاته مسبقاً.</p>
<p>سجّل النتيجة في <a href="/writing/من-تجربة-الذكاء-الاصطناعي-إلى-تحقيق-القيمة">مسار الانتقال من التجربة إلى القيمة</a>، واربطها بضوابط <a href="/writing/نموذج-تشغيلي-لحوكمة-الذكاء-الاصطناعي">حوكمة الذكاء الاصطناعي</a>. ولجلسة ترتيب عملية للحالات، راجع <a href="/services#service-ai-adoption">هندسة تبنّي الذكاء الاصطناعي</a>.</p>
HTML,
                'en' => <<<'HTML'
<p><strong>Updated 29 August 2026.</strong> Your first AI use case should not be the most theatrical idea. It should be the case from which the organization can learn quickly without exposing a sensitive decision or important data to poorly understood risk.</p>
<p>Teams usually begin with a crowded idea list: an employee assistant, document summaries, forecasting, request classification, or an agent that takes actions. The problem is not a lack of ideas; it is the absence of a consistent comparison method. A value-versus-feasibility scorecard makes the discussion inspectable and clarifies what to test, prepare, or defer.</p>
<h2>Start with the problem, not the model capability</h2>
<p>Write one sentence describing the current work. Who performs it? What is the input? What decision or output follows? How often does it happen? Where does delay, rework, or error appear? If the current workflow cannot be described, the team will not know whether AI improved it.</p>
<p>For the first round, exclude cases where errors are irreversible, no operational owner exists, required data is not permitted, or no measurable outcome is available. These cases can be reconsidered after governance capacity improves, but they are poor environments for the first learning cycle.</p>
<h2>The value-versus-feasibility scorecard</h2>
<p>Score each dimension from 1 to 5 and attach one short piece of evidence. Do not let an average conceal a hard stop: a score of 1 for lawful data use, reversibility, or process ownership requires treatment before a pilot begins.</p>
<table>
<thead><tr><th>Dimension</th><th>Question</th><th>Weight</th><th>Evidence</th></tr></thead>
<tbody>
<tr><td>Problem value</td><td>Does it affect meaningful time, quality, cost, or revenue?</td><td>25%</td><td>Documented baseline</td></tr>
<tr><td>Frequency</td><td>Does the task recur enough to create learning and value?</td><td>10%</td><td>Weekly or monthly volume</td></tr>
<tr><td>Workflow readiness</td><td>Are steps, owner, and exceptions understood?</td><td>15%</td><td>Process map</td></tr>
<tr><td>Data readiness</td><td>Is the data available, suitable, and permitted?</td><td>20%</td><td>Sample and field map</td></tr>
<tr><td>Integration ease</td><td>Can the capability enter the workflow without rebuilding it?</td><td>10%</td><td>Named integration point</td></tr>
<tr><td>Reversibility</td><td>Can a person reject the output or restore the path?</td><td>10%</td><td>Fallback and failure test</td></tr>
<tr><td>Measurability</td><td>Is there a success measure and a guardrail?</td><td>10%</td><td>Two computable definitions</td></tr>
</tbody>
</table>
<p>Calculate a weighted value result from the first two dimensions and a feasibility result from the rest. Use the score to order a discussion, not to replace judgment. High-value, high-feasibility work is the natural candidate. High value with low feasibility needs preparation. High feasibility with low value can support a small technical learning exercise, but it is not a business investment case.</p>
<h2>Four quadrants for the decision</h2>
<ul>
<li><strong>High value, high feasibility:</strong> design a bounded pilot and proceed.</li>
<li><strong>High value, low feasibility:</strong> fix data, workflow, ownership, or integration first.</li>
<li><strong>Low value, high feasibility:</strong> proceed only when learning cost is deliberately small.</li>
<li><strong>Low value, low feasibility:</strong> stop and preserve the reason in the register.</li>
</ul>
<p><a href="/en/writing/when-ai-is-not-the-answer">When AI is not the answer</a> helps distinguish process improvement or deterministic automation from a genuine need for a probabilistic model. When data is the barrier, use the <a href="/en/writing/data-readiness-before-ai">data-readiness decision table</a> before increasing the feasibility score.</p>
<h2>Turn the candidate into a bounded pilot</h2>
<ol>
<li>Choose one user group and one request type, not the entire organization.</li>
<li>Freeze the baseline: cycle time, completion, errors, or cost per case.</li>
<li>Define what the system does, what stays with a person, and the escalation path.</li>
<li>Build a representative evaluation set including exceptions and failure, not only easy examples.</li>
<li>Choose a short duration, sample size, and final decision: scale, revise, or stop.</li>
</ol>
<p>Instead of “an intelligent customer-service assistant,” test “draft an Arabic response to order-status requests for an employee to approve before sending.” The input, user, review point, time measure, and error limit are now visible. That scope produces better learning than a large project whose results the team cannot explain. <a href="/en/work#project-maazim">Maazim in selected work</a> offers public context for an order-and-delivery journey where operational scope matters, without claiming an AI implementation or unpublished result.</p>
<h2>Measures that prevent false success</h2>
<p>Use one value measure and at least two guardrails. The value measure could be lower handling time. Guardrails might include human correction rate, unsupported answers, escalations, or cost per case. Do not rely only on user satisfaction or on a demonstration whose examples were selected in advance.</p>
<p>Carry the result into the <a href="/en/writing/from-ai-experiment-to-business-value">experiment-to-value path</a> and connect it to the <a href="/en/writing/a-practical-operating-model-for-ai-governance">AI governance operating model</a>. For a structured prioritization session, use the <a href="/en/services#service-ai-adoption">AI adoption service</a>.</p>
HTML,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function dataReadiness(): array
    {
        return [
            'operation' => 'update',
            'key' => 'data-readiness',
            'slug' => [
                'ar' => 'جاهزية-البيانات-قبل-الذكاء-الاصطناعي',
                'en' => 'data-readiness-before-ai',
            ],
            'title' => [
                'ar' => 'جاهزية البيانات قبل الذكاء الاصطناعي: جدول قرار عملي',
                'en' => 'Data Readiness Before AI: A Practical Decision Table',
            ],
            'summary' => [
                'ar' => 'اختبار عملي يحدد إن كانت البيانات مناسبة لحالة الاستخدام من حيث الغرض والملكية والجودة والوصول والتقييم، وما العمل المطلوب قبل التجربة.',
                'en' => 'A practical test for whether data is fit for a specific AI use case across purpose, ownership, quality, access, and evaluation—and what to fix before a pilot.',
            ],
            'seo_title' => [
                'ar' => 'جاهزية البيانات قبل الذكاء الاصطناعي: جدول قرار',
                'en' => 'Data Readiness Before AI: Decision Table',
            ],
            'seo_description' => [
                'ar' => 'قيّم جاهزية البيانات للذكاء الاصطناعي عبر الغرض والملكية والجودة والصلاحيات والتقييم، وحدد هل تبدأ التجربة أم تعالج الأساس أولاً.',
                'en' => 'Assess AI data readiness across purpose, ownership, quality, access, and evaluation, then decide whether to pilot, prepare, or stop.',
            ],
            'type' => ['ar' => 'دليل بيانات', 'en' => 'Data guide'],
            'published_at' => '2026-06-20',
            'image' => 'images/projects/atlas/rafid-humanitarian-collaboration.webp',
            'image_alt' => [
                'ar' => 'مساحة تعاون رقمية تعرض سجلات وبيانات تشغيلية منظمة',
                'en' => 'A digital collaboration workspace showing structured operational records and data',
            ],
            'image_caption' => ['ar' => '', 'en' => ''],
            'topic_keys' => [
                ArticleTopicClusters::DATA_KNOWLEDGE_SYSTEMS,
                ArticleTopicClusters::AI_ADOPTION_GOVERNANCE,
            ],
            'modified_at' => self::MODIFIED_AT,
            'featured' => false,
            'source_url' => null,
            'is_published' => true,
            'content' => [
                'ar' => <<<'HTML'
<p><strong>آخر تحديث: 29 أغسطس 2026.</strong> لا توجد بيانات «جاهزة للذكاء الاصطناعي» بصورة مطلقة. الجاهزية مرتبطة بحالة استخدام محددة: قد تكون قاعدة المعرفة مناسبة للبحث الداخلي، لكنها غير مناسبة لقرار آلي يؤثر في عميل؛ وقد تكفي عينة صغيرة لاختبار الاسترجاع، لكنها لا تكفي لتدريب نموذج.</p>
<p>السؤال الصحيح ليس «هل لدينا بيانات كثيرة؟» بل «هل لدينا البيانات اللازمة لهذا الغرض، وهل نملك حق استخدامها، وهل نفهم جودتها وحدودها، وهل نستطيع قياس ما ينتجه النظام؟».</p>
<h2>حدّد عقد البيانات للحالة</h2>
<p>اكتب عقداً قصيراً قبل اختيار التقنية: الغرض، ومصدر البيانات، ومالكها، والحقول المستخدمة، ومن يستطيع الوصول، ومدة الاحتفاظ، والمخرج المتوقع، وطريقة التحقق. هذا العقد يكشف مبكراً إن كانت المشكلة نقص بيانات أم تضارب تعريفات أم ضعف عملية.</p>
<p>عند التعامل مع بيانات شخصية، راجع <a href="https://dgp.sdaia.gov.sa/wps/portal/pdp/knowledgecenter/" target="_blank">مركز المعرفة لحماية البيانات الشخصية لدى سدايا</a> ومبدأ ارتباط المعالجة بغرض واضح واستخدام الحد اللازم. هذا المقال أداة تشغيلية وليس استشارة قانونية؛ التحقق النظامي مسؤولية المؤسسة ومستشاريها المختصين.</p>
<h2>جدول قرار الجاهزية</h2>
<table>
<thead><tr><th>البعد</th><th>جاهز للتجربة</th><th>يحتاج معالجة</th><th>مانع مؤقت</th></tr></thead>
<tbody>
<tr><td>الغرض</td><td>قرار ومستخدم ومخرج محدد</td><td>هدف واسع بلا خط أساس</td><td>لا يوجد استخدام مشروع وواضح</td></tr>
<tr><td>الملكية</td><td>مالك معتمد ومصدر معروف</td><td>ملكية موزعة بلا حسم</td><td>مصدر مجهول أو غير مخول</td></tr>
<tr><td>الجودة</td><td>عينة ممثلة ومعدل خطأ معروف</td><td>نواقص يمكن قياسها</td><td>تعريفات متضاربة تمنع التفسير</td></tr>
<tr><td>الوصول</td><td>أقل صلاحية وسجل استخدام</td><td>صلاحيات أوسع من الحاجة</td><td>تصدير غير منضبط لبيانات حساسة</td></tr>
<tr><td>التغطية</td><td>تشمل الحالات المعتادة والاستثناءات</td><td>انحياز زمني أو لفئة واحدة</td><td>لا تمثل بيئة التشغيل</td></tr>
<tr><td>التقييم</td><td>إجابات مرجعية ومعيار قبول</td><td>مراجعة بشرية بلا معيار</td><td>لا توجد طريقة لمعرفة الخطأ</td></tr>
</tbody>
</table>
<p>إذا ظهرت خانة «مانع مؤقت»، لا تحاول تعويضها بنموذج أكبر. أصلح الحق في الاستخدام أو المصدر أو التعريف أو القياس. أما «يحتاج معالجة» فيمكن تحويله إلى مهمة محددة داخل تجربة قصيرة، بشرط أن يبقى أثر الخطأ محدوداً وقابلاً للتراجع.</p>
<h2>للبيانات المنظمة وأنظمة المعرفة مساران مختلفان</h2>
<p>في التحليل أو التنبؤ، تحتاج تعريفات متسقة وتاريخاً قابلاً للمقارنة وفهماً للقيم المفقودة والانحياز. أما في أنظمة المعرفة والاسترجاع المعزز بالتوليد، فالأولوية لموثوقية المصدر، وتجزئة المحتوى، والحداثة، والصلاحيات، والقدرة على إظهار الاستشهاد الصحيح.</p>
<p>لا يكفي أن يجد نظام المعرفة فقرة مشابهة. اختبر: هل أعاد المصدر الصحيح؟ هل احتفظ بسياق الفقرة؟ هل يرفض الإجابة عندما لا يوجد دليل؟ هل يمنع المستخدم من استرجاع مستند لا يحق له رؤيته؟ هذه قرارات منتج وحوكمة وليست إعدادات بحث فقط.</p>
<h2>مجموعة تقييم صغيرة قبل التكامل</h2>
<ol>
<li>اجمع 30 إلى 50 سؤالاً أو حالة تمثل العمل الفعلي، بما فيها الغامض والنادر.</li>
<li>عيّن جواباً أو نتيجة مرجعية ومصدراً معتمداً لكل حالة.</li>
<li>حدد الأخطاء الحرجة التي تستوجب الرفض مهما ارتفع المتوسط.</li>
<li>اختبر الاسترجاع والنتيجة والصلاحيات والتكلفة والزمن بصورة منفصلة.</li>
<li>احفظ النتائج مع الإصدار حتى يمكن اكتشاف التراجع بعد أي تغيير.</li>
</ol>
<p>إذا لم تستطع بناء هذه المجموعة، فغالباً لا تفهم القرار بما يكفي للتشغيل. ابدأ من <a href="/services#service-data-governance">حوكمة البيانات واستراتيجيتها</a> لترتيب الملكية والتعريفات، ثم استخدم <a href="/writing/اختيار-أول-حالة-استخدام-للذكاء-الاصطناعي">بطاقة اختيار حالة الاستخدام</a> لإعادة تقييم الجدوى.</p>
<h2>القرار النهائي: ابدأ، جهّز، أو أوقف</h2>
<ul>
<li><strong>ابدأ تجربة محدودة:</strong> الغرض والمالك والبيانات والتقييم واضحون، وأثر الخطأ محدود.</li>
<li><strong>جهّز الأساس أولاً:</strong> القيمة محتملة لكن الجودة أو الصلاحيات أو التعريفات تحتاج عملاً محدداً.</li>
<li><strong>أوقف الحالة:</strong> لا يوجد حق واضح للاستخدام، أو لا يمكن قياس الخطأ، أو الضرر غير قابل للتراجع.</li>
</ul>
<p>بعد قرار البدء، اربط البيانات بسجل المخاطر في <a href="/writing/نموذج-تشغيلي-لحوكمة-الذكاء-الاصطناعي">نموذج حوكمة الذكاء الاصطناعي</a>. ويعرض <a href="/work#project-rafid-360">رافد 360 ضمن الأعمال المختارة</a> سياقاً عاماً للتعاون والبيانات بين جهات متعددة من دون نسبة نتائج غير منشورة. الجاهزية ليست مشروع تنظيف لا ينتهي؛ إنها حد كفاية موثّق لحالة استخدام معروفة.</p>
HTML,
                'en' => <<<'HTML'
<p><strong>Updated 29 August 2026.</strong> Data is never “AI-ready” in the abstract. Readiness belongs to a specific use case. A knowledge base may be suitable for internal search but unsuitable for an automated customer decision. A small sample may be enough to test retrieval but nowhere near enough to train a model.</p>
<p>The useful question is not “Do we have a lot of data?” It is “Do we have the data required for this purpose, the right to use it, an understanding of its quality and limits, and a way to evaluate the system's result?”</p>
<h2>Define the use case's data contract</h2>
<p>Before selecting technology, write a short contract: purpose, source, owner, fields used, access, retention, expected output, and verification method. This exposes whether the real issue is missing data, conflicting definitions, weak ownership, or an unstable workflow.</p>
<p>Where personal data is involved, consult the <a href="https://dgp.sdaia.gov.sa/wps/portal/pdp/knowledgecenter/" target="_blank">SDAIA Personal Data Protection knowledge center</a>, including purpose and data-minimization considerations. This article is an operating tool, not legal advice; the organization and its qualified advisers remain responsible for regulatory assessment.</p>
<h2>The readiness decision table</h2>
<table>
<thead><tr><th>Dimension</th><th>Ready to pilot</th><th>Preparation needed</th><th>Temporary stop</th></tr></thead>
<tbody>
<tr><td>Purpose</td><td>Named decision, user, and output</td><td>Broad goal without a baseline</td><td>No clear legitimate use</td></tr>
<tr><td>Ownership</td><td>Approved owner and known source</td><td>Distributed ownership without resolution</td><td>Unknown or unauthorized source</td></tr>
<tr><td>Quality</td><td>Representative sample and known error rate</td><td>Measurable gaps</td><td>Conflicting definitions prevent interpretation</td></tr>
<tr><td>Access</td><td>Least privilege and usage logging</td><td>Permissions wider than needed</td><td>Uncontrolled export of sensitive data</td></tr>
<tr><td>Coverage</td><td>Normal cases and exceptions represented</td><td>Time or segment bias</td><td>Not representative of operations</td></tr>
<tr><td>Evaluation</td><td>Reference outcomes and acceptance threshold</td><td>Human review without a standard</td><td>No way to recognize an error</td></tr>
</tbody>
</table>
<p>If a temporary stop appears, do not compensate with a larger model. Fix the right to use, the source, the definition, or the measurement method. A “preparation needed” result can become a bounded task inside a short pilot when the consequence of error remains limited and reversible.</p>
<h2>Structured data and knowledge systems need different tests</h2>
<p>For analysis or prediction, you need consistent definitions, comparable history, and an explanation of missing values and bias. For knowledge systems and retrieval-augmented generation, the priority shifts to source authority, chunking, freshness, permissions, and the ability to show the correct citation.</p>
<p>Finding a similar paragraph is not enough. Test whether the system retrieved the authoritative source, preserved context, refused when evidence was absent, and prevented a user from retrieving a document they could not otherwise access. These are product and governance decisions, not merely search settings.</p>
<h2>Build a small evaluation set before integration</h2>
<ol>
<li>Collect 30 to 50 questions or cases from real work, including ambiguous and rare examples.</li>
<li>Assign an approved reference answer or outcome and source to each case.</li>
<li>Define critical failures that cause rejection regardless of the average score.</li>
<li>Test retrieval, output, permissions, cost, and latency separately.</li>
<li>Store results with the version so regression becomes visible after a change.</li>
</ol>
<p>If the team cannot build this set, it probably does not understand the decision well enough to operate it. Start with the <a href="/en/services#service-data-governance">data governance service</a> to resolve ownership and definitions, then revisit feasibility with the <a href="/en/writing/choosing-your-first-measurable-ai-use-case">use-case scorecard</a>.</p>
<h2>The final decision: pilot, prepare, or stop</h2>
<ul>
<li><strong>Pilot:</strong> purpose, owner, data, and evaluation are clear, and errors have limited consequence.</li>
<li><strong>Prepare first:</strong> value is plausible but quality, permissions, or definitions need a named intervention.</li>
<li><strong>Stop:</strong> there is no clear right to use the data, errors cannot be measured, or harm is irreversible.</li>
</ul>
<p>After a pilot decision, connect the data contract to the risk register in the <a href="/en/writing/a-practical-operating-model-for-ai-governance">AI governance operating model</a>. <a href="/en/work#project-rafid-360">Rafid 360 in selected work</a> provides public context for multi-organization collaboration and data without attributing unpublished outcomes. Readiness is not an endless cleaning program; it is a documented sufficiency threshold for a known use case.</p>
HTML,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function aiAdoptionRoadmap(): array
    {
        return [
            'operation' => 'create',
            'key' => 'ai-adoption-roadmap-saudi-companies-2026',
            'slug' => [
                'ar' => 'خارطة-طريق-تبني-الذكاء-الاصطناعي-للشركات-السعودية-2026',
                'en' => 'ai-adoption-roadmap-saudi-companies-2026',
            ],
            'title' => [
                'ar' => 'خارطة طريق تبنّي الذكاء الاصطناعي للشركات السعودية في 2026',
                'en' => 'AI Adoption Roadmap for Saudi Companies in 2026',
            ],
            'summary' => [
                'ar' => 'خارطة تنفيذ من ست مراحل تربط الأولويات وجاهزية البيانات والتجربة والحوكمة والتشغيل والقياس، كي تنتقل الشركة من الاهتمام بالذكاء الاصطناعي إلى قدرة مؤسسية منضبطة.',
                'en' => 'A six-stage roadmap connecting priorities, data readiness, pilots, governance, operations, and measurement so a company can move from AI interest to a controlled organizational capability.',
            ],
            'seo_title' => [
                'ar' => 'خارطة تبنّي الذكاء الاصطناعي للشركات السعودية 2026',
                'en' => 'AI Adoption Roadmap for Saudi Companies 2026',
            ],
            'seo_description' => [
                'ar' => 'خارطة عملية للشركات السعودية في 2026: تحديد الأولويات، تقييم الجاهزية، اختيار التجربة، بناء الحوكمة، التشغيل، وقياس القيمة.',
                'en' => 'A practical 2026 roadmap for Saudi companies: set priorities, assess readiness, choose a pilot, build governance, operate, and measure value.',
            ],
            'type' => ['ar' => 'دليل استراتيجي', 'en' => 'Strategic guide'],
            'published_at' => '2026-08-29',
            'modified_at' => self::MODIFIED_AT,
            'image' => 'images/ibrahim/workflow-map.png',
            'image_alt' => [
                'ar' => 'خارطة سير عمل تربط مراحل التبنّي والقرار والتنفيذ',
                'en' => 'A workflow map connecting adoption, decision, and delivery stages',
            ],
            'image_caption' => ['ar' => '', 'en' => ''],
            'topic_keys' => [
                ArticleTopicClusters::AI_ADOPTION_GOVERNANCE,
                ArticleTopicClusters::DIGITAL_TRANSFORMATION_OPERATIONS,
                ArticleTopicClusters::PRODUCT_STRATEGY_MEASUREMENT,
            ],
            'featured' => true,
            'source_url' => null,
            'is_published' => true,
            'content' => [
                'ar' => <<<'HTML'
<p><strong>نُشر في 29 أغسطس 2026.</strong> تبنّي الذكاء الاصطناعي ليس شراء تراخيص ثم البحث عن استخدام لها. هو انتقال مؤسسي من مشكلات واضحة إلى حالات استخدام منضبطة، ومن تجارب محدودة إلى تشغيل يمكن قياسه وإيقافه وتحسينه.</p>
<p>هذه الخارطة موجهة لصنّاع القرار في الشركات السعودية الذين يريدون سرعة عملية من دون تجاوز متطلبات البيانات والأمن والمسؤولية. وهي إطار تشغيلي وليست استشارة قانونية؛ يجب الرجوع إلى الجهات الرسمية والمختصين بحسب القطاع والحالة.</p>
<h2>المرحلة الأولى: عرّف هدف العمل وخط الأساس</h2>
<p>ابدأ بثلاثة إلى خمسة أهداف تشغيلية، مثل تقليل زمن معالجة طلب، أو رفع جودة إجابة، أو خفض إعادة العمل، أو تمكين الموظف من الوصول إلى معرفة موثوقة. لا تبدأ بهدف «استخدام الذكاء الاصطناعي». الهدف الجيد يصف النتيجة ومن يملكها وكيف تُقاس اليوم.</p>
<ul>
<li>اختر راعياً تنفيذياً ومالكاً تشغيلياً لكل هدف.</li>
<li>وثّق الزمن والحجم والأخطاء والتكلفة قبل أي تجربة.</li>
<li>سجّل القيود: بيانات، تكامل، مهارات، ميزانية، وتوقيت.</li>
</ul>
<p>يتوافق هذا البدء مع أبعاد الجاهزية الخمسة في <a href="https://www.cst.gov.sa/knowledge-center/reports/ai-adoption-guide-for-tech-companies" target="_blank">دليل هيئة الاتصالات والفضاء والتقنية لتبنّي الذكاء الاصطناعي</a>: السياق، والبيانات، والبنية التحتية، والمهارات والخبرات، والثقافة.</p>
<h2>المرحلة الثانية: ابنِ سجل الفرص ورتّبها</h2>
<p>اجمع الحالات من فرق الأعمال والتقنية والبيانات وخدمة العملاء، ثم اكتب لكل حالة المشكلة والمستخدم والقرار والبيانات والنتيجة المتوقعة. استخدم <a href="/writing/اختيار-أول-حالة-استخدام-للذكاء-الاصطناعي">بطاقة القيمة مقابل الجدوى</a> بدلاً من التصويت على الفكرة الأكثر جاذبية.</p>
<table>
<thead><tr><th>نوع الحالة</th><th>بداية مناسبة</th><th>إشارة تحذير</th></tr></thead>
<tbody>
<tr><td>مساعدة الموظف</td><td>مسودة يراجعها المستخدم</td><td>إرسال أو اعتماد تلقائي</td></tr>
<tr><td>نظام معرفة</td><td>مصادر محددة مع استشهاد</td><td>إجابة بلا دليل أو صلاحيات</td></tr>
<tr><td>تصنيف أو توجيه</td><td>اقتراح مع مسار يدوي</td><td>رفض خدمة بلا مراجعة</td></tr>
<tr><td>وكيل ينفذ أفعالاً</td><td>بيئة محدودة وموافقة قبل الفعل</td><td>صلاحيات واسعة أو أثر غير قابل للتراجع</td></tr>
</tbody>
</table>
<h2>المرحلة الثالثة: افحص البيانات والعملية</h2>
<p>حدد مصادر البيانات ومالك كل مصدر والغرض من الاستخدام والصلاحيات والاحتفاظ. افصل بين بيانات التشغيل، والمحتوى المعرفي، وبيانات التقييم. لا تعتبر مستودع الملفات قاعدة معرفة قبل معالجة النسخ والتعارض والحداثة والوصول.</p>
<p>استخدم <a href="/writing/جاهزية-البيانات-قبل-الذكاء-الاصطناعي">جدول قرار جاهزية البيانات</a>، وراجع <a href="https://dgp.sdaia.gov.sa/wps/portal/pdp/knowledgecenter/" target="_blank">مركز سدايا لحماية البيانات الشخصية</a> عندما تشمل الحالة بيانات شخصية. القرار في نهاية المرحلة هو: جاهز لتجربة محدودة، أو يحتاج عملاً تمهيدياً، أو موقوف مؤقتاً.</p>
<h2>المرحلة الرابعة: نفّذ تجربة لها قرار نهاية</h2>
<p>حدد مستخدمين وحجماً ومدة ومجموعة تقييم. اختبر الحالات العادية والاستثناءات والفشل. قارن بالخط الأساسي، وسجّل تكلفة التشغيل ونسبة التصحيح والتصعيد، لا دقة مختبرية معزولة فقط.</p>
<ol>
<li>جمّد النطاق والإصدار ومصادر البيانات.</li>
<li>حدد نقطة المراجعة البشرية ومسار التراجع.</li>
<li>راقب مؤشراً للقيمة ومؤشرين للحماية على الأقل.</li>
<li>اختم التجربة بقرار موثّق: توسيع أو تعديل أو إيقاف.</li>
</ol>
<p>يعرض <a href="/work#project-digi-pedia">Digi Pedia ضمن الأعمال المختارة</a> سياقاً عاماً لمنتج تعلّم الذكاء الاصطناعي بالعربية، من دون افتراض نتائج أو مقاييس غير منشورة. استخدم صفحات العمل لفهم طبيعة المنتج فقط، لا كبديل عن أدلة تجربتك.</p>
<h2>المرحلة الخامسة: ضع الحوكمة داخل سير العمل</h2>
<p>أنشئ سجلاً موحداً، وتصنيفاً للمخاطر، وبوابات موافقة، ومالكاً لكل خطر، وسجلات تغيير وحوادث. يوضح <a href="/writing/نموذج-تشغيلي-لحوكمة-الذكاء-الاصطناعي">نموذج حوكمة الذكاء الاصطناعي</a> قائمة التنفيذ وسجل المخاطر.</p>
<p>استخدم <a href="https://sdaia.gov.sa/en/MediaCenter/KnowledgeCenter/Pages/SDAIAPublications.aspx" target="_blank">منشورات سدايا الرسمية</a> لإدارة مخاطر الذكاء الاصطناعي، وراجع <a href="https://nca.gov.sa/en/news/2354/" target="_blank">إرشادات الهيئة الوطنية للأمن السيبراني للذكاء الاصطناعي</a> في الحوكمة والحماية والصمود والأطراف الخارجية. حالة الوثائق قد تتغير؛ تحقّق دائماً من النسخة الرسمية الحالية.</p>
<h2>المرحلة السادسة: شغّل القدرة وقِس القيمة</h2>
<p>التشغيل يحتاج مالك خدمة، وميزانية، ودعماً، ومراقبة جودة، وإدارة إصدارات، وخطة خروج من المورد. افصل مؤشر التبنّي عن مؤشر القيمة: كثرة الاستخدام لا تعني أثراً أفضل. قارِن الأداء بخط الأساس وبالتكلفة الكلية وبحدود المخاطر.</p>
<ul>
<li><strong>شهرياً:</strong> راجع الجودة والتكلفة والحوادث والتصحيحات.</li>
<li><strong>ربع سنوياً:</strong> أعد تقييم قيمة الحالة وبدائلها ومخاطر المورد.</li>
<li><strong>عند أي تغيير:</strong> أعد اختبار مجموعة التقييم قبل التعميم.</li>
</ul>
<p>يمكن تنفيذ الخارطة عبر <a href="/services#service-ai-adoption">هندسة تبنّي الذكاء الاصطناعي</a> مع دعم <a href="/services#service-data-governance">حوكمة البيانات</a> و<a href="/services#service-systems">هندسة الأنظمة والأتمتة</a>. النجاح ليس إطلاق أكبر عدد من الحالات، بل إيقاف الحالات الضعيفة مبكراً وتوسيع الحالات التي تثبت قيمة منضبطة.</p>
HTML,
                'en' => <<<'HTML'
<p><strong>Published 29 August 2026.</strong> AI adoption is not buying licenses and then searching for a use. It is an organizational transition from clear problems to controlled use cases, and from bounded experiments to operations that can be measured, stopped, and improved.</p>
<p>This roadmap is for decision-makers in Saudi companies who want practical speed without bypassing data, cybersecurity, or accountability. It is an operating framework, not legal advice; consult current official sources and qualified specialists for the sector and use case.</p>
<h2>Stage one: define the business outcome and baseline</h2>
<p>Begin with three to five operational outcomes, such as shorter request handling, better answer quality, less rework, or faster access to reliable knowledge. Do not begin with “use AI.” A good outcome names the result, its owner, and how it is measured today.</p>
<ul>
<li>Assign an executive sponsor and operational owner to each outcome.</li>
<li>Document time, volume, errors, and cost before any pilot.</li>
<li>Record constraints across data, integration, skills, budget, and timing.</li>
</ul>
<p>This starting point aligns with the five readiness dimensions in the <a href="https://www.cst.gov.sa/en/knowledge-center/reports/ai-adoption-guide-for-tech-companies" target="_blank">CST AI Adoption Guide</a>: context, data, infrastructure, skills and expertise, and culture.</p>
<h2>Stage two: build and prioritize an opportunity register</h2>
<p>Gather cases from business, technology, data, and customer-service teams. For each, record the problem, user, affected decision, required data, and expected outcome. Apply the <a href="/en/writing/choosing-your-first-measurable-ai-use-case">value-versus-feasibility scorecard</a> instead of voting for the most attractive demonstration.</p>
<table>
<thead><tr><th>Use-case type</th><th>Sensible starting point</th><th>Warning sign</th></tr></thead>
<tbody>
<tr><td>Employee assistance</td><td>A draft the user reviews</td><td>Automatic sending or approval</td></tr>
<tr><td>Knowledge system</td><td>Bounded sources with citations</td><td>Answers without evidence or permissions</td></tr>
<tr><td>Classification or routing</td><td>Suggestion with a manual path</td><td>Service denial without review</td></tr>
<tr><td>Action-taking agent</td><td>Bounded environment and approval</td><td>Broad permissions or irreversible impact</td></tr>
</tbody>
</table>
<h2>Stage three: examine data and workflow readiness</h2>
<p>Name data sources, owners, purpose, access, and retention. Separate operational data, knowledge content, and evaluation data. A document repository is not a knowledge base until duplicates, contradictions, freshness, and permissions are addressed.</p>
<p>Use the <a href="/en/writing/data-readiness-before-ai">data-readiness decision table</a>, and consult the <a href="https://dgp.sdaia.gov.sa/wps/portal/pdp/knowledgecenter/" target="_blank">SDAIA Personal Data Protection knowledge center</a> when personal data is involved. The stage ends with one decision: ready for a bounded pilot, preparation required, or temporarily stopped.</p>
<h2>Stage four: run a pilot with an end decision</h2>
<p>Set users, volume, duration, and an evaluation set. Test normal cases, exceptions, and failure. Compare with the baseline, and capture operating cost, correction rate, and escalation—not only an isolated laboratory accuracy measure.</p>
<ol>
<li>Freeze scope, version, and data sources.</li>
<li>Specify human review and the fallback path.</li>
<li>Monitor one value measure and at least two guardrails.</li>
<li>Close with a documented decision: scale, revise, or stop.</li>
</ol>
<p><a href="/en/work#project-digi-pedia">Digi Pedia in selected work</a> provides public context for an Arabic AI learning product without implying unpublished results or metrics. Use work pages to understand product context, not as a substitute for evidence from your own pilot.</p>
<h2>Stage five: place governance inside the workflow</h2>
<p>Create a single register, risk tiers, approval gates, a named owner for each risk, and change and incident logs. The <a href="/en/writing/a-practical-operating-model-for-ai-governance">AI governance operating model</a> provides the implementation checklist and risk-register structure.</p>
<p>Use <a href="https://sdaia.gov.sa/en/MediaCenter/KnowledgeCenter/Pages/SDAIAPublications.aspx" target="_blank">SDAIA's official publications</a> for AI risk management, and review the <a href="https://nca.gov.sa/en/news/2354/" target="_blank">NCA AI Cybersecurity Guidelines announcement</a> for governance, defense, resilience, and third-party considerations. Document status can change; always verify the current official version.</p>
<h2>Stage six: operate the capability and measure value</h2>
<p>Operation needs a service owner, budget, support, quality monitoring, version management, and a vendor exit path. Separate adoption from value: frequent use does not prove a better result. Compare performance with the baseline, total cost, and risk limits.</p>
<ul>
<li><strong>Monthly:</strong> review quality, cost, incidents, and human corrections.</li>
<li><strong>Quarterly:</strong> reassess use-case value, alternatives, and vendor risk.</li>
<li><strong>On every material change:</strong> rerun the evaluation set before rollout.</li>
</ul>
<p>The roadmap can be delivered through <a href="/en/services#service-ai-adoption">AI adoption engineering</a>, supported by <a href="/en/services#service-data-governance">data governance</a> and <a href="/en/services#service-systems">systems and automation architecture</a>. Success is not launching the most cases. It is stopping weak cases early and scaling those that prove controlled value.</p>
HTML,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function aiUseCaseRegister(): array
    {
        return [
            'operation' => 'create',
            'key' => 'ai-use-case-register-governance-template',
            'slug' => [
                'ar' => 'سجل-حالات-استخدام-الذكاء-الاصطناعي-وقالب-الحوكمة',
                'en' => 'ai-use-case-register-governance-template',
            ],
            'title' => [
                'ar' => 'سجل حالات استخدام الذكاء الاصطناعي وقالب الحوكمة',
                'en' => 'AI Use-Case Register and Governance Template',
            ],
            'summary' => [
                'ar' => 'قالب تشغيلي لحصر حالات الاستخدام وترتيبها وتصنيف مخاطرها وربط كل انتقال بدليل ومالك وقرار واضح.',
                'en' => 'An operating template for inventorying, prioritizing, risk-tiering, and governing AI use cases through evidence, ownership, and explicit gates.',
            ],
            'seo_title' => [
                'ar' => 'قالب سجل حالات استخدام الذكاء الاصطناعي والحوكمة',
                'en' => 'AI Use-Case Register and Governance Template',
            ],
            'seo_description' => [
                'ar' => 'قالب عملي لسجل حالات استخدام الذكاء الاصطناعي: الحقول، تصنيف المخاطر، بوابات القرار، أدلة الموافقة، والمراجعة الدورية.',
                'en' => 'A practical AI use-case register template covering fields, risk tiers, decision gates, approval evidence, and recurring review.',
            ],
            'type' => ['ar' => 'قالب عملي', 'en' => 'Working template'],
            'published_at' => '2026-10-14',
            'modified_at' => self::MODIFIED_AT,
            'image' => 'images/ibrahim/product-systems.png',
            'image_alt' => [
                'ar' => 'رسم لنظام مترابط يوضح مكونات وحالات استخدام متعددة',
                'en' => 'An interconnected system diagram showing multiple components and use cases',
            ],
            'image_caption' => ['ar' => '', 'en' => ''],
            'topic_keys' => [ArticleTopicClusters::AI_ADOPTION_GOVERNANCE],
            'featured' => false,
            'source_url' => null,
            'is_published' => false,
            'content' => [
                'ar' => <<<'HTML'
<p>لا تستطيع المؤسسة حوكمة ما لا تستطيع رؤيته. سجل حالات استخدام الذكاء الاصطناعي هو المصدر التشغيلي الواحد الذي يجمع الأفكار والتجارب والأنظمة العاملة، ويبيّن لماذا بدأت كل حالة ومن يملكها وما بياناتها وما القرار التالي.</p>
<p>القالب التالي ليس استشارة قانونية، ولا يغني عن تقييم المتطلبات الرسمية والقطاعية. صمّمه بما يناسب المؤسسة، واستند إلى <a href="https://sdaia.gov.sa/en/MediaCenter/KnowledgeCenter/Pages/SDAIAPublications.aspx" target="_blank">منشورات سدايا الرسمية</a> و<a href="https://nca.gov.sa/en/news/2354/" target="_blank">إرشادات الأمن السيبراني للذكاء الاصطناعي لدى الهيئة الوطنية للأمن السيبراني</a> عند تحديد ضوابط المخاطر.</p>
<h2>الحد الأدنى من حقول السجل</h2>
<table>
<thead><tr><th>المجموعة</th><th>الحقول المطلوبة</th><th>سبب وجودها</th></tr></thead>
<tbody>
<tr><td>الهوية</td><td>معرّف، اسم، وصف المشكلة، الحالة</td><td>منع التكرار وتتبع القرار</td></tr>
<tr><td>الملكية</td><td>راعٍ، مالك عمل، مالك تقني، مراجع مخاطر</td><td>تحديد المسؤولية لا الحضور فقط</td></tr>
<tr><td>القيمة</td><td>خط أساس، مؤشر قيمة، هدف، حجم الاستخدام</td><td>تمييز الأثر عن النشاط</td></tr>
<tr><td>البيانات</td><td>مصادر، تصنيف، غرض، صلاحيات، احتفاظ</td><td>منع الاستخدام غير الضروري أو غير المصرح</td></tr>
<tr><td>الحل</td><td>نموذج أو مورد، إصدار، تكامل، صلاحيات أفعال</td><td>فهم التبعيات وسطح المخاطر</td></tr>
<tr><td>الرقابة</td><td>مراجع بشري، تصعيد، تراجع، سجل تغيير</td><td>إبقاء القرار قابلاً للضبط</td></tr>
<tr><td>الدليل</td><td>مجموعة تقييم، نتائج، حوادث، تكلفة، قرار البوابة</td><td>جعل التوسع قابلاً للمراجعة</td></tr>
</tbody>
</table>
<h2>تصنيف مخاطر بسيط ومتناسق</h2>
<p>امنح الحالة مستوى أعلى إذا استخدمت بيانات أكثر حساسية، أو أثّر الخطأ في حق أو خدمة مهمة، أو نفّذ النظام فعلاً بصورة مستقلة، أو تعذر التراجع. لا تخفض المستوى لمجرد أن المورد مشهور أو أن التجربة نجحت في عرض قصير.</p>
<ul>
<li><strong>منخفض:</strong> مسودة داخلية، بيانات غير حساسة، مراجعة قبل الاستخدام، وتراجع سهل.</li>
<li><strong>متوسط:</strong> تأثير تشغيلي واضح أو بيانات مقيدة أو اعتماد متكرر يحتاج مراقبة.</li>
<li><strong>مرتفع:</strong> قرار مؤثر، أو بيانات حساسة، أو فعل مستقل، أو ضرر يصعب تداركه.</li>
</ul>
<h2>بوابات القرار الخمس</h2>
<ol>
<li><strong>فكرة إلى تقييم:</strong> مشكلة ومالك وخط أساس، ثم درجة من <a href="/writing/اختيار-أول-حالة-استخدام-للذكاء-الاصطناعي">بطاقة القيمة مقابل الجدوى</a>.</li>
<li><strong>تقييم إلى تجربة:</strong> غرض البيانات، وتصنيف المخاطر، ونطاق محدود، ومجموعة تقييم.</li>
<li><strong>تجربة إلى تحقق:</strong> نتيجة مقارنة بخط الأساس، وأخطاء حرجة، وتكلفة، وملاحظات المستخدم.</li>
<li><strong>تحقق إلى تشغيل:</strong> مالك خدمة، ومراقبة، ودعم، وخطة فشل وخروج من المورد.</li>
<li><strong>تشغيل إلى توسع أو تقاعد:</strong> اتجاه قيمة مستقر، ومخاطر ضمن الحدود، وقرار موثّق.</li>
</ol>
<p>لكل بوابة ثلاثة عناصر لا تُحذف: الأدلة المطلوبة، وصاحب القرار، وتاريخ القرار. عبارة «تمت الموافقة» من دون هذه العناصر لا تُنتج مساءلة.</p>
<h2>لوحة مراجعة شهرية</h2>
<table>
<thead><tr><th>السؤال</th><th>المؤشر</th><th>قرار محتمل</th></tr></thead>
<tbody>
<tr><td>هل ما زالت القيمة موجودة؟</td><td>الفرق عن خط الأساس</td><td>استمرار أو إعادة تصميم</td></tr>
<tr><td>هل تدهورت الجودة؟</td><td>القبول والتصحيح والفشل الحرج</td><td>إيقاف إصدار أو إعادة اختبار</td></tr>
<tr><td>هل تغيرت المخاطر؟</td><td>بيانات أو صلاحيات أو مورد جديد</td><td>رفع التصنيف وإضافة ضابط</td></tr>
<tr><td>هل التكلفة مفهومة؟</td><td>تكلفة الحالة والدعم والمراجعة</td><td>تحسين أو تقاعد</td></tr>
</tbody>
</table>
<p>اربط السجل بـ<a href="/writing/نموذج-تشغيلي-لحوكمة-الذكاء-الاصطناعي">نموذج الحوكمة التشغيلي</a>، ولا تجعله جدولاً يملؤه فريق المخاطر وحده. مالك العمل مسؤول عن القيمة، ومالك التقنية عن التشغيل، ووظائف البيانات والأمن والخصوصية عن الضوابط المتخصصة.</p>
<h2>طريقة البدء هذا الأسبوع</h2>
<p>ابدأ بعشر حالات فقط: كل ما هو عامل، وكل تجربة نشطة، وأهم الأفكار المقترحة. اكشف الاستخدام غير الرسمي من دون لوم، ثم صنّف المخاطر وحدد الحالات التي تحتاج إيقاف إدخال بيانات حتى تتم المراجعة. يقدّم <a href="/work#project-digi-pedia">Digi Pedia ضمن الأعمال المختارة</a> سياقاً عاماً لمنتج تعلّم الذكاء الاصطناعي بالعربية من دون افتراض تفاصيل حوكمة غير منشورة. بعد ذلك طبّق <a href="/services#service-ai-adoption">هندسة تبنّي الذكاء الاصطناعي</a> لبناء دورة قرار مستمرة بدلاً من حملة حصر مؤقتة.</p>
HTML,
                'en' => <<<'HTML'
<p>An organization cannot govern what it cannot see. The AI use-case register is the single operating source for proposed ideas, active pilots, and production systems. It explains why each case exists, who owns it, what data it uses, and which decision comes next.</p>
<p>This template is not legal advice and does not replace official or sector-specific assessment. Adapt it to the organization, using <a href="https://sdaia.gov.sa/en/MediaCenter/KnowledgeCenter/Pages/SDAIAPublications.aspx" target="_blank">SDAIA's official publications</a> and the <a href="https://nca.gov.sa/en/news/2354/" target="_blank">NCA AI Cybersecurity Guidelines announcement</a> when defining risk controls.</p>
<h2>The minimum register fields</h2>
<table>
<thead><tr><th>Group</th><th>Required fields</th><th>Why it exists</th></tr></thead>
<tbody>
<tr><td>Identity</td><td>ID, name, problem statement, state</td><td>Prevent duplication and trace decisions</td></tr>
<tr><td>Ownership</td><td>Sponsor, business owner, technical owner, risk reviewer</td><td>Assign responsibility, not attendance</td></tr>
<tr><td>Value</td><td>Baseline, value measure, target, usage volume</td><td>Distinguish impact from activity</td></tr>
<tr><td>Data</td><td>Sources, classification, purpose, access, retention</td><td>Prevent unnecessary or unauthorized use</td></tr>
<tr><td>Solution</td><td>Model or vendor, version, integration, action permissions</td><td>Understand dependencies and risk surface</td></tr>
<tr><td>Oversight</td><td>Human reviewer, escalation, fallback, change log</td><td>Keep the decision controllable</td></tr>
<tr><td>Evidence</td><td>Evaluation set, results, incidents, cost, gate decision</td><td>Make scaling reviewable</td></tr>
</tbody>
</table>
<h2>A simple, consistent risk tier</h2>
<p>Raise the tier when data is more sensitive, errors affect an important right or service, the system acts independently, or reversal is difficult. Do not lower it because the vendor is well known or a short demonstration succeeded.</p>
<ul>
<li><strong>Low:</strong> internal draft, non-sensitive data, review before use, and easy reversal.</li>
<li><strong>Medium:</strong> material operational effect, restricted data, or repeated dependence requiring monitoring.</li>
<li><strong>High:</strong> consequential decision, sensitive data, autonomous action, or difficult-to-repair harm.</li>
</ul>
<h2>The five decision gates</h2>
<ol>
<li><strong>Idea to assessment:</strong> problem, owner, baseline, and a result from the <a href="/en/writing/choosing-your-first-measurable-ai-use-case">value-versus-feasibility scorecard</a>.</li>
<li><strong>Assessment to pilot:</strong> data purpose, risk tier, bounded scope, and evaluation set.</li>
<li><strong>Pilot to validation:</strong> baseline comparison, critical failures, cost, and user observations.</li>
<li><strong>Validation to operation:</strong> service owner, monitoring, support, failure path, and vendor exit plan.</li>
<li><strong>Operation to scale or retirement:</strong> stable value trend, risk inside limits, and a documented decision.</li>
</ol>
<p>Every gate needs three elements: required evidence, decision owner, and decision date. “Approved” without them does not create accountability.</p>
<h2>A monthly review board</h2>
<table>
<thead><tr><th>Question</th><th>Measure</th><th>Possible decision</th></tr></thead>
<tbody>
<tr><td>Does value still exist?</td><td>Difference from baseline</td><td>Continue or redesign</td></tr>
<tr><td>Has quality degraded?</td><td>Acceptance, correction, and critical failure</td><td>Stop a version or retest</td></tr>
<tr><td>Did risk change?</td><td>New data, permissions, or vendor</td><td>Raise tier and add a control</td></tr>
<tr><td>Is cost understood?</td><td>Cost per case, support, and review</td><td>Optimize or retire</td></tr>
</tbody>
</table>
<p>Connect the register to the <a href="/en/writing/a-practical-operating-model-for-ai-governance">AI governance operating model</a>. Do not make it a spreadsheet owned only by risk teams. The business owner owns value, technology owns operation, and data, cybersecurity, privacy, and legal functions own their specialist controls.</p>
<h2>How to start this week</h2>
<p>Begin with ten cases: everything in production, every active pilot, and the most important proposed ideas. Surface unsanctioned use without blame, tier the risks, and identify cases where data entry should pause pending review. <a href="/en/work#project-digi-pedia">Digi Pedia in selected work</a> provides public context for an Arabic AI learning product without implying unpublished governance details. Then use <a href="/en/services#service-ai-adoption">AI adoption engineering</a> to build a continuous decision cycle rather than a temporary inventory campaign.</p>
HTML,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function dataGovernanceBeforeAi(): array
    {
        return [
            'operation' => 'create',
            'key' => 'data-governance-before-ai',
            'slug' => [
                'ar' => 'حوكمة-البيانات-قبل-الذكاء-الاصطناعي',
                'en' => 'data-governance-before-ai',
            ],
            'title' => [
                'ar' => 'حوكمة البيانات قبل الذكاء الاصطناعي',
                'en' => 'Data Governance Before AI',
            ],
            'summary' => [
                'ar' => 'مسار عملي لترتيب الغرض والملكية والتعريفات والجودة والصلاحيات والاحتفاظ قبل ربط البيانات بنموذج أو نظام معرفة.',
                'en' => 'A practical path for resolving purpose, ownership, definitions, quality, permissions, and retention before data reaches a model or knowledge system.',
            ],
            'seo_title' => [
                'ar' => 'حوكمة البيانات قبل الذكاء الاصطناعي',
                'en' => 'Data Governance Before AI: Practical Guide',
            ],
            'seo_description' => [
                'ar' => 'دليل حوكمة البيانات قبل الذكاء الاصطناعي: تحديد الغرض والمالك والتعريفات والجودة والصلاحيات والاحتفاظ ومراقبة الاستخدام.',
                'en' => 'Govern data before AI through explicit purpose, owners, definitions, quality, permissions, retention, lineage, and usage monitoring.',
            ],
            'type' => ['ar' => 'دليل بيانات', 'en' => 'Data guide'],
            'published_at' => '2026-10-21',
            'modified_at' => self::MODIFIED_AT,
            'image' => 'images/ibrahim/rag-console.png',
            'image_alt' => [
                'ar' => 'واجهة نظام معرفة تعرض مصادر ونتائج استرجاع قابلة للمراجعة',
                'en' => 'A knowledge-system interface showing reviewable sources and retrieval results',
            ],
            'image_caption' => ['ar' => '', 'en' => ''],
            'topic_keys' => [
                ArticleTopicClusters::DATA_KNOWLEDGE_SYSTEMS,
                ArticleTopicClusters::AI_ADOPTION_GOVERNANCE,
            ],
            'featured' => false,
            'source_url' => null,
            'is_published' => false,
            'content' => [
                'ar' => <<<'HTML'
<p>الذكاء الاصطناعي يسرّع الوصول إلى البيانات، لكنه يسرّع أيضاً أثر التعريف الخاطئ والصلاحية الواسعة والمصدر القديم. لذلك لا تبدأ حوكمة البيانات بمشروع تنظيف شامل، بل بحالة استخدام وقرار ومجموعة بيانات محددة.</p>
<p>هذا الدليل تشغيلي وليس استشارة قانونية. عند استخدام بيانات شخصية، تحقّق من المتطلبات الحالية عبر <a href="https://dgp.sdaia.gov.sa/wps/portal/pdp/knowledgecenter/" target="_blank">مركز المعرفة لحماية البيانات الشخصية لدى سدايا</a> ومن خلال مختصي المؤسسة.</p>
<h2>ستة قرارات تسبق اختيار التقنية</h2>
<ol>
<li><strong>الغرض:</strong> ما النتيجة التي ستستخدم البيانات لإنتاجها؟ وما الاستخدامات المستبعدة؟</li>
<li><strong>المالك:</strong> من يعتمد المصدر والتعريف ويقرر التصحيح أو الإيقاف؟</li>
<li><strong>التعريف:</strong> ماذا يعني كل حقل أو مستند، وفي أي فترة وسياق؟</li>
<li><strong>الوصول:</strong> من يرى الأصل والمقتطف والنتيجة؟ وهل تنتقل الصلاحيات إلى نظام المعرفة؟</li>
<li><strong>الاحتفاظ:</strong> ما الذي يُحفظ، وكم، وكيف يُحذف أو يُحدّث؟</li>
<li><strong>الدليل:</strong> كيف نعرف أن المصدر صحيح وأن الاستخدام أنتج نتيجة مقبولة؟</li>
</ol>
<h2>بطاقة حوكمة لكل مصدر</h2>
<table>
<thead><tr><th>الحقل</th><th>مثال للإجابة</th><th>إشارة خطر</th></tr></thead>
<tbody>
<tr><td>المالك</td><td>إدارة العمليات تعتمد المحتوى</td><td>«تقنية المعلومات» بلا صاحب قرار</td></tr>
<tr><td>المصدر المعتمد</td><td>نسخة منشورة بتاريخ وإصدار</td><td>نسخ متعددة في مجلدات شخصية</td></tr>
<tr><td>التصنيف</td><td>داخلي، مقيد، أو عام</td><td>لا يوجد تصنيف</td></tr>
<tr><td>الحداثة</td><td>مراجعة كل 90 يوماً</td><td>لا يوجد تاريخ انتهاء أو مراجعة</td></tr>
<tr><td>الجودة</td><td>اكتمال 98% وتعريف أخطاء معروف</td><td>«البيانات جيدة» بلا عينة</td></tr>
<tr><td>الاستخدام المسموح</td><td>استرجاع داخلي لموظفين محددين</td><td>إعادة استخدام مفتوحة لكل نموذج</td></tr>
</tbody>
</table>
<h2>حوكمة أنظمة المعرفة وRAG</h2>
<p>في الاسترجاع المعزز بالتوليد، لا تتوقف الحوكمة عند رفع الملفات. حافظ على سلسلة واضحة من المستند الأصلي إلى المقتطف إلى الاسترجاع إلى الإجابة والاستشهاد. إذا تعذر تتبع الإجابة إلى مصدر مخول وحديث، فلا ينبغي تقديمها كمعرفة مؤسسية.</p>
<ul>
<li>ورّث صلاحيات المصدر ولا تنشئ فهرساً يفتح محتوى مقيداً.</li>
<li>احذف النسخ المتكررة وحدد المصدر الأعلى سلطة عند التعارض.</li>
<li>سجّل تاريخ المصدر وإصداره، وأعد الفهرسة عند التغيير.</li>
<li>اختبر الاسترجاع والإجابة كلّاً على حدة، مع حالات «لا يوجد دليل».</li>
<li>اعرض الاستشهاد للمستخدم، وسجّل التصحيح والاعتراض.</li>
</ul>
<p>تشرح <a href="/writing/جاهزية-البيانات-قبل-الذكاء-الاصطناعي">جاهزية البيانات قبل الذكاء الاصطناعي</a> طريقة بناء مجموعة تقييم لأنظمة المعرفة، بينما يحدد <a href="/writing/نموذج-تشغيلي-لحوكمة-الذكاء-الاصطناعي">نموذج الحوكمة</a> مالك الخطر وبوابة القرار.</p>
<h2>خطة معالجة من ثلاث طبقات</h2>
<table>
<thead><tr><th>الطبقة</th><th>العمل</th><th>شرط الاكتمال</th></tr></thead>
<tbody>
<tr><td>حرج قبل التجربة</td><td>الغرض والحق والصلاحيات والمصدر</td><td>لا يوجد مانع غير معالج</td></tr>
<tr><td>ضروري للقياس</td><td>تعريفات وعينة جودة ومجموعة تقييم</td><td>يمكن تفسير النتيجة والخطأ</td></tr>
<tr><td>ضروري للتوسع</td><td>كتالوج وسجل نسب ومراقبة وحداثة</td><td>يمكن تشغيل المصدر عبر الزمن</td></tr>
</tbody>
</table>
<p>لا تجعل كتالوج البيانات غاية مستقلة. ابدأ بالمصادر التي تخدم حالات الاستخدام الأعلى قيمة، ووسّع الحوكمة كلما توسعت القدرة. هذا يحقق تحكماً ملموساً من دون مشروع طويل لا يصل إلى قرار.</p>
<h2>النتيجة المطلوبة</h2>
<p>في نهاية العمل يجب أن يستطيع الفريق الإجابة عن خمسة أسئلة خلال دقائق: ما المصدر؟ من مالكه؟ لماذا نستخدمه؟ من يستطيع الوصول؟ وكيف نكتشف الخطأ أو القِدم؟ إذا بقيت الإجابات موزعة بين أشخاص ورسائل، فالنظام لم يكتسب بعد أساساً قابلاً للتشغيل.</p>
<p>يمكن البدء من <a href="/services#service-data-governance">حوكمة البيانات واستراتيجيتها</a>، ثم ربط الضوابط بتصميم النظام عبر <a href="/services#service-systems">هندسة الأنظمة والأتمتة</a>. ويعرض <a href="/work#project-rafid-360">رافد 360 ضمن الأعمال المختارة</a> سياقاً عاماً لمنصة تعاون متعددة الجهات من دون افتراض بنية بيانات أو نتائج غير منشورة.</p>
HTML,
                'en' => <<<'HTML'
<p>AI accelerates access to data, but it also accelerates the effect of a wrong definition, broad permission, or stale source. Data governance should therefore begin with a specific use case, decision, and dataset—not an organization-wide cleaning program.</p>
<p>This is an operating guide, not legal advice. When personal data is involved, verify current requirements through the <a href="https://dgp.sdaia.gov.sa/wps/portal/pdp/knowledgecenter/" target="_blank">SDAIA Personal Data Protection knowledge center</a> and qualified specialists.</p>
<h2>Six decisions before choosing technology</h2>
<ol>
<li><strong>Purpose:</strong> What result will the data produce, and which uses are excluded?</li>
<li><strong>Owner:</strong> Who approves the source and definition and can correct or stop it?</li>
<li><strong>Definition:</strong> What does each field or document mean, in which period and context?</li>
<li><strong>Access:</strong> Who may see the source, excerpt, and output, and do source permissions carry into the knowledge system?</li>
<li><strong>Retention:</strong> What is stored, for how long, and how is it deleted or updated?</li>
<li><strong>Evidence:</strong> How will the team know the source is correct and the use produced an acceptable result?</li>
</ol>
<h2>A governance card for every source</h2>
<table>
<thead><tr><th>Field</th><th>Example answer</th><th>Risk signal</th></tr></thead>
<tbody>
<tr><td>Owner</td><td>Operations approves the content</td><td>“IT” without a decision owner</td></tr>
<tr><td>Authoritative source</td><td>Published version with date and release</td><td>Copies spread across personal folders</td></tr>
<tr><td>Classification</td><td>Internal, restricted, or public</td><td>No classification</td></tr>
<tr><td>Freshness</td><td>Review every 90 days</td><td>No expiry or review date</td></tr>
<tr><td>Quality</td><td>98% completeness and known error definition</td><td>“Good data” without a sample</td></tr>
<tr><td>Permitted use</td><td>Internal retrieval for named employees</td><td>Open reuse in every model</td></tr>
</tbody>
</table>
<h2>Governance for knowledge systems and RAG</h2>
<p>In retrieval-augmented generation, governance does not end when documents are uploaded. Preserve a traceable chain from original document to excerpt, retrieval, answer, and citation. If an answer cannot be traced to a current, authorized source, it should not be presented as organizational knowledge.</p>
<ul>
<li>Inherit source permissions instead of creating an index that exposes restricted content.</li>
<li>Remove duplicates and name the higher-authority source when documents conflict.</li>
<li>Store source date and version, and re-index after change.</li>
<li>Test retrieval and answer generation separately, including “no evidence” cases.</li>
<li>Show citations to users and capture corrections and challenges.</li>
</ul>
<p><a href="/en/writing/data-readiness-before-ai">Data readiness before AI</a> explains evaluation-set design for knowledge systems, while the <a href="/en/writing/a-practical-operating-model-for-ai-governance">governance operating model</a> assigns the risk owner and decision gate.</p>
<h2>A three-layer remediation plan</h2>
<table>
<thead><tr><th>Layer</th><th>Work</th><th>Completion condition</th></tr></thead>
<tbody>
<tr><td>Critical before pilot</td><td>Purpose, lawful basis, access, and source</td><td>No untreated stop condition</td></tr>
<tr><td>Required for measurement</td><td>Definitions, quality sample, and evaluation set</td><td>Result and error are interpretable</td></tr>
<tr><td>Required for scale</td><td>Catalog, lineage, monitoring, and freshness</td><td>The source can operate over time</td></tr>
</tbody>
</table>
<p>Do not turn the data catalog into an independent goal. Begin with sources serving the highest-value use cases and expand governance as capability expands. This produces tangible control without a long program that never reaches a decision.</p>
<h2>The required outcome</h2>
<p>At the end, the team should answer five questions within minutes: What is the source? Who owns it? Why is it used? Who can access it? How will error or staleness be detected? If answers remain scattered across people and messages, the system does not yet have an operable foundation.</p>
<p>Begin with <a href="/en/services#service-data-governance">data governance and strategy</a>, then connect the controls to system design through <a href="/en/services#service-systems">systems and automation architecture</a>. <a href="/en/work#project-rafid-360">Rafid 360 in selected work</a> offers public context for multi-organization collaboration without implying an unpublished data architecture or result.</p>
HTML,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function digitalTransformationRoadmap(): array
    {
        return [
            'operation' => 'create',
            'key' => 'digital-transformation-roadmap-process-to-impact',
            'slug' => [
                'ar' => 'خارطة-التحول-الرقمي-من-تشخيص-العملية-إلى-الأثر',
                'en' => 'digital-transformation-roadmap-process-to-impact',
            ],
            'title' => [
                'ar' => 'خارطة التحول الرقمي: من تشخيص العملية إلى أثر قابل للقياس',
                'en' => 'Digital Transformation Roadmap: From Process Diagnosis to Measurable Impact',
            ],
            'summary' => [
                'ar' => 'خارطة تربط رحلة المستخدم وسير العمل والبيانات والملكية والتقنية بخط أساس ومؤشرات أثر، كي لا يتحول المشروع إلى تسليم نظام بلا نتيجة.',
                'en' => 'A roadmap connecting user journeys, workflows, data, ownership, and technology to baselines and impact measures so delivery does not end with a system and no outcome.',
            ],
            'seo_title' => [
                'ar' => 'خارطة التحول الرقمي من العملية إلى الأثر',
                'en' => 'Digital Transformation Roadmap to Measurable Impact',
            ],
            'seo_description' => [
                'ar' => 'خارطة عملية للتحول الرقمي تبدأ بتشخيص العملية وخط الأساس، ثم تصميم الخدمة والتنفيذ المرحلي وقياس الأثر والتبني.',
                'en' => 'A practical transformation roadmap from process diagnosis and baseline through service design, phased delivery, adoption, and measurable impact.',
            ],
            'type' => ['ar' => 'خارطة تنفيذ', 'en' => 'Delivery roadmap'],
            'published_at' => '2026-10-28',
            'modified_at' => self::MODIFIED_AT,
            'image' => 'images/ibrahim/hero-workspace.png',
            'image_alt' => [
                'ar' => 'مساحة عمل توضح تحليل العمليات والتصميم والتنفيذ ضمن خارطة واحدة',
                'en' => 'A workspace showing process analysis, design, and delivery in one roadmap',
            ],
            'image_caption' => ['ar' => '', 'en' => ''],
            'topic_keys' => [
                ArticleTopicClusters::DIGITAL_TRANSFORMATION_OPERATIONS,
                ArticleTopicClusters::PRODUCT_STRATEGY_MEASUREMENT,
            ],
            'featured' => false,
            'source_url' => null,
            'is_published' => false,
            'content' => [
                'ar' => <<<'HTML'
<p>يفشل التحول الرقمي عندما يُعرّف بوصفه مشروع نظام: قائمة متطلبات، ومورد، وموعد إطلاق. النجاح الحقيقي هو تغير في قدرة المؤسسة أو تجربة العميل يمكن إثباته مقارنة بخط أساس.</p>
<p>الخارطة التالية تبدأ من العملية والقرار والملكية، ثم تصل إلى التقنية. وهي تصلح لمبادرة جديدة أو لمشروع متعثر يحتاج إعادة ضبط.</p>
<h2>1. شخّص العملية كما تُنفذ فعلاً</h2>
<p>راقب الحالات الحقيقية لا الإجراء المكتوب فقط. ارسم نقطة البداية والنهاية، والأدوار، والانتظار، وإعادة الإدخال، والاستثناءات، والموافقات، والأنظمة والملفات المستخدمة. افصل بين عمل يضيف قيمة وعمل سببه نقص معلومة أو خوف أو قيد قديم.</p>
<table>
<thead><tr><th>ما نرصده</th><th>الدليل</th><th>السؤال</th></tr></thead>
<tbody>
<tr><td>زمن الدورة</td><td>وقت من الطلب إلى النتيجة</td><td>أين ينتظر العمل ولماذا؟</td></tr>
<tr><td>إعادة العمل</td><td>الرجوع والتصحيح</td><td>ما المعلومة الناقصة أول مرة؟</td></tr>
<tr><td>التحويلات</td><td>انتقال بين أشخاص وأنظمة</td><td>هل لكل انتقال قرار حقيقي؟</td></tr>
<tr><td>الاستثناءات</td><td>حالات خارج المسار</td><td>هل هي نادرة أم أن المسار مصمم خطأ؟</td></tr>
</tbody>
</table>
<h2>2. ثبّت خط الأساس والنتيجة</h2>
<p>اختر مؤشرين أو ثلاثة للنتيجة، مثل زمن الخدمة، ونسبة الإكمال من أول مرة، والأخطاء، والتكلفة لكل حالة، أو رضا المستخدم المرتبط بمرحلة محددة. لا تستخدم عدد الشاشات أو المعاملات الرقمية بوصفه أثراً.</p>
<p>حدّد أيضاً مؤشرات حماية: شكاوى، أو وصول غير مصرح، أو حمل إضافي على الموظف، أو فشل في الحالات الخاصة. يشرح <a href="/writing/قياس-أثر-المنتجات-والتحول-الرقمي">قياس أثر المنتجات والتحول الرقمي</a> الفرق بين النشاط والنتيجة.</p>
<h2>3. أعد تصميم الخدمة والملكية</h2>
<p>لا تنقل التعقيد كما هو إلى شاشة جديدة. احذف الخطوة التي لا تضيف قراراً، واجمع البيانات مرة واحدة، واجعل القاعدة واضحة، وصمّم مساراً للاستثناء. عيّن مالكاً للنتيجة عبر الإدارات لا مالكاً للنظام فقط.</p>
<ul>
<li>عرّف رحلة العميل والموظف معاً؛ تحسين واحدة على حساب الأخرى غير مستدام.</li>
<li>اجعل القرار وقاعدته ودليله مرئياً قبل الأتمتة.</li>
<li>حدّد من يملك البيانات والسياسة والتشغيل والتغيير.</li>
</ul>
<p>تعرض صفحة <a href="/work#project-wafaa">وفاء ضمن الأعمال المختارة</a> سياقاً عاماً لمنظومة تعليم وعمل غير ربحي؛ لا تُستنتج منها مقاييس غير منشورة. الغرض من الرابط هو فهم نطاق المنتج، بينما إثبات الأثر يجب أن يأتي من خط أساس المشروع نفسه.</p>
<h2>4. صمّم حزمة إطلاق صغيرة مكتملة</h2>
<p>قسّم العمل بحسب رحلة قابلة للاستخدام من البداية إلى النهاية، لا بحسب طبقات تقنية منفصلة. الحزمة الأولى يجب أن تحل مشكلة لمستخدم حقيقي، وتشمل الصلاحيات والبيانات والدعم والقياس ومسار الفشل.</p>
<ol>
<li>اختر شريحة وموقفاً تشغيلياً واضحاً.</li>
<li>ابنِ أقل تدفق مكتمل، مع الاستثناء الأكثر شيوعاً.</li>
<li>هاجر البيانات المطلوبة فقط بعد اختبار الجودة.</li>
<li>درّب المستخدمين على القرار الجديد لا على الأزرار فقط.</li>
<li>أطلق على مجموعة صغيرة، وراقب، ثم وسّع.</li>
</ol>
<h2>5. قِس التبني والأثر بصورة منفصلة</h2>
<p>التبني يوضح هل وصل الناس إلى الخدمة واستطاعوا استخدامها. الأثر يوضح هل تحسنت النتيجة. قد يرتفع تسجيل الدخول بينما يزداد العمل اليدوي خارج النظام. راقب المسار الكامل، واسأل الموظفين أين ظهرت حلول جانبية جديدة.</p>
<table>
<thead><tr><th>طبقة القياس</th><th>مثال</th><th>قرار</th></tr></thead>
<tbody>
<tr><td>الوصول</td><td>نسبة المستخدمين المؤهلين الذين بدأوا</td><td>إزالة عائق دخول</td></tr>
<tr><td>الإكمال</td><td>الحالات المكتملة دون مسار جانبي</td><td>إصلاح تجربة أو قاعدة</td></tr>
<tr><td>النتيجة</td><td>فرق زمن الدورة عن خط الأساس</td><td>توسيع أو إعادة تصميم</td></tr>
<tr><td>الحماية</td><td>أخطاء أو شكاوى أو استثناءات</td><td>تقييد أو إيقاف</td></tr>
</tbody>
</table>
<h2>6. حوّل الإطلاق إلى قدرة مستمرة</h2>
<p>بعد الإطلاق، ضع مالك خدمة، واجتماع مراجعة، وقائمة تحسين مرتبطة بالأثر، وميزانية دعم، واتفاقاً على البيانات. أوقف الميزات التي لا تُستخدم أو لا تصنع نتيجة، ولا تجعل تكلفة المشروع السابق سبباً للاستمرار.</p>
<p>ابدأ بـ<a href="/services#service-transformation">استراتيجية التحول الرقمي</a> لتشخيص المسار وترتيب خارطة القرار، واربط البناء بـ<a href="/services#service-systems">هندسة الأنظمة والأتمتة</a>. وإذا كانت المشكلة السابقة هي البدء بالتقنية، اقرأ <a href="/writing/لماذا-يفشل-التحول-قبل-بناء-البرمجيات">لماذا يفشل التحول قبل بناء البرمجيات</a>.</p>
HTML,
                'en' => <<<'HTML'
<p>Digital transformation fails when it is defined as a system project: requirements, vendor, and launch date. Real success is a changed organizational capability or customer experience that can be demonstrated against a baseline.</p>
<p>This roadmap begins with workflow, decision, and ownership, then reaches technology. It works for a new initiative or a troubled program that needs a reset.</p>
<h2>1. Diagnose the process as it is actually performed</h2>
<p>Observe real cases, not only the documented procedure. Map start and end, roles, waiting, re-entry, exceptions, approvals, systems, and files. Separate work that adds value from work caused by missing information, fear, or an obsolete constraint.</p>
<table>
<thead><tr><th>What to observe</th><th>Evidence</th><th>Question</th></tr></thead>
<tbody>
<tr><td>Cycle time</td><td>Time from request to outcome</td><td>Where does work wait, and why?</td></tr>
<tr><td>Rework</td><td>Returns and corrections</td><td>Which information was missing first time?</td></tr>
<tr><td>Handoffs</td><td>Moves between people and systems</td><td>Does each handoff contain a real decision?</td></tr>
<tr><td>Exceptions</td><td>Cases outside the path</td><td>Are they rare, or is the path wrong?</td></tr>
</tbody>
</table>
<h2>2. Freeze the baseline and outcome</h2>
<p>Choose two or three outcome measures such as service time, first-time completion, errors, cost per case, or satisfaction tied to a specific stage. Do not call screen count or digital transaction volume an impact measure.</p>
<p>Define guardrails too: complaints, unauthorized access, extra employee burden, or failure in special cases. <a href="/en/writing/measuring-digital-product-and-transformation-impact">Measuring digital product and transformation impact</a> explains the difference between activity and outcome.</p>
<h2>3. Redesign the service and ownership</h2>
<p>Do not reproduce complexity in a new interface. Remove steps that add no decision, collect information once, make the rule explicit, and design an exception path. Assign an owner for the cross-functional outcome, not only an owner for the system.</p>
<ul>
<li>Design the customer and employee journeys together; optimizing one against the other is unstable.</li>
<li>Make the decision, rule, and evidence visible before automation.</li>
<li>Name owners for data, policy, operation, and change.</li>
</ul>
<p><a href="/en/work#project-wafaa">Wafaa in selected work</a> provides public context for an education and nonprofit operating ecosystem; it does not justify unpublished metrics. Use it to understand product scope while proving impact from the initiative's own baseline.</p>
<h2>4. Design a small, complete release</h2>
<p>Slice delivery by an end-to-end usable journey, not by isolated technical layers. The first release must solve a problem for a real user and include permissions, data, support, measurement, and failure behavior.</p>
<ol>
<li>Choose a user segment and operational situation.</li>
<li>Build the smallest complete flow, including the most common exception.</li>
<li>Migrate only required data after quality testing.</li>
<li>Train users on the new decision, not only the buttons.</li>
<li>Release to a small group, observe, and then expand.</li>
</ol>
<h2>5. Measure adoption and impact separately</h2>
<p>Adoption shows whether people reached and could use the service. Impact shows whether the result improved. Logins can rise while manual work outside the system increases. Observe the whole path and ask employees where new workarounds appeared.</p>
<table>
<thead><tr><th>Measurement layer</th><th>Example</th><th>Decision</th></tr></thead>
<tbody>
<tr><td>Reach</td><td>Eligible users who started</td><td>Remove an entry barrier</td></tr>
<tr><td>Completion</td><td>Cases completed without a workaround</td><td>Fix experience or rule</td></tr>
<tr><td>Outcome</td><td>Cycle-time difference from baseline</td><td>Scale or redesign</td></tr>
<tr><td>Guardrail</td><td>Errors, complaints, or exceptions</td><td>Restrict or stop</td></tr>
</tbody>
</table>
<h2>6. Turn launch into a continuing capability</h2>
<p>After launch, establish a service owner, review meeting, impact-linked improvement backlog, support budget, and data agreement. Retire features that are unused or do not create an outcome; sunk delivery cost is not a reason to continue.</p>
<p>Start with <a href="/en/services#service-transformation">digital transformation strategy</a> to diagnose the path and order decisions, then connect delivery through <a href="/en/services#service-systems">systems and automation architecture</a>. If the previous mistake was starting with technology, read <a href="/en/writing/why-transformation-fails-before-software">why transformation fails before software</a>.</p>
HTML,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function workflowAudit(): array
    {
        return [
            'operation' => 'create',
            'key' => 'workflow-audit-what-should-be-automated',
            'slug' => [
                'ar' => 'تدقيق-سير-العمل-ما-الذي-ينبغي-أتمتته',
                'en' => 'workflow-audit-what-should-be-automated',
            ],
            'title' => [
                'ar' => 'تدقيق سير العمل: ما الذي ينبغي أتمتته؟',
                'en' => 'Workflow Audit: What Should Be Automated?',
            ],
            'summary' => [
                'ar' => 'أداة تميّز ما يجب حذفه أو تبسيطه أو أتمتته بقواعد أو دعمه بالذكاء الاصطناعي أو إبقاؤه قراراً بشرياً.',
                'en' => 'A practical audit for deciding what to remove, simplify, automate with rules, assist with AI, or retain as a human decision.',
            ],
            'seo_title' => [
                'ar' => 'تدقيق سير العمل: ما الذي ينبغي أتمتته؟',
                'en' => 'Workflow Audit: What Should Be Automated?',
            ],
            'seo_description' => [
                'ar' => 'دليل تدقيق سير العمل لتحديد ما يُحذف أو يُبسط أو يُؤتمت بالقواعد أو يُدعم بالذكاء الاصطناعي مع ضوابط قياس وفشل.',
                'en' => 'Audit a workflow to decide what to remove, simplify, automate with rules, assist with AI, and retain for human judgment.',
            ],
            'type' => ['ar' => 'أداة تشغيل', 'en' => 'Operations tool'],
            'published_at' => '2026-11-04',
            'modified_at' => self::MODIFIED_AT,
            'image' => 'images/ibrahim/automation-board.png',
            'image_alt' => [
                'ar' => 'لوحة أتمتة تعرض خطوات سير عمل وقرارات ومسارات متعددة',
                'en' => 'An automation board showing workflow steps, decisions, and multiple paths',
            ],
            'image_caption' => ['ar' => '', 'en' => ''],
            'topic_keys' => [
                ArticleTopicClusters::DIGITAL_TRANSFORMATION_OPERATIONS,
                ArticleTopicClusters::AI_ADOPTION_GOVERNANCE,
            ],
            'featured' => false,
            'source_url' => null,
            'is_published' => false,
            'content' => [
                'ar' => <<<'HTML'
<p>أتمتة سير عمل سيئ تجعل الخطأ أسرع وأكثر ثباتاً. قبل اختيار منصة أو وكيل ذكي، افهم لماذا توجد كل خطوة وما القرار الذي تضيفه وما الذي يحدث عندما تفشل.</p>
<p>التدقيق الجيد لا يسأل فقط «هل نستطيع أتمتتها؟» بل «هل ينبغي أن تبقى؟ وهل القاعدة ثابتة؟ وهل يحتاج العمل حكماً أو سياقاً؟ وهل أثر الخطأ قابل للتراجع؟».</p>
<h2>اجمع دليلاً من حالات حقيقية</h2>
<p>اختر 20 إلى 30 حالة حديثة تشمل العادي والمتأخر والمرفوض والمستثنى. سجّل الزمن النشط وزمن الانتظار، والمدخل والمخرج، والأدوار، والأنظمة، وإعادة الإدخال، والتصحيح. لا تعتمد على ورشة تذكّر فقط؛ راجع السجلات والعينات.</p>
<table>
<thead><tr><th>الخطوة</th><th>السبب المعلن</th><th>الدليل المطلوب</th></tr></thead>
<tbody>
<tr><td>موافقة</td><td>تقليل المخاطر</td><td>نسبة الرفض ونوع الأخطاء المكتشفة</td></tr>
<tr><td>إدخال متكرر</td><td>نظام آخر يحتاج البيانات</td><td>مصدر الحقيقة وإمكان التكامل</td></tr>
<tr><td>تقرير يدوي</td><td>الإدارة تحتاج رؤية</td><td>القرار الذي يتغير بعد التقرير</td></tr>
<tr><td>تحويل لمختص</td><td>الحالة معقدة</td><td>سمات التعقيد ونسبة الحالات</td></tr>
</tbody>
</table>
<h2>مصفوفة القرار: خمسة أنواع من التدخل</h2>
<table>
<thead><tr><th>التدخل</th><th>متى يناسب؟</th><th>مثال</th></tr></thead>
<tbody>
<tr><td>احذف</td><td>لا يضيف قراراً ولا متطلباً ولا دليلاً</td><td>نسخة تقرير لا يستخدمها أحد</td></tr>
<tr><td>بسّط</td><td>الخطوة ضرورية لكن المدخل أو القاعدة مربك</td><td>نموذج طويل يمكن تقسيمه حسب الحالة</td></tr>
<tr><td>أتمت بقواعد</td><td>مدخل منظم ونتيجة حتمية واستثناء معروف</td><td>تحقق اكتمال وتوجيه طلب</td></tr>
<tr><td>ادعم بالذكاء الاصطناعي</td><td>لغة أو تصنيف أو معرفة مع مراجعة</td><td>مسودة أو اقتراح مصدر</td></tr>
<tr><td>أبقِ الحكم البشري</td><td>سياق حساس أو أثر مرتفع أو قاعدة غير مكتملة</td><td>استثناء أو قرار يؤثر في حق</td></tr>
</tbody>
</table>
<p>قد يجمع التدفق الواحد أكثر من تدخل: حذف إدخال مكرر، وأتمتة تحقق ثابت، واستخدام الذكاء الاصطناعي لاقتراح مسودة، ثم إبقاء الموافقة النهائية للإنسان.</p>
<h2>اختبار أهلية الأتمتة</h2>
<ul>
<li>هل المدخل متاح ومنظم أو يمكن التحقق منه؟</li>
<li>هل القاعدة مستقرة ومفهومة لمالك العملية؟</li>
<li>هل حجم التكرار يبرر تكلفة البناء والدعم؟</li>
<li>هل يوجد مسار واضح للاستثناء والفشل؟</li>
<li>هل يمكن التراجع عن الفعل وإعادة الحالة؟</li>
<li>هل سنعرف أن النتيجة تحسنت مقارنة بخط الأساس؟</li>
</ul>
<p>إذا كانت القاعدة حتمية فابدأ بالأتمتة التقليدية. إذا كان المدخل لغوياً أو يحتاج استرجاع معرفة فقد يناسب المساعد. وإذا كان النظام سينفذ أفعالاً متعددة، اقرأ <a href="/writing/الأتمتة-أم-المساعد-أم-الوكيل">الأتمتة أم المساعد أم الوكيل</a> قبل منحه صلاحيات.</p>
<h2>صمّم الفشل قبل المسار السعيد</h2>
<p>لكل أتمتة حدد مهلة، ومحاولة إعادة، ومنع التكرار، وسجل الحدث، وتنبيهاً قابلاً للتصرف، ومساراً يدوياً. لا تجعل الموظف يكتشف الفشل من شكوى العميل. وإذا استخدمت نموذجاً احتماليّاً، أضف حد ثقة أو قاعدة رفض ومراجعة للحالات الحرجة.</p>
<p>قس أثر الفشل أيضاً: عدد الحالات العالقة، وزمن الاستعادة، والأفعال المكررة، والتصحيحات البشرية. هذه مؤشرات جودة تشغيل لا تفاصيل تقنية ثانوية.</p>
<h2>رتّب خارطة التنفيذ</h2>
<ol>
<li>نفّذ سريعاً ما هو عالي التكرار، واضح القاعدة، منخفض الخطر، وسهل التراجع.</li>
<li>عالج ملكية البيانات والتعريفات قبل أتمتة الخطوات الغامضة.</li>
<li>اختبر الذكاء الاصطناعي في نطاق مساعد قبل الانتقال إلى فعل مستقل.</li>
<li>اترك الحالات عالية الأثر للإنسان حتى تتوافر أدلة وضوابط كافية.</li>
</ol>
<p>استخدم <a href="/services#service-systems">هندسة الأنظمة والأتمتة</a> لتحويل التدقيق إلى خارطة تقنية قابلة للتشغيل، و<a href="/services#service-transformation">استراتيجية التحول الرقمي</a> عندما تكون المشكلة في تصميم العملية والملكية. يعرض <a href="/work#project-2060-investments">استثمارات عشرين ستين ضمن الأعمال المختارة</a> سياق خدمات تشغيلية رقمية من دون نسبة أتمتة أو نتائج غير منشورة. ثم اربط كل مبادرة بمؤشرات <a href="/writing/قياس-أثر-المنتجات-والتحول-الرقمي">قياس الأثر</a>.</p>
HTML,
                'en' => <<<'HTML'
<p>Automating a poor workflow makes its failure faster and more consistent. Before choosing a platform or intelligent agent, understand why each step exists, which decision it adds, and what happens when it fails.</p>
<p>A good audit asks more than “Can this be automated?” Ask whether the step should remain, whether its rule is stable, whether context or judgment is required, and whether an error is reversible.</p>
<h2>Collect evidence from real cases</h2>
<p>Select 20 to 30 recent cases covering normal, delayed, rejected, and exceptional paths. Record active time and waiting time, inputs and outputs, roles, systems, re-entry, and correction. Do not rely only on workshop memory; inspect logs and samples.</p>
<table>
<thead><tr><th>Step</th><th>Stated reason</th><th>Required evidence</th></tr></thead>
<tbody>
<tr><td>Approval</td><td>Reduce risk</td><td>Rejection rate and errors found</td></tr>
<tr><td>Repeated entry</td><td>Another system needs the data</td><td>Source of truth and integration option</td></tr>
<tr><td>Manual report</td><td>Management needs visibility</td><td>Decision changed by the report</td></tr>
<tr><td>Specialist handoff</td><td>The case is complex</td><td>Complexity signals and case share</td></tr>
</tbody>
</table>
<h2>The decision matrix: five interventions</h2>
<table>
<thead><tr><th>Intervention</th><th>When does it fit?</th><th>Example</th></tr></thead>
<tbody>
<tr><td>Remove</td><td>No decision, requirement, or evidence is added</td><td>A report copy nobody uses</td></tr>
<tr><td>Simplify</td><td>The step is needed but input or rule is confusing</td><td>A long form split by situation</td></tr>
<tr><td>Automate with rules</td><td>Structured input, deterministic result, known exception</td><td>Completeness check and request routing</td></tr>
<tr><td>Assist with AI</td><td>Language, classification, or knowledge with review</td><td>A draft or source suggestion</td></tr>
<tr><td>Retain human judgment</td><td>Sensitive context, high impact, or incomplete rule</td><td>An exception or rights-affecting decision</td></tr>
</tbody>
</table>
<p>One workflow can combine interventions: remove duplicate entry, automate a fixed check, use AI for a draft, and keep final approval with a person.</p>
<h2>The automation eligibility test</h2>
<ul>
<li>Is the input available and structured, or can it be validated?</li>
<li>Is the rule stable and understood by the process owner?</li>
<li>Does repetition justify build and support cost?</li>
<li>Is there a clear exception and failure path?</li>
<li>Can the action be reversed and the case restored?</li>
<li>Will improvement be visible against a baseline?</li>
</ul>
<p>When the rule is deterministic, start with conventional automation. When the input is language or requires knowledge retrieval, an assistant may fit. When the system will take several actions, read <a href="/en/writing/automation-assistant-or-agent">automation, assistant, or agent</a> before granting permissions.</p>
<h2>Design failure before the happy path</h2>
<p>For every automation, define a timeout, retry, idempotency control, event log, actionable alert, and manual route. Do not let an employee discover failure through a customer complaint. For probabilistic models, add a confidence or refusal rule and review for critical cases.</p>
<p>Measure failure too: stuck cases, recovery time, duplicate actions, and human corrections. These are operating-quality measures, not secondary technical details.</p>
<h2>Prioritize the delivery roadmap</h2>
<ol>
<li>Deliver high-frequency, clear-rule, low-risk, easily reversible work first.</li>
<li>Resolve data ownership and definitions before automating ambiguous steps.</li>
<li>Test AI in an assistive scope before progressing to independent action.</li>
<li>Keep high-consequence cases with people until evidence and controls are sufficient.</li>
</ol>
<p>Use <a href="/en/services#service-systems">systems and automation architecture</a> to turn the audit into an operable technical roadmap, and <a href="/en/services#service-transformation">digital transformation strategy</a> when the underlying issue is process and ownership. <a href="/en/work#project-2060-investments">2060 Investments in selected work</a> provides public context for digital operational services without attributing unpublished automation or results. Connect every initiative to <a href="/en/writing/measuring-digital-product-and-transformation-impact">impact measurement</a>.</p>
HTML,
            ],
        ];
    }

    private function json(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
