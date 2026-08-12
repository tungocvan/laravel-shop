<?php

return [
    'artisan.list' => [
        'group' => 'Thông tin',
        'label' => 'Danh sách Artisan',
        'description' => 'Hiển thị các câu lệnh Artisan đang được đăng ký trong ứng dụng.',
        'command' => 'list',
        'arguments' => [],
        'confirmation' => false,
    ],
    'route.list' => [
        'group' => 'Thông tin',
        'label' => 'Danh sách Route',
        'description' => 'Hiển thị các route đang được đăng ký trong ứng dụng.',
        'command' => 'route:list',
        'arguments' => [],
        'confirmation' => false,
    ],
    'about' => [
        'group' => 'Thông tin',
        'label' => 'Laravel About',
        'description' => 'Hiển thị thông tin tổng quan an toàn về ứng dụng Laravel và runtime.',
        'command' => 'about',
        'arguments' => [],
        'confirmation' => false,
    ],
    'cache.optimize-clear' => [
        'group' => 'Cache',
        'label' => 'Xóa cache framework',
        'description' => 'Xóa config, route, view và các cache tối ưu của Laravel.',
        'command' => 'optimize:clear',
        'arguments' => [],
        'confirmation' => true,
    ],
    'queue.restart' => [
        'group' => 'Queue',
        'label' => 'Restart Queue',
        'description' => 'Yêu cầu các queue worker Laravel khởi động lại an toàn sau job hiện tại.',
        'command' => 'queue:restart',
        'arguments' => [],
        'confirmation' => true,
    ],
];
