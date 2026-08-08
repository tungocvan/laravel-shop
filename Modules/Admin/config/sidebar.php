<?php

return [

    // ======================
    // ACTIVE THEME
    // ======================
    'theme' => env('ADMIN_SIDEBAR_THEME', 'corporate-blue'),
    // 'menu-path' => env('ADMIN_MENU_PATH', base_path('/Modules/Admin/data/menu.json')),

    // ======================
    // THEMES COLLECTION
    // ======================
    'themes' => [

        // ======================
        // SOFT LIGHT (DEFAULT)
        // ======================
        'soft-light' => [
            'background'        => 'bg-slate-50',
            'text'              => 'text-slate-700',
            'hover'             => 'hover:bg-slate-100',

            'active_bg'         => 'bg-indigo-600',
            'active_text'       => 'text-white',

            'icon_active'       => 'text-indigo-600',
            'icon_inactive'     => 'text-slate-400',

            'child_active_bg'   => 'bg-indigo-500/20',
            'child_active_text' => 'text-indigo-600',
            'child_text'        => 'text-slate-500',
            'child_hover'       => 'hover:bg-slate-100 hover:text-slate-900',

            'border'            => 'border-slate-200',
        ],

        // ======================
        // MODERN DARK (SaaS DARK MODE)
        // ======================
        'modern-dark' => [
            'background'        => 'bg-slate-900',
            'text'              => 'text-slate-200',
            'hover'             => 'hover:bg-slate-800',

            'active_bg'         => 'bg-indigo-500',
            'active_text'       => 'text-white',

            'icon_active'       => 'text-indigo-400',
            'icon_inactive'     => 'text-slate-500',

            'child_active_bg'   => 'bg-indigo-500/20',
            'child_active_text' => 'text-indigo-300',
            'child_text'        => 'text-slate-400',
            'child_hover'       => 'hover:bg-slate-800 hover:text-white',

            'border'            => 'border-slate-800',
        ],

        // ======================
        // SUNSET ORANGE (UI EDUCATION / MARKETING STYLE)
        // ======================
        'sunset-orange' => [
            'background'        => 'bg-orange-50',
            'text'              => 'text-orange-900',
            'hover'             => 'hover:bg-orange-100',

            'active_bg'         => 'bg-orange-500',
            'active_text'       => 'text-white',

            'icon_active'       => 'text-orange-500',
            'icon_inactive'     => 'text-orange-300',

            'child_active_bg'   => 'bg-orange-500/20',
            'child_active_text' => 'text-orange-600',
            'child_text'        => 'text-orange-500',
            'child_hover'       => 'hover:bg-orange-100 hover:text-orange-900',

            'border'            => 'border-orange-200',
        ],

        // ======================
        // CORPORATE BLUE (ENTERPRISE STYLE)
        // ======================
        'corporate-blue' => [
            'background'        => 'bg-blue-50',
            'text'              => 'text-blue-900',
            'hover'             => 'hover:bg-blue-100',

            'active_bg'         => 'bg-blue-600',
            'active_text'       => 'text-white',

            'icon_active'       => 'text-indigo-400',
            'icon_inactive'     => 'text-blue-300',

            'child_active_bg'   => 'bg-blue-500/20',
            'child_active_text' => 'text-blue-700',
            'child_text'        => 'text-blue-500',
            'child_hover'       => 'hover:bg-blue-100 hover:text-blue-900',

            'border'            => 'border-blue-200',
        ],

    ],
];