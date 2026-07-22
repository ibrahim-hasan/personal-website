<?php

return [
    'idempotency_ttl_hours' => (int) env('EDITORIAL_API_IDEMPOTENCY_TTL_HOURS', 24),
    'audit_retention_days' => (int) env('EDITORIAL_API_AUDIT_RETENTION_DAYS', 180),
    'allowed_origins' => array_filter(array_map(
        trim(...),
        explode(',', (string) env('EDITORIAL_API_ALLOWED_ORIGINS', '')),
    )),
];
