<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\InventoryBalance;
use Modules\Pharma\Models\InventoryTransaction;

final class OpeningLotEditor
{
    /**
     * Only a pristine opening entry may be corrected. Lock the warehouse to serialize
     * opening writes and reject any other ledger movement, even a reversed movement.
     */
    public function update(int $warehouseId, int $balanceId, array $data, ?int $actorId): void
    {
        DB::transaction(function () use ($warehouseId, $balanceId, $data, $actorId): void {
            DB::table('pharma_inventory_warehouses')->where('id', $warehouseId)->lockForUpdate()->first();
            $balance = InventoryBalance::query()->where('warehouse_id', $warehouseId)
                ->lockForUpdate()->findOrFail($balanceId);
            $transactions = InventoryTransaction::query()
                ->where('warehouse_id', $warehouseId)
                ->where('medicine_id', $balance->medicine_id)
                ->where('batch_number', $balance->batch_number)
                ->whereDate('expiry_date', $balance->expiry_date)
                ->lockForUpdate()->get();
            if ($transactions->count() !== 1 || $transactions->first()->type !== 'opening'
                || (float) $balance->opening_quantity <= 0
                || abs((float) $balance->quantity_on_hand - (float) $balance->opening_quantity) > 0.0001
                || abs((float) $transactions->first()->quantity_delta - (float) $balance->opening_quantity) > 0.0001) {
                throw ValidationException::withMessages(['batch_number' => 'Lô đã có biến động hoặc sổ kho không nhất quán, không thể chỉnh sửa tồn đầu kỳ.']);
            }

            $conflict = InventoryBalance::query()->where('warehouse_id', $warehouseId)
                ->where('medicine_id', $balance->medicine_id)->where('batch_number', $data['batch_number'])
                ->whereDate('expiry_date', $data['expiry_date'])->whereKeyNot($balance->id)->exists();
            $otherLedger = InventoryTransaction::query()->where('warehouse_id', $warehouseId)
                ->where('medicine_id', $balance->medicine_id)->where('batch_number', $data['batch_number'])
                ->whereDate('expiry_date', $data['expiry_date'])->whereKeyNot($transactions->first()->id)->exists();
            if ($conflict || $otherLedger) {
                throw ValidationException::withMessages(['batch_number' => 'Số lô và hạn dùng đã tồn tại trong sổ kho.']);
            }

            $quantity = round((float) $data['opening_quantity'], 3);
            $balance->update([
                'batch_number' => $data['batch_number'],
                'expiry_date' => $data['expiry_date'],
                'opening_quantity' => $quantity,
                'quantity_on_hand' => $quantity,
                'manual_cost_price' => $data['manual_cost_price'] ?? null,
            ]);
            $transactions->first()->update([
                'batch_number' => $data['batch_number'],
                'expiry_date' => $data['expiry_date'],
                'quantity_delta' => $quantity,
                'balance_after' => $quantity,
                'notes' => 'Hiệu chỉnh tồn đầu kỳ bởi user #'.($actorId ?? 'unknown'),
            ]);
        });
    }
}
