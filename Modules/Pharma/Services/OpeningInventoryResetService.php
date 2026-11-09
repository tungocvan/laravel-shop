<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\InventoryBalance;
use Modules\Pharma\Models\InventoryIssue;
use Modules\Pharma\Models\InventoryReceipt;
use Modules\Pharma\Models\InventoryTransaction;
use Modules\Pharma\Models\InventoryWarehouse;

final class OpeningInventoryResetService
{
    /**
     * This operation is intentionally ALL-Pharma. Never assume that a zero
     * global movement total proves that each document and lot was reversed.
     */
    public function eligibility(): array
    {
        $reasons = [];
        if (InventoryReceipt::query()->exists()) {
            $reasons[] = 'Hệ thống đã có phiếu nhập kho (kể cả phiếu nháp).';
        }
        if (InventoryIssue::query()->exists()) {
            $reasons[] = 'Hệ thống đã có phiếu xuất kho (kể cả phiếu nháp).';
        }

        $balances = InventoryBalance::query()->get();
        $transactions = InventoryTransaction::query()->get();
        $openings = $transactions->where('type', 'opening');
        $movements = $transactions->where('type', '!=', 'opening');

        $allowedTypes = ['receipt', 'receipt_reversal', 'issue', 'issue_reversal'];
        if ($movements->contains(fn ($tx) => ! in_array($tx->type, $allowedTypes, true))) {
            $reasons[] = 'Sổ kho chứa loại giao dịch không hỗ trợ reset.';
        }
        if ($transactions->contains(fn ($tx) => $tx->type === 'opening'
            && ($tx->source_type !== null || $tx->source_id !== null))) {
            $reasons[] = 'Có bút toán mở sổ liên kết chứng từ.';
        }
        if ($movements->contains(fn ($tx) =>
            ($tx->type === 'receipt' || $tx->type === 'receipt_reversal')
                ? $tx->source_type !== InventoryReceipt::class
                : $tx->source_type !== InventoryIssue::class
        )) {
            $reasons[] = 'Giao dịch kho có loại chứng từ nguồn không hợp lệ.';
        }
        if ($movements->contains(fn ($tx) => ! $tx->source_type || ! $tx->source_id)) {
            $reasons[] = 'Có giao dịch kho không xác định được chứng từ nguồn.';
        }

        // A reversal must neutralize its own document, warehouse and lot.
        // An unrelated transaction cannot compensate for a missing reversal.
        $groups = $movements->groupBy(fn ($tx) => implode('|', [
            $tx->source_type, $tx->source_id, $tx->warehouse_id,
            $tx->medicine_id, $tx->batch_number,
            $tx->expiry_date?->toDateString(),
        ]));
        foreach ($groups as $group) {
            $types = $group->pluck('type')->unique();
            $isReceipt = $types->every(fn ($type) => in_array($type, ['receipt', 'receipt_reversal'], true));
            $isIssue = $types->every(fn ($type) => in_array($type, ['issue', 'issue_reversal'], true));
            $originalType = $isReceipt ? 'receipt' : ($isIssue ? 'issue' : null);
            $reversalType = $isReceipt ? 'receipt_reversal' : ($isIssue ? 'issue_reversal' : null);
            $original = $group->where('type', $originalType);
            $reversed = $group->where('type', $reversalType);
            if (! $originalType || $original->isEmpty() || $reversed->isEmpty()
                || abs((float) $group->sum('quantity_delta')) > 0.0001
                || ($isReceipt && ($original->contains(fn ($tx) => (float) $tx->quantity_delta <= 0)
                    || $reversed->contains(fn ($tx) => (float) $tx->quantity_delta >= 0)))
                || ($isIssue && ($original->contains(fn ($tx) => (float) $tx->quantity_delta >= 0)
                    || $reversed->contains(fn ($tx) => (float) $tx->quantity_delta <= 0)))) {
                $reasons[] = 'Có nghiệp vụ nhập/xuất chưa hoàn tác đầy đủ theo chứng từ và lô.';
                break;
            }
        }

        if ($balances->count() !== $openings->count()) {
            $reasons[] = 'Số lượng bản ghi tồn kho và bút toán mở sổ không khớp.';
        }
        foreach ($openings as $opening) {
            if (! $balances->contains(fn ($balance) =>
                (int) $balance->warehouse_id === (int) $opening->warehouse_id
                && (int) $balance->medicine_id === (int) $opening->medicine_id
                && $balance->batch_number === $opening->batch_number
                && $balance->expiry_date?->toDateString() === $opening->expiry_date?->toDateString()
            )) {
                $reasons[] = 'Có bút toán mở sổ không có lô tồn tương ứng.';
                break;
            }
        }
        foreach ($balances as $balance) {
            $matching = $openings->filter(fn ($tx) =>
                (int) $tx->warehouse_id === (int) $balance->warehouse_id
                && (int) $tx->medicine_id === (int) $balance->medicine_id
                && $tx->batch_number === $balance->batch_number
                && $tx->expiry_date?->toDateString() === $balance->expiry_date?->toDateString()
            );
            $opening = $matching->first();
            if ($matching->count() !== 1 || (float) $balance->opening_quantity <= 0
                || abs((float) $balance->quantity_on_hand - (float) $balance->opening_quantity) > 0.0001
                || abs((float) $opening?->quantity_delta - (float) $balance->opening_quantity) > 0.0001
                || abs((float) $opening?->balance_after - (float) $balance->opening_quantity) > 0.0001) {
                $reasons[] = 'Phát hiện lô tồn kho không nhất quán với bút toán mở sổ.';
                break;
            }
        }

        return [
            'allowed' => $reasons === [] && $balances->isNotEmpty(),
            'reasons' => $reasons,
            'lots' => $balances->count(),
            'reversed_movements' => $movements->count(),
        ];
    }

    public function reset(): int
    {
        return DB::transaction(function (): int {
            DB::table('pharma_inventory_warehouses')->orderBy('id')->lockForUpdate()->get();
            DB::table('pharma_inventory_receipts')->orderBy('id')->lockForUpdate()->get();
            DB::table('pharma_inventory_issues')->orderBy('id')->lockForUpdate()->get();
            InventoryBalance::query()->orderBy('id')->lockForUpdate()->get();
            InventoryTransaction::query()->orderBy('id')->lockForUpdate()->get();

            $check = $this->eligibility();
            if (! $check['allowed']) {
                throw ValidationException::withMessages([
                    'reset' => implode(' ', $check['reasons']) ?: 'Không có tồn đầu kỳ để xóa.',
                ]);
            }

            // The existing confirmation flow requires an exported backup.
            // Audit the exact scope before removing reversed ledger history.
            Log::warning('Pharma opening reset deleting fully reversed inventory history', [
                'lots' => $check['lots'],
                'reversed_movements' => $check['reversed_movements'],
                'transaction_ids' => InventoryTransaction::query()->orderBy('id')->pluck('id')->all(),
            ]);

            InventoryTransaction::query()->delete();
            InventoryBalance::query()->delete();
            // A fresh Excel import starts a new opening period. The previous
            // cutoff must not survive a successful complete ledger reset.
            InventoryWarehouse::query()->whereNotNull('opening_cutoff_at')
                ->update(['opening_cutoff_at' => null]);
            return $check['lots'];
        }, 3);
    }
}
