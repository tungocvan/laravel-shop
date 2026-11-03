<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\InventoryIssue;

final class UserOrderApprovalService
{
    public function approve(int $actorUserId, InventoryIssue $issue): InventoryIssue
    {
        return DB::transaction(function () use ($actorUserId, $issue): InventoryIssue {
            $issue = $this->lockIssue($issue);
            $this->guardPending($issue);
            $readiness = app(UserOrderStockReadinessService::class)->forIssue($issue);
            if (! ($readiness['can_approve'] ?? false)) {
                throw ValidationException::withMessages([
                    'order' => 'Chưa thể phê duyệt: đơn phải có ít nhất 1 sản phẩm đủ tồn; mọi sản phẩm thiếu phải có ngày dự kiến cung cấp và ghi chú.',
                ]);
            }
            $issue->update([
                'status' => InventoryIssue::APPROVED,
                'approved_by' => $actorUserId,
                'approved_at' => now(),
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            return $issue->refresh();
        });
    }

    /**
     * Approve a bid order partially without losing the original demand.
     * The remaining demand is recorded as deferred supply, never stock-posted.
     */
    public function approveBidPartially(int $actorUserId, InventoryIssue $issue, array $quantities, array $supply): InventoryIssue
    {
        return DB::transaction(function () use ($actorUserId, $issue, $quantities, $supply): InventoryIssue {
            $issue = $this->lockIssue($issue);
            $this->guardPending($issue);
            if ($issue->issue_source !== 'bid' || $issue->posted_at !== null) {
                throw ValidationException::withMessages(['order' => 'Chỉ đơn hàng thầu chờ duyệt mới được điều chỉnh số lượng.']);
            }

            $issue->load('items');
            $knownIds = $issue->items->pluck('id')->map(fn ($id) => (int) $id)->all();
            foreach (array_keys($quantities) as $id) {
                if (! in_array((int) $id, $knownIds, true)) {
                    throw ValidationException::withMessages(['quantities' => 'Sản phẩm không thuộc đơn hàng.']);
                }
            }

            $stocked = false;
            foreach ($issue->items as $item) {
                $original = (float) $item->quantity;
                $raw = $quantities[$item->id] ?? null;
                if (! is_scalar($raw) || ! ctype_digit((string) $raw)) {
                    throw ValidationException::withMessages(['quantities' => 'Số lượng duyệt phải là số nguyên không âm.']);
                }
                $approved = (int) $raw;
                if ($approved > $original) {
                    throw ValidationException::withMessages(['quantities' => 'Số lượng duyệt không được vượt số lượng đặt.']);
                }
                $available = app(UserOrderStockReadinessService::class)
                    ->availableForMedicine($issue, (int) $item->medicine_id);
                if ($approved > $available) {
                    throw ValidationException::withMessages(['quantities' => 'Số lượng duyệt vượt tồn khả dụng.']);
                }
                $deferred = $original - $approved;
                if ($deferred > 0) {
                    $details = $supply[$item->id] ?? [];
                    $note = trim((string) ($details['note'] ?? ''));
                    $date = $details['expected_supply_date'] ?? null;
                    if ($note === '' || ! $date) {
                        throw ValidationException::withMessages(['supply' => 'Phần thiếu cần có ngày dự kiến cung ứng và ghi chú.']);
                    }
                    \Modules\Pharma\Models\InventoryIssueDeferredSupply::updateOrCreate(
                        ['issue_id' => $issue->id, 'drug_bid_award_allocation_id' => $item->drug_bid_award_allocation_id, 'status' => 'pending'],
                        ['medicine_id' => $item->medicine_id, 'drug_bid_award_id' => $item->drug_bid_award_id,
                            'quantity' => $deferred, 'expected_supply_date' => $date, 'note' => $note,
                            'created_by' => $actorUserId, 'status' => 'pending']
                    );
                } else {
                    $issue->deferredSupplies()->where('drug_bid_award_allocation_id', $item->drug_bid_award_allocation_id)
                        ->where('status', 'pending')->delete();
                }
                $item->update(['quantity' => $approved]);
                $stocked = $stocked || $approved > 0;
            }
            if (! $stocked) {
                throw ValidationException::withMessages(['quantities' => 'Cần duyệt ít nhất một sản phẩm có số lượng thực xuất.']);
            }
            $issue->update([
                'status' => InventoryIssue::APPROVED, 'approved_by' => $actorUserId,
                'approved_at' => now(), 'rejected_by' => null, 'rejected_at' => null, 'rejection_reason' => null,
            ]);

            return $issue->refresh();
        });
    }

    /**
     * Approve a price-list order with partial fulfillment.
     * Bid orders keep their allocation-specific workflow.
     */
    public function approvePartially(int $actorUserId, InventoryIssue $issue, array $quantities, array $supply): InventoryIssue
    {
        if ($issue->issue_source === 'bid') {
            return $this->approveBidPartially($actorUserId, $issue, $quantities, $supply);
        }

        return DB::transaction(function () use ($actorUserId, $issue, $quantities, $supply): InventoryIssue {
            $issue = $this->lockIssue($issue);
            $this->guardPending($issue);
            if ($issue->issue_source === 'bid' || $issue->posted_at !== null) {
                throw ValidationException::withMessages(['order' => 'Trạng thái đơn hàng không hợp lệ.']);
            }
            $issue->load('items');
            $knownIds = $issue->items->pluck('id')->map(fn ($id) => (int) $id)->all();
            foreach (array_keys($quantities) as $id) {
                if (! in_array((int) $id, $knownIds, true)) {
                    throw ValidationException::withMessages(['quantities' => 'Sản phẩm không thuộc đơn hàng.']);
                }
            }

            // Split lot lines share medicine-level stock and a single deferred supply record.
            // Validate every requested quantity before writing any changes.
            $existingDeferred = $issue->deferredSupplies()->where('status', 'pending')
                ->whereNull('drug_bid_award_allocation_id')->get()->keyBy('medicine_id');
            $approvedByItem = [];
            $approvedAny = false;
            foreach ($issue->items as $item) {
                $raw = $quantities[$item->id] ?? null;
                if (! is_scalar($raw) || ! ctype_digit((string) $raw)) {
                    throw ValidationException::withMessages(['quantities' => 'Số lượng duyệt phải là số nguyên không âm.']);
                }
                $approved = (int) $raw;
                if ($approved > (float) $item->quantity) {
                    throw ValidationException::withMessages(['quantities' => 'Số lượng duyệt vượt số lượng dòng xuất kho.']);
                }
                $approvedByItem[$item->id] = $approved;
                $approvedAny = $approvedAny || $approved > 0;
            }
            if (! $approvedAny) {
                throw ValidationException::withMessages(['quantities' => 'Cần duyệt ít nhất một sản phẩm có số lượng thực xuất.']);
            }

            foreach ($issue->items->groupBy('medicine_id') as $medicineId => $items) {
                $existingSupply = $existingDeferred->get((int) $medicineId);
                $originalIssued = (float) $items->sum('quantity');
                $requested = $originalIssued + (float) ($existingSupply?->quantity ?? 0);
                $approved = (float) $items->sum(fn ($item) => $approvedByItem[$item->id]);
                $available = app(UserOrderStockReadinessService::class)->availableForMedicine($issue, (int) $medicineId);
                if ($approved > $requested || $approved > $available) {
                    throw ValidationException::withMessages(['quantities' => 'Tổng số lượng duyệt vượt số lượng đặt hoặc tồn khả dụng của sản phẩm.']);
                }
                $deferred = $requested - $approved;
                if ($deferred > 0) {
                    // The first item carries the medicine-level supply fields in the UI.
                    $firstItem = $items->first();
                    $details = $supply[$firstItem->id] ?? [];
                    $note = trim((string) ($details['note'] ?? $existingSupply?->note ?? ''));
                    $date = $details['expected_supply_date'] ?? $existingSupply?->expected_supply_date?->format('Y-m-d');
                    if ($note === '' || ! $date) {
                        throw ValidationException::withMessages(['supply' => 'Phần thiếu cần ngày dự kiến và ghi chú.']);
                    }
                    \Modules\Pharma\Models\InventoryIssueDeferredSupply::updateOrCreate(
                        ['issue_id' => $issue->id, 'medicine_id' => $medicineId, 'drug_bid_award_allocation_id' => null, 'status' => 'pending'],
                        ['quantity' => $deferred, 'expected_supply_date' => $date, 'note' => $note, 'created_by' => $actorUserId]
                    );
                } else {
                    $issue->deferredSupplies()->where('medicine_id', $medicineId)
                        ->whereNull('drug_bid_award_allocation_id')->where('status', 'pending')->delete();
                }
                foreach ($items as $item) {
                    $item->update(['quantity' => $approvedByItem[$item->id]]);
                }
            }
            $issue->update([
                'status' => InventoryIssue::APPROVED, 'approved_by' => $actorUserId,
                'approved_at' => now(), 'rejected_by' => null, 'rejected_at' => null, 'rejection_reason' => null,
            ]);
            return $issue->refresh();
        });
    }

    public function undoApproval(int $actorUserId, InventoryIssue $issue): InventoryIssue
    {
        return DB::transaction(function () use ($issue): InventoryIssue {
            $issue = $this->lockIssue($issue);
            if ($issue->status !== InventoryIssue::APPROVED || $issue->posted_at !== null) {
                throw ValidationException::withMessages(['order' => 'Chỉ đơn đã duyệt nhưng chưa ghi sổ kho mới được hoàn tác duyệt.']);
            }
            $issue->update([
                'status' => InventoryIssue::PENDING_APPROVAL,
                'approved_by' => null,
                'approved_at' => null,
            ]);

            return $issue->refresh();
        });
    }

    public function deleteOwnNonStockOrder(int $actorUserId, InventoryIssue $issue): void
    {
        DB::transaction(function () use ($actorUserId, $issue): void {
            $issue = $this->lockIssue($issue);
            if (! in_array($actorUserId, [(int) $issue->created_by, (int) $issue->manager_user_id], true)) {
                throw ValidationException::withMessages(['order' => 'Bạn chỉ được xóa đơn Nháp hoặc Từ chối do mình tạo hoặc phụ trách.']);
            }
            $this->deleteLockedNonStockOrder($issue);
        });
    }

    public function deleteNonStockOrder(int $actorUserId, InventoryIssue $issue): void
    {
        DB::transaction(function () use ($issue): void {
            $issue = $this->lockIssue($issue);
            $this->deleteLockedNonStockOrder($issue);
        });
    }

    private function deleteLockedNonStockOrder(InventoryIssue $issue): void
    {
        if (! in_array($issue->status, [InventoryIssue::DRAFT, InventoryIssue::REJECTED], true)
            || $issue->posted_at !== null) {
            throw ValidationException::withMessages(['order' => 'Chỉ được xóa đơn Nháp hoặc Từ chối chưa ghi sổ kho.']);
        }
        $issue->deferredSupplies()->delete();
        $issue->items()->delete();
        $issue->delete();
    }

    public function reject(int $actorUserId, InventoryIssue $issue, string $reason): InventoryIssue
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['rejection_reason' => 'Vui lòng nhập lý do từ chối đơn hàng.']);
        }

        return DB::transaction(function () use ($actorUserId, $issue, $reason): InventoryIssue {
            $issue = $this->lockIssue($issue);
            $this->guardPending($issue);
            $issue->update([
                'status' => InventoryIssue::REJECTED,
                'rejected_by' => $actorUserId,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
                'approved_by' => null,
                'approved_at' => null,
            ]);

            return $issue->refresh();
        });
    }

    private function lockIssue(InventoryIssue $issue): InventoryIssue
    {
        return InventoryIssue::query()->lockForUpdate()->findOrFail($issue->getKey());
    }

    private function guardPending(InventoryIssue $issue): void
    {
        if ($issue->status !== InventoryIssue::PENDING_APPROVAL) {
            throw ValidationException::withMessages(['order' => 'Chỉ đơn hàng đang Chờ duyệt mới được phê duyệt hoặc từ chối.']);
        }

    }
}
