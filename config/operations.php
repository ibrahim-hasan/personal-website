<?php

return [
    'release_environment' => in_array(env('APP_ENV', 'production'), ['production', 'staging'], true),

    'readiness' => [
        'header' => env('READINESS_HEADER', 'X-Readiness-Key'),
        'secret' => env('READINESS_SECRET'),
        'probe_url' => env('READINESS_PROBE_URL'),
        'rate_limit_attempts' => (int) env('READINESS_RATE_LIMIT_ATTEMPTS', 30),
        'rate_limit_decay_seconds' => (int) env('READINESS_RATE_LIMIT_DECAY_SECONDS', 60),
    ],

    'consultation_notifications' => [
        'queue' => env('CONSULTATION_NOTIFICATION_QUEUE', env('REDIS_QUEUE', 'default')),
        'max_attempts' => (int) env('CONSULTATION_NOTIFICATION_MAX_ATTEMPTS', 12),
        'retry_delay_seconds' => (int) env('CONSULTATION_NOTIFICATION_RETRY_DELAY_SECONDS', 900),
    ],

    'scheduler_heartbeat' => [
        'key' => 'operations:scheduler-heartbeat',
        'max_age_seconds' => (int) env('SCHEDULER_HEARTBEAT_MAX_AGE_SECONDS', 120),
        'ttl_seconds' => (int) env('SCHEDULER_HEARTBEAT_TTL_SECONDS', 600),
    ],

    'required_storage_disks' => array_values(array_unique(array_filter([
        env('FILESYSTEM_DISK', 'local'),
        env('MEDIA_DISK', 'public'),
        env('ELEVENLABS_AUDIO_DISK', 'public'),
    ]))),
    'storage_probe_path' => env('OPERATIONS_STORAGE_PROBE_PATH', '.healthcheck'),
    'build_revision_path' => env('OPERATIONS_BUILD_REVISION_PATH', base_path('REVISION')),
];
