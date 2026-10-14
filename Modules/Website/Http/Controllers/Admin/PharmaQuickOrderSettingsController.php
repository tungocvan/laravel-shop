<?php

namespace Modules\Website\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Pharma\Models\PriceList;
use Modules\Website\Services\PharmaQuickOrderSettingsService;

class PharmaQuickOrderSettingsController extends Controller
{
    public function index(PharmaQuickOrderSettingsService $settings): View
    {
        return view('Website::pages.admin.pharma-quick-order.index', [
            'configuration' => $settings->get(),
            'priceLists' => PriceList::query()
                ->orderByDesc('id')
                ->get(['id', 'code', 'name', 'type', 'status', 'effective_from', 'effective_to']),
        ]);
    }

    public function update(Request $request, PharmaQuickOrderSettingsService $settings): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'price_list_ids' => ['sometimes', 'array', 'max:200'],
            'price_list_ids.*' => ['required', 'integer', 'distinct', 'min:1'],
        ]);

        $ids = array_map('intval', ($validated['price_list_ids'] ?? []));
        $eligibleCount = PriceList::query()->whereIn('id', $ids)
            ->where('status', PriceList::STATUS_ACTIVE)->count();

        if ($eligibleCount !== count($ids)) {
            return back()->withErrors([
                'price_list_ids' => 'Chỉ được chọn bảng giá đang hoạt động và tồn tại.',
            ])->withInput();
        }

        $settings->save((bool) $validated['enabled'], $ids);

        return back()->with('success', 'Đã lưu thiết lập đặt hàng nhanh Pharma.');
    }
}
