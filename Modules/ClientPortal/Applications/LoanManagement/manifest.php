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
        'lenders' => ['name' => 'Đối tác cho vay', 'route' => 'client.loans.lenders.index', 'permission' => 'client.loans.contracts.view', 'icon' => 'users', 'sort_order' => 20],
        'contracts' => ['name' => 'Hợp đồng vay', 'route' => 'client.loans.contracts.index', 'permission' => 'client.loans.contracts.view', 'icon' => 'document-text', 'sort_order' => 30],
        'payments' => ['name' => 'Thanh toán', 'route' => 'client.loans.payments.index', 'permission' => 'client.loans.contracts.view', 'icon' => 'credit-card', 'sort_order' => 40],
        'reminders' => ['name' => 'Lịch nhắc nhở', 'route' => 'client.loans.reminders.index', 'permission' => 'client.loans.contracts.view', 'icon' => 'bell', 'sort_order' => 50],
        'insurance' => ['name' => 'Bảo hiểm', 'route' => 'client.loans.insurance.index', 'permission' => 'client.loans.insurance.view', 'icon' => 'shield-check', 'sort_order' => 60],
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
        'lenders' => [
            'name' => 'Đối tác cho vay', 'description' => 'Cá nhân, công ty và tổ chức tín dụng.', 'eyebrow' => 'Khoản vay',
            'page_title' => 'Quản lý bên cho vay', 'page_description' => 'Cá nhân, công ty và tổ chức tín dụng.',
            'route' => 'client.loans.lenders.index', 'permission' => 'client.loans.contracts.view',
            'icon' => 'users', 'sort_order' => 20,
        ],
        'contracts' => [
            'name' => 'Hợp đồng vay', 'description' => 'Tra cứu và quản lý hợp đồng vay.', 'eyebrow' => 'Khoản vay',
            'page_title' => 'Danh sách hợp đồng', 'page_description' => 'Tra cứu và quản lý hợp đồng vay.',
            'route' => 'client.loans.contracts.index', 'permission' => 'client.loans.contracts.view',
            'icon' => 'document-text', 'sort_order' => 30,
            'actions' => [
                'create' => ['name' => 'Tạo hợp đồng', 'permission' => 'client.loans.contracts.create'],
                'edit' => ['name' => 'Sửa hợp đồng', 'permission' => 'client.loans.contracts.edit'],
                'reports' => ['name' => 'Xem báo cáo', 'permission' => 'client.loans.reports.view'],
            ],
        ],
        'payments' => [
            'name' => 'Thanh toán', 'description' => 'Theo dõi giải ngân và nghĩa vụ trả nợ.', 'eyebrow' => 'Khoản vay',
            'page_title' => 'Giải ngân và thanh toán', 'page_description' => 'Theo dõi giải ngân và nghĩa vụ trả nợ.',
            'route' => 'client.loans.payments.index', 'permission' => 'client.loans.contracts.view',
            'icon' => 'credit-card', 'sort_order' => 40,
            'actions' => [
                'create' => ['name' => 'Ghi nhận giải ngân và thanh toán', 'permission' => 'client.loans.payments.create'],
            ],
        ],
        'reminders' => [
            'name' => 'Lịch nhắc nhở', 'description' => 'Thiết lập và theo dõi nhắc nhở.', 'eyebrow' => 'Khoản vay',
            'page_title' => 'Nhắc hạn thanh toán', 'page_description' => 'Thiết lập và theo dõi nhắc nhở.',
            'route' => 'client.loans.reminders.index', 'permission' => 'client.loans.contracts.view',
            'icon' => 'bell', 'sort_order' => 50,
        ],
        'insurance' => [
            'name' => 'Bảo hiểm', 'description' => 'Theo dõi hợp đồng bảo hiểm và kỳ phí.', 'eyebrow' => 'Khoản vay',
            'page_title' => 'Bảo hiểm khoản vay', 'page_description' => 'Theo dõi hợp đồng bảo hiểm và kỳ phí.',
            'route' => 'client.loans.insurance.index', 'permission' => 'client.loans.insurance.view',
            'icon' => 'shield-check', 'sort_order' => 60,
            'actions' => [
                'manage' => ['name' => 'Quản lý bảo hiểm và phí', 'permission' => 'client.loans.insurance.manage'],
            ],
        ],
    ],
];
