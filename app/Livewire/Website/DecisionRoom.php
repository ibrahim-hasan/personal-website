<?php

namespace App\Livewire\Website;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class DecisionRoom extends Component
{
    /** @var list<string> */
    private const CHALLENGES = [
        'ai-adoption',
        'digital-transformation',
        'product-platform',
        'operations-automation',
    ];

    /** @var array<string, list<string>> */
    private const FRICTIONS = [
        'ai-adoption' => [
            'unclear-business-case',
            'trust-and-review',
            'ownership-and-adoption',
        ],
        'digital-transformation' => [
            'fragmented-journey',
            'competing-priorities',
            'change-readiness',
        ],
        'product-platform' => [
            'unclear-product-direction',
            'platform-friction',
            'delivery-drift',
        ],
        'operations-automation' => [
            'manual-handoffs',
            'low-visibility',
            'fragile-process',
        ],
    ];

    /** @var array<string, list<string>> */
    private const OUTCOMES = [
        'ai-adoption' => [
            'opportunity-brief',
            'bounded-pilot',
            'governance-principles',
        ],
        'digital-transformation' => [
            'phased-roadmap',
            'priority-map',
            'target-journey',
        ],
        'product-platform' => [
            'product-direction',
            'platform-roadmap',
            'operating-model',
        ],
        'operations-automation' => [
            'workflow-roadmap',
            'automation-brief',
            'visibility-plan',
        ],
    ];

    /** @var array<string, string> */
    private const CONSULTATION_SERVICES = [
        'ai-adoption' => 'ai-adoption',
        'digital-transformation' => 'transformation',
        'product-platform' => 'systems',
        'operations-automation' => 'systems',
    ];

    #[Locked]
    public int $step = 1;

    #[Locked]
    public ?string $selectedChallenge = null;

    #[Locked]
    public ?string $primaryFriction = null;

    #[Locked]
    public ?string $desiredOutcome = null;

    public function selectChallenge(string $challenge): void
    {
        if (! in_array($challenge, self::CHALLENGES, true)) {
            return;
        }

        if ($this->selectedChallenge !== $challenge) {
            $this->primaryFriction = null;
            $this->desiredOutcome = null;
        }

        $this->selectedChallenge = $challenge;
        $this->step = 2;
    }

    public function selectFriction(string $friction): void
    {
        $allowedFrictions = self::FRICTIONS[$this->selectedChallenge] ?? [];

        if (! in_array($friction, $allowedFrictions, true)) {
            return;
        }

        $this->primaryFriction = $friction;
        $this->step = 2;
    }

    public function selectOutcome(string $outcome): void
    {
        $allowedOutcomes = self::OUTCOMES[$this->selectedChallenge] ?? [];

        if (! in_array($outcome, $allowedOutcomes, true)) {
            return;
        }

        $this->desiredOutcome = $outcome;
        $this->step = 2;
    }

    public function showRecommendation(): void
    {
        if (! $this->hasValidChallenge()) {
            $this->resetDecisionRoom();

            return;
        }

        if (! $this->hasValidFriction()) {
            $this->primaryFriction = null;
        }

        if (! $this->hasValidOutcome()) {
            $this->desiredOutcome = null;
        }

        $this->step = $this->hasCompleteContext() ? 3 : 2;
    }

    public function back(): void
    {
        $activeStep = $this->activeStep();

        if ($activeStep === 3) {
            $this->step = 2;

            return;
        }

        $this->step = 1;
    }

    public function resetDecisionRoom(): void
    {
        $this->step = 1;
        $this->selectedChallenge = null;
        $this->primaryFriction = null;
        $this->desiredOutcome = null;
    }

    public function render(): View
    {
        $activeStep = $this->activeStep();

        return view('livewire.website.decision-room', [
            'activeStep' => $activeStep,
            'copy' => $this->copy(),
            'challenges' => $this->challengeOptions(),
            'frictions' => $this->frictionOptions(),
            'outcomes' => $this->outcomeOptions(),
            'canShowRecommendation' => $this->hasCompleteContext(),
            'recommendation' => $activeStep === 3 ? $this->recommendation() : null,
            'consultationUrl' => $activeStep === 3 ? $this->consultationUrl() : null,
            'directConsultationUrl' => localized_route('contact').'#consultation',
        ]);
    }

