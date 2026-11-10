<?php

return [
    'key' => 'loans',
    'source_module' => 'LoanManagement',
    'name' => 'Quản lý khoản vay',
    'description' => 'Theo dõi hợp đồng vay, nghĩa vụ trả nợ và bảo hiểm liên quan.',
    'icon' => 'building-library',
    'route' => 'client.loans.dashboard',
    'permission' => 'client.loans.access',
    'sort_order' => 60,
    'layout' => ['mode' => 'workspace'],
    'hub' => [
        'key' => 'overview',
        'name' => 'Tổng quan',
        'permission' => 'client.loans.overview.view',
        'eyebrow' => 'Tài chính doanh nghiệp',
        'title' => 'Quản lý khoản vay',
        'description' => 'Theo dõi dư nợ, lịch trả nợ và các hợp đồng bảo hiểm.',
        'supporting' => [
            'visible' => true,
            'title' => 'Theo dõi nghĩa vụ tài chính',
            'body' => 'Quản lý các khoản vay của doanh nghiệp theo hợp đồng.',
        ],
    ],
    'navigation' => [
        'overview' => [
            'name' => 'Tổng quan', 'route' => 'client.loans.dashboard',
            'permission' => 'client.loans.overview.view', 'icon' => 'home', 'sort_order' => 10,
        ],
    ],
    'features' => [
        'overview' => [
            'name' => 'Tổng quan khoản vay',
            'description' => 'Theo dõi các khoản vay và nghĩa vụ đến hạn.',
            'eyebrow' => 'Khoản vay',
            'page_title' => 'Tổng quan khoản vay',
            'page_description' => 'Tình hình khoản vay và các nghĩa vụ phải thanh toán.',
            'route' => 'client.loans.dashboard',
            'permission' => 'client.loans.overview.view',
            'icon' => 'home',
            'sort_order' => 10,
        ],
    ],
];
