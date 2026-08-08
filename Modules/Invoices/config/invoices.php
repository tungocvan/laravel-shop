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
];
