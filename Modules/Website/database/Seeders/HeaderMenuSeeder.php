<?php

namespace Modules\Website\database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Website\Models\HeaderMenu;
use Modules\Website\Models\HeaderMenuItem;

class HeaderMenuSeeder extends Seeder
{
    /**
     * Seed only the authenticated account Header menu.
     *
     * Safe to run repeatedly: no truncate, no destructive reset.
     *
     * php artisan db:seed --class="Modules\\Website\\database\\Seeders\\HeaderMenuSeeder"
     */
    public function run(): void
    {
        $menu = HeaderMenu::query()->updateOrCreate(
            ['location' => 'account'],
            [
                'name' => 'Menu tài khoản sau đăng nhập',
                'is_active' => true,
            ]
        );

        $items = [
            [
                'title' => 'Ứng dụng của tôi',
                'url' => '/my-apps',
                'sort_order' => 1,
            ],
            [
                'title' => 'Hồ sơ cá nhân',
                'url' => '/account/profile',
                'sort_order' => 2,
            ],
            [
                'title' => 'Đơn hàng của tôi',
                'url' => '/account/orders',
                'sort_order' => 3,
            ],
        ];

        foreach ($items as $item) {
            HeaderMenuItem::query()->updateOrCreate(
                [
                    'header_menu_id' => $menu->id,
                    'title' => $item['title'],
                ],
                [
                    'parent_id' => null,
                    'url' => $item['url'],
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
