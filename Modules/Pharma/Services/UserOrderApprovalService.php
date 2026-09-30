<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\InventoryIssue;

final class UserOrderApprovalService
{
    public function approve(int $actorUserId, InventoryIssue $issue): InventoryIssue
    {
        $this->guardPending($actorUserId, $issue);

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

        if (in_array($actorUserId, array_filter([
            (int) $issue->created_by,
            (int) $issue->manager_user_id,
            (int) $issue->submitted_by,
        ]), true)) {
            throw ValidationException::withMessages(['order' => 'Người lập, người phụ trách hoặc người gửi duyệt không được tự phê duyệt đơn hàng này.']);
        }
    }
}
