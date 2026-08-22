<?php

return [
    'presentation' => [
        'mode' => 'basic',
        'container' => 'standard',
        'spacing' => 'comfortable',
        'column_gap' => 'comfortable',
        'inherit_colors' => true,
        'accent' => true,
        'border' => true,
        'colors' => [
            'background' => '#111827',
            'foreground' => '#9ca3af',
            'heading' => '#ffffff',
            'muted' => '#6b7280',
            'accent' => '#2563eb',
            'border' => '#1f2937',
        ],
        'custom' => [
            'container_width' => 1280,
            'padding_top' => 64,
            'padding_bottom' => 32,
            'column_gap' => 48,
            'section_gap' => 64,
            'logo_max_height' => 40,
            'social_icon_size' => 40,
        ],
    ],

    'presets' => [
        'container' => [
            'compact' => 1024,
            'standard' => 1280,
            'wide' => 1440,
            'full' => null,
        ],
        'spacing' => [
            'compact' => ['top' => 40, 'bottom' => 24, 'section' => 40],
            'normal' => ['top' => 48, 'bottom' => 28, 'section' => 48],
            'comfortable' => ['top' => 64, 'bottom' => 32, 'section' => 64],
        ],
        'column_gap' => [
            'compact' => 24,
            'normal' => 32,
            'comfortable' => 48,
        ],
    ],

    'bounds' => [
        'container_width' => [960, 1920],
        'padding_top' => [24, 120],
        'padding_bottom' => [16, 96],
        'column_gap' => [16, 80],
        'section_gap' => [24, 120],
        'logo_max_height' => [24, 72],
        'social_icon_size' => [32, 56],
    ],

    'layout' => [
        'desktop' => [
            'top' => [],
            'main' => [
                'brand' => [
                    ['type' => 'brand'],
                    ['type' => 'contact'],
                ],
                'columns' => [
                    ['type' => 'menu-columns'],
                ],
                'extra' => [
                    ['type' => 'app-install'],
                    ['type' => 'social-links'],
                ],
            ],
            'bottom' => [
                'left' => [
                    ['type' => 'copyright'],
                    ['type' => 'legal-links'],
                ],
                'right' => [
                    ['type' => 'trust-badges'],
                ],
            ],
        ],
        'mobile' => [
            'main' => [
                ['type' => 'brand'],
                ['type' => 'contact'],
                ['type' => 'menu-columns'],
                ['type' => 'app-install'],
                ['type' => 'social-links'],
            ],
            'bottom' => [
                ['type' => 'copyright'],
                ['type' => 'legal-links'],
                ['type' => 'trust-badges'],
            ],
        ],
        'overlay' => [
            ['type' => 'back-to-top'],
        ],
    ],

    'components' => [
        'brand' => [
            'label' => 'Brand',
            'view' => 'Website::components.footer.brand',
            'allowed_slots' => ['desktop.main.brand', 'mobile.main'],
            'default_config' => [],
        ],
        'contact' => [
            'label' => 'Contact',
            'view' => 'Website::components.footer.contact',
            'allowed_slots' => ['desktop.main.brand', 'mobile.main'],
            'default_config' => [],
        ],
        'menu-columns' => [
            'label' => 'Menu Columns',
            'view' => 'Website::components.footer.menu-columns',
            'allowed_slots' => ['desktop.main.columns', 'mobile.main'],
            'default_config' => [],
        ],
        'app-install' => [
            'label' => 'App Install',
            'view' => 'Website::components.footer.app-install',
            'allowed_slots' => ['desktop.main.extra', 'mobile.main'],
            'default_config' => [],
        ],
        'social-links' => [
            'label' => 'Social Links',
            'view' => 'Website::components.footer.social-links',
            'allowed_slots' => ['desktop.main.extra', 'mobile.main'],
            'default_config' => [],
        ],
        'copyright' => [
            'label' => 'Copyright',
            'view' => 'Website::components.footer.copyright',
            'allowed_slots' => ['desktop.bottom.left', 'mobile.bottom'],
            'default_config' => [],
        ],
        'legal-links' => [
            'label' => 'Legal Links',
            'view' => 'Website::components.footer.legal-links',
            'allowed_slots' => ['desktop.bottom.left', 'mobile.bottom'],
            'default_config' => [],
        ],
        'trust-badges' => [
            'label' => 'Trust / Payment Badges',
            'view' => 'Website::components.footer.trust-badges',
            'allowed_slots' => ['desktop.bottom.right', 'mobile.bottom'],
            'default_config' => [],
        ],
        'back-to-top' => [
            'label' => 'Back to Top',
            'view' => 'Website::components.footer.back-to-top',
            'allowed_slots' => ['overlay'],
            'default_config' => [],
        ],
    ],
];
