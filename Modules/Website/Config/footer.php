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
];
