<?php

namespace Modules\Website\database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Order\Models\Order;
use Modules\Product\Models\Product;

class AffiliateSeeder extends Seeder
{
    public function run(): void
    {
        $affiliateUser = User::query()->where('email', 'tungocvan@gmail.com')->first();

        if (! $affiliateUser) {
            $this->command?->error('❌ Không tìm thấy user tungocvan@gmail.com. Vui lòng kiểm tra lại bảng users!');

            return;
        }

        $products = Product::query()->get()->values();
        if ($products->isEmpty()) {
            $this->command?->error('❌ Cần chạy ProductSeeder trước khi chạy AffiliateSeeder!');

            return;
        }

        $this->command?->info('👤 Đang tạo dữ liệu mẫu cho Đối tác: '.$affiliateUser->name);

        $commissionStatuses = ['pending', 'approved', 'rejected'];
        $rejectionReasons = ['Đơn hàng bị hoàn trả', 'Phát hiện gian lận click', 'Khách hàng hủy đơn'];
        $customerNames = ['Nguyễn Hải Nam', 'Trần Thu Trang', 'Phạm Minh Khang', 'Lê Ngọc Anh', 'Võ Thanh Tùng'];
        $customerAddresses = [
            '15 Nguyễn Huệ, Quận 1, TP.HCM',
            '72 Lê Duẩn, Hải Châu, Đà Nẵng',
            '30 Tràng Tiền, Hoàn Kiếm, Hà Nội',
            '101 Nguyễn Văn Cừ, Ninh Kiều, Cần Thơ',
            '48 Phan Đình Phùng, TP. Huế',
        ];

        for ($i = 1; $i <= 15; $i++) {
            $orderItemsData = [];
            $totalSubtotal = 0;
            $totalCommission = 0;
            $itemCount = 1 + (($i - 1) % min(3, $products->count()));

            for ($offset = 0; $offset < $itemCount; $offset++) {
                $product = $products[(($i - 1) + $offset) % $products->count()];
                $qty = 1 + (($i + $offset) % 2);
                $price = $product->sale_price ?: $product->regular_price;
                $lineTotal = $price * $qty;
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
                    'commission_amount' => $commissionAmount,
                ];
            }

            $commissionStatus = $commissionStatuses[($i - 1) % count($commissionStatuses)];

            $order = Order::query()->create([
                'user_id' => null,
                'affiliate_id' => $affiliateUser->id,
                'commission_status' => $commissionStatus,
                'commission_amount' => $totalCommission,
                'rejection_reason' => $commissionStatus === 'rejected'
                    ? $rejectionReasons[($i - 1) % count($rejectionReasons)]
                    : null,
                'order_code' => 'AFF-'.str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                'customer_name' => $customerNames[($i - 1) % count($customerNames)],
                'customer_phone' => '0912'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'customer_email' => 'affiliate-customer-'.$i.'@website.test',
                'customer_address' => $customerAddresses[($i - 1) % count($customerAddresses)],
                'subtotal' => $totalSubtotal,
                'total' => $totalSubtotal + 30000,
                'status' => $commissionStatus === 'approved' ? 'completed' : 'pending',
                'payment_method' => $i % 2 === 0 ? 'bank_transfer' : 'cod',
                'created_at' => now()->subDays(($i * 2) % 30),
                'updated_at' => now()->subDays(($i * 2) % 30),
            ]);

            foreach ($orderItemsData as $item) {
                $order->items()->create($item);
            }
        }

        $this->command?->info('✅ Đã tạo 15 đơn hàng đối soát mẫu thành công!');
    }
}
