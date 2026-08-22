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
            'allowed_slots' => ['desktop.main.left', 'desktop.main.center', 'desktop.main.right'],
        ],
        'search' => [
            'label' => 'Tìm kiếm',
            'view' => 'Website::components.header.search',
            'allowed_slots' => ['desktop.main.left', 'desktop.main.center', 'desktop.main.right', 'mobile.search'],
        ],
        'actions' => [
            'label' => 'Điều hướng / hành động',
            'view' => 'Website::components.header.actions',
            'allowed_slots' => ['desktop.main.left', 'desktop.main.center', 'desktop.main.right'],
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
                ['type' => 'topbar', 'enabled' => true],
            ],
            'main' => [
                'left' => [
                    ['type' => 'brand', 'enabled' => true],
                ],
                'center' => [
                    ['type' => 'search', 'enabled' => true, 'config' => ['mode' => 'desktop']],
                ],
                'right' => [
                    ['type' => 'actions', 'enabled' => true],
                ],
            ],
        ],
        'mobile' => [
            'search' => [
                ['type' => 'search', 'enabled' => true, 'config' => ['mode' => 'mobile']],
            ],
            'drawer' => [
                ['type' => 'mobile-menu', 'enabled' => true],
            ],
        ],
    ],

    'presentation' => [
        'mode' => 'basic',
        'container' => 'standard',
        'size' => 'normal',
        'sticky' => true,
        'shadow' => 'soft',
        'inherit_colors' => true,
        'background' => '#ffffff',
        'foreground' => '#111827',
        'accent' => '#2563eb',
        'border' => '#e5e7eb',
        'topbar_background' => '#111827',
        'topbar_foreground' => '#ffffff',
        'custom' => [
            'container_width' => 1280,
            'desktop_height' => 80,
            'tablet_height' => 72,
            'mobile_height' => 64,
            'topbar_height' => 32,
            'logo_max_height' => 48,
            'search_max_width' => 560,
        ],
    ],

    'presets' => [
        'container' => [
            'compact' => 1024,
            'standard' => 1280,
            'wide' => 1440,
            'full' => null,
        ],
        'size' => [
            'compact' => ['desktop' => 64, 'tablet' => 60, 'mobile' => 56],
            'normal' => ['desktop' => 80, 'tablet' => 72, 'mobile' => 64],
            'comfortable' => ['desktop' => 96, 'tablet' => 84, 'mobile' => 72],
        ],
    ],

    'bounds' => [
        'container_width' => [960, 1920],
        'header_height' => [52, 120],
        'topbar_height' => [24, 56],
        'logo_max_height' => [24, 72],
        'search_max_width' => [320, 900],
    ],
];
