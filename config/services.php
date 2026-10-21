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

    'doanhnghiep' => [
        'api_key' => env('DOANHNGHIEP_API_KEY'),
    ],

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

    'facebook' => [
        'scheduler_enabled' => filter_var(env('FACEBOOK_SCHEDULER_ENABLED', false), FILTER_VALIDATE_BOOL),
    ],

    // Legacy boot-time NodeJS configuration. Server-side Chat HTTP broadcasts use
    // Modules\System\Services\Env\AdvancedConfigService (database-backed).
    // Keep these keys until all other callers are proven migrated; the socket
    // process still requires BRIDGE_SECRET_KEY in its own runtime environment.
    'nodejs' => [
        'url' => env('NODEJS_SERVER_URL', 'https://flexbiz.nodejs.tk'),
        'public_url' => env('VITE_SOCKET_PUBLIC_URL'),
        'port' => (int) env('NODEJS_SERVER_PORT', 6001),
        'bridge_secret' => env('BRIDGE_SECRET_KEY'),
    ],
];
