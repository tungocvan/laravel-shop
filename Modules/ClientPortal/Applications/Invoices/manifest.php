<?php

return [
    'key' => 'invoices',
    'source_module' => 'Invoices',
    'name' => 'Hóa đơn',
    'description' => 'Tra cứu, theo dõi và xử lý hóa đơn trên thiết bị di động và máy tính bảng.',
    'icon' => 'document-text',
    'route' => 'client.invoices.dashboard',
    'permission' => 'client.invoices.access',
    'sort_order' => 30,
    'layout' => ['mode' => 'workspace'],
    'capabilities' => ['search', 'filter', 'export', 'background-jobs'],
    'quick_actions' => [
        'search' => [
            'name' => 'Tra cứu hóa đơn',
            'route' => 'client.invoices.index',
            'permission' => 'client.invoices.list.view',
            'icon' => 'magnifying-glass',
            'sort_order' => 10,
        ],
        'sync' => [
            'name' => 'Đồng bộ',
            'route' => 'client.invoices.sync',
            'permission' => 'client.invoices.sync',
            'icon' => 'arrow-path',
            'sort_order' => 20,
        ],
    ],
    'navigation' => [
        'overview' => [
            'name' => 'Tổng quan',
            'route' => 'client.invoices.dashboard',
            'permission' => 'client.invoices.overview.view',
            'icon' => 'home',
            'sort_order' => 10,
        ],
        'invoices' => [
            'name' => 'Hóa đơn',
            'route' => 'client.invoices.index',
            'permission' => 'client.invoices.list.view',
            'icon' => 'document-text',
            'sort_order' => 20,
        ],
        'sync' => [
            'name' => 'Đồng bộ',
            'route' => 'client.invoices.sync',
            'permission' => 'client.invoices.sync',
            'icon' => 'arrow-path',
            'placement' => 'more',
            'sort_order' => 30,
        ],
    ],
    'features' => [
        'overview' => [
            'name' => 'Tổng quan',
            'description' => 'Theo dõi nhanh số lượng, giá trị và trạng thái tài liệu hóa đơn.',
            'route' => 'client.invoices.dashboard',
            'permission' => 'client.invoices.overview.view',
            'icon' => 'home',
            'sort_order' => 10,
        ],
        'list' => [
            'name' => 'Danh sách hóa đơn',
            'description' => 'Tìm kiếm, lọc và xem chi tiết hóa đơn với giao diện tối ưu cho cảm ứng.',
            'route' => 'client.invoices.index',
            'permission' => 'client.invoices.list.view',
            'icon' => 'document-text',
            'sort_order' => 20,
            'actions' => [
                'detail' => [
                    'name' => 'Xem chi tiết',
                    'permission' => 'client.invoices.detail.view',
                    'sort_order' => 10,
                ],
                'export' => [
                    'name' => 'Xuất Excel',
                    'permission' => 'client.invoices.export',
                    'sort_order' => 20,
                ],
                'pdf' => [
                    'name' => 'Tải PDF',
                    'permission' => 'client.invoices.pdf.download',
                    'sort_order' => 30,
                ],
            ],
        ],
        'sync' => [
            'name' => 'Đồng bộ hóa đơn',
            'description' => 'Khởi tạo tác vụ đồng bộ được kiểm soát quyền mà không đưa thông tin xác thực GDT xuống trình duyệt.',
            'route' => 'client.invoices.sync',
            'permission' => 'client.invoices.sync',
            'icon' => 'arrow-path',
            'sort_order' => 30,
        ],
    ],
];
