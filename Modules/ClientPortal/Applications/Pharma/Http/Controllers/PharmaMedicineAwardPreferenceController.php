<?php

namespace Modules\ClientPortal\Applications\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\Pharma\Services\MedicineAwardPreferenceService;

final class PharmaMedicineAwardPreferenceController extends Controller
{
    public function update(
        int $medicine,
        Request $request,
        ApplicationRegistry $registry,
        MedicineAwardPreferenceService $preferences,
    ): RedirectResponse {
        abort_unless($request->user('web') && $registry->userCan($request->user('web'), 'client.pharma.products.edit'), 403);

        $data = $request->validate([
            'origin' => ['required', Rule::in(['canonical', 'public_tender'])],
            'record_id' => ['required', 'integer', 'min:1'],
            'action' => ['required', Rule::in(['default', 'hide', 'restore', 'validity'])],
            'tender_validity_text' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['action'] === 'validity') {
            $value = trim($data['tender_validity_text'] ?? '');
            $preferences->updateTenderValidity(
                $medicine,
                $data['origin'],
                (int) $data['record_id'],
                $value !== '' ? $value : null,
            );

            return back()->with('success', 'Đã cập nhật hiệu lực thời gian thầu (nhập thủ công).');
        }

        $preferences->update($medicine, $data['origin'], (int) $data['record_id'], $data['action']);

        return back()->with('success', match ($data['action']) {
            'default' => 'Đã đặt thông tin trúng thầu mặc định.',
            'hide' => 'Đã gỡ bản ghi khỏi danh sách. Có thể khôi phục.',
            'restore' => 'Đã khôi phục bản ghi trúng thầu.',
        });
    }
}
