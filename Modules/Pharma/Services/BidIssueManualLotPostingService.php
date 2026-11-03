<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\InventoryBalance;
use Modules\Pharma\Models\InventoryIssue;

/**
 * Canonical manual-lot posting for approved bid orders.
 *
 * All stock and document mutations remain in the Pharma domain. The ClientPortal
 * adapter must authorize its actor before invoking this service.
 */
final class BidIssueManualLotPostingService
{
    public function availableLots(InventoryIssue $issue): \Illuminate\Support\Collection
    {
        return InventoryBalance::query()
            ->where('warehouse_id', $issue->warehouse_id)
            ->whereIn('medicine_id', $issue->items()->pluck('medicine_id'))
            ->where('quantity_on_hand', '>', 0)
            ->whereDate('expiry_date', '>=', now()->toDateString())
            ->orderBy('medicine_id')
            ->orderBy('expiry_date')
            ->orderBy('batch_number')
            ->get()
            ->groupBy('medicine_id');
    }

    public function post(InventoryIssue $issue, array $batches, int $actorUserId, InventoryService $inventory): void
    {
        DB::transaction(function () use ($issue, $batches, $actorUserId, $inventory): void {
            $locked = InventoryIssue::query()->lockForUpdate()->findOrFail($issue->getKey());
            if ($locked->status !== InventoryIssue::APPROVED || $locked->posted_at !== null
                || $locked->issue_source !== 'bid') {
                throw ValidationException::withMessages(['status' => 'Chỉ đơn hàng thầu đã duyệt và chưa ghi sổ mới được chọn lô.']);
            }

            $locked->load(['items', 'deferredSupplies']);
            $deferredIds = $locked->deferredSupplies->pluck('drug_bid_award_allocation_id')->map(fn ($id) => (int) $id);
            $postable = $locked->items->reject(fn ($item) => $deferredIds->contains((int) $item->drug_bid_award_allocation_id));
            if ($postable->isEmpty()) {
                throw ValidationException::withMessages(['batches' => 'Không có sản phẩm đủ điều kiện thực xuất.']);
            }

            // Existing InventoryService::postIssue excludes deferred by medicine.
            // Refuse ambiguous mixed allocations rather than silently dropping stock.
            $deferredMedicines = $locked->items
                ->filter(fn ($item) => $deferredIds->contains((int) $item->drug_bid_award_allocation_id))
                ->pluck('medicine_id');
            if ($postable->pluck('medicine_id')->intersect($deferredMedicines)->isNotEmpty()) {
                throw ValidationException::withMessages(['batches' => 'Một thuốc vừa có dòng chờ cung ứng vừa có dòng thực xuất. Cần tách đơn trước khi ghi sổ.']);
            }

            $newRows = [];
            $lotTotals = [];
            foreach ($postable as $item) {
                $parts = $batches[$item->id] ?? [];
                if (! is_array($parts)) {
                    throw ValidationException::withMessages(['batches' => 'Dữ liệu phân bổ lô không hợp lệ.']);
                }
                $allocated = 0.0;
                foreach ($parts as $part) {
                    $balanceId = (int) ($part['balance_id'] ?? 0);
                    $quantity = (float) ($part['quantity'] ?? 0);
                    if ($quantity <= 0) continue;
                    if ($balanceId <= 0 || ! is_finite($quantity)) {
                        throw ValidationException::withMessages(['batches' => 'Lô hoặc số lượng không hợp lệ.']);
                    }
                    $balance = InventoryBalance::query()
                        ->whereKey($balanceId)->where('warehouse_id', $locked->warehouse_id)
                        ->where('medicine_id', $item->medicine_id)
                        ->whereDate('expiry_date', '>=', now()->toDateString())
                        ->lockForUpdate()->first();
                    if (! $balance) {
                        throw ValidationException::withMessages(['batches' => 'Lô không tồn tại, không thuộc kho hoặc đã hết hạn.']);
                    }
                    $lotTotals[$balanceId] = ($lotTotals[$balanceId] ?? 0) + $quantity;
                    if ($lotTotals[$balanceId] > (float) $balance->quantity_on_hand + 0.00005) {
                        throw ValidationException::withMessages(['batches' => "Tổng số lượng xuất vượt tồn lô {$balance->batch_number}."]);
                    }
                    $allocated += $quantity;
                    $newRows[] = [
                        'medicine_id' => $item->medicine_id,
                        'drug_bid_award_id' => $item->drug_bid_award_id,
                        'drug_bid_award_allocation_id' => $item->drug_bid_award_allocation_id,
                        'batch_number' => $balance->batch_number,
                        'expiry_date' => $balance->expiry_date,
                        'quantity' => $quantity,
                        'unit_price' => $item->unit_price,
                    ];
                }
                if (abs($allocated - (float) $item->quantity) > 0.00005) {
                    throw ValidationException::withMessages(['batches' => 'Tổng số lượng chọn lô phải bằng số lượng đã được phê duyệt cho từng sản phẩm.']);
                }
            }

            // Keep deferred item rows intact for canonical posting exclusion.
            $postableIds = $postable->pluck('id');
            $locked->items()->whereIn('id', $postableIds)->delete();
            $locked->items()->createMany($newRows);
            $inventory->postIssue($locked->fresh('items'), $actorUserId);
        });
    }
}
