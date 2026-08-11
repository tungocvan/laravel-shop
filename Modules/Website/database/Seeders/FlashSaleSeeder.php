<?php

namespace Modules\Website\database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Product\Models\Product;
use Modules\Website\Models\FlashSale;

class FlashSaleSeeder extends Seeder
{
    public function run(): void
    {
        $sale = FlashSale::query()->create([
            'title' => 'Flash Sale Demo 48H',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHours(48),
            'is_active' => true,
        ]);

        foreach (Product::query()->where('is_active', true)->limit(8)->get() as $index => $product) {
            $price = round(((float) $product->regular_price * (0.55 + ($index % 4) * 0.05)) / 1000) * 1000;
            $sale->items()->create(['product_id' => $product->id, 'price' => $price, 'quantity' => 50 + $index * 10, 'sold' => 5 + $index * 3]);
        }
    }
}
