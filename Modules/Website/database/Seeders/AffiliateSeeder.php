<?php

namespace Modules\Website\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Website\Models\Order;
use Modules\Website\Models\WpProduct;
use App\Models\User;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class AffiliateSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('vi_VN');

        // 1. Xác định User làm đối tác (Affiliate)
        $affiliateUser = User::where('email', 'tungocvan@gmail.com')->first();
        
        if (!$affiliateUser) {
            $this->command->error('❌ Không tìm thấy user tungocvan@gmail.com. Vui lòng kiểm tra lại bảng users!');
            return;
        }

        // 2. Lấy danh sách sản phẩm để tạo đơn hàng giả lập
        $products = WpProduct::all();
        if ($products->isEmpty()) {
            $this->command->error('❌ Cần chạy ProductSeeder trước khi chạy AffiliateSeeder!');
            return;
        }

        $this->command->info('👤 Đang tạo dữ liệu mẫu cho Đối tác: ' . $affiliateUser->name);

        // 3. Tạo 15 đơn hàng với các kịch bản khác nhau
        $statuses = ['pending', 'approved', 'rejected'];

        for ($i = 1; $i <= 15; $i++) {
            $orderItemsData = [];
            $totalSubtotal = 0;
            $totalCommission = 0;

            // Mỗi đơn hàng giả lập có từ 1 đến 3 sản phẩm khác nhau
            $randomProducts = $products->random(rand(1, 3));

            foreach ($randomProducts as $product) {
                $qty = rand(1, 2);
                $price = $product->sale_price ?: $product->regular_price;
                $lineTotal = $price * $qty;

                // Lấy tỷ lệ % hoa hồng đã cấu hình tại Giai đoạn 1 & 3
                // Nếu sản phẩm không có % riêng, mặc định dùng 10%
                $rate = $product->affiliate_commission_rate ?: 10;
                $commissionAmount = ($lineTotal * $rate) / 100;

                $totalSubtotal += $lineTotal;
                $totalCommission += $commissionAmount;

                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->title,
                    'price' => $price,
                    'quantity' => $qty,
                    'total' => $lineTotal,
                    'commission_rate' => $rate,
                    'commission_amount' => $commissionAmount, // Snapshot quan trọng!
                ];
            }

            $commissionStatus = $faker->randomElement($statuses);
            
            // 4. Tạo Order
            $order = Order::create([
                'user_id'           => null, // Khách mua là người lạ
                'affiliate_id'      => $affiliateUser->id,
                'commission_status' => $commissionStatus,
                'commission_amount' => $totalCommission,
                'rejection_reason'  => ($commissionStatus === 'rejected') ? $faker->randomElement(['Đơn hàng bị hoàn trả', 'Phát hiện gian lận click', 'Khách hàng hủy đơn']) : null,
                
                'order_code'        => 'AFF-' . strtoupper(Str::random(8)),
                'customer_name'     => $faker->name,
                'customer_phone'    => $faker->phoneNumber,
                'customer_email'    => $faker->safeEmail,
                'customer_address'  => $faker->address,
                
                'subtotal'          => $totalSubtotal,
                'total'             => $totalSubtotal + 30000, // Cộng phí ship giả định
                'status'            => ($commissionStatus === 'approved') ? 'completed' : 'pending',
                'payment_method'    => $faker->randomElement(['cod', 'bank_transfer']),
                'created_at'        => $faker->dateTimeBetween('-1 month', 'now'),
            ]);

            // 5. Lưu chi tiết Order Items (Quan trọng để test Modal chi tiết)
            foreach ($orderItemsData as $item) {
                $order->items()->create($item);
            }
        }

        $this->command->info('✅ Đã tạo 15 đơn hàng đối soát mẫu thành công!');
    }
}