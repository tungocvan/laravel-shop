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
}
