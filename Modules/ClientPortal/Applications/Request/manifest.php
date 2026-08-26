<?php

return [
    'key' => 'request',
    'source_module' => 'Request',
    'name' => 'Đề nghị & Phê duyệt',
    'description' => 'Tạo và theo dõi đề nghị cá nhân, đồng thời xử lý các công việc phê duyệt được giao.',
    'icon' => 'clipboard-document-check',
    'route' => 'client.request.dashboard',
    'permission' => 'client.request.access',
    'sort_order' => 20,
    'features' => [
        'overview' => [
            'name' => 'Tổng quan',
            'description' => 'Theo dõi nhanh đề nghị của bạn và khối lượng công việc cần phê duyệt.',
            'route' => 'client.request.dashboard',
            'permission' => 'client.request.overview.view',
            'sort_order' => 10,
        ],
        'create' => [
            'name' => 'Tạo đề nghị',
            'description' => 'Chọn loại đề nghị và bắt đầu một đề nghị mới.',
            'route' => 'client.request.catalog',
            'permission' => 'client.request.create.view',
            'sort_order' => 20,
        ],
        'mine' => [
            'name' => 'Đề nghị của tôi',
            'description' => 'Theo dõi bản nháp, đề nghị đang xử lý, cần bổ sung và đã hoàn tất.',
            'route' => 'client.request.mine',
            'permission' => 'client.request.mine.view',
            'sort_order' => 30,
        ],
        'inbox' => [
            'name' => 'Cần phê duyệt',
            'description' => 'Xem các công việc đang chờ bạn xem xét và quyết định.',
            'permission' => 'client.request.inbox.view',
            'sort_order' => 40,
        ],
        'processed' => [
            'name' => 'Đã xử lý',
            'description' => 'Tra cứu các công việc phê duyệt bạn đã xử lý.',
            'permission' => 'client.request.processed.view',
            'sort_order' => 50,
        ],
    ],
];
