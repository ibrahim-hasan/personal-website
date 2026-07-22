<?php

return [
    'documents' => [
        'label' => 'Legal information',
        'privacy' => 'Privacy notice',
        'cookies' => 'Cookie policy',
        'terms' => 'Terms of use',
    ],
    'cookie_preferences' => 'Manage cookie preferences',
    'cookies' => [
        'title' => 'Analytics are optional',
        'body' => 'Analytics are off by default. Allow them only if you want to help improve the site.',
        'learn_more' => 'Cookie policy',
        'accept' => 'Allow analytics',
        'reject' => 'Necessary only',
        'settings' => 'Settings',
        'settings_title' => 'Cookie settings',
        'settings_body' => 'Necessary storage keeps the site secure. Analytics remains optional.',
        'necessary_title' => 'Necessary storage',
        'necessary_body' => 'Keeps your session secure and provides features you request.',
        'analytics_title' => 'Analytics',
        'analytics_body' => 'Measures selected public pages with Google Analytics.',
        'always_on' => 'Always on',
        'back_to_choices' => 'Back',
        'save_preferences' => 'Save choice',
    ],
    'privacy' => [
        'athar_short_prefix' => 'Read',
        'eyebrow' => 'Privacy notice',
        'title' => 'Privacy',
        'description' => 'How ibrahimhasan.net handles the personal data needed for its public site and reader features.',
        'effective_date' => 'Last updated: 22 July 2026',
        'introduction' => 'This notice explains what is processed when you browse the site, request a consultation, create a reader account, or join the moderated community.',
        'sections' => [
            [
                'heading' => 'Who is responsible',
                'paragraphs' => [
                    'Ibrahim Hasan is responsible for ibrahimhasan.net. For privacy questions or rights requests, email :email.',
                ],
            ],
            [
                'heading' => 'What we process',
                'paragraphs' => [
                    'We use only the data needed for the feature you choose. The applicable basis may be a requested service, site security and legitimate interests where available, a legal obligation, or consent for optional analytics.',
                ],
                'facts' => [
                    [
                        'title' => 'Browsing and security',
                        'values' => [
                            ['label' => 'Data', 'value' => 'IP address, browser information, session and CSRF data.'],
                            ['label' => 'Purpose', 'value' => 'Run the site, prevent abuse, and diagnose faults.'],
                            ['label' => 'Retention', 'value' => 'Session data follows the configured session lifetime; operational records follow applicable controls.'],
                        ],
                    ],
                    [
                        'title' => 'Reader account',
                        'values' => [
                            ['label' => 'Data', 'value' => 'Name, email, securely hashed password, locale, verification, Terms acceptance, and saved reading activity.'],
                            ['label' => 'Purpose', 'value' => 'Create and secure the account and provide reader features.'],
                            ['label' => 'Retention', 'value' => 'Until account deletion, unless a longer period is required for security, disputes, or law.'],
                        ],
                    ],
                    [
                        'title' => 'Community',
                        'values' => [
                            ['label' => 'Data', 'value' => 'Comments, replies, appreciation, bookmarks, and private moderation reports.'],
                            ['label' => 'Purpose', 'value' => 'Moderate discussion and provide the reader community.'],
                            ['label' => 'Retention', 'value' => 'Private and non-public contributions are deleted with the account. Approved comments may remain anonymised; resolved reports are eligible for deletion after :resolved_reports_days days.'],
                        ],
                    ],
                    [
                        'title' => 'Consultation request',
                        'values' => [
                            ['label' => 'Data', 'value' => 'Name, email, optional company, selected service, challenge details, locale, and follow-up notes.'],
                            ['label' => 'Purpose', 'value' => 'Respond to the request and manage follow-up.'],
                            ['label' => 'Retention', 'value' => 'Active requests are kept while they need a response. Archived requests are eligible for deletion after :archived_inquiries_days days.'],
                        ],
                    ],
                    [
                        'title' => 'Optional analytics',
                        'values' => [
                            ['label' => 'Data', 'value' => 'Browser and device information, approximate location, and configured interactions on selected public pages.'],
                            ['label' => 'Purpose', 'value' => 'Understand use of the public site and improve it.'],
                            ['label' => 'Retention', 'value' => 'Only after consent and subject to the Google Analytics property retention setting.'],
                        ],
                    ],
                ],
            ],
            [
                'heading' => 'Providers, transfers, and updates',
                'paragraphs' => [
                    'We use providers for hosting, storage, security, and transactional email. Google receives analytics data only after you allow it. Providers may process data outside your country; contact us for current provider and transfer information. We update this notice when the site or its processing changes.',
                ],
            ],
            [
                'heading' => 'Your choices and rights',
                'paragraphs' => [
                    'You can update or delete a reader account in account settings and change analytics through Cookie preferences at any time.',
                    'Depending on the law that applies, you may request access, correction, deletion, restriction, objection, portability, or information about recipients and transfers. Where the GDPR applies, you may withdraw consent and complain to the relevant supervisory authority. Email :email; we respond within the period required by applicable law.',
                ],
            ],
        ],
    ],
    'cookies_policy' => [
        'eyebrow' => 'Cookie policy',
        'title' => 'Cookies and storage',
        'description' => 'The necessary and optional browser storage used by ibrahimhasan.net.',
        'effective_date' => 'Last updated: 22 July 2026',
        'introduction' => 'This policy explains the storage used to secure the site, remember requested features, and measure selected public pages only when you allow it.',
        'sections' => [
            [
                'heading' => 'Your choice',
                'paragraphs' => [
                    'Necessary storage supports security and requested features. Analytics is off unless you allow it; if you do nothing, it stays off. You can change your choice at any time. We remember it with this policy version for up to 180 days.',
                ],
            ],
            [
                'heading' => 'What is stored',
                'facts' => [
                    [
                        'title' => 'Security and session',
                        'tokens' => ['XSRF-TOKEN', 'ibrahim-hasan-session'],
                        'values' => [
                            ['label' => 'Use', 'value' => 'Necessary. Protects requests and maintains a reader session. It expires under the configured session settings.'],
                        ],
                    ],
                    [
                        'title' => 'Signed-in state',
                        'tokens' => ['remember_web_*'],
                        'values' => [
                            ['label' => 'Use', 'value' => 'Used only when you choose to stay signed in.'],
                        ],
                    ],
                    [
                        'title' => 'Analytics choice',
                        'tokens' => ['ibrahimhasan_analytics_consent'],
                        'values' => [
                            ['label' => 'Use', 'value' => 'Necessary. Records your analytics choice and policy version for up to 180 days.'],
                        ],
                    ],
                    [
                        'title' => 'Reader and audio preferences',
                        'values' => [
                            ['label' => 'Use', 'value' => 'Stored on your device only when you use the feature; remains until browser data is cleared.'],
                        ],
                    ],
                    [
                        'title' => 'Optional analytics',
                        'tokens' => ['_ga', '_ga_<stream-id>'],
                        'values' => [
                            ['label' => 'Use', 'value' => 'Created only after you allow analytics and governed by Google settings.'],
                        ],
                    ],
                ],
            ],
            [
                'heading' => 'Analytics',
                'paragraphs' => [
                    'If you allow it, Google Analytics measures selected public pages. The site excludes URL query strings from page views and does not enable advertising storage, advertising personalisation, or Google Signals in site code.',
                ],
            ],
            [
                'heading' => 'Change your choice',
                'paragraphs' => [
                    'Use Cookie preferences in the footer or on this page to allow analytics or keep only necessary storage. Choosing necessary storage stops future site-initiated analytics collection and removes Google Analytics cookies that this site can access. For questions, email :email.',
                ],
            ],
            [
                'heading' => 'Private Athar notes and website publication',
                'paragraphs' => [
                    'An Athar invitation uses a private link, and Ibrahim may send that same link by email when he chooses. Possession of the link is the access credential, so it must not be forwarded. Your private reflection, draft, sealed source, and consent evidence are processed to receive and protect your note. Nothing is published automatically.',
                    'If you choose publication, you approve one exact text card, one named website page, selected languages, and a display-name choice. The card is not reused for social media, proposals, talks, or other channels. You can stop showing the text on the site at any time through the private management link. A separate deletion request covers the private note and follows the configured retention period.',
                ],
            ],
        ],
    ],
    'terms' => [
        'eyebrow' => 'Terms of use',
        'title' => 'Terms of use',
        'description' => 'The essential rules for using ibrahimhasan.net and the reader community.',
        'effective_date' => 'Last updated: 22 July 2026',
        'introduction' => 'These Terms apply when you use ibrahimhasan.net. Reader accounts require acceptance of the current Terms version.',
        'sections' => [
            [
                'heading' => 'Using the site',
                'paragraphs' => [
                    'Use the site lawfully and do not interfere with its security, availability, or other people. Keep account credentials confidential and provide accurate information. Reader features require a verified email address.',
                    'You can delete a reader account from account settings. If the Terms change materially, accept the current version before using protected reader features again.',
                ],
            ],
            [
                'heading' => 'Community',
                'paragraphs' => [
                    'Comments and replies must be relevant, respectful, lawful, and free of spam, harassment, confidential material, or another person’s personal data. Contributions are moderated before publication.',
                    'You keep ownership of your original contribution. By submitting it, you grant a non-exclusive licence to review, moderate, display, and retain it within the discussion. Private reports are not public contributions.',
                ],
            ],
            [
                'heading' => 'Content, consultations, and enforcement',
                'paragraphs' => [
                    'Site content is general information, not professional advice or a guarantee of an outcome. Sending or answering a consultation request does not create a client, employment, partnership, agency, confidentiality, or other contractual relationship.',
                    'We may change, pause, or remove features, suspend accounts, or remove content when reasonably needed to protect people, the service, or the law.',
                ],
            ],
            [
                'heading' => 'Changes and contact',
                'paragraphs' => [
                    'We may update these Terms as the site changes. The date above identifies the current version. For questions, email :email.',
                ],
            ],
        ],
    ],
];
