<?php

namespace Modules\LoanManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class LoanManagementDemoSeeder extends Seeder
{
    /**
     * Opt-in, illustrative local/testing dataset only.
     * Every scenario has a stable DEMO-LOAN key; existing contracts are never modified.
     */
    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            throw new RuntimeException('LoanManagement demo seeder is restricted to local/testing.');
        }

        DB::transaction(function (): void {
            $scenarios = $this->scenarios();
            foreach ($scenarios as $scenario) {
                $number = $scenario['number'];
                if (DB::table('loan_contracts')->where('contract_number', $number)->exists()) {
                    $this->command?->info("Skipping existing demo contract: {$number}");
                    continue;
                }

                $lender = $scenario['lender'];
                $lenderId = DB::table('loan_lenders')->insertGetId([
                    'name' => $lender[0], 'type' => $lender[1],
                    'tax_identifier' => null, 'contact' => null,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $contractId = DB::table('loan_contracts')->insertGetId([
                    'lender_id' => $lenderId,
                    'contract_number' => $number,
                    'committed_amount' => $scenario['amount'],
                    'annual_rate_percent' => $scenario['rate'],
                    'day_count_basis' => 365,
                    'signed_at' => $scenario['signed'],
                    'matures_at' => $scenario['matures'],
                    'status' => $scenario['status'],
                    'created_at' => now(), 'updated_at' => now(),
                ]);

                foreach ($scenario['disbursements'] as $index => [$date, $amount]) {
                    DB::table('loan_disbursements')->insert([
                        'loan_contract_id' => $contractId, 'disbursed_at' => $date,
                        'amount' => $amount, 'reference' => "DEMO-DISBURSE-{$scenario['key']}-".($index + 1),
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                foreach ($scenario['schedules'] as $index => [$date, $principal, $interest, $fees]) {
                    DB::table('loan_repayment_schedules')->insert([
                        'loan_contract_id' => $contractId, 'installment' => $index + 1,
                        'due_at' => $date, 'principal_due' => $principal,
                        'interest_due' => $interest, 'fees_due' => $fees,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                foreach ($scenario['payments'] as $index => [$date, $principal, $interest, $fees]) {
                    DB::table('loan_payments')->insert([
                        'loan_contract_id' => $contractId, 'paid_at' => $date,
                        'principal_paid' => $principal, 'interest_paid' => $interest,
                        'fees_paid' => $fees, 'reference' => "DEMO-PAY-{$scenario['key']}-".($index + 1),
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                foreach ($scenario['policies'] as $index => $policy) {
                    $policyId = DB::table('loan_insurance_policies')->insertGetId([
                        'loan_contract_id' => $contractId,
                        'type' => $policy['type'],
                        'provider' => $policy['provider'],
                        'policy_number' => "DEMO-POLICY-{$scenario['key']}-".($index + 1),
                        'starts_at' => $policy['starts'],
                        'ends_at' => $policy['ends'],
                        'requirement' => $policy['requirement'],
                        'insured_amount' => $policy['insured'],
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    foreach ($policy['premiums'] as [$due, $amount, $paid, $paidAt]) {
                        DB::table('loan_insurance_premium_schedules')->insert([
                            'loan_insurance_policy_id' => $policyId,
                            'due_at' => $due, 'amount_due' => $amount,
                            'amount_paid' => $paid, 'paid_at' => $paidAt,
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                }
                $this->command?->info("Created illustrative LoanManagement scenario: {$number}");
            }
        });
    }

    private function scenarios(): array
    {
        // Dates are fixed for reproducible financial records. Alert states depend on today's date.
        $policy = static fn (string $type, string $provider, string $requirement, string $starts, ?string $ends, string $insured, array $premiums): array => [
            'type' => $type, 'provider' => $provider, 'requirement' => $requirement,
            'starts' => $starts, 'ends' => $ends, 'insured' => $insured, 'premiums' => $premiums,
        ];
        $scenario = static fn (string $key, array $lender, string $amount, string $rate, string $signed, string $matures, string $status, array $disbursements, array $schedules, array $payments, array $policies): array => [
            'key' => $key, 'number' => "DEMO-LOAN-20261110-{$key}", 'lender' => $lender,
            'amount' => $amount, 'rate' => $rate, 'signed' => $signed, 'matures' => $matures,
            'status' => $status, 'disbursements' => $disbursements,
            'schedules' => $schedules, 'payments' => $payments, 'policies' => $policies,
        ];

        return [
            // 01 retains the original demo contract identifier and banking example.
            $scenario('01', ['DEMO — Ngân hàng minh họa', 'credit_institution'], '500000000.00', '9.50000', '2026-11-01', '2027-11-01', 'active',
                [['2026-11-02', '300000000.00']],
                [['2026-12-01', '25000000.00', '2300000.00', '0.00']],
                [['2026-11-08', '10000000.00', '500000.00', '0.00']],
                [$policy('collateral', 'DEMO — Bảo hiểm minh họa', 'unverified', '2026-11-01', '2027-11-01', '300000000.00',
                    [['2026-12-01', '1800000.00', '0.00', null]])]),
            // 02: individual, no insurance, partial repayment.
            $scenario('02', ['DEMO — Cá nhân Nguyễn Minh An', 'individual'], '120000000.00', '8.00000', '2026-01-10', '2027-01-10', 'active',
                [['2026-01-12', '80000000.00']],
                [['2026-08-10', '20000000.00', '900000.00', '0.00'], ['2026-12-10', '20000000.00', '600000.00', '0.00']],
                [['2026-08-10', '10000000.00', '900000.00', '0.00']], []),
            // 03: individual, life insurance, partial and full premium examples.
            $scenario('03', ['DEMO — Cá nhân Trần Thu Hà', 'individual'], '180000000.00', '7.50000', '2026-02-01', '2027-02-01', 'active',
                [['2026-02-05', '100000000.00'], ['2026-03-05', '50000000.00']],
                [['2026-09-01', '30000000.00', '1000000.00', '0.00'], ['2026-12-01', '30000000.00', '750000.00', '0.00']],
                [['2026-09-01', '30000000.00', '1000000.00', '0.00']],
                [$policy('life', 'DEMO — Bảo hiểm nhân thọ', 'voluntary', '2026-02-01', '2027-02-01', '180000000.00',
                    [['2026-06-01', '2000000.00', '2000000.00', '2026-06-01'], ['2026-12-01', '2000000.00', '500000.00', '2026-11-05']])]),
            // 04: company, fully disbursed, no insurance.
            $scenario('04', ['DEMO — Công ty Minh Phát', 'company'], '600000000.00', '10.00000', '2026-03-01', '2027-03-01', 'active',
                [['2026-03-03', '400000000.00'], ['2026-04-03', '200000000.00']],
                [['2026-09-01', '150000000.00', '8000000.00', '500000.00'], ['2027-03-01', '450000000.00', '5000000.00', '0.00']],
                [['2026-09-01', '150000000.00', '8000000.00', '500000.00']], []),
            // 05: company, overdue schedule and overdue premium.
            $scenario('05', ['DEMO — Công ty Hoàng Gia', 'company'], '350000000.00', '11.00000', '2026-01-01', '2027-01-01', 'active',
                [['2026-01-05', '250000000.00']],
                [['2026-06-01', '50000000.00', '5000000.00', '0.00'], ['2026-09-01', '50000000.00', '4000000.00', '0.00']],
                [],
                [$policy('loan', 'DEMO — Bảo hiểm khoản vay', 'contractual', '2026-01-01', '2027-01-01', '250000000.00',
                    [['2026-07-01', '3500000.00', '0.00', null]])]),
            // 06: credit institution, several repayment periods, no insurance.
            $scenario('06', ['DEMO — Quỹ tín dụng Hợp Tác', 'credit_institution'], '900000000.00', '9.20000', '2026-01-01', '2027-06-01', 'active',
                [['2026-01-05', '600000000.00']],
                [['2026-04-01', '100000000.00', '12000000.00', '0.00'], ['2026-07-01', '100000000.00', '9000000.00', '0.00'], ['2026-10-01', '100000000.00', '7000000.00', '0.00'], ['2027-01-01', '100000000.00', '5000000.00', '0.00']],
                [['2026-04-01', '100000000.00', '12000000.00', '0.00'], ['2026-07-01', '100000000.00', '9000000.00', '0.00'], ['2026-10-01', '100000000.00', '7000000.00', '0.00']], []),
            // 07: draft with insurance proposal; no disbursement or actual repayment.
            $scenario('07', ['DEMO — Ngân hàng Đầu Tư', 'credit_institution'], '750000000.00', '8.80000', '2026-11-01', '2027-11-01', 'draft',
                [], [['2027-01-01', '100000000.00', '4000000.00', '0.00']], [],
                [$policy('collateral', 'DEMO — Bảo hiểm tài sản', 'unverified', '2026-11-01', '2026-12-01', '750000000.00',
                    [['2026-11-15', '4500000.00', '0.00', null]])]),
            // 08: individual, fully repaid principal, closed only as an illustrative reconciled state.
            $scenario('08', ['DEMO — Cá nhân Lê Quốc Bình', 'individual'], '90000000.00', '6.50000', '2026-01-01', '2026-10-31', 'closed',
                [['2026-01-03', '90000000.00']],
                [['2026-05-01', '45000000.00', '2000000.00', '0.00'], ['2026-10-01', '45000000.00', '1000000.00', '0.00']],
                [['2026-05-01', '45000000.00', '2000000.00', '0.00'], ['2026-10-01', '45000000.00', '1000000.00', '0.00']], []),
        ];
    }
}
