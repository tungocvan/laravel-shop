<?php

namespace Modules\Website\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Website\Models\PharmaQuickOrderRequest;

final class PharmaQuickOrderLifecycleService
{
    public function change(int $id, int $adminId, string $action, string $reason): void
    {
        DB::transaction(function () use ($id, $adminId, $action, $reason): void {
            $record = PharmaQuickOrderRequest::query()->lockForUpdate()->findOrFail($id);
            if ($record->inventory_issue_id !== null) {
                throw ValidationException::withMessages(['reason' => 'Yêu cầu còn liên kết phiếu xuất. Phải xử lý phiếu qua quy trình kho trước khi hoàn tác hoặc xóa.']);
            }
            $old = $record->status;
            if ($action === 'revert' && in_array($old, ['accepted', 'rejected'], true)) {
                $record->forceFill([
                    'status' => PharmaQuickOrderRequest::PENDING_REVIEW,
                    'reviewed_by' => null, 'reviewed_at' => null, 'review_note' => null,
                ])->save();
                $new = PharmaQuickOrderRequest::PENDING_REVIEW;
            } elseif ($action === 'delete' && in_array($old, ['pending_review', 'rejected'], true)) {
                $record->delete();
                $new = 'deleted';
            } else {
                throw ValidationException::withMessages(['reason' => 'Trạng thái yêu cầu không cho phép thao tác này.']);
            }
            DB::table('website_pharma_quick_order_reviews')->insert([
                'quick_order_request_id' => $record->id,
                'admin_id' => $adminId,
                'from_status' => $old,
                'to_status' => $new,
                'note' => ($action === 'delete' ? 'Xóa mềm: ' : 'Hoàn tác: ').$reason,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
    }
}
