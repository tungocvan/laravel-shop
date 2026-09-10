<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_menus')) {
            return;
        }

        $parent = DB::table('admin_menus')->where('slug', 'inventory-operations')->first();
        if ($parent === null) {
            return;
        }

        $now = now();
        $existing = DB::table('admin_menus')->where('slug', 'inventory-invoice-inbox')->first();
        $payload = [
            'name' => 'Hóa đơn chờ nhập kho',
            'url' => '/admin/inventory/invoice-inbox',
            'icon' => null,
            'can' => 'inventory.receipt.view',
            'parent_id' => $parent->id,
            'sort_order' => 2,
            'is_active' => true,
            'updated_at' => $now,
            'deleted_at' => null,
        ];

        if ($existing === null) {
            DB::table('admin_menus')->insert($payload + [
                'slug' => 'inventory-invoice-inbox',
                'created_at' => $now,
            ]);
        } else {
            DB::table('admin_menus')->where('id', $existing->id)->update($payload);
        }

        Cache::forget('admin.menus');
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin_menus')) {
            return;
        }

        DB::table('admin_menus')->where('slug', 'inventory-invoice-inbox')->delete();
        Cache::forget('admin.menus');
    }
};
