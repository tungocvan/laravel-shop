<?php

namespace Modules\LoanManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class LoanTransactionService
{
    public function disburse(int $contractId, array $data): void
    {
        DB::transaction(function () use ($contractId, $data): void {
            $contract = DB::table('loan_contracts')->where('id', $contractId)->lockForUpdate()->first();
            abort_unless($contract, 404);
            if (!in_array($contract->status, ['draft', 'active'], true)) {
                throw ValidationException::withMessages(['contract' => 'Hợp đồng không còn cho phép giải ngân.']);
            }
            $disbursed = (string) DB::table('loan_disbursements')->where('loan_contract_id', $contractId)->sum('amount');
            if (bccomp(bcadd($disbursed, (string) $data['amount'], 2), (string) $contract->committed_amount, 2) > 0) {
                throw ValidationException::withMessages(['amount' => 'Giải ngân vượt số tiền cam kết.']);
            }
            if ($data['disbursed_at'] < $contract->signed_at || $data['disbursed_at'] > $contract->matures_at) {
                throw ValidationException::withMessages(['disbursed_at' => 'Ngày giải ngân phải nằm trong thời hạn hợp đồng.']);
            }
            DB::table('loan_disbursements')->insert([
                'loan_contract_id' => $contractId,
                'disbursed_at' => $data['disbursed_at'],
                'amount' => $data['amount'],
                'reference' => $data['reference'] ?? null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            if ($contract->status === 'draft') {
                DB::table('loan_contracts')->where('id', $contractId)->update(['status' => 'active', 'updated_at' => now()]);
            }
        }, 3);
    }

    public function schedule(int $contractId, array $data): void
    {
        DB::transaction(function () use ($contractId, $data): void {
            $contract = DB::table('loan_contracts')->where('id', $contractId)->lockForUpdate()->first();
            abort_unless($contract, 404);
            if (!in_array($contract->status, ['draft', 'active'], true)) {
                throw ValidationException::withMessages(['contract' => 'Hợp đồng không cho phép lập lịch.']);
            }
            if ($data['due_at'] < $contract->signed_at || $data['due_at'] > $contract->matures_at) {
                throw ValidationException::withMessages(['due_at' => 'Ngày đến hạn nằm ngoài thời hạn hợp đồng.']);
            }
            if (DB::table('loan_repayment_schedules')->where('loan_contract_id', $contractId)->where('installment', $data['installment'])->exists()) {
                throw ValidationException::withMessages(['installment' => 'Kỳ trả nợ đã tồn tại.']);
            }
            $scheduled = (string) DB::table('loan_repayment_schedules')->where('loan_contract_id', $contractId)->sum('principal_due');
            if (bccomp(bcadd($scheduled, (string) $data['principal_due'], 2), (string) $contract->committed_amount, 2) > 0) {
                throw ValidationException::withMessages(['principal_due' => 'Tổng gốc dự kiến vượt hạn mức hợp đồng.']);
            }
            DB::table('loan_repayment_schedules')->insert([
                'loan_contract_id' => $contractId,
                'installment' => $data['installment'],
                'due_at' => $data['due_at'],
                'principal_due' => $data['principal_due'],
                'interest_due' => $data['interest_due'],
                'fees_due' => $data['fees_due'],
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }, 3);
    }

    public function pay(int $contractId, array $data): void
    {
        DB::transaction(function () use ($contractId, $data): void {
            $contract = DB::table('loan_contracts')->where('id', $contractId)->lockForUpdate()->first();
            abort_unless($contract, 404);
            if ($contract->status !== 'active') {
                throw ValidationException::withMessages(['contract' => 'Chỉ thanh toán cho hợp đồng đang vay.']);
            }
            $disbursed = (string) DB::table('loan_disbursements')->where('loan_contract_id', $contractId)->sum('amount');
            $paid = (string) DB::table('loan_payments')->where('loan_contract_id', $contractId)->sum('principal_paid');
            $remaining = bcsub($disbursed, $paid, 2);
            if (bccomp((string) $data['principal_paid'], $remaining, 2) > 0) {
                throw ValidationException::withMessages(['principal_paid' => 'Gốc thanh toán vượt dư nợ thực tế.']);
            }
            if (bccomp(bcadd(bcadd((string) $data['principal_paid'], (string) $data['interest_paid'], 2), (string) $data['fees_paid'], 2), '0', 2) <= 0) {
                throw ValidationException::withMessages(['payment' => 'Tổng thanh toán phải lớn hơn 0.']);
            }
            if ($data['paid_at'] < $contract->signed_at) {
                throw ValidationException::withMessages(['paid_at' => 'Ngày thanh toán không thể trước ngày ký.']);
            }
            DB::table('loan_payments')->insert([
                'loan_contract_id' => $contractId,
                'paid_at' => $data['paid_at'],
                'principal_paid' => $data['principal_paid'],
                'interest_paid' => $data['interest_paid'],
                'fees_paid' => $data['fees_paid'],
                'reference' => $data['reference'] ?? null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            // No automatic close: remaining interest, fees and insurance obligations need reconciliation.
        }, 3);
    }
}
