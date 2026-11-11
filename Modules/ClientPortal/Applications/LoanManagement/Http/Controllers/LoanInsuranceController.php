<?php

namespace Modules\ClientPortal\Applications\LoanManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        return $this->redirectAfterOperation($request, $contract, 'Đã thêm hợp đồng bảo hiểm.');
    }

    public function storePremium(Request $request, int $policy, LoanInsuranceService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('client.loans.insurance.manage'), 403);
        $data = $request->validate([
            'due_at' => ['required', 'date_format:Y-m-d'],
            'amount_due' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
        ]);
        $service->createPremium($policy, $data);
        $contractId = (int) DB::table('loan_insurance_policies')->where('id', $policy)->value('loan_contract_id');
        return $this->redirectAfterOperation($request, $contractId, 'Đã thêm kỳ đóng phí bảo hiểm.');
    }

    public function payPremium(Request $request, int $premium, LoanInsuranceService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('client.loans.insurance.manage'), 403);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'paid_at' => ['required', 'date_format:Y-m-d'],
        ]);
        $service->recordPremiumPayment($premium, $data);
        $contractId = (int) DB::table('loan_insurance_premium_schedules as s')
            ->join('loan_insurance_policies as p', 'p.id', '=', 's.loan_insurance_policy_id')
            ->where('s.id', $premium)->value('p.loan_contract_id');
        return $this->redirectAfterOperation($request, $contractId, 'Đã ghi nhận phí bảo hiểm.');
    }
    private function redirectAfterOperation(Request $request, int $contractId, string $message): RedirectResponse
    {
        $destination = $request->input('source') === 'detail' && $contractId > 0
            ? route('client.loans.contracts.show', $contractId)
            : route('client.loans.dashboard');

        return redirect($destination)->with('success', $message);
    }
}
