<?php

namespace Modules\Website\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Website\Models\PharmaQuickOrderRequest;
use Modules\Website\Services\PharmaQuickOrderIssueHandoffService;

final class PharmaQuickOrderIssueHandoffController extends Controller
{
    public function accept(Request $request, PharmaQuickOrderRequest $quickOrderRequest, PharmaQuickOrderIssueHandoffService $handoff): RedirectResponse
    {
        $data = $request->validate([
            'manager_user_id' => ['required', 'integer', 'exists:users,id'],
            'recipient_partner_id' => ['required', 'integer'],
            'note' => ['required', 'string', 'max:2000'],
            'eligibility_confirmed' => ['required', 'accepted'],
        ]);

        $issue = $handoff->acceptAndCreateDraft(
            $quickOrderRequest->id,
            (int) $request->user('admin')->id,
            (int) $data['manager_user_id'],
            (int) $data['recipient_partner_id'],
            $data['note'],
        );

        return redirect()->route('admin.website.pharma-quick-order.requests.show', $quickOrderRequest)
            ->with('success', 'Đã chấp nhận yêu cầu và tạo phiếu xuất nháp #'.$issue->id.'. Chưa ghi sổ.');
    }

    public function store(Request $request, PharmaQuickOrderRequest $quickOrderRequest, PharmaQuickOrderIssueHandoffService $handoff): RedirectResponse
    {
        $data = $request->validate([
            'manager_user_id' => ['required', 'integer', 'exists:users,id'],
            'recipient_partner_id' => ['required', 'integer'],
            'price_list_id' => ['required', 'integer'],
            'items' => ['required', 'array', 'min:1', 'max:500'],
            'items.*' => ['required', 'integer', 'distinct'],
        ]);

        $issue = $handoff->createDraft(
            $quickOrderRequest->id,
            (int) $data['manager_user_id'],
            (int) $data['recipient_partner_id'],
            (int) $data['price_list_id'],
            $data['items'],
        );

        return redirect()->route('admin.website.pharma-quick-order.requests.show', $quickOrderRequest)
            ->with('success', 'Đã liên kết phiếu xuất nháp #'.$issue->id.'. Chưa phê duyệt hoặc ghi sổ.');
    }
}
