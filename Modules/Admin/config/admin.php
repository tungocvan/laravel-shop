<?php

return [
    'locale' => 'vi',
    'layout' => [
        'preset' => env('ADMIN_LAYOUT_PRESET', 'default'),
        'container' => env('ADMIN_LAYOUT_CONTAINER', 'screen-2xl'),
        'density' => env('ADMIN_LAYOUT_DENSITY', 'comfortable'),
        'sticky_header' => true,
        'show_footer' => false,
        'spacing' => ['content_padding_x' => '6', 'content_padding_top' => '6', 'content_padding_bottom' => '8', 'section_gap' => '6', 'tablet_padding_x' => '5', 'mobile_padding_x' => '4'],
        'surface' => ['page_background' => 'system', 'content_surface' => 'transparent', 'border' => 'system', 'radius' => 'lg'],
        'behavior' => ['reduced_motion' => true],
    ],
    'design' => [
        'typography' => ['font_family' => 'sans', 'body_size' => 'sm', 'page_title_size' => '2xl', 'heading_weight' => 'semibold'],
        'colors' => ['surface_base' => 'slate-50', 'surface_raised' => 'white', 'text_primary' => 'slate-900', 'text_secondary' => 'slate-700', 'text_muted' => 'slate-500', 'border_subtle' => 'slate-200', 'accent' => 'indigo-600', 'focus_ring' => 'indigo-500', 'success' => 'emerald-600', 'warning' => 'amber-500', 'danger' => 'rose-600', 'info' => 'sky-600'],
        'sidebar_menu' => [
            'item' => ['font_family' => 'inherit', 'font_size' => 'sm', 'font_weight' => 'medium', 'title_color' => 'slate-900', 'icon_color' => 'slate-400', 'icon_size' => '20', 'item_height' => '44', 'padding_x' => '12', 'padding_y' => '8', 'content_gap' => '12', 'item_gap' => '4'],
            'submenu' => ['font_family' => 'inherit', 'font_size' => '13', 'font_weight' => 'normal', 'title_color' => 'slate-500', 'icon_color' => 'slate-400', 'indent' => '28', 'item_height' => '36', 'padding_x' => '12', 'padding_y' => '6', 'offset' => '12', 'item_gap' => '2'],
            'group' => ['gap' => '4'],
            'active' => ['title_color' => 'white', 'icon_color' => 'white', 'font_weight' => 'semibold', 'menu_border_color' => 'indigo-600', 'menu_border_width' => '0', 'menu_border_style' => 'solid', 'submenu_border_color' => 'indigo-600', 'submenu_border_width' => '0', 'submenu_border_style' => 'solid'],
        ],
        'spacing' => ['tight' => '2', 'control' => '3', 'content' => '4', 'section' => '6'],
        'radius' => ['control' => 'lg', 'panel' => 'lg', 'overlay' => 'xl'],
    ],
    'sidebar' => ['enabled' => true, 'expanded_width' => '256px', 'collapsed_width' => '80px', 'desktop_collapsible' => true, 'mobile_drawer' => true, 'persist_state' => true, 'show_footer_profile' => true, 'navigation_search_threshold' => 12, 'controls' => ['collapse_enabled' => true, 'fullscreen_enabled' => true], 'header' => ['enabled' => true, 'show_mark' => true, 'show_title' => true, 'title' => null, 'show_subtitle' => true, 'subtitle' => 'Không gian quản trị'], 'footer' => ['enabled' => true, 'show_avatar' => true, 'show_name' => true, 'show_subtitle' => true, 'subtitle' => 'Tài khoản quản trị'], 'search' => ['enabled' => true], 'presentation' => ['background' => 'theme']],
    'header' => ['height' => '4rem', 'sticky' => true, 'search' => true, 'notifications' => true, 'theme_switcher' => false, 'user_menu' => true, 'mobile_search_mode' => 'overlay', 'brand' => ['enabled' => true, 'logo' => null, 'logo_size' => '32', 'show_title' => true, 'title' => null, 'show_subtitle' => false, 'subtitle' => null, 'url' => '/admin'], 'user_menu_config' => ['show_avatar' => true, 'show_name' => true, 'show_email' => true, 'show_role' => false, 'items' => []], 'actions' => ['items' => [], 'mobile_overflow' => true], 'presentation' => ['mode' => 'balanced', 'padding_x' => '6', 'action_gap' => '2', 'background' => 'system', 'divider' => 'subtle', 'shadow' => 'subtle', 'backdrop_blur' => true], 'responsive' => ['mobile_brand' => 'logo-only', 'hide_title_on_mobile' => true, 'overflow_secondary_actions' => true]],
    'footer' => ['show_app_name' => true, 'copyright' => ['enabled' => true, 'owner' => null, 'url' => null, 'start_year' => null], 'datetime' => ['show_date' => true, 'show_time' => true, 'date_format' => 'd/m/Y', 'time_format' => 'H:i:s'], 'presentation' => ['alignment' => 'split', 'background' => 'system', 'divider' => 'subtle', 'compact' => true]],
    'theme' => ['default' => env('ADMIN_SIDEBAR_THEME', 'corporate-blue'), 'dark_mode' => 'class', 'accent' => 'blue'],
    'navigation' => ['cache_ttl' => 3600, 'active_strategy' => 'url-prefix', 'max_depth' => 2],
];
