<?php

namespace Modules\Website\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Website\Models\WpProduct;
use Modules\Website\Models\Setting;
use Carbon\Carbon;

class FlashSaleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Cấu hình thời gian đếm ngược (Kết thúc sau 24h hoặc 48h tính từ lúc chạy seeder)
        // Lưu vào bảng wp_settings [cite: 52]
        $endTime = Carbon::now()->addHours(24); // Flash Sale diễn ra trong 24h tới

        Setting::setValue('flash_sale_end_time', $endTime->toDateTimeString(), 'marketing');

        $this->command->info("⏰ Đã cài đặt Flash Sale kết thúc lúc: " . $endTime->format('H:i d/m/Y'));

        // 2. Chọn 6 sản phẩm ngẫu nhiên để làm "Sản phẩm Flash Sale"
        // Điều kiện: Phải là sản phẩm đang active
        $products = WpProduct::where('is_active', true)
            ->inRandomOrder()
            ->take(6)
            ->get();

        if ($products->isEmpty()) {
            $this->command->error("❌ Không tìm thấy sản phẩm nào. Hãy chạy ProductSeeder trước!");
            return;
        }

        foreach ($products as $product) {
            // Logic giảm giá sốc (Giảm 30% - 60% so với giá gốc)
            // regular_price giữ nguyên hoặc set lại cho đẹp
            $originalPrice = $product->regular_price > 0 ? $product->regular_price : 1000000;

            // Random mức giảm từ 30% đến 60%
            $discountPercent = rand(30, 60);
            $salePrice = $originalPrice * ((100 - $discountPercent) / 100);

            // Làm tròn giá về hàng nghìn (VD: 156.789 -> 157.000)
            $salePrice = ceil($salePrice / 1000) * 1000;

            // Cập nhật Tags để đảm bảo nó có thể hiện ở các mục khác nếu cần
            $tags = $product->tags ?? [];
            if (!in_array('flash-sale', $tags)) {
                $tags[] = 'flash-sale';
            }

            $product->update([
                'regular_price' => $originalPrice,
                'sale_price' => $salePrice,
                'tags' => $tags // Cột JSON [cite: 21]
            ]);

            $this->command->info("⚡ Đã cập nhật sản phẩm: {$product->title} - Giảm {$discountPercent}%");
        }

        $this->command->info('✅ Đã khởi tạo dữ liệu Flash Sale thành công!');
    }
}
