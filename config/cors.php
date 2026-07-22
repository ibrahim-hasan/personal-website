<?php

return [
    'paths' => ['api/v1/*'],
    'allowed_methods' => ['GET', 'POST', 'PATCH', 'PUT', 'DELETE', 'OPTIONS'],
    'allowed_origins' => array_filter(array_map(
        trim(...),
        explode(',', (string) env('EDITORIAL_API_ALLOWED_ORIGINS', '')),
    )),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'Idempotency-Key', 'If-Match', 'X-Request-Id'],
    'exposed_headers' => ['ETag', 'Retry-After', 'X-Request-Id'],
    'max_age' => 0,
    'supports_credentials' => false,
];
