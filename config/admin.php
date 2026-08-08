<?php
return [

    'sidebar' => [

        // ======================
        // BEHAVIOR
        // ======================
        'default_open' => false,
        'collapse' => true,

        // ======================
        // THEME
        // ======================
        'theme' => 'dark', // đổi sang 'blue-dark' hoặc 'soft-light'

        'themes' => [

            'dark' => [
                'background' => 'bg-gray-900',
                'text' => 'text-white',
                'hover' => 'hover:bg-gray-800',
                'active_bg' => 'bg-gray-800',
                'active_text' => 'text-white',
                'icon_active' => 'text-indigo-400',
                'icon_inactive' => 'text-gray-500',
            ],

            // 🔵 NEW
            'blue-dark' => [
                'background' => 'bg-slate-900',
                'text' => 'text-slate-200',
                'hover' => 'hover:bg-slate-800',
                'active_bg' => 'bg-blue-600/20',
                'active_text' => 'text-blue-400',
                'icon_active' => 'text-blue-400',
                'icon_inactive' => 'text-slate-500',
            ],

            // 🌤 NEW
            'soft-light' => [
                'background' => 'bg-gray-50',
                'text' => 'text-gray-700',
                'hover' => 'hover:bg-gray-100',
                'active_bg' => 'bg-indigo-100',
                'active_text' => 'text-indigo-600',
                'icon_active' => 'text-indigo-500',
                'icon_inactive' => 'text-gray-400',
            ],
        ],

        // ======================
        // MENU STYLE
        // ======================
        'menu' => [
            'font_size' => 'text-sm',
            'item_height' => 'py-2.5',
            'padding' => 'px-3',
            'gap' => 'space-y-1',
        ],

        // ======================
        // ICON
        // ======================
        'icon' => [
            'size' => 'h-6 w-6',
            'active' => 'text-indigo-400',
            'inactive' => 'text-gray-500',
        ],

    ],

];
