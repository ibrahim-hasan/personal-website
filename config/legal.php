<?php

return [
    'privacy_version' => '2026-07-22',
    'terms_version' => '2026-07-22',
    'cookie_consent_version' => '2026-07-22',
    'retention' => [
        'enabled' => (bool) env('PRIVACY_RETENTION_ENABLED', false),
        'archived_inquiries_days' => (int) env('PRIVACY_ARCHIVED_INQUIRIES_RETENTION_DAYS', 365),
        'resolved_reports_days' => (int) env('PRIVACY_RESOLVED_REPORTS_RETENTION_DAYS', 365),
    ],
];