    private function activeStep(): int
    {
        if ($this->step === 3 && $this->hasCompleteContext()) {
            return 3;
        }

        if ($this->step >= 2 && $this->hasValidChallenge()) {
            return 2;
        }

        return 1;
    }

    private function hasValidChallenge(): bool
    {
        return is_string($this->selectedChallenge)
            && in_array($this->selectedChallenge, self::CHALLENGES, true);
    }

    private function hasValidFriction(): bool
    {
        if (! is_string($this->selectedChallenge) || ! is_string($this->primaryFriction)) {
            return false;
        }

        return in_array(
            $this->primaryFriction,
            self::FRICTIONS[$this->selectedChallenge] ?? [],
            true,
        );
    }

    private function hasValidOutcome(): bool
    {
        if (! is_string($this->selectedChallenge) || ! is_string($this->desiredOutcome)) {
            return false;
        }

        return in_array(
            $this->desiredOutcome,
            self::OUTCOMES[$this->selectedChallenge] ?? [],
            true,
        );
    }

    private function hasCompleteContext(): bool
    {
        return $this->hasValidChallenge()
            && $this->hasValidFriction()
            && $this->hasValidOutcome();
    }

    /**
     * @return array{
     *     eyebrow: string,
     *     title: string,
     *     intro: string,
     *     direct: string,
     *     progress_label: string,
     *     step_label: string,
     *     steps: list<string>,
     *     choose_challenge: string,
     *     challenge_hint: string,
     *     context_title: string,
     *     context_intro: string,
     *     choose_friction: string,
     *     friction_hint: string,
     *     choose_outcome: string,
     *     outcome_hint: string,
     *     review: string,
     *     review_loading: string,
     *     back: string,
     *     reset: string,
     *     loading: string,
     *     selected: string,
     *     recommendation_eyebrow: string,
     *     context_label: string,
     *     challenge_label: string,
     *     friction_label: string,
     *     outcome_label: string,
     *     disclaimer: string,
     *     consultation: string
     * }
     */
    private function copy(): array
    {
        $copy = [
            'en' => [
                'eyebrow' => 'Optional decision room',
                'title' => 'Turn the challenge into a useful first conversation.',
                'intro' => 'Choose what is closest to your situation. In three short steps, you will get a practical starting point, not an automated diagnosis.',
                'direct' => 'Skip the room and request a consultation',
                'progress_label' => 'Decision room steps',
                'step_label' => 'Step',
                'steps' => ['Challenge', 'Context', 'Starting point'],
                'choose_challenge' => 'What needs a clearer business decision?',
                'challenge_hint' => 'Choose the path closest to the challenge. You can change it later.',
                'context_title' => 'Add two signals from the current situation.',
                'context_intro' => 'This keeps the starting point specific without turning the experience into a long form.',
                'choose_friction' => 'Where is the work getting stuck?',
                'friction_hint' => 'Choose the primary friction.',
                'choose_outcome' => 'What would make the conversation useful?',
                'outcome_hint' => 'Choose the most useful near-term output.',
                'review' => 'Show my starting point',
                'review_loading' => 'Preparing the starting point…',
                'back' => 'Back',
                'reset' => 'Start over',
                'loading' => 'Updating the decision room…',
                'selected' => 'Selected',
                'recommendation_eyebrow' => 'A useful starting point',
                'context_label' => 'Selected context',
                'challenge_label' => 'Challenge',
                'friction_label' => 'Primary friction',
                'outcome_label' => 'Desired outcome',
                'disclaimer' => 'This is a conversation starter based only on your selections. It is not an automated diagnosis or a promise of a specific result.',
                'consultation' => 'Take this context into a consultation',
            ],
            'ar' => [
                'eyebrow' => 'غرفة قرار اختيارية',
                'title' => 'حوّل التحدّي إلى بداية مفيدة للحوار.',
                'intro' => 'اختر الأقرب إلى واقعك. خلال ثلاث خطوات قصيرة ستحصل على نقطة بداية عملية، لا تشخيصاً آلياً.',
                'direct' => 'تجاوز الغرفة واطلب استشارة',
                'progress_label' => 'خطوات غرفة القرار',
                'step_label' => 'الخطوة',
                'steps' => ['التحدّي', 'السياق', 'نقطة البداية'],
                'choose_challenge' => 'ما الذي يحتاج إلى قرار عمل أوضح؟',
                'challenge_hint' => 'اختر المسار الأقرب إلى التحدّي، ويمكنك تغييره لاحقاً.',
                'context_title' => 'أضف إشارتين من الواقع الحالي.',
                'context_intro' => 'يساعد هذا السياق على جعل نقطة البداية أكثر تحديداً من دون تحويل التجربة إلى نموذج طويل.',
                'choose_friction' => 'أين يتعطّل العمل حالياً؟',
                'friction_hint' => 'اختر العائق الأساسي.',
                'choose_outcome' => 'ما الذي سيجعل الحوار مفيداً؟',
                'outcome_hint' => 'اختر المخرج الأقرب الذي تحتاجه.',
                'review' => 'اعرض نقطة البداية',
                'review_loading' => 'جارٍ إعداد نقطة البداية…',
                'back' => 'رجوع',
                'reset' => 'ابدأ من جديد',
                'loading' => 'جارٍ تحديث غرفة القرار…',
                'selected' => 'محدّد',
                'recommendation_eyebrow' => 'نقطة بداية مفيدة',
                'context_label' => 'السياق المختار',
                'challenge_label' => 'التحدّي',
                'friction_label' => 'العائق الأساسي',
                'outcome_label' => 'المخرج المطلوب',
                'disclaimer' => 'هذه نقطة بداية للحوار مبنية فقط على اختياراتك، وليست تشخيصاً آلياً أو وعداً بنتيجة محددة.',
                'consultation' => 'انتقل بهذا السياق إلى الاستشارة',
            ],
        ];

        return $copy[$this->locale()];
    }

