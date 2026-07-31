<?php

return [
    'access' => [
        'code_ttl_minutes' => 10,
        'code_max_attempts' => 6,
        'resend_cooldown_seconds' => 60,
        'max_code_requests_per_hour' => 5,
        'email_link_ttl_hours' => 72,
        'session_ttl_minutes' => 3 * 24 * 60,
    ],
];
