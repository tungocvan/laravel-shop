<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class InventoryDashboardService
{
    public function snapshot(): array
    {
        $tables = [
            'warehouses' => 'inventory_warehouses',
            'items' => 'inventory_items',
            'receipts' => 'inventory_receipts',
            'stocktakes' => 'inventory_stocktakes',
            'balances' => 'inventory_balances',
            'lots' => 'inventory_lots',
            'movements' => 'inventory_movements',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                return $this->unavailable();
            }
        }

        $lowStock = DB::table('inventory_balances as b')
            ->join('inventory_items as i', 'i.id', '=', 'b.inventory_item_id')
            ->whereNotNull('i.reorder_level')
            ->whereColumn('b.quantity_on_hand', '<=', 'i.reorder_level')
            ->count();

        $expiringLots = DB::table('inventory_lots')
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now()->toDateString(), now()->addDays(90)->toDateString()])
            ->count();

        $pendingInvoiceInbox = Schema::hasTable('inventory_invoice_inbox')
            ? DB::table('inventory_invoice_inbox')->whereIn('processing_status', ['RECEIVED', 'MATCHING', 'REVIEW_REQUIRED', 'READY'])->count()
            : 0;

        return [
            'available' => true,
            'generated_at' => now(),
            'metrics' => [
                'warehouses' => DB::table('inventory_warehouses')->where('is_active', true)->count(),
                'items' => DB::table('inventory_items')->where('is_active', true)->count(),
                'draft_receipts' => DB::table('inventory_receipts')->where('status', 'DRAFT')->count(),
                'invoice_inbox_pending' => $pendingInvoiceInbox,
                'low_stock' => $lowStock,
                'expiring_lots' => $expiringLots,
                'pending_stocktakes' => DB::table('inventory_stocktakes')->whereIn('status', ['DRAFT', 'COUNTED'])->count(),
                'movements_today' => DB::table('inventory_movements')->whereDate('occurred_at', today())->count(),
            ],
            'recent_movements' => DB::table('inventory_movements as m')
                ->leftJoin('inventory_items as i', 'i.id', '=', 'm.inventory_item_id')
                ->leftJoin('inventory_warehouses as w', 'w.id', '=', 'm.warehouse_id')
                ->select(['m.id', 'm.movement_type', 'm.quantity_delta', 'm.base_uom', 'm.occurred_at', 'i.sku', 'i.display_name', 'w.name as warehouse_name'])
                ->latest('m.occurred_at')
                ->limit(8)
                ->get(),
            'warnings' => $this->warnings($lowStock, $expiringLots),
        ];
    }

    private function warnings(int $lowStock, int $expiringLots): array
    {
        $warnings = [];
        if ($lowStock > 0) {
            $warnings[] = ['level' => 'warning', 'message' => $lowStock.' mặt hàng đang ở hoặc dưới mức tồn tối thiểu.'];
        }
        if ($expiringLots > 0) {
            $warnings[] = ['level' => 'warning', 'message' => $expiringLots.' lô sẽ hết hạn trong 90 ngày tới.'];
        }

        return $warnings;
    }

    private function unavailable(): array
    {
        return [
            'available' => false,
            'generated_at' => now(),
            'metrics' => array_fill_keys(['warehouses', 'items', 'draft_receipts', 'invoice_inbox_pending', 'low_stock', 'expiring_lots', 'pending_stocktakes', 'movements_today'], 0),
            'recent_movements' => collect(),
            'warnings' => [['level' => 'danger', 'message' => 'Schema Inventory chưa sẵn sàng. Hãy chạy migration trước khi vận hành Dashboard.']],
        ];
    }
}