    /**
     * @return array<string, array{
     *     label: string,
     *     description: string,
     *     recommendation_title: string,
     *     recommendation: string
     * }>
     */
    private function challengeOptions(): array
    {
        $options = [
            'en' => [
                'ai-adoption' => [
                    'label' => 'AI adoption',
                    'description' => 'Move from interest or pilots to a responsible, useful business case.',
                    'recommendation_title' => 'Frame one AI decision worth testing',
                    'recommendation' => 'Begin with one decision or workflow where AI can support people with clear evidence, ownership, and review boundaries.',
                ],
                'digital-transformation' => [
                    'label' => 'Digital transformation',
                    'description' => 'Sequence change around the customer journey and the way work actually happens.',
                    'recommendation_title' => 'Sequence the transformation around the work',
                    'recommendation' => 'Start from the customer or operating journey, then separate what needs simplification, digitization, or organizational change before prioritizing initiatives.',
                ],
                'product-platform' => [
                    'label' => 'Product or platform direction',
                    'description' => 'Clarify who it serves, what it must enable, and what deserves priority.',
                    'recommendation_title' => 'Reconnect the roadmap to a clear decision',
                    'recommendation' => 'Start with the users, business constraints, and decisions the product or platform must support, then shape the next priorities around that evidence.',
                ],
                'operations-automation' => [
                    'label' => 'Operations and automation',
                    'description' => 'Reduce manual handoffs and make recurring work easier to see and run.',
                    'recommendation_title' => 'Make the workflow visible before automating it',
                    'recommendation' => 'Map the real handoffs, exceptions, and ownership first, then identify the parts that are stable enough to simplify or automate.',
                ],
            ],
            'ar' => [
                'ai-adoption' => [
                    'label' => 'تبنّي الذكاء الاصطناعي',
                    'description' => 'الانتقال من الاهتمام أو التجارب إلى حالة عمل مفيدة ومسؤولة.',
                    'recommendation_title' => 'حدّد قراراً واحداً يستحق اختبار الذكاء الاصطناعي',
                    'recommendation' => 'ابدأ بقرار أو مسار عمل واحد يمكن للذكاء الاصطناعي أن يدعم فيه الفريق، مع أدلة واضحة وملكية وحدود للمراجعة.',
                ],
                'digital-transformation' => [
                    'label' => 'التحول الرقمي',
                    'description' => 'ترتيب التغيير حول رحلة العميل وطريقة إنجاز العمل فعلياً.',
                    'recommendation_title' => 'رتّب التحول حول طريقة إنجاز العمل',
                    'recommendation' => 'ابدأ برحلة العميل أو مسار التشغيل، ثم افصل ما يحتاج إلى تبسيط أو رقمنة أو تغيير تنظيمي قبل ترتيب المبادرات.',
                ],
                'product-platform' => [
                    'label' => 'اتجاه المنتج أو المنصة',
                    'description' => 'توضيح من يخدمه المنتج، وما الذي يجب أن يتيحه، وما الذي يستحق الأولوية.',
                    'recommendation_title' => 'أعد ربط خارطة المنتج بقرار واضح',
                    'recommendation' => 'ابدأ بالمستخدمين وقيود العمل والقرارات التي يجب أن يدعمها المنتج أو المنصة، ثم رتّب الأولويات التالية حول هذا الدليل.',
                ],
                'operations-automation' => [
                    'label' => 'العمليات والأتمتة',
                    'description' => 'تقليل التسليمات اليدوية وجعل العمل المتكرر أسهل في الرؤية والتنفيذ.',
                    'recommendation_title' => 'اجعل سير العمل مرئياً قبل أتمتته',
                    'recommendation' => 'ارسم التسليمات والاستثناءات والملكية كما تحدث فعلاً، ثم حدّد الأجزاء المستقرة بما يكفي للتبسيط أو الأتمتة.',
                ],
            ],
        ];

        return $options[$this->locale()];
    }

