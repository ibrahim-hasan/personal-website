<?php

return [
    'index' => [
        'eyebrow' => 'Articles & insights',
        'title' => 'Practical reading for decision-makers.',
        'description' => 'Original writing on AI adoption, digital transformation, data, governance, and building products that deliver measurable impact.',
        'featured' => 'Featured article',
        'latest' => 'Latest articles',
        'all_articles' => 'All articles',
        'all_topics' => 'All topics',
        'library' => 'Idea library',
        'topics' => 'Article topics',
        'back' => 'Back to writing',
        'view_all' => 'View all articles',
        'read_article' => 'Read article',
        'read_more' => 'Continue reading',
        'search' => 'Search articles',
        'search_placeholder' => 'Search by title or topic',
        'filter_by_topic' => 'Filter by topic',
        'previous_topics' => 'Show previous topics',
        'next_topics' => 'Show more topics',
        'clear_filters' => 'Show all articles',
        'no_results' => 'No articles match this search yet.',
        'statement' => 'Good writing does more than display knowledge. It makes the next decision clearer.',
        'consultation_prompt' => 'Have a question like the one you just read? Bring it as it is, and let us begin with the decision the work needs.',
    ],
    'reader' => [
        'reader_mode' => 'Reader mode',
        'tools' => 'Reading tools',
        'enter_reader' => 'Enter reader mode',
        'exit_reader' => 'Exit reader mode',
        'listen' => 'Listen to article',
        'continue_listening' => 'Keep listening while you explore the site',
        'now_listening' => 'Now listening',
        'audio_player' => 'Article audio player',
        'close' => 'Close audio player',
        'pause' => 'Pause',
        'resume' => 'Resume',
        'speed' => 'Playback speed',
        'progress' => 'Playback position',
        'ready' => 'Ready to listen',
        'unsupported' => 'Audio reading is not supported by this browser.',
        'loading' => 'Preparing audio…',
        'playing' => 'Playing article',
        'paused' => 'Audio paused',
        'finished' => 'Article finished',
        'error' => 'Audio could not be played. Please try again.',
        'contents' => 'In this article',
        'published' => 'Published',
        'updated' => 'Updated',
        'related' => 'Related articles',
        'source_linkedin' => 'Read the original version on LinkedIn',
        'minutes' => ':count min read',
    ],
    'share' => [
        'label' => 'Share article',
        'actions' => 'Article sharing options',
        'native' => 'Share',
        'copy' => 'Copy link',
        'copied' => 'Link copied',
        'copy_failed' => 'The link could not be copied. Please try again.',
        'share_failed' => 'Sharing was unavailable. Choose another sharing option.',
        'linkedin' => 'Share on LinkedIn',
        'linkedin_short' => 'LinkedIn',
        'whatsapp' => 'Share via WhatsApp',
        'whatsapp_short' => 'WhatsApp',
        'email' => 'Share by email',
        'email_short' => 'Email',
    ],
    'topics' => [
        'ai_strategy' => 'AI strategy',
        'transformation' => 'Digital transformation',
        'data' => 'Data',
        'governance' => 'Governance',
        'operations' => 'Operations',
        'products' => 'Digital products',
        'leadership' => 'Leadership',
    ],
    'articles' => [
        'ai-value' => [
            'title' => 'From AI Experiment to Business Value: The Practical Steps Companies Need',
            'summary' => 'A ten-phase path from scattered AI exploration to a governed use case with measurable business impact and a credible route to scale.',
            'seo_title' => 'From AI Experiment to Measurable Business Value',
            'seo_description' => 'A practical ten-phase guide to selecting an AI use case, preparing workflows and data, measuring impact, establishing governance, and scaling responsibly.',
            'type' => 'Practical guide',
            'lead' => 'Saying that a company uses AI reveals very little about whether it creates value. The useful evidence is a visible change in work: a faster decision, fewer errors, better service, controlled cost, or greater team capacity. Moving from individual experimentation to repeatable value does not begin with another tool. It requires a sequence of decisions that connects the problem, workflow, data, accountability, and measurement.',
            'sections' => [
                [
                    'heading' => '1. Establish where the organization really is',
                    'paragraphs' => [
                        '“We use AI” might mean that several employees use a general tool to draft and summarize, or that the company runs a defined solution against an operational goal and monitors its results. Those are different levels of maturity. Individual use is a valuable way to build curiosity and skill, but it is not an organizational capability until its sources, boundaries, and accountability are understood.',
                        'Create an honest baseline rather than a promotional one. Inventory current uses, the people involved, the data being entered, the decisions affected, and any result being measured. Most activity will fall into one of three states: scattered exploration, organized pilots, or controlled operations. Naming the state prevents a premature push to scale.',
                    ],
                    'points' => [
                        'Document sanctioned and unsanctioned use instead of assuming the latter does not exist.',
                        'Separate learning how a tool works from operating a responsible use case.',
                        'Assess process, data, and oversight readiness rather than technical novelty.',
                    ],
                ],
                [
                    'heading' => '2. Start with a problem worth solving',
                    'paragraphs' => [
                        'The early question is not whether the business needs an assistant or an agent. It is where work loses time, quality, or opportunity. A service team may repeat the same answers; sales may spend too long assembling proposals; employees may struggle to find an approved policy; finance may manually reconcile similar documents. These are business problems before they are technology opportunities.',
                        'Frame the issue around the outcome and the people affected. Replace “we need a chatbot” with “approved answers are slow because knowledge is fragmented; we want faster access while routing sensitive cases to a specialist.” This wording preserves room for the right solution and may reveal that knowledge organization or a process change should come first.',
                    ],
                    'note' => 'If simplifying the process or repairing the current system removes the problem, the business has succeeded even though no AI project was launched.',
                ],
                [
                    'heading' => '3. Select one measurable use case',
                    'paragraphs' => [
                        'A long opportunity list can lead to several pilots running at once, scattering data, ownership, and attention. The stronger start is one recurring task with clear boundaries and a result that can be compared before and after. A narrow scope does not reduce ambition; it accelerates learning and makes false success harder to hide.',
                        'Define the use case through a task, outcome, and boundary. In support, the system might draft answers to known questions from approved sources while the employee sends them and escalates complaints or sensitive data. In sales, it might extract requirements and prepare a proposal draft without approving a price or discount. The contribution and the limits are both explicit.',
                        'Capture a baseline before the pilot: current cycle time, rework, errors, volume, and a quality assessment. Choose one primary outcome plus guardrails. A faster response is not a win if accuracy falls or complaints rise; a quicker proposal is not useful if a manager must rebuild it.',
                    ],
                    'points' => [
                        'A specific, recurring task.',
                        'A business owner able to make decisions.',
                        'Data that is reasonably available.',
                        'An outcome metric and a quality or risk guardrail.',
                    ],
                ],
                [
                    'heading' => '4. Map the workflow before changing it',
                    'paragraphs' => [
                        'A team cannot improve a process it does not understand. Map the start and finish, inputs, roles, approvals, exceptions, and systems. Identify where work waits, where information is entered twice, and where a decision depends on one person’s private knowledge. A simple map often shows that the actual bottleneck is somewhere other than the assumed one.',
                        'Customer service may appear to have a writing problem when the real issue is routing cases between support, sales, and finance. The best AI contribution might then be suggested classification and context summarization, not direct answers. In procurement, creating the request may be quick while unclear approval limits cause the delay.',
                    ],
                ],
                [
                    'heading' => '5. Prepare the knowledge and data',
                    'paragraphs' => [
                        'Model capability cannot compensate for outdated or contradictory institutional knowledge. If prices live in a spreadsheet, terms in email, and policies in several versions, a powerful model will reproduce the conflict faster. Before building, identify the authoritative source for each important fact, its owner, review date, and access rules.',
                        'A sales assistant may need the product catalog, approved prices, discount limits, payment terms, and proposal templates. Collecting them is not enough: terminology must be consistent, obsolete versions removed, and permitted suggestions defined. An employee assistant should separate general policy from personnel records and must not infer an HR decision from data collected for another purpose.',
                        'Test realistic samples. Are required fields complete? Does each department use the same meaning? Can a result be traced to its source? Is personal or confidential data included even though the use case does not need it? Data quality here is not an endless cleanup program; it is fitness for a defined purpose with visible limits.',
                    ],
                    'note' => 'Any source without an owner and review date becomes an operational risk, even if it was correct at launch.',
                ],
                [
                    'heading' => '6. Choose the right level of solution',
                    'paragraphs' => [
                        'Not every problem needs a system that acts autonomously. Conventional automation fits fixed rules and structured inputs. An assistant fits work where an employee benefits from a summary, analysis, or draft that remains subject to review. An agent is justified only when a task requires variable steps and system actions within permissions and boundaries that can be observed.',
                        'Moving a complete request into an internal system according to a known rule is automation. Drafting a response from varied context is closer to an assistant. Following up for missing documents, preparing an action, and stopping at an approval gate may justify a constrained agent.',
                    ],
                    'points' => [
                        'Fixed rules and structured inputs: automation.',
                        'Analysis or drafting with human approval: assistant.',
                        'Variable steps and bounded, traceable actions: agent.',
                    ],
                ],
                [
                    'heading' => '7. Build a small prototype that tests the hypothesis',
                    'paragraphs' => [
                        'A prototype is not merely a smaller final system. It is a test of the riskiest assumptions. Limit the team, use real tasks, and run long enough to encounter repetition and exceptions. The first version might cover thirty recurring questions, one proposal type, or one category of incoming requests.',
                        'Preserve a fair comparison. Collect examples from the existing process, evaluate outputs against a defined standard, and record human edits, rejection reasons, and cases without an adequate source. User enthusiasm alone is insufficient: a tool can be enjoyable without saving time, or fast while shifting correction work downstream.',
                        'The purpose is early learning. Is the knowledge sufficient? Does the team trust the proposal? Does the solution fit the operating rhythm? Did previously hidden risks appear? Expansion, redesign, and stopping are all useful outcomes when the decision is based on evidence.',
                    ],
                ],
                [
                    'heading' => '8. Measure impact in business language',
                    'paragraphs' => [
                        'Technical metrics help a team operate the solution, but they do not prove value. Leaders need to see what changed in the workflow. Support can track time to an approved response, first-contact resolution, answer quality, and complaints. Finance can track review time, detected exceptions, rework, and closing speed.',
                        'Make the comparison fair. Compare similar request types under comparable conditions, and distinguish AI impact from training or policy changes. Include the full cost: review time, integration, knowledge maintenance, and monitoring, not only subscription fees. A system may save minutes at the front while creating invisible correction work later.',
                        'Combine four dimensions: business outcome, output quality, user adoption, and risk. Balanced success means a meaningful improvement without transferring harm to customers, employees, or compliance. If the result does not improve, revisit the hypothesis instead of decorating the dashboard.',
                    ],
                    'points' => [
                        'Time, cost, or operating capacity.',
                        'Accuracy, quality, and rework.',
                        'Actual use, trust, and adoption.',
                        'Sensitive errors, exceptions, and incidents.',
                    ],
                ],
                [
                    'heading' => '9. Establish governance before scaling',
                    'paragraphs' => [
                        'Governance is not a committee that approves a pilot once and disappears. It is a set of operational answers: who owns the use case, who approves the output, which data is permitted, where records are kept, what confidence is acceptable, when the solution stops or escalates, and who responds when something fails.',
                        'Apply controls in proportion to risk. A tool that summarizes public material does not need the same oversight as one proposing a financial decision or handling personal data. Every use case still needs a purpose, owner, data sources, permissions, change history, and incident path. In Saudi Arabia and the Gulf, these controls must connect to applicable privacy, cybersecurity, sector, and contractual obligations.',
                        'Build review into operations through periodic sample tests, quality-drift monitoring, access reviews, knowledge updates, and a fallback plan. These controls are not the opposite of speed. They allow the organization to move without losing control.',
                    ],
                ],
                [
                    'heading' => '10. Scale only what has earned the right to scale',
                    'paragraphs' => [
                        'A successful pilot should not be copied immediately across every department. First determine whether the result is stable, the cost is acceptable, knowledge can be maintained, the owner is prepared to operate it, and controls hold under higher volume. A use case may work in one team because its process is consistent and fail elsewhere because terminology and exceptions differ.',
                        'Scale in stages: more users inside the same process, adjacent scopes, and only then additional integrations or permissions. Keep a comparison group, stop condition, and risk review at each stage. Do not add autonomy merely because the technology permits it; add it when evidence shows a better result that remains observable.',
                    ],
                    'note' => 'A healthy AI portfolio is not the one with the most projects. It is the one with the highest share of useful, operable, and trusted use cases.',
                ],
            ],
            'closing' => 'Moving from AI experimentation to business value is a disciplined path: understand the current state, choose a real problem, narrow the use case, map the workflow, prepare knowledge, select the right solution level, pilot at small scale, measure impact, govern it, and expand only what deserves to grow. The practical question is not “which tool is next?” It is “what is the first result in our work that we can change clearly and prove through evidence, quality, and accountability?”',
        ],
        'ai-not-answer' => [
            'title' => 'When AI Is Not the Answer: Fix the Process, Data, or System First',
            'summary' => 'A diagnostic guide to recognizing when the root cause is a broken process, unreliable data, or an underused core system—and where AI becomes useful after the foundation is repaired.',
            'seo_title' => 'When AI Is Not the Right Business Solution',
            'seo_description' => 'Learn how to distinguish problems that need process, data, or system improvement from those where AI can add genuine value.',
            'type' => 'Diagnostic article',
            'lead' => 'AI is sometimes offered as a quick answer to a question that has not been diagnosed. When a process is contradictory, data is unreliable, or the core system does not enforce a clear rule, adding an intelligent model can conceal the defect and accelerate it. A mature decision does not reject AI; it establishes when AI belongs and what must be fixed first.',
            'sections' => [
                [
                    'heading' => '1. Identify the symptom before naming the solution',
                    'paragraphs' => [
                        'Begin with what actually happens. Where does a request stop? Who returns it? Which error repeats? Which decision waits? If every person gives a different answer, the process is unclear. If the answer is consistent but staff copy data between two screens, the opportunity is automation. If people must interpret varied documents or messages, AI may have a role.',
                        'A backlog of supplier invoices may appear to call for AI document extraction. But if invoices arrive without purchase-order references or approval rules differ by department, extraction will not resolve the delay. It will deliver information faster to a disagreement that the organization has not settled.',
                    ],
                    'points' => [
                        'Unclear ownership points to a process problem.',
                        'Missing fields or conflicting definitions point to a data problem.',
                        'Repeated fixed steps point to automation or system improvement.',
                        'Interpreting varied content under uncertainty may justify AI.',
                    ],
                ],
                [
                    'heading' => '2. Fix the process when variation is organizational',
                    'paragraphs' => [
                        'If a team cannot agree when work starts, who approves it, or which exception is valid, a model cannot manufacture a sound policy. It may suggest a path, but that path will reflect contradictions in the examples. The immediate work is to standardize the flow, name the owner, remove unnecessary approvals, and document exceptions.',
                        'Customer complaints may be slow because three teams each believe another owns them. A bot that writes better responses will not change this. Defining categories, service expectations, and case ownership will. Once the flow is stable, an assistant can summarize the conversation or propose routing.',
                    ],
                    'note' => 'Do not automate a disagreement that leadership has not resolved; resolving it is part of transformation.',
                ],
                [
                    'heading' => '3. Fix the data when there is no shared truth',
                    'paragraphs' => [
                        'AI cannot know which customer, product, or price record is authoritative unless the business makes that decision. Different product names, duplicated customers, and undefined order states produce plausible answers that cannot be trusted.',
                        'Consider demand forecasting. If one branch records returns as negative sales while another excludes them, a forecasting model learns two incompatible definitions. The first solution is a shared definition, a data owner, quality checks, and correction at the source. Only then can advanced forecasting be evaluated honestly.',
                    ],
                    'points' => [
                        'An authoritative source for each core entity.',
                        'Shared definitions for states and metrics.',
                        'Completeness and freshness matched to the decision.',
                        'A way to trace and correct errors at their source.',
                    ],
                ],
                [
                    'heading' => '4. Improve the system when the rule is known',
                    'paragraphs' => [
                        'If a decision can be expressed as “when this condition is true, do that,” a deterministic solution is usually better. Checking whether a form is complete, preventing a discount beyond authority, and sending a reminder after a defined interval do not require a probabilistic model. They require a system that always applies and records the rule.',
                        'For inventory, a reorder alert based on a known threshold and trustworthy stock count can be a simple rule. AI might later propose dynamic thresholds using seasonality, but it should not compensate for incorrect stock records or uncontrolled issue transactions.',
                    ],
                ],
                [
                    'heading' => '5. Apply a four-part decision test',
                    'paragraphs' => [
                        'Ask four questions before approving a use case. Are inputs structured or do they require interpretation? Is the rule fixed or context-dependent? Is an error reversible or high impact? Is there a correct result against which performance can be evaluated? These questions move the conversation away from product names and toward the nature of work.',
                        'Structured inputs and fixed rules favor automation. Contextual work that produces a reviewable draft favors an assistant. A high-impact decision with no clear ground truth may be unsuitable for automation, or may use AI only for research and summarization while judgment remains human.',
                    ],
                    'points' => [
                        'Start with the simplest intervention that changes the outcome.',
                        'Require evidence that uncertainty is genuine rather than internal disorder.',
                        'Include reversibility and error cost in the choice.',
                    ],
                ],
                [
                    'heading' => '6. Add AI after it has a clear job',
                    'paragraphs' => [
                        'Once the process is simplified, data definitions are aligned, and fixed rules are enforced, expensive variable work will remain: reading free text, summarizing files, searching broad knowledge, or proposing options for a novel case. AI now adds value by handling variation rather than covering a weak foundation.',
                        'Design the use around a specific contribution and explicit boundary. It can propose a category with confidence, draft an answer while showing its source, or flag a document for review. Measure it against the improved stable process—not against the old chaos—to see the value AI itself created.',
                    ],
                ],
            ],
            'closing' => 'The best technology decision may be to simplify a form, remove an approval, align a definition, or repair an integration. If that solves the problem, the company has created value sooner and with less risk. Use AI when the remaining task genuinely involves language, uncertainty, and context, and only after the work has a foundation that can be trusted.',
        ],
        'transformation-before-software' => [
            'title' => 'Why Transformation Fails Before Software Implementation Begins',
            'summary' => 'Many initiatives fail before any code is written because the operating outcome, ownership, incentives, and exceptions remain unresolved. This is a readiness framework for the work before build or procurement.',
            'seo_title' => 'Why Digital Transformation Fails Before Implementation',
            'seo_description' => 'A practical framework for defining the target operating model, ownership, decisions, data, and readiness before buying or building transformation software.',
            'type' => 'Leadership article',
            'lead' => 'Many transformation programs are later described as implementation failures even though the failure was present in the initiative’s definition. Software cannot choose between conflicting goals, replace an absent process owner, or repair incentives that reward people for bypassing the system. Delivery quality matters, but the conditions for success are created before delivery starts.',
            'sections' => [
                [
                    'heading' => '1. Separate digitizing the current state from changing work',
                    'paragraphs' => [
                        'Turning a paper form into a screen is not transformation if the same approvals, waiting, and re-entry remain. Transformation changes the flow of work, a decision, or the customer experience, and software then makes that change repeatable. If an initiative cannot describe what will be different in daily operations, it is closer to a tool replacement.',
                        'A procurement initiative may be called “a digital platform,” while its actual desired outcomes are fewer incomplete requests, clear approval limits, and visible status. Those outcomes should lead the design. The platform name does not define them.',
                    ],
                    'points' => [
                        'Which behavior or decision will change?',
                        'Who should experience the improvement, and how?',
                        'Which step will disappear, move, or become visible?',
                    ],
                ],
                [
                    'heading' => '2. Design the target operating model',
                    'paragraphs' => [
                        'Before drawing screens, map how work should operate after the change: who initiates it, required data, decision rights, standard and exceptional paths, and the service users should expect. This target operating model is a practical description of how people, policy, data, and systems work together.',
                        'A field-service app is not enough on its own. The organization must decide who sets priority, how visits are assigned, when a job is complete, who accounts for a spare part, and what happens without connectivity. If those questions remain open, the app becomes an interface layered over calls and side messages.',
                    ],
                ],
                [
                    'heading' => '3. Give the process an owner with decision rights',
                    'paragraphs' => [
                        'Technology may manage the project, but the business process cannot remain ownerless. The owner settles definitions, balances departmental needs, approves exceptions, and accepts the operational outcome. A broad committee without a decision-maker accumulates requirements and produces a solution that satisfies everyone superficially and serves nobody well.',
                        'Define practical roles: a sponsor who removes obstacles, a process owner, a product owner who orders priorities, data owners, and user representatives. Names are not enough. State which decisions each role controls and the expected time for resolving them.',
                    ],
                    'note' => 'If every disagreement requires executive escalation, the bottleneck is governance design rather than software delivery speed.',
                ],
                [
                    'heading' => '4. Expose incentives and shadow work',
                    'paragraphs' => [
                        'Users may reject a system because it slows them down, but their performance measure may reward individual speed rather than shared data quality. A manager may keep a private spreadsheet because it provides flexibility the official process lacks. Calling all of this “resistance to change” loses useful information.',
                        'Inventory shadow work: spreadsheets, chat approvals, verbal agreements, and personal calculations. Ask which need each one serves. It may reveal a valid exception to design, a control to preserve, or a habit that can be retired. Successful transformation resolves the reason for the workaround.',
                    ],
                ],
                [
                    'heading' => '5. Convert ambiguity into readiness gates',
                    'paragraphs' => [
                        'Do not begin building merely because funding is approved. Use clear gates: an agreed outcome and measure, a mapped current process, an approved target model, named owners and decisions, understood core data, and a plan for adoption and support. Documentation need not be perfect; the aim is to remove questions that would later change the solution’s foundation.',
                        'A customer-relationship program is not ready if sales and marketing disagree on a qualified lead, account ownership, or mandatory data. Buying the system first transfers the disagreement into fields and reports, where it becomes more expensive.',
                    ],
                    'points' => [
                        'Business outcome and baseline.',
                        'Current and target process.',
                        'Decision rights and exception route.',
                        'Data definitions and quality ownership.',
                        'Transition, training, and support plan.',
                    ],
                ],
                [
                    'heading' => '6. Deliver increments that change a complete outcome',
                    'paragraphs' => [
                        'Once ready, divide delivery by usable outcomes rather than isolated technology layers. A strong increment may enable one request type end to end for one team, including measurement and support. It tests policy, data, and experience together.',
                        'Give each increment a success hypothesis, user group, guardrails, and fallback. Observe whether work actually moved into the new path or became duplicated. If users update both the new system and an old spreadsheet, login counts are not evidence of transformation.',
                    ],
                ],
            ],
            'closing' => 'Software amplifies the decisions that precede it. Clear outcomes, ownership, and definitions allow it to stabilize a better way of working; ambiguity becomes expensive ambiguity at scale. Before implementation, require a testable description of work after transformation, ownership of each decision, and evidence that will show whether the outcome changed.',
        ],
        'data-readiness' => [
            'title' => 'Data Readiness Before AI: A Practical Decision Framework',
            'summary' => 'A six-part framework for testing whether data is fit for a specific use case across purpose, source, quality, meaning, access, and sustainable operation.',
            'seo_title' => 'A Practical Data-Readiness Framework Before AI',
            'seo_description' => 'Assess data sources, quality, definitions, privacy, ownership, and operating readiness before launching an AI use case.',
            'type' => 'Practical framework',
            'lead' => '“We have a lot of data” does not mean the data is ready. Readiness is not a perfect warehouse or an endless cleanup program. It is the ability of defined data to support a decision or task with acceptable accuracy, freshness, and lawful use. Readiness should therefore be assessed at use-case level, not declared once for the entire company.',
            'sections' => [
                [
                    'heading' => '1. Begin with the decision the data will support',
                    'paragraphs' => [
                        'Define the output, its user, when it is needed, and the consequence of error. An assistant answering leave-policy questions needs authoritative current sources. Demand forecasting needs consistent history and factors that explain change. One universal quality checklist will not serve both.',
                        'Write a simple use-case contract: required inputs, intended output, update frequency, acceptable accuracy, and excluded cases. This prevents the team from collecting everything available and forces every data element to justify its role.',
                    ],
                    'points' => [
                        'What decision or task is being supported?',
                        'What time horizon is required?',
                        'What is the impact of error or delay?',
                        'How will the result be verified?',
                    ],
                ],
                [
                    'heading' => '2. Know the source, owner, and data path',
                    'paragraphs' => [
                        'Every important field has a history: where it originated, who entered it, how it changed, and where it was copied. Data lineage means being able to trace that history. A company does not need a complex platform to begin; it needs a clear map of sources, integrations, transformations, and owners.',
                        'If “total sales” comes from the order system and is then adjusted in a finance spreadsheet, decide which is authoritative and why. If product descriptions are copied among teams, name the owner of the final text. A source without an owner has no reliable route for correction.',
                    ],
                ],
                [
                    'heading' => '3. Measure quality against the purpose',
                    'paragraphs' => [
                        'Test completeness, accuracy, freshness, consistency, and uniqueness, but do not treat them as cosmetic scores. Connect each defect to the outcome. A missing phone number may be irrelevant for inventory analysis and critical for customer contact. Week-old data may suit monthly planning and fail real-time routing.',
                        'Use a representative sample that includes ordinary and exceptional cases. Quantify missing fields, duplication, impossible values, and source conflicts, then review a sample with a business expert. Numbers expose patterns; experts explain what those patterns mean.',
                    ],
                    'note' => 'The target is “fit for this purpose,” with explicit documentation of purposes the data cannot support.',
                ],
                [
                    'heading' => '4. Align meaning before training or integration',
                    'paragraphs' => [
                        'Data can be complete while carrying different meanings. Is an active customer someone who bought in the last month or year? Does cancelled demand count as revenue? Does resolution time start at ticket creation or assignment? Semantic disagreement produces conflicting reports and models.',
                        'Create a compact glossary for consequential concepts, including definition, calculation, owner, and exceptions. Do not begin with hundreds of fields. Start with the terms used by the use case. Shared language makes AI output testable.',
                    ],
                ],
                [
                    'heading' => '5. Design access and privacy from the start',
                    'paragraphs' => [
                        'Availability does not establish permission to use data. Define the purpose, minimum necessary data, authorized roles, retention, and whether the information is personal, sensitive, sector-regulated, or contractually restricted. Mask or aggregate when identity is not required.',
                        'An employee knowledge assistant may need general policies but not payroll records. Complaint-theme analysis may work on de-identified text. Minimization reduces risk and cost while making access review easier.',
                    ],
                    'points' => [
                        'A legitimate, specific purpose.',
                        'The minimum data required.',
                        'Role-based access.',
                        'A record of use and change.',
                    ],
                ],
                [
                    'heading' => '6. Use a readiness card and improvement plan',
                    'paragraphs' => [
                        'Rate each dimension visibly: relevance to purpose, source and ownership, quality, shared meaning, access and compliance, and the ability to update and monitor. Do not hide a critical blocker inside one combined score. Overall readiness may look high while lack of usage rights prevents the project.',
                        'Turn gaps into owned actions with dates: align an order state, repair an integration, archive an obsolete policy, or add a quality check. After critical blockers are addressed, run a prototype on a controlled sample and monitor change in production. Readiness is an operating condition, not a launch certificate.',
                    ],
                ],
            ],
            'closing' => 'Do not wait for perfect data, and do not ignore the foundation. Select a use case, define the minimum data it needs, test source, meaning, quality, and permission, then remove the largest blockers. Ready data is data the organization can explain, protect, correct, and operate with confidence.',
        ],
        'human-in-loop' => [
            'title' => 'Where Human Judgment Belongs in AI-Enabled Workflows',
            'summary' => 'A practical design for placing people at points of risk, uncertainty, and accountability without turning human review into a ceremonial approval or bottleneck.',
            'seo_title' => 'Human Judgment in AI-Enabled Business Workflows',
            'seo_description' => 'Design human review, escalation, and approval in AI workflows according to risk, confidence, reversibility, and accountability.',
            'type' => 'Workflow design guide',
            'lead' => '“Human in the loop” is not a sufficient control. It can describe genuine review, or a tired employee clicking approve without evidence. A sound design explains why a person intervenes, what context they receive, which decision they own, and when they can stop or correct the system.',
            'sections' => [
                [
                    'heading' => '1. Place people where judgment has value',
                    'paragraphs' => [
                        'Not every output requires individual review. Mandatory inspection of every low-risk case can erase the benefit and turn employees into machine monitors. At the other extreme, allowing high-impact decisions to proceed without review creates risk that speed does not justify.',
                        'Look for points requiring context unavailable in the data, a balance between competing interests, formal accountability, or empathy for an exception. Suggested prioritization of routine tickets may run automatically; closing a sensitive complaint or granting an exception outside policy requires an authorized person.',
                    ],
                ],
                [
                    'heading' => '2. Classify decisions by impact and reversibility',
                    'paragraphs' => [
                        'Use three dimensions: impact on people, money, or obligations; ease of reversal; and uncertainty. As impact rises, reversal becomes harder, and confidence falls, pre-action review becomes more important. Low-impact reversible actions may instead be monitored through post-action samples.',
                        'A product-description draft is easily changed. Sending a binding proposal or changing an entitlement is not. In recruitment, AI might organize and summarize applications, but automated rejection introduces questions of fairness, explanation, and responsibility.',
                    ],
                    'points' => [
                        'Impact and sensitivity for the affected person.',
                        'Ability to cancel or correct the action.',
                        'System confidence and available evidence.',
                        'Regulatory or contractual obligations.',
                    ],
                ],
                [
                    'heading' => '3. Distinguish input, output, and action review',
                    'paragraphs' => [
                        'A person may need to confirm data before processing, review the proposed output, or approve the final action. The location matters. If the source is unreliable, reviewing polished language at the end is insufficient. If the output is advisory analysis, review may belong at the decision rather than every intermediate step.',
                        'In procurement, software can extract offer details, an employee confirms missing fields, the system compares options, and an authorized owner approves the choice. This distribution puts each person’s expertise where it affects the outcome without repeating the entire task.',
                    ],
                ],
                [
                    'heading' => '4. Design escalation with context and evidence',
                    'paragraphs' => [
                        'An escalation is not successful when it delivers an unexplained case to a person. The reviewer should see the original request, sources used, proposed output, reason for escalation, interpretable confidence information, and actions already taken. This reduces reconstruction time and discourages blind approval.',
                        'Define escalation rules such as conflicting sources, missing required information, an out-of-policy request, sensitive content, or low confidence. Give the reviewer explicit options: accept, edit, reject, request information, or report a problem. Record the reason so human judgment becomes improvement data.',
                    ],
                ],
                [
                    'heading' => '5. Protect reviewers from approval fatigue',
                    'paragraphs' => [
                        'If a system sends hundreds of similar cases, reviewers begin approving quickly. This automation bias is the tendency to accept a machine suggestion because it is present and confident. Reduce it by exposing evidence and differences, distributing workload, and, for selected sensitive reviews, asking for an independent view before showing the recommendation.',
                        'Use full review for high-risk cases, random samples for low-risk categories, and targeted review after model or data changes. Measure review time, edit rate, and rejection reasons. High levels may show that the solution is transferring work rather than reducing it.',
                    ],
                    'note' => 'Human approval is not a control when the reviewer lacks time, information, or authority to disagree.',
                ],
                [
                    'heading' => '6. Learn from human decisions without treating them as perfect',
                    'paragraphs' => [
                        'Edits and escalations reveal system weaknesses, but the human decision is not automatically correct. Review consistency between people, investigate important differences, and update policy or training when disagreement is organizational.',
                        'Create a regular review of samples, sensitive errors, overrides, and category performance. Evidence may later support automating more cases or returning review to an earlier point. A useful human loop evolves with risk and evidence rather than remaining fixed after launch.',
                    ],
                ],
            ],
            'closing' => 'Place people at points of responsibility and uncertainty, not at every click. Give them context, evidence, and the right to reject, and measure review quality as carefully as system performance. The goal is a workflow that combines machine speed with real human judgment and makes final accountability unmistakable.',
        ],
        'first-ai-use-case' => [
            'title' => 'How to Choose the First Measurable AI Use Case',
            'summary' => 'A method for ranking AI opportunities by value, feasibility, risk, and adoption, then turning the selected opportunity into a baselined experiment with a scale decision.',
            'seo_title' => 'Choosing Your First Measurable AI Use Case',
            'seo_description' => 'Use a practical scorecard to select a first AI project, define its scope and metrics, run the pilot, and make an evidence-based scale decision.',
            'type' => 'Selection guide',
            'lead' => 'The first AI use case should not be the most spectacular. It should be the one most able to teach the organization and produce clear impact at proportionate risk. A weak selection creates a long experiment that nobody can judge. A strong one builds trust, data, and operating capability that can be reused.',
            'sections' => [
                [
                    'heading' => '1. Collect problems rather than tool ideas',
                    'paragraphs' => [
                        'Ask departments for time-consuming tasks, slow decisions, recurring errors, repeated questions, and information that is hard to reach. Keep the first round from becoming a list of bots and agents. The purpose is to understand pain and outcomes.',
                        'Turn each opportunity into a sentence: “When this happens, this role spends time doing this, which causes that result, and we want to change this measure.” For example, customer requests arrive as free text and service staff classify them manually, delaying routing; the desired contribution is faster suggested classification while unclear cases stay with a reviewer.',
                    ],
                ],
                [
                    'heading' => '2. Score business value',
                    'paragraphs' => [
                        'Assess frequency, volume, time, and impact on the customer, revenue, cost, or risk. A task that takes minutes but occurs thousands of times may matter more than a complex monthly executive report. Ask whether improvement changes a result or merely makes an unimportant step more elegant.',
                        'Name a benefiting owner who is prepared to change the process, not only a sponsor interested in technology. Without an owner who supplies experts and data and settles scope decisions, the use case remains a demonstration.',
                    ],
                    'points' => [
                        'Recurring, material pain.',
                        'An outcome connected to a business goal.',
                        'An owner able to change the work.',
                        'A baseline that can be captured.',
                    ],
                ],
                [
                    'heading' => '3. Score feasibility and readiness',
                    'paragraphs' => [
                        'Ask whether real examples, knowledge sources, an evaluation method, and a place in the workflow exist. Summarizing repeated documents with historical examples is easier to evaluate than a rare strategic decision with no reference answer. Do not reject a case because it needs cleanup, but distinguish a fixable gap from ownerless disorder.',
                        'Assess user readiness as well. An assistant will fail if the team has no time to review it or distrusts the source. A better interface, source cleanup, or training may be a prerequisite to the pilot.',
                    ],
                ],
                [
                    'heading' => '4. Subtract risk from attractiveness',
                    'paragraphs' => [
                        'A high-value opportunity may still be a poor first use case if errors are consequential and irreversible, data rights are unclear, or outputs are hard to explain. Start with a reviewable contribution rather than a final decision affecting people, money, or obligations.',
                        'Drafting a proposal from an approved catalog is lower risk than approving a discount. Summarizing a complaint is lower risk than rejecting compensation. A large use case can often be divided into a lower-risk step that proves value and builds controls.',
                    ],
                    'note' => 'Risk does not end innovation; it determines the starting point and the form of oversight.',
                ],
                [
                    'heading' => '5. Write a one-page use-case card',
                    'paragraphs' => [
                        'Document the problem, user, task, inputs, output, exclusions, human review, success metrics, and guardrails. Add the largest assumption the experiment is designed to test. If the card requires many broad phrases, the scope is not yet ready.',
                        'Capture baseline cycle time, errors, rework, volume, and quality before launch. Define the pilot target as a realistic direction or decision threshold without inventing a number unsupported by history. The team must know what evidence will lead it to scale, revise, or stop.',
                    ],
                    'points' => [
                        'One primary outcome measure.',
                        'A quality or risk measure.',
                        'A real-adoption measure.',
                        'Full operating cost.',
                    ],
                ],
                [
                    'heading' => '6. Test in a limited real setting and decide explicitly',
                    'paragraphs' => [
                        'Choose a representative sample, small team, and period long enough to include exceptions. Keep a comparison with current work and record edits, rejections, and escalations. Do not showcase only the best examples; difficult cases determine operational viability.',
                        'End with one of four decisions: scale, make a defined improvement and retest, narrow the scope, or stop. Tie the decision to evidence rather than enthusiasm or sunk cost. A good pilot reduces uncertainty even when it proves the opportunity is not ready.',
                    ],
                ],
            ],
            'closing' => 'Choose a first use case with recurring pain, a real owner, usable data, an evaluable output, and manageable risk. Narrow it until the hypothesis is explicit, measure it against a baseline, and announce the decision. That turns the first project into a foundation for a value portfolio rather than an isolated demonstration.',
        ],
        'automation-assistant-agent' => [
            'title' => 'Automation, Assistant, or Agent? Choosing in Business Language',
            'summary' => 'A practical explanation of automation, assistants, and agents, with criteria for selecting the simplest level that can deliver the required result within understandable permissions and risk.',
            'seo_title' => 'Automation, AI Assistant, or Agent: A Business Guide',
            'seo_description' => 'Choose automation, an assistant, or an AI agent based on rule stability, judgment, permissions, reversibility, and the cost of error.',
            'type' => 'Decision guide',
            'lead' => 'These terms describe different levels of responsibility, not marketing tiers of sophistication. Automation executes a known rule, an assistant prepares work for a person who decides, and an agent chooses steps and takes actions within a mandate. The right choice begins with the process and risk, not the desire to use the newest label.',
            'sections' => [
                [
                    'heading' => '1. Automation: when the steps are known',
                    'paragraphs' => [
                        'Automation suits stable work with defined inputs, conditions, and outputs. It moves a file, checks a field, creates a task, sends a notification, or enforces an approval route. Its advantage is predictability and testability; a probabilistic interpretation is unnecessary when the rule is explicit.',
                        'Once a complete purchase request is approved, a system can create an order, notify the supplier, and update status. If required data is absent, it stops and routes the case to an employee. Adding an AI model here may reduce clarity without adding value.',
                    ],
                    'points' => [
                        'Fixed rules and known cases.',
                        'Structured data.',
                        'A correct result known in advance.',
                        'A strong need for consistency.',
                    ],
                ],
                [
                    'heading' => '2. Assistant: when people need faster judgment',
                    'paragraphs' => [
                        'An assistant reads, summarizes, proposes, or drafts while leaving the decision and execution to the user. It fits unstructured content and cases that benefit from human context. Its value is reducing research and first-draft effort without obscuring accountability.',
                        'A service assistant can assemble case history and draft a response from approved policy; an employee reviews accuracy and tone before sending. An internal assistant can answer policy questions with sources and route personal circumstances to HR.',
                    ],
                    'note' => 'If users must rebuild every output, the assistant is adding a review layer rather than removing work.',
                ],
                [
                    'heading' => '3. Agent: when steps vary and action is permitted',
                    'paragraphs' => [
                        'An agent receives a goal, selects from available tools or steps, observes the result, and may try again. This can help when the path cannot be fully specified in advance, but it raises the standard for permissions, logs, and operating boundaries.',
                        'An agent might follow up on missing supplier documents: inspect status, send a precise request, classify the reply, and update the task before stopping at supplier approval. The aim is not unlimited autonomy. A useful agent has a narrow scope, permitted actions, communication or spending limits, and approval gates.',
                    ],
                ],
                [
                    'heading' => '4. Use five questions to choose',
                    'paragraphs' => [
                        'Ask whether the steps are fixed, inputs are structured, the output needs judgment, the solution will act in a system, and a wrong action is costly. Fixed steps favor automation. A supervised proposal favors an assistant. Move to an agent only when path flexibility is necessary and actions can be constrained and observed.',
                        'Add an economic question: does the value of flexibility exceed the cost of operation and oversight? An agent may be able to execute ten steps, but a simple daily process may be served more safely by three deterministic rules.',
                    ],
                    'points' => [
                        'Path stability.',
                        'Input variation.',
                        'Need for human judgment.',
                        'Breadth of permission.',
                        'Impact and reversibility of error.',
                    ],
                ],
                [
                    'heading' => '5. Design boundaries before granting permissions',
                    'paragraphs' => [
                        'Every solution has boundaries, but they become critical for agents. Define systems, readable data, permitted actions, value limits, communication recipients, and approval points. Separate read, propose, write, and execute permissions.',
                        'Log each step, input, output, and approval. Provide an immediate stop and a recovery path for reversible actions. Do not use a broad account merely to simplify integration; match authority to the task.',
                    ],
                ],
                [
                    'heading' => '6. Evolve only when evidence shows the need',
                    'paragraphs' => [
                        'A company can begin with an assistant to observe how employees work and capture edit reasons. Stable parts may then become automation. If variable, recurring steps remain and can be bounded, an agent can be tested in a restricted environment. Progress is not a compulsory march toward more autonomy.',
                        'Measure outcome at every level: time, quality, exceptions, review burden, incidents, and cost. The strongest design may combine automation for rules, an assistant for language, and a person for approval—with no agent at all.',
                    ],
                ],
            ],
            'closing' => 'Choose the simplest level that delivers the result with confidence. Use automation for rules, an assistant to accelerate human work, and an agent only when the task requires variable planning and bounded actions. Maturity is measured by clarity of authority, accountability, and value—not by autonomy.',
        ],
        'measure-digital-impact' => [
            'title' => 'How Leaders Measure Digital Product and Transformation Impact',
            'summary' => 'A measurement system connecting delivery and adoption to behavior change and business outcomes, so leaders do not confuse launching a product with creating impact.',
            'seo_title' => 'Measuring Digital Product and Transformation Impact',
            'seo_description' => 'Connect product, process, financial, and risk measures through baselines, an impact chain, unit economics, and a regular decision cadence.',
            'type' => 'Measurement article',
            'lead' => 'Launching a platform or increasing feature count does not establish transformation success. Impact appears when people use a new capability, a behavior or process changes, and an important outcome improves. Leaders need a measurement chain connecting delivery to change—not a dashboard of easy numbers without an explanation.',
            'sections' => [
                [
                    'heading' => '1. Write the impact chain before the metric list',
                    'paragraphs' => [
                        'Start with the desired outcome and work backward. Which behavior must change? Which digital capability enables it? What must be delivered? For digital onboarding, the result is not “launch the form.” It is faster, higher-quality completion; the behavior is customers completing steps without assistance; the capability is a clear form that validates data.',
                        'This chain prevents the product from claiming every change. If a campaign launched at the same time or policy changed, record it. Good measurement acknowledges other causes and selects proportionate evidence instead of claiming perfect causality.',
                    ],
                    'points' => [
                        'Capability delivered.',
                        'Adoption and use.',
                        'Behavior or process change.',
                        'Business outcome and risk.',
                    ],
                ],
                [
                    'heading' => '2. Establish a baseline and comparison',
                    'paragraphs' => [
                        'Measure conditions before the change: time, cost, conversion, errors, complaints, or another relevant result. Define the calculation, source, and period. An undocumented baseline lets every team select a number that flatters the story.',
                        'Choose a useful comparison: a team or branch starting later, phased rollout, or before-and-after analysis adjusted for seasonality. Not every initiative requires a scientific experiment, but every impact claim needs a reasonable view of what might have happened without the change.',
                    ],
                ],
                [
                    'heading' => '3. Balance value, quality, capability, and risk',
                    'paragraphs' => [
                        'Use a small metric set. Business value may be revenue, cost, or operating capacity. Quality may be accuracy or first-time completion. Capability includes reliability, response time, and support. Risk includes privacy, fraud, and exception incidents.',
                        'Focusing only on conversion may push unsuitable customers into a later problem. Focusing on speed may increase rework. Guardrails expose the price paid for an improvement and prevent one part of the system from being optimized at the expense of another.',
                    ],
                    'points' => [
                        'One or two primary outcome measures.',
                        'Quality and experience measures.',
                        'Operating-capability measures.',
                        'Risk and cost measures.',
                    ],
                ],
                [
                    'heading' => '4. Treat adoption as behavior, not login',
                    'paragraphs' => [
                        'Account and visit counts do not prove that a product solved the problem. Track task completion, repeated useful use, return to manual channels, and time to first value. Everyone may log into an internal portal because it is mandatory while continuing real work in spreadsheets.',
                        'Combine quantitative measurement with interviews and observation. Ask where users stop, what they copy outside the system, and why they seek help. This evidence explains the numbers and distinguishes product, policy, training, and incentive problems.',
                    ],
                ],
                [
                    'heading' => '5. Calculate unit economics and full cost',
                    'paragraphs' => [
                        'Connect cost to a unit of work: a completed request, resolved ticket, or active customer. Include development, operation, support, integration, and change management—not only licensing. Compare the total with time, revenue, or risk that actually changed.',
                        'A product may show high usage while requiring hidden manual intervention for every transaction. If that work is excluded, scaling appears successful while cost grows with volume. Watch whether unit economics improve or deteriorate.',
                    ],
                    'note' => 'Theoretical time saved becomes value only when capacity is genuinely released, redirected, or converted into lower cost.',
                ],
                [
                    'heading' => '6. Turn review into a decision meeting',
                    'paragraphs' => [
                        'Do not use the impact dashboard only for status. Hold a regular review asking what changed, why, which segments differ, and what will stop, accelerate, or be tested next. Give each measure and decision an owner, and record the hypotheses explaining movement.',
                        'Separate delivery review from impact review. A team may deliver the plan while the outcome remains flat; the correct response is to change the hypothesis or experiment, not automatically add features. Continued funding should follow evidence and learning capacity.',
                    ],
                ],
            ],
            'closing' => 'Measure the complete value journey: what was built, who used it, which behavior changed, which outcome improved, and at what cost and risk. When those measures feed regular decisions, they stop being reports and become a leadership system that directs investment toward real impact.',
        ],
        'ai-governance' => [
            'title' => 'A Lightweight AI Governance Operating Model for Saudi and Gulf Companies',
            'summary' => 'A proportionate governance model combining a use-case register, risk tiers, clear ownership, lifecycle gates, and continuous monitoring without creating a separate bureaucracy.',
            'seo_title' => 'AI Governance for Saudi and Gulf Companies',
            'seo_description' => 'A lightweight AI governance operating model covering roles, risk classification, data, vendors, lifecycle reviews, monitoring, and incidents in a Saudi and Gulf context.',
            'type' => 'Operating model',
            'lead' => 'Effective governance does not begin with a large committee or a long policy. It begins by knowing where AI is used, who owns each use, what happens if it fails, and how it is approved and monitored. In Saudi and Gulf companies, the model must work with privacy, cybersecurity, sector requirements, and contracts rather than operating as a separate lane.',
            'sections' => [
                [
                    'heading' => '1. Create one register of AI use cases',
                    'paragraphs' => [
                        'Inventory production solutions, pilots, vendor services, and intelligent features embedded in existing systems. Record purpose, owner, users, data, model or vendor, outputs, actions, human review, and latest assessment date. Do not wait for perfect detail; begin with the minimum information that makes use visible.',
                        'Include tools bought by individual departments because risk does not depend on the procurement route. Offer a simple disclosure path that does not punish teams, then use the register to prioritize attention. The organization cannot protect or support a use it does not know exists.',
                    ],
                ],
                [
                    'heading' => '2. Classify risk before selecting controls',
                    'paragraphs' => [
                        'Classify each use by data type, affected people, decision impact, reversibility, degree of autonomy, and breadth of use. A tool editing public text is lower risk than a system proposing a decision about an employee or customer or executing a transaction.',
                        'Use three practical tiers: low risk with registration and baseline controls; medium risk with assessment, testing, and periodic review; high risk with specialist approval and stronger oversight or prohibited actions. Write the classification rationale so the result is not merely personal judgment.',
                    ],
                    'points' => [
                        'Data sensitivity and processing purpose.',
                        'Impact on rights, money, or service.',
                        'System autonomy and permissions.',
                        'Explainability and ability to correct.',
                    ],
                ],
                [
                    'heading' => '3. Distribute accountability across existing roles',
                    'paragraphs' => [
                        'The business owner is accountable for purpose, outcome, and final decision. The product owner manages operation and change. The data owner manages source, quality, and access. Security, privacy, and legal teams define controls within their mandates. Technical or data teams test performance but should not alone accept business risk.',
                        'Create a small council for medium- and high-risk cases, with published criteria and a defined decision time. Low-risk cases can use a simplified self-service route with audit. Scarce specialist attention is then focused where its effect is greatest.',
                    ],
                    'note' => 'Every use case needs a named person able to stop it. “The team is responsible” is not enough during an incident.',
                ],
                [
                    'heading' => '4. Use short gates across the lifecycle',
                    'paragraphs' => [
                        'At idea stage, assess purpose, alternatives, and data. Before a pilot, approve scope, success measures, and test environment. Before launch, review performance, human oversight, security, privacy, contracts, and incident plans. During operation, monitor quality, drift, and access. At retirement, remove permissions and decide what happens to retained data.',
                        'Ask for evidence proportionate to risk: test samples, source records, results across relevant groups, failure-case testing, and a fallback plan. A gate should not be a static document list. Its question is whether evidence is sufficient for this impact and authority.',
                    ],
                ],
                [
                    'heading' => '5. Turn principles into daily controls',
                    'paragraphs' => [
                        'Saudi AI Ethics Principles emphasize integrity and fairness, privacy and security, reliability and safety, transparency and interpretability, and accountability. Translate them into practice: relevant-group testing, data minimization and permissions, performance boundaries, notice when people interact with AI, and a named decision owner with an audit trail.',
                        'When personal data is processed, review purpose, the appropriate basis, minimization, retention, and sharing under the Saudi Personal Data Protection Law and its regulations, plus applicable sector and contractual requirements. When a vendor processes data outside the company environment, understand processing locations, subprocessors, model-improvement use, deletion, and retrieval.',
                    ],
                    'note' => 'This operating model is general guidance and does not replace legal or regulatory assessment for a particular company or use case.',
                ],
                [
                    'heading' => '6. Monitor change and prepare for incidents',
                    'paragraphs' => [
                        'Launch performance does not guarantee future performance. Data, policy, or a vendor model may change. Monitor outcome and quality, low-confidence cases, overrides, complaints, and review cost. Retest when a source, model, or permission changes.',
                        'Define an AI incident and connect it to the existing incident process: a harmful output, data exposure, unauthorized action, or broad degradation. Name who stops service, preserves evidence, communicates, and corrects impact. Run a tabletop exercise before scale so the plan is operational rather than theoretical.',
                    ],
                    'points' => [
                        'Review frequency matched to risk tier.',
                        'A stop control and fallback path.',
                        'Change logs for vendors, models, and data.',
                        'A post-incident review that creates a stronger control.',
                    ],
                ],
                [
                    'heading' => '7. Build a responsible-use culture that works',
                    'paragraphs' => [
                        'Train by role. Users need to know prohibited data and how to verify outputs. Owners need measurement and escalation skills. Procurement needs vendor questions. Specialist teams need testing methods. A single generic course cannot cover these differences.',
                        'Provide an approved experimentation route and safe alternatives, or pilots will move into the shadows. Publish examples of permitted use and cases requiring review, and make reporting an error or new use easy. Governance succeeds when it helps teams make better decisions, not only when it appears at an approval gate.',
                    ],
                ],
            ],
            'closing' => 'Begin with a register, named owner, and risk tier. Add lifecycle gates and controls proportionate to impact, connected to existing privacy, security, and legal obligations. Monitor operations and prepare to stop or respond. A lightweight model is not less serious; it puts the right decision in the right place and leaves evidence that can be reviewed.',
        ],
    ],
];
