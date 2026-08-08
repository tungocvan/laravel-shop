<?php

namespace Modules\Website\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Website\Models\AffiliateLevel;
use Modules\Website\Models\AffiliateScheme;
use Modules\Website\Models\WpProduct;
use App\Models\User;

// php artisan db:seed --class="Modules\Website\database\Seeders\AffiliateSchemeSeeder"
class AffiliateSchemeSeeder extends Seeder
{
    public function run()
    {
        $products = WpProduct::all();
        $levels = AffiliateLevel::all();
        $specialUser = User::where('email', 'tungocvan@gmail.com')->first();

        if ($products->isEmpty() || $levels->isEmpty()) {
            $this->command->error('❌ Thiếu dữ liệu Sản phẩm hoặc Cấp độ. Hãy chạy ProductSeeder và AffiliateLevelSeeder trước!');
            return;
        }

        $this->command->info('🚀 Đang đổ dữ liệu Ma trận hoa hồng Hybrid...');

        foreach ($products as $product) {
            // 1. Cấu hình theo Cấp bậc (Level-based)
            foreach ($levels as $level) {
                $type = $this->getRandomType();
                
                AffiliateScheme::create([
                    'product_id'      => $product->id,
                    'level_id'        => $level->id,
                    'user_id'         => null,
                    'commission_type' => $type,
                    'percent_value'   => ($type === 'fixed') ? 0 : $this->getPercentByLevel($level->slug),
                    'fixed_value'     => ($type === 'percentage') ? 0 : rand(1, 5) * 10000,
                    'is_active'       => true,
                ]);
            }

            // 2. Cấu hình Đặc biệt cho 1 User cụ thể (User-based)
            // Giả lập: Cứ 5 sản phẩm thì có 1 sản phẩm ưu đãi cực cao cho User này
            if ($specialUser && rand(1, 5) === 1) {
                AffiliateScheme::create([
                    'product_id'      => $product->id,
                    'level_id'        => null,
                    'user_id'         => $specialUser->id,
                    'commission_type' => 'hybrid',
                    'percent_value'   => 20.00, // Ưu đãi 20%
                    'fixed_value'     => 100000, // Thưởng thêm 100k tiền mặt
                    'is_active'       => true,
                ]);
            }
        }

        $this->command->info('✅ Đã hoàn thành Ma trận hoa hồng cho ' . $products->count() . ' sản phẩm.');
    }

    private function getRandomType() {
        return collect(['percentage', 'fixed', 'hybrid'])->random();
    }

    private function getPercentByLevel($slug) {
        return match($slug) {
            'bronze'  => 5.00,
            'silver'  => 8.00,
            'gold'    => 12.00,
            'diamond' => 15.00,
            default   => 10.00
        };
    }
}