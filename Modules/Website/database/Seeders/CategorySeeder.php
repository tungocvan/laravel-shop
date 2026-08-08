<?php

namespace Modules\Website\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Website\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Category::where('type', 'product')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $categories = [
            ['name' => 'Điện Thoại & Tablet', 'image' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=500'],
            ['name' => 'Laptop & Đồ Họa', 'image' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=500'],
            ['name' => 'Thời Trang Nam', 'image' => 'https://images.unsplash.com/photo-1490114538077-0a7f8cb49891?w=500'],
            ['name' => 'Thời Trang Nữ', 'image' => 'https://images.unsplash.com/photo-1567401893414-76b7b1e5a7a5?w=500'],
            ['name' => 'Giày Sneaker', 'image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=500'],
            ['name' => 'Đồng Hồ Cao Cấp', 'image' => 'https://images.unsplash.com/photo-1524592094714-0f0654e20314?w=500'],
            ['name' => 'Nội Thất Phòng Khách', 'image' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=500'],
            ['name' => 'Mỹ Phẩm & Làm Đẹp', 'image' => 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=500'],
        ];

        foreach ($categories as $index => $cat) {
            Category::create([
                'name' => $cat['name'],
                'slug' => Str::slug($cat['name']),
                'type' => 'product',
                'image' => $cat['image'],
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }
        $this->command->info('✅ CategorySeeder: Đã tạo 8 danh mục thực tế.');
    }
}