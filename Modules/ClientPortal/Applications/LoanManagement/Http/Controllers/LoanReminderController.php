<?php

namespace Modules\ClientPortal\Applications\LoanManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\LoanManagement\Services\LoanReminderService;

final class LoanReminderController extends Controller
{
    public function update(Request $request, int $contract, LoanReminderService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('client.loans.contracts.edit'), 403);
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'recipient_emails' => ['required', 'array', 'min:1', 'max:10'],
            'recipient_emails.*' => ['required', 'email', 'max:255'],
            'days_before' => ['array'],
            'days_before.*' => ['integer', Rule::in(LoanReminderService::OFFSETS)],
        ]);
        $service->save($contract, $data);

        return redirect()->route('client.loans.dashboard')
            ->with('success', 'Đã lưu thiết lập nhắc hẹn. Email chỉ được gửi khi dịch vụ gửi nhắc hẹn được quản trị viên kích hoạt.');
    }
}
