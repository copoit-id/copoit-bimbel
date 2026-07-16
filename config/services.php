<?php

return [
    'payment_gateway' => env('PAYMENT_GATEWAY', config('payment_gateways.default', 'xendit')),

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

    'interactive_qris' => [
        'api_key' => env('INTERACTIVE_QRIS_API_KEY'),
        'mid' => env('INTERACTIVE_QRIS_MID'),
        'use_tip' => env('INTERACTIVE_QRIS_USE_TIP', false),
        'base_url' => env('INTERACTIVE_QRIS_BASE_URL', 'https://qris.interactive.co.id/restapi/qris'),
    ],

    'ipaymu' => [
        'api_key' => env('IPAYMU_API_KEY'),
        'va' => env('IPAYMU_VA'),
        'base_url' => env(
            'IPAYMU_BASE_URL',
            env('IPAYMU_IS_PRODUCTION', false)
                ? env('IPAYMU_PRODUCTION_URL', 'https://my.ipaymu.com')
                : env('IPAYMU_SANDBOX_URL', 'https://sandbox.ipaymu.com')
        ),
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

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'question_model' => env('OPENAI_QUESTION_MODEL', 'gpt-5.4-mini'),
        'timeout' => env('OPENAI_TIMEOUT', 90),
        'question_models' => [
            'gpt-5.4' => 'OpenAI - GPT-5.4',
            'gpt-5.4-mini' => 'OpenAI - GPT-5.4 Mini',
            'gpt-5-mini' => 'OpenAI - GPT-5 Mini',
            'gpt-4o' => 'OpenAI - GPT-4o',
            'gpt-4o-mini' => 'OpenAI - GPT-4o Mini',
        ],
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'timeout' => env('GEMINI_TIMEOUT', 90),
        'question_models' => [
            'gemini-2.5-flash' => 'Gemini - 2.5 Flash',
            'gemini-2.5-flash-lite' => 'Gemini - 2.5 Flash-Lite',
        ],
    ],
    'ai_gateway' => [
        'url' => env('AI_GATEWAY_URL'),
        'key' => env('AI_GATEWAY_KEY'),
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
