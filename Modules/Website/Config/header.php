<?php

return [
    'components' => [
        'topbar' => [
            'label' => 'Top bar',
            'view' => 'Website::components.header.topbar',
            'allowed_slots' => ['desktop.topbar'],
        ],
        'brand' => [
            'label' => 'Logo / thương hiệu',
            'view' => 'Website::components.header.brand',
            'allowed_slots' => ['desktop.main.left'],
        ],
        'search' => [
            'label' => 'Tìm kiếm',
            'view' => 'Website::components.header.search',
            'allowed_slots' => ['desktop.main.center', 'mobile.search'],
        ],
        'actions' => [
            'label' => 'Điều hướng / hành động',
            'view' => 'Website::components.header.actions',
            'allowed_slots' => ['desktop.main.right'],
        ],
        'mobile-menu' => [
            'label' => 'Menu di động',
            'view' => 'Website::components.header.mobile-menu',
            'allowed_slots' => ['mobile.drawer'],
        ],
    ],

    'layout' => [
        'desktop' => [
            'topbar' => [
                ['type' => 'topbar'],
            ],
            'main' => [
                'left' => [
                    ['type' => 'brand'],
                ],
                'center' => [
                    ['type' => 'search', 'config' => ['mode' => 'desktop']],
                ],
                'right' => [
                    ['type' => 'actions'],
                ],
            ],
        ],
        'mobile' => [
            'search' => [
                ['type' => 'search', 'config' => ['mode' => 'mobile']],
            ],
            'drawer' => [
                ['type' => 'mobile-menu'],
            ],
        ],
    ],
];
