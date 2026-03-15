<?php

return [
    'payment_gateway' => env('PAYMENT_GATEWAY', 'xendit'),

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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'xendit' => [
        'secret_key' => env('XENDIT_SECRET_KEY'),
        'public_key' => env('XENDIT_PUBLIC_KEY'),
        'webhook_token' => env('XENDIT_WEBHOOK_TOKEN'),
        'base_url' => env('XENDIT_BASE_URL', 'https://api.xendit.co'),
    ],

    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
        'snap_url' => env('MIDTRANS_SNAP_URL', 'https://app.sandbox.midtrans.com/snap/v1/transactions'),
        'status_url' => env('MIDTRANS_STATUS_URL', 'https://api.sandbox.midtrans.com/v2'),
    ],

    'recaptcha' => [
        'enabled' => env('RECAPTCHA_ENABLED', false),
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        'min_score' => env('RECAPTCHA_MIN_SCORE', 0.5), // For v3, minimum score threshold
    ],
    'tinymce' => [
        'key' => env('TINYMCE_API_KEY'),
    ],

    'ai_similarity' => [
        'enabled' => env('AI_SIMILARITY_ENABLED', true),
        'url' => env('SIMILARITY_SERVICE_URL', 'http://localhost:8000'),
        'timeout' => env('SIMILARITY_SERVICE_TIMEOUT', 20),
        'threshold' => env('ESSAY_AUTO_PASS_THRESHOLD', 0.6),
        'callback_url' => env('SIMILARITY_CALLBACK_URL'),
        'callback_secret' => env('SIMILARITY_CALLBACK_SECRET'),
    ],
];
