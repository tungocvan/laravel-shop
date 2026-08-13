<?php

namespace Modules\Website\database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Order\Models\Order;
use Modules\Product\Models\Product;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::query()->get()->values();
        $users = User::query()->get()->values();
        $affiliates = User::query()->take(3)->get()->values();

        if ($products->isEmpty()) {
            $this->command?->error('❌ Cần chạy ProductSeeder trước khi chạy OrderSeeder!');

            return;
        }

        $customerNames = ['Nguyễn Minh Anh', 'Trần Hoàng Nam', 'Lê Thu Hà', 'Phạm Quốc Bảo', 'Võ Ngọc Lan'];
        $customerAddresses = [
            '12 Nguyễn Huệ, Quận 1, TP.HCM',
            '45 Lê Lợi, Quận 1, TP.HCM',
            '88 Trần Phú, Hải Châu, Đà Nẵng',
            '21 Hai Bà Trưng, Hoàn Kiếm, Hà Nội',
            '67 Nguyễn Văn Linh, Ninh Kiều, Cần Thơ',
        ];
        $commissionStatuses = ['pending', 'approved', 'rejected'];
        $orderStatuses = ['pending', 'processing', 'completed'];

        for ($i = 1; $i <= 30; $i++) {
            $subtotal = 0;
            $totalCommission = 0;
            $orderItems = [];
            $itemCount = 1 + (($i - 1) % min(3, $products->count()));

            for ($offset = 0; $offset < $itemCount; $offset++) {
                $product = $products[(($i - 1) + $offset) % $products->count()];
                $qty = 1 + (($i + $offset) % 2);
                $price = $product->sale_price ?: $product->regular_price;
                $lineTotal = $price * $qty;
                $rate = $product->affiliate_commission_rate ?: 10;
                $commissionAmount = ($lineTotal * $rate) / 100;

                $subtotal += $lineTotal;
                $totalCommission += $commissionAmount;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->title,
                    'price' => $price,
                    'quantity' => $qty,
                    'total' => $lineTotal,
                    'commission_rate' => $rate,
                    'commission_amount' => $commissionAmount,
                ];
            }

            $userId = $users->isNotEmpty() && $i % 2 === 0
                ? $users[($i - 1) % $users->count()]->id
                : null;
            $affiliateId = $affiliates->isNotEmpty() && $i % 3 === 0
                ? $affiliates[($i - 1) % $affiliates->count()]->id
                : null;

            $order = Order::query()->create([
                'order_code' => 'ORD-'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'user_id' => $userId,
                'affiliate_id' => $affiliateId,
                'customer_name' => $customerNames[($i - 1) % count($customerNames)],
                'customer_phone' => '0901'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'customer_address' => $customerAddresses[($i - 1) % count($customerAddresses)],
                'subtotal' => $subtotal,
                'total' => $subtotal + 30000,
                'commission_amount' => $totalCommission,
                'commission_status' => $commissionStatuses[($i - 1) % count($commissionStatuses)],
                'status' => $orderStatuses[($i - 1) % count($orderStatuses)],
                'payment_method' => 'cod',
                'created_at' => now()->subDays(($i * 2) % 60),
                'updated_at' => now()->subDays(($i * 2) % 60),
            ]);

            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }
        }

        $this->command?->info('✅ OrderSeeder: Đã tạo 30 đơn hàng chi tiết.');
    }
}
