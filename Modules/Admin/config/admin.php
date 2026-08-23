<?php

return [
    'locale' => 'vi',

    'layout' => [
        'preset' => env('ADMIN_LAYOUT_PRESET', 'default'),
        'container' => env('ADMIN_LAYOUT_CONTAINER', 'screen-2xl'),
        'density' => env('ADMIN_LAYOUT_DENSITY', 'comfortable'),
        'sticky_header' => true,
        'show_footer' => false,
    ],

    'design' => [
        'typography' => [
            'font_family' => 'sans',
            'body_size' => 'sm',
            'page_title_size' => '2xl',
            'heading_weight' => 'semibold',
        ],
        'colors' => [
            'surface_base' => 'slate-50',
            'surface_raised' => 'white',
            'text_primary' => 'slate-900',
            'text_secondary' => 'slate-700',
            'text_muted' => 'slate-500',
            'border_subtle' => 'slate-200',
            'accent' => 'indigo-600',
            'focus_ring' => 'indigo-500',
            'success' => 'emerald-600',
            'warning' => 'amber-500',
            'danger' => 'rose-600',
            'info' => 'sky-600',
        ],
        'spacing' => [
            'tight' => '2',
            'control' => '3',
            'content' => '4',
            'section' => '6',
        ],
        'radius' => [
            'control' => 'lg',
            'panel' => 'lg',
            'overlay' => 'xl',
        ],
    ],

    'sidebar' => [
        'enabled' => true,
        'expanded_width' => '16rem',
        'collapsed_width' => '5rem',
        'desktop_collapsible' => true,
        'mobile_drawer' => true,
        'persist_state' => true,
        'show_footer_profile' => true,
    ],

    'header' => [
        'height' => '4rem',
        'sticky' => true,
        'search' => true,
        'notifications' => true,
        'theme_switcher' => false,
        'user_menu' => true,
        'mobile_search_mode' => 'overlay',
    ],

    'theme' => [
        'default' => env('ADMIN_SIDEBAR_THEME', 'corporate-blue'),
        'dark_mode' => 'class',
        'accent' => 'blue',
    ],

    'navigation' => [
        'cache_ttl' => 3600,
        'active_strategy' => 'url-prefix',
        'max_depth' => 2,
    ],
];
