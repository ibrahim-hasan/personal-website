<?php

return [
    'permissions_policy' => implode(', ', [
        'accelerometer=()',
        'camera=()',
        'geolocation=()',
        'gyroscope=()',
        'magnetometer=()',
        'microphone=()',
        'payment=()',
        'usb=()',
    ]),

    'csp' => [
        /*
         * Keep CSP disabled until the report-only rollout is deliberately
         * enabled for an environment. This application still contains inline
         * scripts and styles that must be separately migrated before any
         * enforced policy is considered.
         */
        'report_only' => (bool) env('CSP_REPORT_ONLY', false),

        /*
         * Report-only observation is intentionally bounded. Browser reports
         * are accepted on a same-origin endpoint, but the application keeps
         * only whitelisted directive/category counters for a short period.
         */
        'reporting' => [
            'group' => 'csp',
            'route' => 'security.csp-reports',
            'max_age' => 86_400,
            'max_request_bytes' => 16_384,
            'rate_limit_per_minute' => 30,
        ],

        /*
         * These are the only client-side third-party origins currently used:
         * Turnstile, consent-gated Google Analytics, Bunny Fonts on the OAuth
         * authorization page, and the configured R2 media origin below.
         */
        'sources' => [
            'script-src' => [
                "'self'",
                'https://challenges.cloudflare.com',
                'https://www.googletagmanager.com',
            ],
            'style-src' => [
                "'self'",
                'https://fonts.bunny.net',
            ],
            'font-src' => [
                "'self'",
                'data:',
                'https://fonts.bunny.net',
            ],
            'img-src' => [
                "'self'",
                'data:',
            ],
            'media-src' => [
                "'self'",
            ],
            'connect-src' => [
                "'self'",
                'https://challenges.cloudflare.com',
                'https://www.google-analytics.com',
            ],
            'frame-src' => [
                'https://challenges.cloudflare.com',
            ],
        ],

        'media_origins' => array_values(array_filter([
            env('AWS_URL'),
        ])),
    ],
];
