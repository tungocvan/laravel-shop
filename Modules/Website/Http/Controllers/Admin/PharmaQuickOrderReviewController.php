<?php

namespace Modules\Website\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Website\Models\PharmaQuickOrderRequest;
use Modules\Website\Services\PharmaQuickOrderDisplayService;
use Modules\Website\Services\PharmaQuickOrderIssueReadinessService;
use Modules\Pharma\Services\UserOrderAuthoringService;

final class PharmaQuickOrderReviewController extends Controller
{
    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'pending_review');
        abort_unless(in_array($status, ['pending_review', 'accepted', 'rejected', 'all'], true), 422);

        $search = mb_substr(trim((string) $request->query('search', '')), 0, 120);
        $requests = PharmaQuickOrderRequest::query()
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('company_name', 'like', '%'.$search.'%')
                        ->orWhere('recipient_name', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('tax_code', 'like', '%'.$search.'%');
                    if (ctype_digit($search)) $q->orWhere('id', (int) $search);
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $counts = PharmaQuickOrderRequest::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        return view('Website::pages.admin.pharma-quick-order.requests', compact('requests', 'status', 'counts', 'search'));
    }

    public function show(PharmaQuickOrderRequest $quickOrderRequest, PharmaQuickOrderDisplayService $display, PharmaQuickOrderIssueReadinessService $readiness, UserOrderAuthoringService $authoring): View
    {
        $reviews = DB::table('website_pharma_quick_order_reviews')
            ->where('quick_order_request_id', $quickOrderRequest->id)
            ->orderBy('id')->get();

        return view('Website::pages.admin.pharma-quick-order.request-detail', [
            'quickOrderRequest' => $quickOrderRequest,
            'reviews' => $reviews,
            'lines' => $display->lines($quickOrderRequest),
            'issueReadiness' => $readiness->inspect($quickOrderRequest),
            'handoffManagers' => $authoring->orderManagers(),
            'handoffCustomers' => $authoring->customers(),
        ]);
    }

    public function review(Request $request, PharmaQuickOrderRequest $quickOrderRequest): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:accepted,rejected'],
            'note' => ['required', 'string', 'max:2000'],
            'eligibility_confirmed' => ['required_if:decision,accepted', 'nullable', 'accepted'],
        ]);

        $changed = DB::transaction(function () use ($quickOrderRequest, $data, $request): bool {
            $record = PharmaQuickOrderRequest::query()->whereKey($quickOrderRequest->id)->lockForUpdate()->firstOrFail();
            if ($record->status !== PharmaQuickOrderRequest::PENDING_REVIEW) {
                return false;
            }

            $record->forceFill([
                'status' => $data['decision'],
                'reviewed_by' => $request->user('admin')->id,
                'reviewed_at' => now(),
                'review_note' => $data['note'],
            ])->save();

            DB::table('website_pharma_quick_order_reviews')->insert([
                'quick_order_request_id' => $record->id,
                'admin_id' => $request->user('admin')->id,
                'from_status' => PharmaQuickOrderRequest::PENDING_REVIEW,
                'to_status' => $data['decision'],
                'note' => $data['note'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        });

        if (! $changed) {
            return back()->withErrors(['decision' => 'Yêu cầu đã được xử lý. Vui lòng tải lại trang.']);
        }

        return redirect()->route('admin.website.pharma-quick-order.requests.show', $quickOrderRequest)
            ->with('success', 'Đã ghi nhận kết quả xét duyệt. Chưa tạo phiếu xuất kho.');
    }
}
