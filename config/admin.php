<?php

return [
    'name' => env('ADMIN_NAME'),
    'email' => env('ADMIN_EMAIL'),
    'password' => env('ADMIN_PASSWORD'),

    'mfa' => [
        'required' => (bool) env('ADMIN_MFA_REQUIRED', false),
    ],
];
