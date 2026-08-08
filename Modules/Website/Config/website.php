<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Website Route Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix cho toàn bộ frontend website
    | Ví dụ:
    | - 'website'
    | - 'shop'
    | - ''  (root)
    |
    */
    'route_prefix' => '/',
    'name' => 'Website',

    // Thêm cấu hình thanh toán vào đây
    'payment' => [
        'momo' => [
            'endpoint' => 'https://test-payment.momo.vn/v2/gateway/api/create',
            'partner_code' => env('MOMO_PARTNER_CODE'),
            'access_key' => env('MOMO_ACCESS_KEY'),
            'secret_key' => env('MOMO_SECRET_KEY'),
        ],
    ],
];
