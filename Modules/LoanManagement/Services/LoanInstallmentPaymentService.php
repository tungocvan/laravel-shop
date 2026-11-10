<?php

namespace Modules\LoanManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class LoanInstallmentPaymentService
{
    /**
     * Allocate one actual payment to one scheduled installment.
     * Legacy unallocated payments are never inferred as settled installments.
     */
    public function record(int $contractId, int $scheduleId, array $data): int
    {
        return DB::transaction(function () use ($contractId, $scheduleId, $data): int {
            $contract = DB::table('loan_contracts')->where('id', $contractId)->lockForUpdate()->first();
            abort_unless($contract, 404);
            $schedule = DB::table('loan_repayment_schedules')
                ->where('id', $scheduleId)->where('loan_contract_id', $contractId)->first();
            abort_unless($schedule, 404);
            if ($contract->status !== 'active') {
                throw ValidationException::withMessages(['contract' => 'Chỉ thanh toán theo kỳ khi hợp đồng đang vay.']);
            }
            $paid = DB::table('loan_payment_allocations')
                ->where('loan_repayment_schedule_id', $scheduleId)
                ->selectRaw('COALESCE(SUM(principal_allocated),0) as principal, COALESCE(SUM(interest_allocated),0) as interest, COALESCE(SUM(fees_allocated),0) as fees')
                ->first();
            foreach ([
                'principal_paid' => ['principal_due', 'principal'],
                'interest_paid' => ['interest_due', 'interest'],
                'fees_paid' => ['fees_due', 'fees'],
            ] as $field => [$due, $allocated]) {
                $remaining = bcsub((string) $schedule->$due, (string) $paid->$allocated, 2);
                if (bccomp((string) $data[$field], '0', 2) < 0 ||
                    bccomp((string) $data[$field], $remaining, 2) > 0) {
                    throw ValidationException::withMessages([$field => 'Số tiền phân bổ vượt phần còn lại của kỳ.']);
                }
            }
            $total = bcadd(bcadd((string) $data['principal_paid'], (string) $data['interest_paid'], 2), (string) $data['fees_paid'], 2);
            if (bccomp($total, '0', 2) <= 0) {
                throw ValidationException::withMessages(['payment' => 'Cần nhập số tiền thanh toán lớn hơn 0.']);
            }

            $paymentId = app(LoanTransactionService::class)->pay($contractId, $data);
            DB::table('loan_payment_allocations')->insert([
                'loan_payment_id' => $paymentId,
                'loan_repayment_schedule_id' => $scheduleId,
                'principal_allocated' => $data['principal_paid'],
                'interest_allocated' => $data['interest_paid'],
                'fees_allocated' => $data['fees_paid'],
                'created_at' => now(), 'updated_at' => now(),
            ]);
            return $paymentId;
        }, 3);
    }

    public function forSchedules(array $scheduleIds): array
    {
        if ($scheduleIds === []) {
            return [];
        }
        return DB::table('loan_payment_allocations')
            ->whereIn('loan_repayment_schedule_id', $scheduleIds)
            ->selectRaw('loan_repayment_schedule_id, COALESCE(SUM(principal_allocated),0) as principal, COALESCE(SUM(interest_allocated),0) as interest, COALESCE(SUM(fees_allocated),0) as fees')
            ->groupBy('loan_repayment_schedule_id')->get()->keyBy('loan_repayment_schedule_id')->all();
    }
}