    /** @return array<string, array{label: string, recommendation: string}> */
    private function frictionOptions(): array
    {
        if (! $this->hasValidChallenge()) {
            return [];
        }

        $options = [
            'en' => [
                'ai-adoption' => [
                    'unclear-business-case' => [
                        'label' => 'There is interest, but no agreed business case',
                        'recommendation' => 'First clarify the business decision worth supporting, who owns it, and what would make an experiment useful.',
                    ],
                    'trust-and-review' => [
                        'label' => 'Pilot outputs are not yet trusted in daily work',
                        'recommendation' => 'Map why current outputs are hard to trust and what evidence a reviewer needs before using them.',
                    ],
                    'ownership-and-adoption' => [
                        'label' => 'Ownership, safeguards, and team adoption are unclear',
                        'recommendation' => 'Make roles, use boundaries, and human review part of the operating design from the beginning.',
                    ],
                ],
                'digital-transformation' => [
                    'fragmented-journey' => [
                        'label' => 'The customer or operating journey is split across disconnected channels',
                        'recommendation' => 'Map the current journey and its breaks before defining the target experience.',
                    ],
                    'competing-priorities' => [
                        'label' => 'There are too many initiatives and no clear sequence',
                        'recommendation' => 'Separate essential change from desirable change, then tie each priority to a business decision and dependency.',
                    ],
                    'change-readiness' => [
                        'label' => 'The process changed on paper more than in practice',
                        'recommendation' => 'Examine incentives, ownership, and daily ways of working so the change can be adopted in practice.',
                    ],
                ],
                'product-platform' => [
                    'unclear-product-direction' => [
                        'label' => 'The roadmap is not clearly tied to customer or business needs',
                        'recommendation' => 'Clarify the user decision and business constraint each major priority is meant to address.',
                    ],
                    'platform-friction' => [
                        'label' => 'The current platform makes growth or operations harder',
                        'recommendation' => 'Identify the recurring constraints that block teams or customers before proposing a replacement or expansion.',
                    ],
                    'delivery-drift' => [
                        'label' => 'The team ships, but learning and ownership keep drifting',
                        'recommendation' => 'Reconnect delivery to clear owners, learning questions, and decisions that follow from the evidence.',
                    ],
                ],
                'operations-automation' => [
                    'manual-handoffs' => [
                        'label' => 'Repeated manual handoffs slow the work down',
                        'recommendation' => 'Trace the handoffs, waiting points, and exceptions before deciding what should be simplified or automated.',
                    ],
                    'low-visibility' => [
                        'label' => 'Status, exceptions, and priorities are hard to see',
                        'recommendation' => 'Define which operating signals each owner needs and when an exception requires attention.',
                    ],
                    'fragile-process' => [
                        'label' => 'The process depends on specific people or varies each time',
                        'recommendation' => 'Capture the stable decisions and legitimate exceptions before standardizing the flow.',
                    ],
                ],
            ],
            'ar' => [
                'ai-adoption' => [
                    'unclear-business-case' => [
                        'label' => 'يوجد اهتمام، لكن لا توجد حالة عمل متفق عليها',
                        'recommendation' => 'وضّح أولاً القرار التجاري الذي يستحق الدعم، ومن يملكه، وما الذي سيجعل التجربة مفيدة.',
                    ],
                    'trust-and-review' => [
                        'label' => 'نتائج التجارب لا يمكن الاعتماد عليها بعد في العمل اليومي',
                        'recommendation' => 'حدّد لماذا يصعب الوثوق بالمخرجات الحالية وما الأدلة التي يحتاجها المراجع قبل استخدامها.',
                    ],
                    'ownership-and-adoption' => [
                        'label' => 'الملكية والضوابط وتبنّي الفريق غير واضحة',
                        'recommendation' => 'اجعل الأدوار وحدود الاستخدام والمراجعة البشرية جزءاً من طريقة العمل منذ البداية.',
                    ],
                ],
                'digital-transformation' => [
                    'fragmented-journey' => [
                        'label' => 'رحلة العميل أو العمل موزعة بين قنوات منفصلة',
                        'recommendation' => 'ارسم الرحلة الحالية ونقاط الانقطاع قبل تحديد شكل التجربة المستهدفة.',
                    ],
                    'competing-priorities' => [
                        'label' => 'هناك مبادرات كثيرة من دون تسلسل واضح',
                        'recommendation' => 'افصل التغيير الضروري عن المرغوب، ثم اربط كل أولوية بقرار عمل واعتماد واضح.',
                    ],
                    'change-readiness' => [
                        'label' => 'تغيّرت العملية على الورق أكثر مما تغيّرت في الممارسة',
                        'recommendation' => 'افحص الحوافز والملكية وطريقة العمل اليومية حتى يكون التغيير قابلاً للتبنّي فعلياً.',
                    ],
                ],
                'product-platform' => [
                    'unclear-product-direction' => [
                        'label' => 'خارطة المنتج غير مرتبطة بوضوح بحاجة العميل أو العمل',
                        'recommendation' => 'وضّح قرار المستخدم وقيد العمل الذي يفترض أن تعالجه كل أولوية رئيسية.',
                    ],
                    'platform-friction' => [
                        'label' => 'المنصة الحالية تجعل النمو أو التشغيل أصعب',
                        'recommendation' => 'حدّد القيود المتكررة التي تعيق الفرق أو العملاء قبل اقتراح استبدال المنصة أو توسيعها.',
                    ],
                    'delivery-drift' => [
                        'label' => 'الفريق يسلّم، لكن التعلم والملكية يتشتتان',
                        'recommendation' => 'أعد ربط التسليم بمالكين واضحين وأسئلة تعلم وقرارات تتبع الأدلة.',
                    ],
                ],
                'operations-automation' => [
                    'manual-handoffs' => [
                        'label' => 'التسليمات اليدوية المتكررة تبطئ العمل',
                        'recommendation' => 'تتبّع التسليمات ونقاط الانتظار والاستثناءات قبل تحديد ما يجب تبسيطه أو أتمتته.',
                    ],
                    'low-visibility' => [
                        'label' => 'الحالة والاستثناءات والأولويات غير ظاهرة بوضوح',
                        'recommendation' => 'حدّد إشارات التشغيل التي يحتاجها كل مسؤول ومتى يتطلب الاستثناء تدخلاً.',
                    ],
                    'fragile-process' => [
                        'label' => 'العملية تعتمد على أشخاص محددين أو تُنفّذ بطريقة مختلفة كل مرة',
                        'recommendation' => 'وثّق القرارات الثابتة والاستثناءات المشروعة قبل توحيد سير العمل.',
                    ],
                ],
            ],
        ];

        return $options[$this->locale()][$this->selectedChallenge] ?? [];
    }

