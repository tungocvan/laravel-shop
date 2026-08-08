<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Menu Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for the menu management system.
    |
    */

    'seeder_path' => env('MENU_STORAGE_PATH', base_path('storage/app/menu/menu.json')),

    'cache' => [
        'enabled' => env('MENU_CACHE_ENABLED', true),
        'ttl' => env('MENU_CACHE_TTL', 3600), // 1 hour
        'key' => env('MENU_CACHE_KEY', 'admin.menus'),
    ],

    'validation' => [
        'name' => 'required|string|max:255',
        'url' => 'nullable|string|max:500|regex:/^[a-zA-Z0-9\/\-_\.]+$/',
        'icon' => 'nullable|string|max:100',
        'can' => 'nullable|exists:permissions,name',
        'is_active' => 'boolean',
    ],

    'import' => [
        'max_file_size' => 2048, // KB
        'allowed_mimes' => ['json', 'txt'],
    ],

    'export' => [
        'filename_prefix' => 'menu_backup',
        'pretty_print' => true,
        'unicode_support' => true,
    ],
];
