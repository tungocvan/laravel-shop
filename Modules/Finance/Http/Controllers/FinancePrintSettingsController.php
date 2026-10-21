<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Finance\Services\FinancePrintSettings;

final class FinancePrintSettingsController extends Controller
{
    public function index(FinancePrintSettings $settings): View
    {
        return view('Finance::vouchers.settings', ['settings' => $settings->get()]);
    }

    public function update(Request $request, FinancePrintSettings $settings): RedirectResponse
    {
        $settings->save([
            ...$request->only([
                'organization', 'subtitle', 'paper', 'orientation', 'margin_mm', 'font_size',
                'signature_creator', 'signature_approver',
            ]),
            'show_reference' => $request->boolean('show_reference'),
            'show_bank' => $request->boolean('show_bank'),
            'show_signatures' => $request->boolean('show_signatures'),
        ]);
        return redirect()->route('admin.finance.print.settings')->with('finance_success', 'Đã lưu cấu hình mẫu in.');
    }
}
