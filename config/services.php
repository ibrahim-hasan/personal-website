<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'n8n' => [
        'log_webhook_url' => env('N8N_LOG_WEBHOOK_URL'),
        'log_webhook_token' => env('N8N_LOG_WEBHOOK_TOKEN'),
        'log_project' => env('N8N_LOG_PROJECT', env('APP_NAME', 'Ibrahim Hasan')),
    ],

    'social' => [
        'linkedin' => env('SOCIAL_LINKEDIN_URL', 'https://sa.linkedin.com/in/i-hasan'),
        'facebook' => env('SOCIAL_FACEBOOK_URL'),
        'twitter' => env('SOCIAL_X_URL'),
        'instagram' => env('SOCIAL_INSTAGRAM_URL'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'narration_model' => env('OPENAI_NARRATION_MODEL', 'gpt-5.6-terra'),
        'narration_max_output_tokens' => (int) env('OPENAI_NARRATION_MAX_OUTPUT_TOKENS', 20000),
        'timeout' => (int) env('OPENAI_TIMEOUT', 180),
        'connect_timeout' => (int) env('OPENAI_CONNECT_TIMEOUT', 15),
        'queue_connection' => env('OPENAI_QUEUE_CONNECTION', 'database'),
        'queue' => env('OPENAI_QUEUE', 'article-audio'),
    ],

    'elevenlabs' => [
        'api_key' => env('ELEVENLABS_API_KEY'),
        'base_url' => env('ELEVENLABS_BASE_URL', 'https://api.elevenlabs.io/v1'),
        'voice_id' => env('ELEVENLABS_VOICE_ID'),
        'model_id' => env('ELEVENLABS_MODEL_ID', 'eleven_multilingual_v2'),
        'output_format' => env('ELEVENLABS_OUTPUT_FORMAT', 'mp3_44100_128'),
        'max_characters' => (int) env('ELEVENLABS_MAX_CHARACTERS', 9000),
        'context_characters' => (int) env('ELEVENLABS_CONTEXT_CHARACTERS', 400),
        'sample_characters' => (int) env('ELEVENLABS_SAMPLE_CHARACTERS', 650),
        'text_normalization' => env('ELEVENLABS_TEXT_NORMALIZATION', 'on'),
        'timeout' => (int) env('ELEVENLABS_TIMEOUT', 150),
        'connect_timeout' => (int) env('ELEVENLABS_CONNECT_TIMEOUT', 15),
        'audio_disk' => env('ELEVENLABS_AUDIO_DISK', 'public'),
        'queue_connection' => env('ELEVENLABS_QUEUE_CONNECTION', 'database'),
        'queue' => env('ELEVENLABS_QUEUE', 'article-audio'),
        'voice_settings' => [
            'stability' => (float) env('ELEVENLABS_STABILITY', 0.72),
            'similarity_boost' => (float) env('ELEVENLABS_SIMILARITY_BOOST', 0.78),
            'style' => (float) env('ELEVENLABS_STYLE', 0.08),
            'speed' => (float) env('ELEVENLABS_SPEED', 0.96),
            'use_speaker_boost' => (bool) env('ELEVENLABS_SPEAKER_BOOST', true),
        ],
        'models' => [
            'eleven_v3' => [
                'label' => 'Eleven v3 · expressive',
                'max_characters' => (int) env('ELEVENLABS_V3_MAX_CHARACTERS', 4500),
                'supports_language_code' => true,
                'supports_request_stitching' => false,
                'voice_settings' => [
                    'stability' => (float) env('ELEVENLABS_V3_STABILITY', 0.50),
                    'similarity_boost' => (float) env('ELEVENLABS_V3_SIMILARITY_BOOST', 0.78),
                    'style' => (float) env('ELEVENLABS_V3_STYLE', 0.0),
                    'speed' => (float) env('ELEVENLABS_V3_SPEED', 0.90),
                    'use_speaker_boost' => (bool) env('ELEVENLABS_V3_SPEAKER_BOOST', false),
                ],
            ],
            'eleven_multilingual_v2' => [
                'label' => 'Multilingual v2 · stable long-form',
                'max_characters' => (int) env('ELEVENLABS_V2_MAX_CHARACTERS', env('ELEVENLABS_MAX_CHARACTERS', 9000)),
                'supports_language_code' => false,
                'supports_request_stitching' => true,
                'voice_settings' => [
                    'stability' => (float) env('ELEVENLABS_V2_STABILITY', 0.65),
                    'similarity_boost' => (float) env('ELEVENLABS_V2_SIMILARITY_BOOST', 0.78),
                    'style' => (float) env('ELEVENLABS_V2_STYLE', 0.0),
                    'speed' => (float) env('ELEVENLABS_V2_SPEED', 0.90),
                    'use_speaker_boost' => (bool) env('ELEVENLABS_V2_SPEAKER_BOOST', true),
                ],
            ],
        ],
    ],

];
