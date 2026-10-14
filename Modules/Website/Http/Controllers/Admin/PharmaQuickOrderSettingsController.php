<?php

namespace Modules\Website\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
            'notification_email' => ['nullable', 'string', 'max:2550'],
            'price_list_ids' => ['sometimes', 'array', 'max:200'],
            'price_list_ids.*' => ['required', 'integer', 'distinct', 'min:1'],
        ]);

        $recipients = $settings->parseNotificationEmails($validated['notification_email'] ?? '');
        if (count($recipients) > 10 || collect($recipients)->contains(
            static fn (string $email): bool => strlen($email) > 254
                || Validator::make(['email' => $email], ['email' => ['required', 'email:rfc']])->fails()
        )) {
            return back()->withErrors([
                'notification_email' => 'Nhập tối đa 10 địa chỉ email hợp lệ, phân cách bằng dấu ; hoặc ,.',
            ])->withInput();
        }

        $ids = array_map('intval', ($validated['price_list_ids'] ?? []));
        $eligibleCount = PriceList::query()->whereIn('id', $ids)
            ->where('status', PriceList::STATUS_ACTIVE)->count();

        if ($eligibleCount !== count($ids)) {
            return back()->withErrors([
                'price_list_ids' => 'Chỉ được chọn bảng giá đang hoạt động và tồn tại.',
            ])->withInput();
        }

        $settings->save((bool) $validated['enabled'], $ids, $validated['notification_email'] ?? null);

        return back()->with('success', 'Đã lưu thiết lập đặt hàng nhanh Pharma.');
    }
}
