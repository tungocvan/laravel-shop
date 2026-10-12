<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('category_types')) {
            return;
        }

        DB::table('category_types')->updateOrInsert(
            ['type' => 'medicine'],
            [
                'title' => 'Danh mục Thuốc',
                'icon' => 'pill',
                'sort_order' => 2,
                'is_active' => true,
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        if (Schema::hasTable('category_types') && Schema::hasTable('categories')
            && ! DB::table('categories')->where('type', 'medicine')->exists()) {
            DB::table('category_types')->where('type', 'medicine')->delete();
        }
    }
};
