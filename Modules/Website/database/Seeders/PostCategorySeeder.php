<?php

namespace Modules\Website\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Website\Models\Category;
use Illuminate\Support\Facades\DB;

class PostCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Tắt check khóa ngoại để xóa sạch dữ liệu cũ
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Category::where('type', 'post')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $categories = [
            ['name' => 'Technology', 'slug' => 'technology', 'image' => 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=400&q=80'],
            ['name' => 'Lifestyle', 'slug' => 'lifestyle', 'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=400&q=80'],
            ['name' => 'Design & UI', 'slug' => 'design-ui', 'image' => 'https://images.unsplash.com/photo-1561070791-2526d30994b5?auto=format&fit=crop&w=400&q=80'],
            ['name' => 'Business', 'slug' => 'business', 'image' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=400&q=80'],
        ];

        foreach ($categories as $cat) {
            Category::create([
                'name' => $cat['name'],
                'slug' => $cat['slug'],
                'image' => $cat['image'],
                'type' => 'post',
                'is_active' => true,
            ]);
        }

        $this->command->info('✅ PostCategorySeeder: Đã tạo danh mục Blog.');
    }
}
