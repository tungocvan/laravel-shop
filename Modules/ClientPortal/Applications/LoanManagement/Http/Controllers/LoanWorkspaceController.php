<?php

namespace Modules\ClientPortal\Applications\LoanManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\LoanManagement\Services\LoanContractService;

final class LoanWorkspaceController extends Controller
{
    public function storeLender(Request $request, LoanContractService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('client.loans.contracts.create'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['individual', 'company', 'credit_institution'])],
            'tax_identifier' => ['nullable', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
        ]);
        $service->createLender($data);

        return redirect()->route('client.loans.dashboard')->with('success', 'Đã thêm đối tác cho vay.');
    }

    public function storeContract(Request $request, LoanContractService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('client.loans.contracts.create'), 403);
        $data = $this->validatedContract($request);
        $service->createContract($data);

        return redirect()->route('client.loans.dashboard')->with('success', 'Đã tạo hợp đồng nháp.');
    }

    public function updateContract(Request $request, int $contract, LoanContractService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('client.loans.contracts.edit'), 403);
        $data = $this->validatedContract($request, $contract);
        $service->updateContract($contract, $data);

        return redirect()->route('client.loans.dashboard')->with('success', 'Đã cập nhật hợp đồng.');
    }

    private function validatedContract(Request $request, ?int $id = null): array
    {
        $data = $request->validate([
            'lender_id' => ['required', 'integer', Rule::exists('loan_lenders', 'id')],
            'contract_number' => ['required', 'string', 'max:255', Rule::unique('loan_contracts', 'contract_number')->ignore($id)],
            'committed_amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'annual_rate_percent' => ['required', 'numeric', 'between:0,100', 'decimal:0,5'],
            'day_count_basis' => ['required', 'integer', Rule::in([360, 365, 366])],
            'signed_at' => ['required', 'date_format:Y-m-d'],
            'matures_at' => ['required', 'date_format:Y-m-d', 'after_or_equal:signed_at'],
        ]);
        return $data;
    }
}