    /** @return array<string, array{label: string, recommendation: string}> */
    private function outcomeOptions(): array
    {
        if (! $this->hasValidChallenge()) {
            return [];
        }

        $options = [
            'en' => [
                'ai-adoption' => [
                    'opportunity-brief' => [
                        'label' => 'A decision-ready AI opportunity brief',
                        'recommendation' => 'Turn the discussion into an opportunity brief covering the decision, users, boundaries, and validation questions.',
                    ],
                    'bounded-pilot' => [
                        'label' => 'A bounded pilot plan',
                        'recommendation' => 'Shape a small, reviewable pilot with a clear owner, boundaries, and acceptance criteria.',
                    ],
                    'governance-principles' => [
                        'label' => 'Practical adoption and governance principles',
                        'recommendation' => 'Finish with practical principles for ownership, review, and responsible use.',
                    ],
                ],
                'digital-transformation' => [
                    'phased-roadmap' => [
                        'label' => 'A phased transformation roadmap',
                        'recommendation' => 'Turn the priorities into phases with clear decisions, dependencies, and owners.',
                    ],
                    'priority-map' => [
                        'label' => 'A priority and dependency map',
                        'recommendation' => 'Create a decision map that shows what comes first, what depends on it, and what can wait.',
                    ],
                    'target-journey' => [
                        'label' => 'A clearer target customer or operating journey',
                        'recommendation' => 'Define the target journey, the moments that matter, and the organizational changes it requires.',
                    ],
                ],
                'product-platform' => [
                    'product-direction' => [
                        'label' => 'A sharper product direction',
                        'recommendation' => 'Write a concise direction that connects the audience, problem, business constraint, and next learning decisions.',
                    ],
                    'platform-roadmap' => [
                        'label' => 'A platform priority roadmap',
                        'recommendation' => 'Sequence platform priorities around the constraints they remove and the capabilities they enable.',
                    ],
                    'operating-model' => [
                        'label' => 'A clearer product operating model',
                        'recommendation' => 'Clarify decision ownership, learning cadence, and how evidence changes the roadmap.',
                    ],
                ],
                'operations-automation' => [
                    'workflow-roadmap' => [
                        'label' => 'A workflow improvement roadmap',
                        'recommendation' => 'Sequence workflow changes from clarification and simplification through to appropriate automation.',
                    ],
                    'automation-brief' => [
                        'label' => 'A bounded automation opportunity brief',
                        'recommendation' => 'Define one automation opportunity with its trigger, owner, exceptions, and safe fallback.',
                    ],
                    'visibility-plan' => [
                        'label' => 'An operating visibility plan',
                        'recommendation' => 'Agree on the small set of status and exception signals needed for timely operating decisions.',
                    ],
                ],
            ],
            'ar' => [
                'ai-adoption' => [
                    'opportunity-brief' => [
                        'label' => 'موجز فرصة ذكاء اصطناعي جاهز للنقاش',
                        'recommendation' => 'حوّل النقاش إلى موجز فرصة يحدد القرار والمستخدمين والحدود وأسئلة التحقق.',
                    ],
                    'bounded-pilot' => [
                        'label' => 'خطة تجربة محدودة',
                        'recommendation' => 'صغ تجربة صغيرة قابلة للمراجعة، مع مالك واضح وحدود ومعايير قبول.',
                    ],
                    'governance-principles' => [
                        'label' => 'مبادئ عملية للتبنّي والحوكمة',
                        'recommendation' => 'اختم بمبادئ عملية للملكية والمراجعة والاستخدام المسؤول.',
                    ],
                ],
                'digital-transformation' => [
                    'phased-roadmap' => [
                        'label' => 'خارطة تحول مرحلية',
                        'recommendation' => 'حوّل الأولويات إلى مراحل ذات قرارات واعتمادات ومالكين واضحين.',
                    ],
                    'priority-map' => [
                        'label' => 'خارطة أولويات واعتمادات',
                        'recommendation' => 'أنشئ خارطة قرار توضّح ما يبدأ أولاً، وما يعتمد عليه، وما يمكن تأجيله.',
                    ],
                    'target-journey' => [
                        'label' => 'رحلة مستهدفة أوضح للعميل أو التشغيل',
                        'recommendation' => 'حدّد الرحلة المستهدفة واللحظات المهمة والتغييرات التنظيمية التي تتطلبها.',
                    ],
                ],
                'product-platform' => [
                    'product-direction' => [
                        'label' => 'اتجاه منتج أكثر وضوحاً',
                        'recommendation' => 'اكتب اتجاهاً موجزاً يربط الجمهور والمشكلة وقيد العمل وقرارات التعلم التالية.',
                    ],
                    'platform-roadmap' => [
                        'label' => 'خارطة أولويات للمنصة',
                        'recommendation' => 'رتّب أولويات المنصة حول القيود التي تزيلها والقدرات التي تتيحها.',
                    ],
                    'operating-model' => [
                        'label' => 'نموذج تشغيل أوضح للمنتج',
                        'recommendation' => 'وضّح ملكية القرار وإيقاع التعلم وكيف تغيّر الأدلة خارطة المنتج.',
                    ],
                ],
                'operations-automation' => [
                    'workflow-roadmap' => [
                        'label' => 'خارطة لتحسين سير العمل',
                        'recommendation' => 'رتّب تغييرات سير العمل من التوضيح والتبسيط وصولاً إلى الأتمتة المناسبة.',
                    ],
                    'automation-brief' => [
                        'label' => 'موجز فرصة أتمتة محدودة',
                        'recommendation' => 'حدّد فرصة أتمتة واحدة مع محفّزها ومالكها واستثناءاتها ومسارها البديل الآمن.',
                    ],
                    'visibility-plan' => [
                        'label' => 'خطة لوضوح التشغيل',
                        'recommendation' => 'اتفق على مجموعة صغيرة من إشارات الحالة والاستثناء اللازمة لاتخاذ قرارات التشغيل في وقتها.',
                    ],
                ],
            ],
        ];

        return $options[$this->locale()][$this->selectedChallenge] ?? [];
    }

