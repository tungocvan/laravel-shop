<?php

return [
    'demo.system-info' => [
        'group' => 'Demo an toàn',
        'label' => 'Thông tin hệ thống',
        'description' => 'Chạy script demo chỉ đọc để hiển thị thời gian UTC, PHP CLI và ngữ cảnh thư mục ứng dụng.',
        'script' => 'demo-system-info.sh',
        'arguments' => [],
        'timeout' => 10,
        'confirmation' => false,
    ],
    'demo.disk-usage' => [
        'group' => 'Kiểm tra hệ thống',
        'label' => 'Dung lượng filesystem',
        'description' => 'Chạy kiểm tra chỉ đọc dung lượng filesystem chứa ứng dụng.',
        'script' => 'demo-disk-usage.sh',
        'arguments' => [],
        'timeout' => 10,
        'confirmation' => false,
    ],
];
