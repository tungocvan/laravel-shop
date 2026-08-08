<?php

namespace Modules\Website\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
// Lưu ý: Đảm bảo Model Coupon của bạn nằm đúng namespace này
use Modules\Website\Models\Coupon; 
// php artisan db:seed --class="Modules\Website\database\Seeders\CouponSeeder"
class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coupons = [
            [
                "code" => "WELCOME2024",
                "description" => "Giảm giá chào mừng thành viên mới",
                "type" => "percent",
                "value" => 10,
                "min_order_value" => 0,
                "usage_limit" => 100,
                "starts_at" => "2024-01-01 00:00:00",
                "expires_at" => "2024-12-31 23:59:59",
                "is_active" => true
            ],
            [
                "code" => "FREESHIP50K",
                "description" => "Miễn phí vận chuyển cho đơn từ 300k",
                "type" => "fixed",
                "value" => 50000,
                "min_order_value" => 300000,
                "usage_limit" => 500,
                "starts_at" => "2024-06-01 00:00:00",
                "expires_at" => null,
                "is_active" => true
            ],
            [
                "code" => "SUMMER_SALE",
                "description" => "Siêu sale mùa hè giảm cực sâu",
                "type" => "percent",
                "value" => 25,
                "min_order_value" => 500000,
                "usage_limit" => 50,
                "starts_at" => "2024-06-01 00:00:00",
                "expires_at" => "2024-08-31 23:59:59",
                "is_active" => true
            ],
            [
                "code" => "TET2025",
                "description" => "Lì xì Tết Nguyên Đán",
                "type" => "fixed",
                "value" => 100000,
                "min_order_value" => 1000000,
                "usage_limit" => 1000,
                "starts_at" => "2025-01-01 00:00:00",
                "expires_at" => "2025-02-15 23:59:59",
                "is_active" => false
            ],
            [
                "code" => "FLASH_HOUR",
                "description" => "Giảm giá giờ vàng (chỉ 10 lượt)",
                "type" => "percent",
                "value" => 50,
                "min_order_value" => 0,
                "usage_limit" => 10,
                "starts_at" => null,
                "expires_at" => null,
                "is_active" => true
            ]
        ];

        foreach ($coupons as $coupon) {
            // Sử dụng updateOrCreate dựa trên 'code' để bảo toàn dữ liệu khi chạy lại seeder
            Coupon::updateOrCreate(
                ['code' => $coupon['code']],
                [
                    'description'     => $coupon['description'],
                    'type'            => $coupon['type'],
                    'value'           => $coupon['value'],
                    'min_order_value' => $coupon['min_order_value'],
                    'usage_limit'     => $coupon['usage_limit'],
                    'starts_at'       => $coupon['starts_at'],
                    'expires_at'      => $coupon['expires_at'],
                    'is_active'       => $coupon['is_active'],
                ]
            );
        }
    }
}