<?php

namespace Modules\Website\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Website\Models\HeaderMenu;
use Modules\Website\Models\HeaderMenuItem;
use Illuminate\Support\Str;

class HeaderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * php artisan db:seed --class="Modules\Website\database\Seeders\HeaderSeeder"
     */
    public function run(): void
    {
        // 1. Xóa sạch dữ liệu cũ để tránh trùng lặp
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        HeaderMenuItem::truncate();
        HeaderMenu::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. TẠO DỮ LIỆU CHO DESKTOP (PRIMARY)
        $primaryMenu = HeaderMenu::create([
            'name' => 'Desktop Main Menu',
            'location' => 'primary',
            'is_active' => true
        ]);
        $this->createDefaultItems($primaryMenu->id);

        // 3. TẠO DỮ LIỆU CHO MOBILE (MOBILE)
        $mobileMenu = HeaderMenu::create([
            'name' => 'Mobile Slide-over',
            'location' => 'mobile',
            'is_active' => true
        ]);
        $this->createDefaultItems($mobileMenu->id);

        // 4. TẠO DỮ LIỆU CHO ADMIN DROPDOWN (ADMIN)
        $adminMenu = HeaderMenu::create([
            'name' => 'Admin Menu Dropdown',
            'location' => 'admin',
            'is_active' => true
        ]);
        $this->createAdminItems($adminMenu->id);
    }

    /**
     * Tạo các mục menu mặc định cho Frontend (Desktop & Mobile)
     */
    private function createDefaultItems($menuId)
    {
        // Trang chủ
        HeaderMenuItem::create([
            'header_menu_id' => $menuId,
            'title' => 'Trang chủ',
            'url' => '/',
            'sort_order' => 1,
            'is_active' => true
        ]);

        // Cửa hàng
        HeaderMenuItem::create([
            'header_menu_id' => $menuId,
            'title' => 'Cửa hàng',
            'url' => '/shop',
            'sort_order' => 2,
            'is_active' => true
        ]);

        // Dropdown Sản phẩm (Cấp 1)
        $productParent = HeaderMenuItem::create([
            'header_menu_id' => $menuId,
            'title' => 'Sản phẩm',
            'url' => '#',
            'sort_order' => 3,
            'is_active' => true
        ]);

        // Các mục con (Cấp 2)
        $categories = ['Điện Thoại & Tablet', 'Laptop & Đồ Họa', 'Thời Trang Nam'];
        foreach ($categories as $index => $cat) {
            HeaderMenuItem::create([
                'header_menu_id' => $menuId,
                'parent_id' => $productParent->id,
                'title' => $cat,
                'url' => '/shop?category=' . Str::slug($cat),
                'sort_order' => $index,
                'is_active' => true
            ]);
        }

        // Blog
        HeaderMenuItem::create([
            'header_menu_id' => $menuId,
            'title' => 'Blog',
            'url' => '/blog',
            'sort_order' => 4,
            'is_active' => true
        ]);
    }

    /**
     * Tạo các mục riêng cho Admin User Dropdown
     */
    private function createAdminItems($menuId)
    {
        HeaderMenuItem::create([
            'header_menu_id' => $menuId,
            'title' => 'Hồ sơ cá nhân',
            'url' => '/admin/profile', // Thay bằng route thực tế nếu có, ví dụ: route('admin.profile')
            'sort_order' => 1,
            'is_active' => true
        ]);

        HeaderMenuItem::create([
            'header_menu_id' => $menuId,
            'title' => 'Cài đặt',
            'url' => '/admin/settings', // Thay bằng route thực tế nếu có
            'sort_order' => 2,
            'is_active' => true
        ]);
    }
}
