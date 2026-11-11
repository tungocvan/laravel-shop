<?php

namespace Modules\LoanManagement\Services;

use Illuminate\Support\Facades\DB;
use Modules\LoanManagement\Database\Seeders\LoanManagementDemoSeeder;
use RuntimeException;

final class LoanDemoResetService
{
    private const NUMBERS = [
        'DEMO-LOAN-20261110-01', 'DEMO-LOAN-20261110-02',
        'DEMO-LOAN-20261110-03', 'DEMO-LOAN-20261110-04',
        'DEMO-LOAN-20261110-05', 'DEMO-LOAN-20261110-06',
        'DEMO-LOAN-20261110-07', 'DEMO-LOAN-20261110-08',
    ];

    private function assertSafeEnvironment(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Demo reset is restricted to local/testing.');
        }
    }

    public function preview(): array
    {
        $this->assertSafeEnvironment();

        $ids = DB::table('loan_contracts')->whereIn('contract_number', self::NUMBERS)->pluck('id')->all();
        $scheduleIds = DB::table('loan_repayment_schedules')->whereIn('loan_contract_id', $ids)->pluck('id')->all();
        $paymentIds = DB::table('loan_payments')->whereIn('loan_contract_id', $ids)->pluck('id')->all();
        $policyIds = DB::table('loan_insurance_policies')->whereIn('loan_contract_id', $ids)->pluck('id')->all();

        return [
            'contracts' => count($ids),
            'disbursements' => DB::table('loan_disbursements')->whereIn('loan_contract_id', $ids)->count(),
            'schedules' => count($scheduleIds),
            'payments' => count($paymentIds),
            'allocations' => DB::table('loan_payment_allocations')->whereIn('loan_payment_id', $paymentIds)->orWhereIn('loan_repayment_schedule_id', $scheduleIds)->count(),
            'policies' => count($policyIds),
            'premiums' => DB::table('loan_insurance_premium_schedules')->whereIn('loan_insurance_policy_id', $policyIds)->count(),
            'reminder_settings' => DB::table('loan_reminder_settings')->whereIn('loan_contract_id', $ids)->count(),
            'reminder_recipients' => DB::table('loan_reminder_recipients')->whereIn('loan_contract_id', $ids)->count(),
            'reminder_deliveries' => DB::table('loan_reminder_deliveries')->whereIn('loan_repayment_schedule_id', $scheduleIds)->count(),
            'schedule_adjustments' => DB::table('loan_schedule_adjustments')->whereIn('loan_repayment_schedule_id', $scheduleIds)->count(),
        ];
    }

    /**
     * Delete only the eight canonical demo contracts and their dependent records.
     * Never truncate tables or delete lenders still referenced by other contracts.
     */
    public function resetAndSeed(): void
    {
        $this->assertSafeEnvironment();

        DB::transaction(function (): void {
            $contracts = DB::table('loan_contracts')
                ->whereIn('contract_number', self::NUMBERS)
                ->lockForUpdate()->get(['id', 'lender_id']);

            $contractIds = $contracts->pluck('id')->all();
            $lenderIds = $contracts->pluck('lender_id')->unique()->all();
            $scheduleIds = DB::table('loan_repayment_schedules')->whereIn('loan_contract_id', $contractIds)->pluck('id')->all();
            $paymentIds = DB::table('loan_payments')->whereIn('loan_contract_id', $contractIds)->pluck('id')->all();
            $policyIds = DB::table('loan_insurance_policies')->whereIn('loan_contract_id', $contractIds)->pluck('id')->all();

            // Refuse cross-contract allocations: never mutate accounting links owned by a real contract.
            $crossLinked = DB::table('loan_payment_allocations')
                ->where(function ($query) use ($paymentIds, $scheduleIds): void {
                    $query->whereIn('loan_payment_id', $paymentIds)
                        ->orWhereIn('loan_repayment_schedule_id', $scheduleIds);
                })
                ->where(function ($query) use ($paymentIds, $scheduleIds): void {
                    $query->whereNotIn('loan_payment_id', $paymentIds)
                        ->orWhereNotIn('loan_repayment_schedule_id', $scheduleIds);
                })->exists();
            if ($crossLinked) {
                throw new RuntimeException('Demo records have cross-contract payment allocations; reset refused.');
            }

            // Dependent records first: all FKs use RESTRICT, not cascading deletes.
            DB::table('loan_reminder_deliveries')->whereIn('loan_repayment_schedule_id', $scheduleIds)->delete();
            DB::table('loan_schedule_adjustments')->whereIn('loan_repayment_schedule_id', $scheduleIds)->delete();
            DB::table('loan_payment_allocations')->whereIn('loan_payment_id', $paymentIds)
                ->orWhereIn('loan_repayment_schedule_id', $scheduleIds)->delete();
            DB::table('loan_reminder_recipients')->whereIn('loan_contract_id', $contractIds)->delete();
            DB::table('loan_reminder_settings')->whereIn('loan_contract_id', $contractIds)->delete();
            DB::table('loan_insurance_premium_schedules')->whereIn('loan_insurance_policy_id', $policyIds)->delete();
            DB::table('loan_insurance_policies')->whereIn('id', $policyIds)->delete();
            DB::table('loan_payments')->whereIn('id', $paymentIds)->delete();
            DB::table('loan_repayment_schedules')->whereIn('id', $scheduleIds)->delete();
            DB::table('loan_disbursements')->whereIn('loan_contract_id', $contractIds)->delete();
            DB::table('loan_contracts')->whereIn('id', $contractIds)->delete();

            // A demo lender may have been reused by a real contract; preserve it.
            foreach ($lenderIds as $lenderId) {
                if (! DB::table('loan_contracts')->where('lender_id', $lenderId)->exists()) {
                    DB::table('loan_lenders')->where('id', $lenderId)->delete();
                }
            }

            app(LoanManagementDemoSeeder::class)->run();
        }, 3);
    }
}
