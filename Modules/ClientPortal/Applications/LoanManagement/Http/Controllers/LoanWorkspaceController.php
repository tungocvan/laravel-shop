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
    public function storeLender(Request $request, LoanContractService $service): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        abort_unless($request->user()?->can('client.loans.contracts.create'), 403);
        $data = $this->validatedLender($request);
        $id = $service->createLender($data);
        if ($request->expectsJson()) {
            return response()->json(['id' => $id, 'name' => $data['name'], 'type' => $data['type']], 201);
        }

        $destination = $request->input('source') === 'lenders' ? 'client.loans.lenders.index' : 'client.loans.dashboard';
        return redirect()->route($destination)->with('success', 'Đã thêm đối tác cho vay.');
    }

    public function updateLender(Request $request, int $lender, LoanContractService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('client.loans.contracts.edit'), 403);
        $data = $this->validatedLender($request);
        $service->updateLender($lender, $data);
        return redirect()->route('client.loans.lenders.index')->with('success', 'Đã cập nhật đối tác.');
    }

    public function deleteLender(Request $request, int $lender, LoanContractService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('client.loans.contracts.edit'), 403);
        $service->deleteUnusedLender($lender);
        return redirect()->route('client.loans.lenders.index')->with('success', 'Đã xóa đối tác chưa có hợp đồng.');
    }

    private function validatedLender(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['individual', 'company', 'credit_institution'])],
            'tax_identifier' => ['nullable', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
        ]);
    }

    public function storeContract(Request $request, LoanContractService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('client.loans.contracts.create'), 403);
        $data = $this->validatedContract($request);
        $options = $request->validate([
            'repayment_mode' => ['nullable', Rule::in(['manual', 'interest_monthly', 'all_at_maturity'])],
        ]);
        $mode = $options['repayment_mode'] ?? 'manual';
        $service->createContractWithOptionalSchedule($data, $mode === 'manual' ? null : $mode);

        $destination = $request->input('source') === 'wizard' ? 'client.loans.contracts.index' : 'client.loans.dashboard';
        return redirect()->route($destination)->with('success', $mode === 'manual'
            ? 'Đã tạo hợp đồng nháp. Hãy ghi nhận giải ngân khi thực nhận tiền.'
            : 'Đã tạo hợp đồng nháp và lịch trả nợ dự kiến. Chưa ghi nhận giải ngân hay thanh toán.');
    }

    public function updateContract(Request $request, int $contract, LoanContractService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('client.loans.contracts.edit'), 403);
        $data = $this->validatedContract($request, $contract);
        $service->updateContract($contract, $data);

        return redirect()->route('client.loans.dashboard')->with('success', 'Đã cập nhật hợp đồng.');
    }


    public function deleteContract(Request $request, int $contract, LoanContractService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('client.loans.contracts.edit'), 403);
        $service->deleteDraft($contract);

        return redirect()->route('client.loans.dashboard')->with('success', 'Đã xóa hợp đồng nháp.');
    }

    public function restoreDraft(Request $request, int $contract, LoanContractService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('client.loans.contracts.edit'), 403);
        $service->restoreDraft($contract);

        return redirect()->route('client.loans.dashboard')->with('success', 'Đã chuyển hợp đồng về nháp.');
    }

    public function disburse(Request $request, int $contract, \Modules\LoanManagement\Services\LoanTransactionService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('client.loans.payments.create'), 403);
        $data = $request->validate([
            'disbursed_at' => ['required', 'date_format:Y-m-d'],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);
        $service->disburse($contract, $data);
        return redirect()->route('client.loans.dashboard')->with('success', 'Đã ghi nhận giải ngân.');
    }

    public function schedule(Request $request, int $contract, \Modules\LoanManagement\Services\LoanTransactionService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('client.loans.contracts.edit'), 403);
        $data = $request->validate([
            'installment' => ['required', 'integer', 'min:1'],
            'due_at' => ['required', 'date_format:Y-m-d'],
            'principal_due' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'interest_due' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'fees_due' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ]);
        $service->schedule($contract, $data);
        return redirect()->route('client.loans.dashboard')->with('success', 'Đã lập kỳ trả nợ dự kiến.');
    }

    public function updateSchedule(Request $request, int $contract, int $schedule, \Modules\LoanManagement\Services\LoanScheduleAdjustmentService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('client.loans.contracts.edit'), 403);
        $data = $request->validate([
            'due_at' => ['required', 'date_format:Y-m-d'],
            'principal_due' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'interest_due' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'fees_due' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ]);
        $service->update($contract, $schedule, $data, $request->user()?->id);

        return redirect()->route('client.loans.dashboard')->with('success', 'Đã lưu điều chỉnh kỳ trả nợ.');
    }

    public function pay(Request $request, int $contract, \Modules\LoanManagement\Services\LoanTransactionService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('client.loans.payments.create'), 403);
        $data = $request->validate([
            'paid_at' => ['required', 'date_format:Y-m-d'],
            'principal_paid' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'interest_paid' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'fees_paid' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);
        $service->pay($contract, $data);
        return redirect()->route('client.loans.dashboard')->with('success', 'Đã ghi nhận thanh toán.');
    }

    public function payInstallment(Request $request, int $contract, int $schedule, \Modules\LoanManagement\Services\LoanInstallmentPaymentService $service): RedirectResponse
    {
        abort_unless($request->user()?->can('client.loans.payments.create'), 403);
        $data = $request->validate([
            'paid_at' => ['required', 'date_format:Y-m-d'],
            'principal_paid' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'interest_paid' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'fees_paid' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);
        $service->record($contract, $schedule, $data);

        return redirect()->route('client.loans.dashboard')->with('success', 'Đã ghi nhận và phân bổ thanh toán cho kỳ trả nợ.');
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
