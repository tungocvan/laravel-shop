<?php

return [
    'key' => 'muasamcong',
    'source_module' => 'Muasamcong',
    'name' => 'Mua sắm công',
    'description' => 'Tra cứu và phân tích dữ liệu mua sắm công, giá thuốc và kết quả lựa chọn nhà thầu.',
    'icon' => 'shopping-cart',
    'route' => 'client.muasamcong.dashboard',
    'permission' => 'client.muasamcong.access',
    'sort_order' => 10,
    'layout' => ['mode' => 'workspace'],
    'capabilities' => ['search', 'filter', 'share', 'background-jobs', 'export'],
    'shell_extensions' => [
        'head' => [
            [
                'routes' => ['client.muasamcong.price-list*'],
                'view' => 'ClientPortal::applications.muasamcong.partials.price-list-workspace-polish',
            ],
        ],
        'overlays' => [
            [
                'routes' => ['client.muasamcong.drug-pricing*'],
                'view' => 'ClientPortal::applications.muasamcong.partials.sync-queue-status',
            ],
        ],
    ],
    'quick_actions' => [
        'drug-search' => [
            'name' => 'Tra cứu thuốc',
            'route' => 'client.muasamcong.drug-pricing',
            'permission' => 'client.muasamcong.drug-pricing.view',
            'icon' => 'magnifying-glass',
            'sort_order' => 10,
        ],
        'price-list' => [
            'name' => 'Bảng Giá',
            'route' => 'client.muasamcong.price-list',
            'permission' => 'client.muasamcong.price-list.view',
            'icon' => 'document-chart-bar',
            'sort_order' => 20,
        ],
    ],
    'navigation' => [
        'overview' => [
            'name' => 'Tổng quan',
            'route' => 'client.muasamcong.dashboard',
            'icon' => 'home',
            'sort_order' => 10,
        ],
        'drug-pricing' => [
            'name' => 'Tra cứu',
            'route' => 'client.muasamcong.drug-pricing',
            'permission' => 'client.muasamcong.drug-pricing.view',
            'icon' => 'magnifying-glass',
            'sort_order' => 20,
        ],
        'price-list' => [
            'name' => 'Bảng giá',
            'route' => 'client.muasamcong.price-list',
            'permission' => 'client.muasamcong.price-list.view',
            'icon' => 'document-chart-bar',
            'sort_order' => 30,
        ],
        'history' => [
            'name' => 'Lịch sử',
            'route' => 'client.muasamcong.history',
            'permission' => 'client.muasamcong.history.view',
            'icon' => 'clock',
            'sort_order' => 40,
        ],
        'wishlist' => [
            'name' => 'Quan tâm',
            'route' => 'client.muasamcong.wishlist',
            'permission' => 'client.muasamcong.wishlist.view',
            'icon' => 'heart',
            'placement' => 'more',
            'sort_order' => 50,
        ],
    ],
    'features' => [
        'drug-pricing' => [
            'name' => 'Tra cứu thuốc trúng thầu',
            'description' => 'Tra cứu dữ liệu giá thuốc và kết quả trúng thầu thực tế.',
            'route' => 'client.muasamcong.drug-pricing',
            'permission' => 'client.muasamcong.drug-pricing.view',
            'sort_order' => 10,
            'actions' => [
                'sync' => [
                    'name' => 'Đồng bộ dữ liệu tra cứu',
                    'permission' => 'client.muasamcong.drug-pricing.sync',
                    'sort_order' => 10,
                ],
            ],
        ],
        'history' => [
            'name' => 'Lịch sử tra cứu',
            'description' => 'Xem lại từ khóa đã tra cứu và trạng thái các lần đồng bộ dữ liệu.',
            'route' => 'client.muasamcong.history',
            'permission' => 'client.muasamcong.history.view',
            'sort_order' => 20,
        ],
        'wishlist' => [
            'name' => 'Danh sách quan tâm',
            'description' => 'Lưu các thuốc và kết quả trúng thầu cần theo dõi để mở lại nhanh.',
            'route' => 'client.muasamcong.wishlist',
            'permission' => 'client.muasamcong.wishlist.view',
            'sort_order' => 30,
        ],
        'price-list' => [
            'name' => 'Bảng Giá',
            'description' => 'Chọn thuốc đã đồng bộ hoặc Wishlist và xuất Excel theo cấu hình Admin.',
            'route' => 'client.muasamcong.price-list',
            'permission' => 'client.muasamcong.price-list.view',
            'sort_order' => 35,
            'actions' => [
                'export' => [
                    'name' => 'Xuất Bảng Giá Excel',
                    'permission' => 'client.muasamcong.price-list.export',
                    'sort_order' => 10,
                ],
            ],
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
