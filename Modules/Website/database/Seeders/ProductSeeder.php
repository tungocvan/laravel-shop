<?php

namespace Modules\Website\database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categoryIds = Category::where('type', 'product')->pluck('id')->values()->all();

        $techImages = [
            'https://images.unsplash.com/photo-1517336714731-489689fd1ca8',
            'https://images.unsplash.com/photo-1505740420928-5e560c06d30e',
            'https://images.unsplash.com/photo-1523275335684-37898b6baf30',
        ];

        $productNames = [
            'Laptop Văn Phòng',
            'Tai Nghe Không Dây',
            'Đồng Hồ Thông Minh',
            'Điện Thoại Thông Minh',
            'Máy Tính Bảng',
            'Bàn Phím Cơ',
            'Chuột Không Dây',
            'Màn Hình Máy Tính',
            'Loa Bluetooth',
            'Ổ Cứng Di Động',
        ];

        $prices = [500000, 1200000, 4500000, 15000000, 250000];

        for ($i = 1; $i <= 40; $i++) {
            $name = $productNames[($i - 1) % count($productNames)].' '.$i;
            $price = $prices[($i - 1) % count($prices)];
            $image = $techImages[($i - 1) % count($techImages)];

            $product = Product::create([
                'title' => 'Sản phẩm '.$name,
                'slug' => Str::slug($name).'-'.$i,
                'short_description' => 'Sản phẩm demo '.$name.' phục vụ kiểm thử giao diện và chức năng Website.',
                'description' => '<p>Thông tin mô tả demo cho '.$name.'.</p>',
                'regular_price' => $price,
                'sale_price' => $i % 2 === 0 ? $price * 0.9 : null,
                'quantity' => ($i * 7) % 101,
                'affiliate_commission_rate' => 5 + ($i % 16),
                'image' => $image.'?w=600',
                'gallery' => [$techImages[0].'?w=600', $techImages[1].'?w=600'],
                'is_active' => true,
                'is_featured' => $i % 3 === 0,
                'sold_count' => 10 + ($i * 23),
                'views' => 500 + ($i * 211),
            ]);

            if ($categoryIds !== []) {
                $primaryCategory = $categoryIds[($i - 1) % count($categoryIds)];
                $attachIds = [$primaryCategory];

                if (count($categoryIds) > 1 && $i % 2 === 0) {
                    $secondaryCategory = $categoryIds[$i % count($categoryIds)];
                    if ($secondaryCategory !== $primaryCategory) {
                        $attachIds[] = $secondaryCategory;
                    }
                }

                $product->categories()->attach($attachIds);
            }
        }

        $this->command->info('✅ ProductSeeder: Đã tạo 40 sản phẩm.');
    }
}
