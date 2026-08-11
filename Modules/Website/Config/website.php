<?php

return [
    'route_prefix' => '/',
    'name' => 'Website',

    'payment' => [
        'bank_transfer' => [
            'bank_name' => env('BANK_NAME', ''),
            'account_number' => env('BANK_ACCOUNT_NUMBER', ''),
            'account_name' => env('BANK_ACCOUNT_NAME', ''),
            'branch' => env('BANK_BRANCH', ''),
            'instructions' => env('BANK_TRANSFER_INSTRUCTIONS', 'Vui lòng chuyển đúng số tiền và ghi chính xác mã đơn hàng trong nội dung chuyển khoản.'),
        ],

        'momo' => [
            'endpoint' => env('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create'),
            'partner_code' => env('MOMO_PARTNER_CODE'),
            'access_key' => env('MOMO_ACCESS_KEY'),
            'secret_key' => env('MOMO_SECRET_KEY'),
            'timeout' => (int) env('MOMO_TIMEOUT', 30),
        ],
    ],
];
