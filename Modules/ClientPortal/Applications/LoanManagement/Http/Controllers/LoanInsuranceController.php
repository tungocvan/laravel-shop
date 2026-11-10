<?php

namespace Modules\ClientPortal\Applications\LoanManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\LoanManagement\Services\LoanInsuranceService;

final class LoanInsuranceController extends Controller
{
    public function storePolicy(Request $request, int $contract, LoanInsuranceService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('client.loans.insurance.manage'), 403);
        $data = $request->validate([
            'type' => ['required', Rule::in(['loan', 'life', 'collateral', 'other'])],
            'provider' => ['required', 'string', 'max:255'],
            'policy_number' => ['required', 'string', 'max:255', Rule::unique('loan_insurance_policies', 'policy_number')->where('provider', $request->input('provider'))],
            'starts_at' => ['required', 'date_format:Y-m-d'],
            'ends_at' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_at'],
            'requirement' => ['required', Rule::in(['contractual', 'voluntary', 'unverified'])],
            'insured_amount' => ['nullable', 'numeric', 'gt:0', 'decimal:0,2'],
        ]);
        $service->createPolicy($contract, $data);
        return redirect()->route('client.loans.dashboard')->with('success', 'Đã thêm hợp đồng bảo hiểm.');
    }

    public function storePremium(Request $request, int $policy, LoanInsuranceService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('client.loans.insurance.manage'), 403);
        $data = $request->validate([
            'due_at' => ['required', 'date_format:Y-m-d'],
            'amount_due' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
        ]);
        $service->createPremium($policy, $data);
        return redirect()->route('client.loans.dashboard')->with('success', 'Đã thêm kỳ đóng phí bảo hiểm.');
    }

    public function payPremium(Request $request, int $premium, LoanInsuranceService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('client.loans.insurance.manage'), 403);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'paid_at' => ['required', 'date_format:Y-m-d'],
        ]);
        $service->recordPremiumPayment($premium, $data);
        return redirect()->route('client.loans.dashboard')->with('success', 'Đã ghi nhận phí bảo hiểm.');
    }
}
