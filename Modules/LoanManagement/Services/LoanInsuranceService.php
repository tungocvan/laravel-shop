<?php

namespace Modules\LoanManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class LoanInsuranceService
{
    public function createPolicy(int $contractId, array $data): int
    {
        return DB::transaction(function () use ($contractId, $data): int {
            $contract = DB::table('loan_contracts')->where('id', $contractId)->lockForUpdate()->first();
            abort_unless($contract, 404);
            if ($contract->status === 'cancelled') {
                throw ValidationException::withMessages(['contract' => 'Hợp đồng đã hủy.']);
            }
            if (isset($data['ends_at']) && $data['ends_at'] < $data['starts_at']) {
                throw ValidationException::withMessages(['ends_at' => 'Ngày hết hạn phải sau ngày bắt đầu.']);
            }
            return (int) DB::table('loan_insurance_policies')->insertGetId([
                'loan_contract_id' => $contractId,
                'type' => $data['type'],
                'provider' => trim($data['provider']),
                'policy_number' => trim($data['policy_number']),
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'] ?? null,
                'requirement' => $data['requirement'],
                'insured_amount' => $data['insured_amount'] ?? null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }, 3);
    }

    public function createPremium(int $policyId, array $data): int
    {
        return DB::transaction(function () use ($policyId, $data): int {
            $policy = DB::table('loan_insurance_policies')->where('id', $policyId)->lockForUpdate()->first();
            abort_unless($policy, 404);
            if ($data['due_at'] < $policy->starts_at || ($policy->ends_at !== null && $data['due_at'] > $policy->ends_at)) {
                throw ValidationException::withMessages(['due_at' => 'Ngày đóng phí nằm ngoài thời hạn bảo hiểm.']);
            }
            if (DB::table('loan_insurance_premium_schedules')->where('loan_insurance_policy_id', $policyId)->where('due_at', $data['due_at'])->exists()) {
                throw ValidationException::withMessages(['due_at' => 'Ngày đóng phí này đã có trong lịch.']);
            }
            return (int) DB::table('loan_insurance_premium_schedules')->insertGetId([
                'loan_insurance_policy_id' => $policyId,
                'due_at' => $data['due_at'],
                'amount_due' => $data['amount_due'],
                'amount_paid' => '0.00',
                'paid_at' => null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }, 3);
    }

    public function recordPremiumPayment(int $premiumId, array $data): void
    {
        DB::transaction(function () use ($premiumId, $data): void {
            $premium = DB::table('loan_insurance_premium_schedules')->where('id', $premiumId)->lockForUpdate()->first();
            abort_unless($premium, 404);
            $newPaid = bcadd((string) $premium->amount_paid, (string) $data['amount'], 2);
            if (bccomp($newPaid, (string) $premium->amount_due, 2) > 0) {
                throw ValidationException::withMessages(['amount' => 'Số tiền đóng vượt phí còn phải trả.']);
            }
            DB::table('loan_insurance_premium_schedules')->where('id', $premiumId)->update([
                'amount_paid' => $newPaid,
                'paid_at' => $data['paid_at'],
                'updated_at' => now(),
            ]);
        }, 3);
    }

    public function overview(): array
    {
        $policies = DB::table('loan_insurance_policies as p')
            ->join('loan_contracts as c', 'c.id', '=', 'p.loan_contract_id')
            ->select('p.*', 'c.contract_number')
            ->orderByDesc('p.id')->limit(100)->get();

        $premiums = DB::table('loan_insurance_premium_schedules')
            ->whereIn('loan_insurance_policy_id', $policies->pluck('id'))
            ->orderBy('due_at')->get();

        $today = now()->toDateString();
        foreach ($policies as $policy) {
            $policy->expiry_status = $policy->ends_at === null ? 'unknown'
                : ($policy->ends_at < $today ? 'expired'
                    : ($policy->ends_at <= now()->addDays(30)->toDateString() ? 'expiring' : 'valid'));
            $policy->premiums = $premiums->where('loan_insurance_policy_id', $policy->id)->values();
            foreach ($policy->premiums as $premium) {
                $premium->payment_status = bccomp((string) $premium->amount_paid, (string) $premium->amount_due, 2) >= 0
                    ? 'paid' : ($premium->due_at < $today ? 'overdue' : 'pending');
            }
        }
        return ['policies' => $policies];
    }
}
