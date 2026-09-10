<?php

return [
    'route_middleware' => ['web', 'auth:admin'],
    'api_middleware' => ['api', 'auth:sanctum'],

    'gdt' => [
        'base_url' => 'https://hoadondientu.gdt.gov.vn/api',
        'username' => env('GDT_API_USERNAME'),
        'password' => env('GDT_API_PASSWORD'),
        'verify_ssl' => true,
        'timeout' => 15,
        'token_ttl' => 36000,
        'cache_key' => 'gdt_token',
        'detail_request_delay_ms' => (int) env('GDT_DETAIL_REQUEST_DELAY_MS', 1000),
        'detail_retry_attempts' => (int) env('GDT_DETAIL_RETRY_ATTEMPTS', 4),
        'detail_retry_backoff_seconds' => [5, 10, 20, 40],
    ],

    'meinvoice' => [
        'base_url' => 'https://api.meinvoice.vn/api/integration',
        'token' => env('MEINVOICE_API_TOKEN'),
    ],

    'storage' => [
        'export_directory' => 'gdt',
        'pdf_directory' => 'hoadon_temp',
    ],

    'backup' => [
        'email_chunk_bytes' => (int) env('INVOICES_BACKUP_EMAIL_CHUNK_BYTES', 12 * 1024 * 1024),
        'recipient' => env('INVOICES_BACKUP_EMAIL'),
        'automatic_enabled' => env('INVOICES_BACKUP_AUTOMATIC_ENABLED', false),
        'schedule_day' => (int) env('INVOICES_BACKUP_SCHEDULE_DAY', 1),
        'schedule_time' => env('INVOICES_BACKUP_SCHEDULE_TIME', '00:15'),
    ],
];
