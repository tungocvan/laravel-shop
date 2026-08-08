<?php

namespace Modules\Website\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Website\Models\Order;
use Modules\Website\Models\WpProduct;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('vi_VN');
        $products = WpProduct::all();
        $users = User::all();
        $affiliates = User::take(3)->get(); // Lấy 3 user làm đối tác mẫu

        for ($i = 0; $i < 30; $i++) {
            $subtotal = 0;
            $totalCommission = 0;
            $orderItems = [];
            
            $randomProducts = $products->random(rand(1, 3));
            foreach ($randomProducts as $product) {
                $qty = rand(1, 2);
                $price = $product->sale_price ?: $product->regular_price;
                $lineTotal = $price * $qty;
                
                // Logic hoa hồng theo từng sản phẩm
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
                    'commission_amount' => $commissionAmount, // Snapshot hoa hồng item
                ];
            }

            $order = Order::create([
                'order_code' => 'ORD-' . strtoupper(Str::random(6)),
                'user_id' => rand(0, 1) ? $users->random()->id : null,
                'affiliate_id' => rand(0, 1) ? $affiliates->random()->id : null,
                'customer_name' => $faker->name,
                'customer_phone' => $faker->phoneNumber,
                'customer_address' => $faker->address,
                'subtotal' => $subtotal,
                'total' => $subtotal + 30000,
                'commission_amount' => $totalCommission, // Tổng hoa hồng đơn hàng
                'commission_status' => $faker->randomElement(['pending', 'approved', 'rejected']),
                'status' => $faker->randomElement(['pending', 'processing', 'completed']),
                'payment_method' => 'cod',
                'created_at' => $faker->dateTimeBetween('-2 months', 'now'),
            ]);

            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }
        }
        $this->command->info('✅ OrderSeeder: Đã tạo 30 đơn hàng chi tiết.');
    }
}