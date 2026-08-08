<?php

return [
    'locale' => 'vi',

    'layout' => [
        'preset' => env('ADMIN_LAYOUT_PRESET', 'default'),
        'container' => env('ADMIN_LAYOUT_CONTAINER', '7xl'),
        'density' => env('ADMIN_LAYOUT_DENSITY', 'comfortable'),
        'sticky_header' => true,
        'show_footer' => false,
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
