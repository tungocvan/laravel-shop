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
        if (! $this->stockReadiness->forIssue($issue)['can_approve']) {
            throw ValidationException::withMessages(['order' => 'Chưa thể phê duyệt: sản phẩm thiếu hàng phải được lưu ghi chú chờ cung cấp trước.']);
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
