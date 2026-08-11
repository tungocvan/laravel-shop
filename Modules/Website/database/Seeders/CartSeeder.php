<?php

namespace Modules\Website\database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Product\Models\Product;
use Modules\Website\Models\Cart;

class CartSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'demo@website.test')->firstOrFail();
        $cart = Cart::query()->create(['session_id' => 'website-demo-user', 'user_id' => $user->id]);

        foreach (Product::query()->where('is_active', true)->limit(3)->get() as $index => $product) {
            $quantity = $index + 1;
            $price = $product->sale_price ?: $product->regular_price;
            $cart->items()->create(['product_id' => $product->id, 'price' => $price, 'quantity' => $quantity, 'total' => $price * $quantity]);
        }
    }
}
