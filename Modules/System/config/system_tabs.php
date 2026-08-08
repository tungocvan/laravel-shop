<?php

return [

    /*
    |--------------------------------------------------------------------------
    | System Settings Tabs (Core)
    |--------------------------------------------------------------------------
    | Đây là cấu hình mặc định (core) của hệ thống.
    | JSON override sẽ ghi đè các field nếu trùng ID.
    |
    | RULE:
    | - id: unique (bắt buộc)
    | - label: tên hiển thị
    | - component: Livewire component
    | - icon: SVG path : https://heroicons.com/ => Chọn icon (ví dụ: database, cloud, mail...) => Copy SVG => lấy chỉ phần d
        hoặc icon copy full svg luôn.
    | - enabled: mặc định true
    |
    */
     [
        'id' => 'themes',
        'label' => 'Giao diện Sidebar',
        'icon' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.65l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
        'component' => 'admin.theme-switcher',
        'enabled' => true,
    ],

    [
        'id' => 'database',
        'label' => 'Cơ sở dữ liệu',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
</svg>
',
        'component' => 'system.settings.database-config',
        'enabled' => true,
    ],

    [
        'id' => 'mail',
        'label' => 'Cấu hình Email',
        'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
        'component' => 'system.settings.mail-config',
        'enabled' => true,
    ],
    [
        'id' => 'artisan',
        'label' => 'Thực hiện Artisan',
        'icon' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.65l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
        'component' => 'system.settings.artisan-list',
        'enabled' => false,
    ],   
     [
        'id' => 'sh',
        'label' => 'Thực hiện Sh Script',
        'icon' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.65l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
        'component' => 'system.settings.sh-script',
        'enabled' => false,
    ],   

];
 
