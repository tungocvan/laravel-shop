<?php

return [
    'key' => 'attendance',
    'source_module' => 'Attendance',
    'name' => 'Chấm công',
    'description' => 'Chấm công vào/ra theo vị trí công ty, xem lịch sử và gửi yêu cầu điều chỉnh thời gian làm việc.',
    'icon' => 'clock',
    'route' => 'client.attendance.dashboard',
    'permission' => 'client.attendance.access',
    'sort_order' => 10,
    'layout' => ['mode' => 'focus'],
    'capabilities' => ['geolocation', 'history'],
    'quick_actions' => [
        'today' => [
            'name' => 'Chấm công hôm nay',
            'route' => 'client.attendance.dashboard',
            'permission' => 'attendance.record.view-own',
            'icon' => 'clock',
            'sort_order' => 10,
        ],
        'adjustment' => [
            'name' => 'Xin điều chỉnh',
            'route' => 'client.attendance.adjustments',
            'permission' => 'attendance.adjustment.create',
            'icon' => 'pencil-square',
            'sort_order' => 20,
        ],
    ],
    'navigation' => [
        'today' => [
            'name' => 'Hôm nay',
            'route' => 'client.attendance.dashboard',
            'permission' => 'attendance.record.view-own',
            'icon' => 'clock',
            'sort_order' => 10,
        ],
        'history' => [
            'name' => 'Lịch sử',
            'route' => 'client.attendance.history',
            'permission' => 'attendance.record.view-own',
            'icon' => 'calendar-days',
            'sort_order' => 20,
        ],
        'adjustments' => [
            'name' => 'Điều chỉnh',
            'route' => 'client.attendance.adjustments',
            'permission' => 'attendance.adjustment.create',
            'icon' => 'pencil-square',
            'placement' => 'more',
            'sort_order' => 30,
        ],
    ],
    'features' => [
        'today' => [
            'name' => 'Chấm công hôm nay',
            'description' => 'Xem ca hiện tại và thực hiện chấm công vào/ra bằng vị trí thiết bị.',
            'route' => 'client.attendance.dashboard',
            'permission' => 'attendance.record.view-own',
            'sort_order' => 10,
            'actions' => [
                'check-in' => [
                    'name' => 'Chấm công vào',
                    'permission' => 'attendance.check-in',
                    'sort_order' => 10,
                ],
                'check-out' => [
                    'name' => 'Chấm công ra',
                    'permission' => 'attendance.check-out',
                    'sort_order' => 20,
                ],
            ],
        ],
        'history' => [
            'name' => 'Lịch sử chấm công',
            'description' => 'Xem các phiên chấm công của chính bạn.',
            'route' => 'client.attendance.history',
            'permission' => 'attendance.record.view-own',
            'sort_order' => 20,
        ],
        'adjustments' => [
            'name' => 'Yêu cầu điều chỉnh',
            'description' => 'Gửi yêu cầu sửa giờ vào/ra khi cần và theo dõi trạng thái xử lý.',
            'route' => 'client.attendance.adjustments',
            'permission' => 'attendance.adjustment.create',
            'sort_order' => 30,
        ],
    ],
];
