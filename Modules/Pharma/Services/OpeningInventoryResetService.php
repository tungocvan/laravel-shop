<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\InventoryBalance;
use Modules\Pharma\Models\InventoryIssue;
use Modules\Pharma\Models\InventoryReceipt;
use Modules\Pharma\Models\InventoryTransaction;

/**
 * Destructive opening reset: refuse if ANY Pharma receipt, issue, or
 * non-opening ledger entry exists, including drafts and reversals.
 * Never delete documents or unrelated stock.
 */
final class OpeningInventoryResetService
{
    public function eligibility(): array
    {
        $reasons = [];
        if (InventoryReceipt::query()->exists()) $reasons[] = 'Hệ thống đã có phiếu nhập kho (kể cả phiếu nháp).';
        if (InventoryIssue::query()->exists()) $reasons[] = 'Hệ thống đã có phiếu xuất kho (kể cả phiếu nháp).';
        if (InventoryTransaction::query()->where('type', '!=', 'opening')->exists()) $reasons[] = 'Sổ kho đã có phát sinh ngoài tồn đầu kỳ.';
        if (InventoryTransaction::query()->where('type', 'opening')->where(function ($q) {
            $q->whereNotNull('source_id')->orWhereNotNull('source_type');
        })->exists()) $reasons[] = 'Có bút toán mở sổ liên kết chứng từ.';

        $balances = InventoryBalance::query()->get();
        $openings = InventoryTransaction::query()->where('type', 'opening')->get();
        if ($balances->count() !== $openings->count()) {
            $reasons[] = 'Số lượng bản ghi tồn kho và bút toán mở sổ không khớp.';
        }
        foreach ($balances as $balance) {
            $matched = $openings->filter(fn ($entry) =>
                (int) $entry->warehouse_id === (int) $balance->warehouse_id
                && (int) $entry->medicine_id === (int) $balance->medicine_id
                && $entry->batch_number === $balance->batch_number
                && $entry->expiry_date?->toDateString() === $balance->expiry_date?->toDateString()
            );
            if ($matched->count() !== 1
                || (float) $balance->opening_quantity <= 0
                || abs((float) $balance->quantity_on_hand - (float) $balance->opening_quantity) > 0.0001
                || abs((float) $matched->first()?->quantity_delta - (float) $balance->opening_quantity) > 0.0001
                || abs((float) $matched->first()?->balance_after - (float) $balance->opening_quantity) > 0.0001) {
                $reasons[] = 'Phát hiện lô tồn kho không nhất quán với bút toán mở sổ.';
                break;
            }
        }
        return ['allowed' => $reasons === [] && $balances->isNotEmpty(), 'reasons' => $reasons,
            'lots' => $balances->count()];
    }

    public function reset(): int
    {
        return DB::transaction(function (): int {
            DB::table('pharma_inventory_warehouses')->orderBy('id')->lockForUpdate()->get();
            // Lock all rows before the final eligibility check and deletion.
            InventoryBalance::query()->orderBy('id')->lockForUpdate()->get();
            InventoryTransaction::query()->orderBy('id')->lockForUpdate()->get();
            $check = $this->eligibility();
            if (! $check['allowed']) {
                throw ValidationException::withMessages(['reset' => implode(' ', $check['reasons']) ?: 'Không có tồn đầu kỳ để xóa.']);
            }
            InventoryTransaction::query()->where('type', 'opening')->delete();
            InventoryBalance::query()->delete();
            return $check['lots'];
        }, 3);
    }
}
