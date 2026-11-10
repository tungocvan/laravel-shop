<?php

namespace Modules\LoanManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class LoanManagementDemoSeeder extends Seeder
{
    /**
     * Opt-in local/demo only. Never overwrite existing records or seed production.
     * Run: php artisan db:seed --class="Modules\\LoanManagement\\Database\\Seeders\\LoanManagementDemoSeeder"
     */
    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('LoanManagement demo seeder is restricted to local/testing.');
        }

        DB::transaction(function (): void {
            $number = 'DEMO-LOAN-20261110-01';
            if (DB::table('loan_contracts')->where('contract_number', $number)->exists()) {
                $this->command?->info('LoanManagement demo already exists; no changes made.');
                return;
            }

            $lenderId = DB::table('loan_lenders')->insertGetId([
                'name' => 'DEMO — Ngân hàng minh họa',
                'type' => 'credit_institution',
                'tax_identifier' => null,
                'contact' => null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $contractId = DB::table('loan_contracts')->insertGetId([
                'lender_id' => $lenderId,
                'contract_number' => $number,
                'committed_amount' => '500000000.00',
                'annual_rate_percent' => '9.50000',
                'day_count_basis' => 365,
                'signed_at' => '2026-11-01',
                'matures_at' => '2027-11-01',
                'status' => 'active',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('loan_disbursements')->insert([
                'loan_contract_id' => $contractId, 'disbursed_at' => '2026-11-02',
                'amount' => '300000000.00', 'reference' => 'DEMO-DISBURSE-01',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('loan_repayment_schedules')->insert([
                'loan_contract_id' => $contractId, 'installment' => 1, 'due_at' => '2026-12-01',
                'principal_due' => '25000000.00', 'interest_due' => '2300000.00', 'fees_due' => '0.00',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('loan_payments')->insert([
                'loan_contract_id' => $contractId, 'paid_at' => '2026-11-08',
                'principal_paid' => '10000000.00', 'interest_paid' => '500000.00',
                'fees_paid' => '0.00', 'reference' => 'DEMO-PAY-01',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $policyId = DB::table('loan_insurance_policies')->insertGetId([
                'loan_contract_id' => $contractId, 'type' => 'collateral',
                'provider' => 'DEMO — Bảo hiểm minh họa',
                'policy_number' => 'DEMO-POLICY-20261110-01',
                'starts_at' => '2026-11-01', 'ends_at' => '2027-11-01',
                'requirement' => 'unverified', 'insured_amount' => '300000000.00',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('loan_insurance_premium_schedules')->insert([
                'loan_insurance_policy_id' => $policyId, 'due_at' => '2026-12-01',
                'amount_due' => '1800000.00', 'amount_paid' => '0.00', 'paid_at' => null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->command?->info('Created illustrative LoanManagement records.');
        });
    }
}
