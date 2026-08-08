<?php

namespace Modules\Website\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Website\Models\WpProduct;
use Modules\Website\Models\Category;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('vi_VN');
        $categoryIds = Category::where('type', 'product')->pluck('id')->toArray();
        
        $techImages = [
            'https://images.unsplash.com/photo-1517336714731-489689fd1ca8',
            'https://images.unsplash.com/photo-1505740420928-5e560c06d30e',
            'https://images.unsplash.com/photo-1523275335684-37898b6baf30'
        ];

        for ($i = 1; $i <= 40; $i++) {
            $price = $faker->randomElement([500000, 1200000, 4500000, 15000000, 250000]);
            
            WpProduct::create([
                'title' => 'Sản phẩm ' . $faker->words(3, true),
                'slug' => Str::slug($faker->words(3, true)) . '-' . Str::random(5),
                'short_description' => $faker->paragraph(2),
                'description' => $faker->randomHtml(2, 3),
                'regular_price' => $price,
                'sale_price' => rand(0, 1) ? $price * 0.9 : null,
                'quantity' => rand(0, 100),
                'affiliate_commission_rate' => rand(5, 20), // % Hoa hồng từ 5-20%
                'image' => $faker->randomElement($techImages) . '?w=600',
                'gallery' => [$techImages[0].'?w=600', $techImages[1].'?w=600'],
                'is_active' => true,
                'is_featured' => $faker->boolean(30),
                'sold_count' => rand(10, 1000),
                'views' => rand(500, 10000),
            ])->categories()->attach($faker->randomElements($categoryIds, rand(1, 2)));
        }
        $this->command->info('✅ ProductSeeder: Đã tạo 40 sản phẩm.');
    }
}