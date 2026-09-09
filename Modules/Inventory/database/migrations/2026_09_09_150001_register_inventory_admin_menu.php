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

        $now = now();
        $parentSlug = 'inventory-operations';

        $parent = DB::table('admin_menus')->where('slug', $parentSlug)->first();

        if ($parent === null) {
            $parentId = DB::table('admin_menus')->insertGetId([
                'name' => 'Quản lý kho',
                'slug' => $parentSlug,
                'url' => '/admin/inventory',
                'icon' => 'archive-box',
                'can' => 'inventory.dashboard.view',
                'parent_id' => null,
                'sort_order' => 60,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);
        } else {
            $parentId = (int) $parent->id;
            DB::table('admin_menus')->where('id', $parentId)->update([
                'name' => 'Quản lý kho',
                'url' => '/admin/inventory',
                'icon' => 'archive-box',
                'can' => 'inventory.dashboard.view',
                'is_active' => true,
                'updated_at' => $now,
                'deleted_at' => null,
            ]);
        }

        $children = [
            ['slug' => 'inventory-warehouses', 'name' => 'Kho hàng', 'url' => '/admin/inventory/warehouses', 'can' => 'inventory.warehouse.view'],
            ['slug' => 'inventory-items', 'name' => 'Mặt hàng tồn kho', 'url' => '/admin/inventory/items', 'can' => 'inventory.item.view'],
            ['slug' => 'inventory-receipts', 'name' => 'Phiếu nhập kho', 'url' => '/admin/inventory/receipts', 'can' => 'inventory.receipt.view'],
            ['slug' => 'inventory-issues', 'name' => 'Phiếu xuất kho', 'url' => '/admin/inventory/issues', 'can' => 'inventory.issue.view'],
            ['slug' => 'inventory-transfers', 'name' => 'Điều chuyển kho', 'url' => '/admin/inventory/transfers', 'can' => 'inventory.transfer.view'],
            ['slug' => 'inventory-stocktakes', 'name' => 'Kiểm kê', 'url' => '/admin/inventory/stocktakes', 'can' => 'inventory.stocktake.view'],
            ['slug' => 'inventory-stock', 'name' => 'Tồn kho', 'url' => '/admin/inventory/stock', 'can' => 'inventory.stock.view'],
            ['slug' => 'inventory-lots', 'name' => 'Lô / HSD', 'url' => '/admin/inventory/lots', 'can' => 'inventory.stock.view'],
            ['slug' => 'inventory-movements', 'name' => 'Biến động kho', 'url' => '/admin/inventory/movements', 'can' => 'inventory.movement.view'],
        ];

        foreach ($children as $sort => $child) {
            $existing = DB::table('admin_menus')->where('slug', $child['slug'])->first();
            $payload = [
                'name' => $child['name'],
                'url' => $child['url'],
                'icon' => null,
                'can' => $child['can'],
                'parent_id' => $parentId,
                'sort_order' => $sort,
                'is_active' => true,
                'updated_at' => $now,
                'deleted_at' => null,
            ];

            if ($existing === null) {
                DB::table('admin_menus')->insert($payload + [
                    'slug' => $child['slug'],
                    'created_at' => $now,
                ]);
            } else {
                DB::table('admin_menus')->where('id', $existing->id)->update($payload);
            }
        }

        Cache::forget('admin.menus');
    }

    public function down(): void
    {
        if (! Schema::hasTable('admin_menus')) {
            return;
        }

        DB::table('admin_menus')->whereIn('slug', [
            'inventory-warehouses',
            'inventory-items',
            'inventory-receipts',
            'inventory-issues',
            'inventory-transfers',
            'inventory-stocktakes',
            'inventory-stock',
            'inventory-lots',
            'inventory-movements',
        ])->delete();

        DB::table('admin_menus')->where('slug', 'inventory-operations')->delete();
        Cache::forget('admin.menus');
    }
};