    /**
     * @return array{
     *     title: string,
     *     body: string,
     *     challenge: string,
     *     friction: string,
     *     outcome: string
     * }|null
     */
    private function recommendation(): ?array
    {
        if (! $this->hasCompleteContext()) {
            return null;
        }

        $challengeId = $this->selectedChallenge;
        $frictionId = $this->primaryFriction;
        $outcomeId = $this->desiredOutcome;

        if (! is_string($challengeId) || ! is_string($frictionId) || ! is_string($outcomeId)) {
            return null;
        }

        $challenge = $this->challengeOptions()[$challengeId];
        $friction = $this->frictionOptions()[$frictionId];
        $outcome = $this->outcomeOptions()[$outcomeId];

        return [
            'title' => $challenge['recommendation_title'],
            'body' => implode(' ', [
                $challenge['recommendation'],
                $friction['recommendation'],
                $outcome['recommendation'],
            ]),
            'challenge' => $challenge['label'],
            'friction' => $friction['label'],
            'outcome' => $outcome['label'],
        ];
    }

    private function consultationUrl(): ?string
    {
        if (! $this->hasCompleteContext()) {
            return null;
        }

        $recommendation = $this->recommendation();

        if ($recommendation === null || ! is_string($this->selectedChallenge)) {
            return null;
        }

        return localized_route('contact', [
            'source' => 'decision-room',
            'challenge' => $this->selectedChallenge,
            'friction' => $this->primaryFriction,
            'outcome' => $this->desiredOutcome,
            'service' => self::CONSULTATION_SERVICES[$this->selectedChallenge],
            'context' => $this->consultationContext($recommendation),
        ]).'#consultation';
    }

    /**
     * @param  array{challenge: string, friction: string, outcome: string}  $recommendation
     */
    private function consultationContext(array $recommendation): string
    {
        $copy = $this->copy();

        return implode("\n", [
            $copy['challenge_label'].': '.$recommendation['challenge'],
            $copy['friction_label'].': '.$recommendation['friction'],
            $copy['outcome_label'].': '.$recommendation['outcome'],
        ]);
    }

    private function locale(): string
    {
        return current_locale() === 'ar' ? 'ar' : 'en';
    }
}
