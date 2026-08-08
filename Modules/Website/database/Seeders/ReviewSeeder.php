<?php

namespace Modules\Website\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Website\Models\Review;
use Modules\Website\Models\WpProduct;

class ReviewSeeder extends Seeder
{
    public function run()
    {
        $products = WpProduct::all();
        $realComments = [
            5 => ['Tuyệt vời, shop phục vụ rất tốt!', 'Giao hàng nhanh như chớp, sản phẩm xịn.', 'Rất đáng đồng tiền bát gạo.', 'Đã mua lần 2 và vẫn rất ưng ý.'],
            4 => ['Sản phẩm tốt nhưng đóng gói hơi nhăn.', 'Chất lượng ổn, phù hợp túi tiền.', 'Dùng khá mượt, sẽ giới thiệu bạn bè.'],
            3 => ['Hàng tạm ổn, giao hơi chậm chút.', 'Màu sắc hơi khác so với ảnh nhưng vẫn dùng được.'],
        ];

        foreach ($products as $product) {
            for ($i = 0; $i < rand(2, 5); $i++) {
                $rating = rand(3, 5);
                Review::create([
                    'product_id' => $product->id,
                    'user_id' => 1,
                    'rating' => $rating,
                    'comment' => collect($realComments[$rating])->random(),
                    'is_approved' => true,
                    'created_at' => now()->subDays(rand(1, 60)),
                ]);
            }
        }
        $this->command->info('✅ ReviewSeeder: Đã tạo đánh giá khách hàng thực tế.');
    }
}