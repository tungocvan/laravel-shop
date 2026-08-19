<?php

return [
    'key' => 'muasamcong',
    'name' => 'Mua sắm công',
    'description' => 'Tra cứu và phân tích dữ liệu mua sắm công, giá thuốc và kết quả lựa chọn nhà thầu.',
    'icon' => 'shopping-cart',
    'route' => 'client.muasamcong.dashboard',
    'permission' => 'client.muasamcong.access',
    'sort_order' => 10,

    'features' => [
        'drug-pricing' => [
            'name' => 'Tra cứu thuốc trúng thầu',
            'description' => 'Tra cứu dữ liệu giá thuốc và kết quả trúng thầu thực tế.',
            'route' => 'client.muasamcong.dashboard',
            'permission' => 'client.muasamcong.drug-pricing.view',
            'sort_order' => 10,
        ],
        'history' => [
            'name' => 'Lịch sử tra cứu',
            'permission' => 'client.muasamcong.history.view',
            'sort_order' => 20,
        ],
        'wishlist' => [
            'name' => 'Danh sách quan tâm',
            'permission' => 'client.muasamcong.wishlist.view',
            'sort_order' => 30,
        ],
        'contractors' => [
            'name' => 'Nhà thầu',
            'permission' => 'client.muasamcong.contractors.view',
            'sort_order' => 40,
        ],
        'analytics' => [
            'name' => 'Phân tích',
            'permission' => 'client.muasamcong.analytics.view',
            'sort_order' => 50,
        ],
    ],
];
