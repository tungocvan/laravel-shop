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
        // Keep raw file payload conservative because email attachments grow after MIME/base64 encoding.
        'email_chunk_bytes' => (int) env('INVOICES_BACKUP_EMAIL_CHUNK_BYTES', 12 * 1024 * 1024),
    ],
];
