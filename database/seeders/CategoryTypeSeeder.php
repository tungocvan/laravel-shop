<?php

namespace Database\Seeders; 

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoryTypeSeeder extends Seeder
{
    public function run()
    {
        foreach ([
            [
                'type' => 'product',
                'title' => 'Danh mục Sản phẩm',
                'icon' => '🛍️'
            ],
            [
                'type' => 'post',
                'title' => 'Danh mục Bài viết',
                'icon' => '📝'
            ]
        ] as $type) {
            DB::table('category_types')->updateOrInsert(
                ['type' => $type['type']],
                $type + ['sort_order' => 0, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
