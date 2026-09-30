<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\InventoryIssue;

final class UserOrderApprovalService
{
    public function __construct(private readonly UserOrderStockReadinessService $stockReadiness) {}
    public function approve(int $actorUserId, InventoryIssue $issue): InventoryIssue
    {
        $this->guardPending($actorUserId, $issue);
        if (! $this->stockReadiness->forIssue($issue)['is_ready']) {
            throw ValidationException::withMessages(['order' => 'Chưa thể phê duyệt: toàn bộ sản phẩm phải đủ tồn kho khả dụng. Ghi chú chờ cung cấp chỉ dùng để theo dõi hàng thiếu.']);
        }

        return DB::transaction(function () use ($actorUserId, $issue): InventoryIssue {
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
        if ($issue->status !== InventoryIssue::APPROVED || $issue->posted_at !== null) {
            throw ValidationException::withMessages(['order' => 'Chỉ đơn đã duyệt nhưng chưa ghi sổ kho mới được hoàn tác duyệt.']);
        }

        return DB::transaction(function () use ($issue): InventoryIssue {
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
        if (! in_array($issue->status, [InventoryIssue::DRAFT, InventoryIssue::REJECTED], true)
            || $issue->posted_at !== null) {
            throw ValidationException::withMessages(['order' => 'Chỉ được xóa đơn Nháp hoặc Từ chối chưa ghi sổ kho.']);
        }

        DB::transaction(function () use ($issue): void {
            $issue->deferredSupplies()->delete();
            $issue->items()->delete();
            $issue->delete();
        });
    }

    public function reject(int $actorUserId, InventoryIssue $issue, string $reason): InventoryIssue
    {
        $this->guardPending($actorUserId, $issue);
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['rejection_reason' => 'Vui lòng nhập lý do từ chối đơn hàng.']);
        }

        return DB::transaction(function () use ($actorUserId, $issue, $reason): InventoryIssue {
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

    private function guardPending(int $actorUserId, InventoryIssue $issue): void
    {
        if ($issue->status !== InventoryIssue::PENDING_APPROVAL) {
            throw ValidationException::withMessages(['order' => 'Chỉ đơn hàng đang Chờ duyệt mới được phê duyệt hoặc từ chối.']);
        }

    }
}
