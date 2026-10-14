<?php

namespace Modules\Website\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Website\Models\PharmaQuickOrderRequest;
use Modules\Website\Services\PharmaQuickOrderLifecycleService;

final class PharmaQuickOrderLifecycleController extends Controller
{
    public function revert(Request $request, PharmaQuickOrderRequest $quickOrderRequest, PharmaQuickOrderLifecycleService $service): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:2000']]);
        $service->change($quickOrderRequest->id, (int) $request->user('admin')->id, 'revert', $data['reason']);
        return back()->with('success', 'Đã hoàn tác yêu cầu về Chờ xét duyệt.');
    }

    public function destroy(Request $request, PharmaQuickOrderRequest $quickOrderRequest, PharmaQuickOrderLifecycleService $service): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:2000']]);
        $service->change($quickOrderRequest->id, (int) $request->user('admin')->id, 'delete', $data['reason']);
        return redirect()->route('admin.website.pharma-quick-order.requests.index')
            ->with('success', 'Đã xóa mềm yêu cầu. Lịch sử vẫn được lưu.');
    }
    public function unlink(Request $request, PharmaQuickOrderRequest $quickOrderRequest): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:2000']]);
        \Illuminate\Support\Facades\DB::transaction(function () use ($quickOrderRequest, $request, $data): void {
            $record = PharmaQuickOrderRequest::query()->whereKey($quickOrderRequest->id)->lockForUpdate()->firstOrFail();
            if (! $record->inventory_issue_id) {
                throw \Illuminate\Validation\ValidationException::withMessages(['reason' => 'Yêu cầu không có phiếu xuất liên kết.']);
            }
            $issue = \Modules\Pharma\Models\InventoryIssue::query()->whereKey($record->inventory_issue_id)->lockForUpdate()->first();
            if ($issue && $issue->status !== \Modules\Pharma\Models\InventoryIssue::DRAFT) {
                throw \Illuminate\Validation\ValidationException::withMessages(['reason' => 'Chỉ được hủy liên kết phiếu nháp. Phiếu đã chuyển trạng thái phải xử lý theo quy trình kho.']);
            }
            $old = $record->status;
            $issueId = $record->inventory_issue_id;
            $record->forceFill([
                'inventory_issue_id' => null,
                'status' => PharmaQuickOrderRequest::PENDING_REVIEW,
                'reviewed_by' => null, 'reviewed_at' => null, 'review_note' => null,
            ])->save();
            \Illuminate\Support\Facades\DB::table('website_pharma_quick_order_reviews')->insert([
                'quick_order_request_id' => $record->id,
                'admin_id' => $request->user('admin')->id,
                'from_status' => $old,
                'to_status' => PharmaQuickOrderRequest::PENDING_REVIEW,
                'note' => 'Hủy liên kết phiếu #'.$issueId.($issue ? ' (phiếu nháp được giữ nguyên)' : ' (phiếu không còn tồn tại)').': '.$data['reason'],
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
        return back()->with('success', 'Đã hủy liên kết và đưa yêu cầu về Chờ xét duyệt. Phiếu nháp còn tồn tại không bị xóa.');
    }

}
