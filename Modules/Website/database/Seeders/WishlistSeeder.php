<?php

namespace Modules\Website\database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Product;

class WishlistSeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::query()->where('email', 'demo@website.test')->value('id');
        foreach (Product::query()->where('is_active', true)->limit(6)->pluck('id') as $productId) {
            DB::table('wishlists')->insert(['user_id' => $userId, 'product_id' => $productId, 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
