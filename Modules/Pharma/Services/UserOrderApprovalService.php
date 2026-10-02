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

    public function deleteNonStockOrder(int $actorUserId, InventoryIssue $issue): void
    {
        DB::transaction(function () use ($issue): void {
            $issue = $this->lockIssue($issue);
            if (! in_array($issue->status, [InventoryIssue::DRAFT, InventoryIssue::REJECTED], true)
                || $issue->posted_at !== null) {
                throw ValidationException::withMessages(['order' => 'Chỉ được xóa đơn Nháp hoặc Từ chối chưa ghi sổ kho.']);
            }
            $issue->deferredSupplies()->delete();
            $issue->items()->delete();
            $issue->delete();
        });
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
