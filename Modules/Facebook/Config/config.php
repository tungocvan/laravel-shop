<?php

return [
    'graph_base_url' => env('FACEBOOK_GRAPH_BASE_URL', 'https://graph.facebook.com'),
    'oauth_base_url' => env('FACEBOOK_OAUTH_BASE_URL', 'https://www.facebook.com'),
    // Keep this single default aligned with the Meta Graph API version enabled for your app.
    'graph_version' => env('FACEBOOK_GRAPH_VERSION', 'v25.0'),
    'app_id' => env('FACEBOOK_APP_ID'),
    'app_secret' => env('FACEBOOK_APP_SECRET'),
    'redirect_uri' => env('FACEBOOK_REDIRECT_URI', env('APP_URL').'/admin/facebook/callback'),
    'webhook_verify_token' => env('FACEBOOK_WEBHOOK_VERIFY_TOKEN'),
    'http_timeout' => (int) env('FACEBOOK_HTTP_TIMEOUT', 30),
    'connect_timeout' => (int) env('FACEBOOK_CONNECT_TIMEOUT', 10),
    'max_retries' => (int) env('FACEBOOK_MAX_RETRIES', 3),
    'retry_delay' => (int) env('FACEBOOK_RETRY_DELAY', 1000),
    'token_encryption' => (bool) env('FACEBOOK_TOKEN_ENCRYPTION', true),
    'queue' => env('FACEBOOK_QUEUE', 'facebook'),
    'media_disk' => env('FACEBOOK_MEDIA_DISK', 'local'),
    'duplicate_lock_seconds' => (int) env('FACEBOOK_DUPLICATE_LOCK_SECONDS', 300),
    'scopes' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('FACEBOOK_SCOPES', 'pages_show_list,pages_read_engagement,pages_manage_posts'))
    ))),
];
