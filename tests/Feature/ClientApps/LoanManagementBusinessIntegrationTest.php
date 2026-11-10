<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\LoanManagement\Database\Seeders\LoanManagementDemoSeeder;
use Modules\LoanManagement\Services\LoanInsuranceService;
use Modules\LoanManagement\Services\LoanContractService;
use Modules\LoanManagement\Services\LoanContractHistoryService;
use Modules\LoanManagement\Services\LoanTransactionService;
use Modules\LoanManagement\Services\LoanScheduleGenerator;
use Tests\TestCase;

final class LoanManagementBusinessIntegrationTest extends TestCase
{
    private string $previousConnection;
    protected function setUp(): void
    {
        parent::setUp();

        $this->previousConnection = DB::getDefaultConnection();

        // Fail closed: these tests must never use the app's configured MySQL database.
        config()->set('database.connections.loan_management_test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('loan_management_test');
        DB::setDefaultConnection('loan_management_test');
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        $migration = require base_path('Modules/LoanManagement/database/migrations/2026_11_10_000001_create_loan_management_tables.php');
        $migration->up();
    }

    protected function tearDown(): void
    {
        DB::setDefaultConnection($this->previousConnection);
        DB::disconnect('loan_management_test');
        parent::tearDown();
    }

    private function contract(string $number = 'INTEGRATION-LOAN-01', string $committed = '1000.00'): int
    {
        $lender = DB::table('loan_lenders')->insertGetId([
            'name' => 'Integration lender', 'type' => 'company',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return (int) DB::table('loan_contracts')->insertGetId([
            'lender_id' => $lender, 'contract_number' => $number,
            'committed_amount' => $committed, 'annual_rate_percent' => '10.00000',
            'day_count_basis' => 365, 'signed_at' => '2026-01-01',
            'matures_at' => '2027-01-01', 'status' => 'draft',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_guided_schedule_for_individual_company_and_credit_lenders_is_forecast_only(): void
    {
        foreach (['individual', 'company', 'credit_institution'] as $type) {
            $lenderId = DB::table('loan_lenders')->insertGetId([
                'name' => 'Wizard '.$type, 'type' => $type, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $id = app(LoanContractService::class)->createContractWithOptionalSchedule([
                'lender_id' => $lenderId, 'contract_number' => 'WIZARD-'.$type,
                'committed_amount' => '100000000.00', 'annual_rate_percent' => '8.00000',
                'day_count_basis' => 365, 'signed_at' => '2027-01-01',
                'matures_at' => '2028-01-01',
            ], 'interest_monthly');
            $rows = DB::table('loan_repayment_schedules')->where('loan_contract_id', $id)->orderBy('installment')->get();
            $this->assertCount(12, $rows);
            $this->assertSame('2028-01-01', $rows->last()->due_at);
            $this->assertSame('100000000', (string) (int) $rows->last()->principal_due);
            $this->assertSame('0', (string) (int) $rows->first()->principal_due);
            $this->assertSame(0, DB::table('loan_disbursements')->where('loan_contract_id', $id)->count());
            $this->assertSame(0, DB::table('loan_payments')->where('loan_contract_id', $id)->count());
            $this->assertSame('draft', DB::table('loan_contracts')->where('id', $id)->value('status'));
        }
    }

    public function test_maturity_schedule_and_invalid_modes_are_atomic(): void
    {
        $lenderId = DB::table('loan_lenders')->insertGetId([
            'name' => 'Maturity lender', 'type' => 'company', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $data = [
            'lender_id' => $lenderId, 'contract_number' => 'WIZARD-MATURITY',
            'committed_amount' => '100000000.00', 'annual_rate_percent' => '8.00000',
            'day_count_basis' => 365, 'signed_at' => '2027-01-01', 'matures_at' => '2028-01-01',
        ];
        $service = app(LoanContractService::class);
        $id = $service->createContractWithOptionalSchedule($data, 'all_at_maturity');
        $rows = DB::table('loan_repayment_schedules')->where('loan_contract_id', $id)->get();
        $this->assertCount(1, $rows);
        $this->assertSame('2028-01-01', $rows->first()->due_at);
        $this->assertSame('100000000', (string) (int) $rows->first()->principal_due);
        $this->assertSame('8000000', (string) (int) $rows->first()->interest_due);

        $data['contract_number'] = 'WIZARD-INVALID';
        try {
            $service->createContractWithOptionalSchedule($data, 'invalid');
            $this->fail('Invalid repayment mode must rollback the draft.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('repayment_mode', $exception->errors());
        }
        $this->assertFalse(DB::table('loan_contracts')->where('contract_number', 'WIZARD-INVALID')->exists());

        try {
            app(LoanScheduleGenerator::class)->create($id, 'all_at_maturity');
            $this->fail('Duplicate schedule generation must fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('repayment_mode', $exception->errors());
        }
    }

    public function test_individual_lender_cannot_create_new_insurance_but_history_remains_untouched(): void
    {
        $id = $this->contract('PERSONAL-NO-INSURANCE');
        $lenderId = DB::table('loan_contracts')->where('id', $id)->value('lender_id');
        DB::table('loan_lenders')->where('id', $lenderId)->update(['type' => 'individual']);
        try {
            app(LoanInsuranceService::class)->createPolicy($id, [
                'type' => 'life', 'provider' => 'Example insurer',
                'policy_number' => 'PERSONAL-POLICY-NEW',
                'starts_at' => '2026-02-01', 'ends_at' => '2027-01-01',
                'requirement' => 'voluntary', 'insured_amount' => '1000.00',
            ]);
            $this->fail('Individual lender must not accept new insurance.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('contract', $exception->errors());
        }
        $this->assertSame(0, DB::table('loan_insurance_policies')->count());
    }

    public function test_contract_history_reads_actual_transactions_without_inventing_schedule_settlement(): void
    {
        $id = $this->contract('HISTORY-01');
        $transactions = app(LoanTransactionService::class);
        $transactions->disburse($id, ['amount' => '500.00', 'disbursed_at' => '2026-02-01', 'reference' => 'DIS-1']);
        $transactions->schedule($id, [
            'installment' => 1, 'due_at' => '2026-06-01',
            'principal_due' => '250.00', 'interest_due' => '10.00', 'fees_due' => '0.00',
        ]);
        $transactions->pay($id, [
            'paid_at' => '2026-03-01', 'principal_paid' => '100.00',
            'interest_paid' => '5.00', 'fees_paid' => '2.00', 'reference' => 'PAY-1',
        ]);
        $history = app(LoanContractHistoryService::class)->forContracts([$id]);
        $this->assertCount(1, $history['disbursements'][$id]);
        $this->assertCount(1, $history['schedules'][$id]);
        $this->assertCount(1, $history['payments'][$id]);
        $this->assertSame('100.00', $history['totals'][$id]['principal']);
        $this->assertSame('5.00', $history['totals'][$id]['interest']);
        $this->assertSame('2.00', $history['totals'][$id]['fees']);
        $this->assertSame('107.00', $history['totals'][$id]['paid']);
        $this->assertSame([], app(LoanContractHistoryService::class)->forContracts([])['payments']);
    }

    public function test_draft_can_be_deleted_only_without_related_records(): void
    {
        $service = app(LoanContractService::class);
        $empty = $this->contract('EMPTY-DRAFT');
        $service->deleteDraft($empty);
        $this->assertFalse(DB::table('loan_contracts')->where('id', $empty)->exists());

        $scheduled = $this->contract('SCHEDULED-DRAFT');
        DB::table('loan_repayment_schedules')->insert([
            'loan_contract_id' => $scheduled, 'installment' => 1, 'due_at' => '2026-06-01',
            'principal_due' => '100.00', 'interest_due' => '0.00', 'fees_due' => '0.00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        try {
            $service->deleteDraft($scheduled);
            $this->fail('Scheduled draft must not be deleted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('contract', $exception->errors());
        }
        $this->assertTrue(DB::table('loan_contracts')->where('id', $scheduled)->exists());
    }

    public function test_restore_to_draft_requires_no_related_records_and_eligible_status(): void
    {
        $service = app(LoanContractService::class);
        $active = $this->contract('EMPTY-ACTIVE');
        DB::table('loan_contracts')->where('id', $active)->update(['status' => 'active']);
        $service->restoreDraft($active);
        $this->assertSame('draft', DB::table('loan_contracts')->where('id', $active)->value('status'));

        $closed = $this->contract('CLOSED-NO-RESTORE');
        DB::table('loan_contracts')->where('id', $closed)->update(['status' => 'closed']);
        try {
            $service->restoreDraft($closed);
            $this->fail('Closed contract cannot be restored.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('contract', $exception->errors());
        }

        $cancelled = $this->contract('CANCELLED-RESTORE');
        DB::table('loan_contracts')->where('id', $cancelled)->update(['status' => 'cancelled']);
        $service->restoreDraft($cancelled);
        $this->assertSame('draft', DB::table('loan_contracts')->where('id', $cancelled)->value('status'));

        $disbursed = $this->contract('DISBURSED-ACTIVE');
        app(LoanTransactionService::class)->disburse($disbursed, ['amount' => '100.00', 'disbursed_at' => '2026-02-01']);
        try {
            $service->restoreDraft($disbursed);
            $this->fail('Disbursed active contract must not be restored.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('contract', $exception->errors());
        }
        $this->assertSame('active', DB::table('loan_contracts')->where('id', $disbursed)->value('status'));
    }

    public function test_disbursement_caps_commitment_and_activates_contract(): void
    {
        $id = $this->contract();
        $service = app(LoanTransactionService::class);
        $service->disburse($id, ['amount' => '700.00', 'disbursed_at' => '2026-02-01']);
        $this->assertSame('active', DB::table('loan_contracts')->where('id', $id)->value('status'));
        try {
            $service->disburse($id, ['amount' => '300.01', 'disbursed_at' => '2026-02-02']);
            $this->fail('Over-limit disbursement should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('amount', $exception->errors());
        }
        $this->assertSame(1, DB::table('loan_disbursements')->where('loan_contract_id', $id)->count());
    }

    public function test_payments_cannot_exceed_actual_disbursed_principal(): void
    {
        $id = $this->contract();
        $service = app(LoanTransactionService::class);
        $service->disburse($id, ['amount' => '300.00', 'disbursed_at' => '2026-02-01']);
        $service->pay($id, ['paid_at' => '2026-03-01', 'principal_paid' => '100.00', 'interest_paid' => '5.00', 'fees_paid' => '0.00']);
        try {
            $service->pay($id, ['paid_at' => '2026-03-02', 'principal_paid' => '200.01', 'interest_paid' => '0.00', 'fees_paid' => '0.00']);
            $this->fail('Overpayment should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('principal_paid', $exception->errors());
        }
        $this->assertSame(1, DB::table('loan_payments')->where('loan_contract_id', $id)->count());
    }

    public function test_repayment_schedule_rejects_duplicate_installment_and_excess_principal(): void
    {
        $id = $this->contract();
        $service = app(LoanTransactionService::class);
        $schedule = ['installment' => 1, 'due_at' => '2026-05-01', 'principal_due' => '700.00', 'interest_due' => '5.00', 'fees_due' => '0.00'];
        $service->schedule($id, $schedule);
        try {
            $service->schedule($id, $schedule);
            $this->fail('Duplicate installment should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('installment', $exception->errors());
        }
        try {
            $service->schedule($id, array_merge($schedule, ['installment' => 2, 'principal_due' => '300.01']));
            $this->fail('Excess scheduled principal should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('principal_due', $exception->errors());
        }
        $this->assertSame(1, DB::table('loan_repayment_schedules')->count());
    }

    public function test_insurance_partial_payment_and_overpayment_guard(): void
    {
        $id = $this->contract();
        $service = app(LoanInsuranceService::class);
        $policy = $service->createPolicy($id, [
            'type' => 'collateral', 'provider' => 'Integration insurer',
            'policy_number' => 'POLICY-1', 'starts_at' => '2026-01-01',
            'ends_at' => '2027-01-01', 'requirement' => 'voluntary',
        ]);
        $premium = $service->createPremium($policy, ['due_at' => '2026-06-01', 'amount_due' => '120.00']);
        $service->recordPremiumPayment($premium, ['amount' => '50.00', 'paid_at' => '2026-06-01']);
        try {
            $service->recordPremiumPayment($premium, ['amount' => '70.01', 'paid_at' => '2026-06-02']);
            $this->fail('Insurance premium overpayment should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('amount', $exception->errors());
        }
        $this->assertEquals(50, DB::table('loan_insurance_premium_schedules')->where('id', $premium)->value('amount_paid'));
        $service->recordPremiumPayment($premium, ['amount' => '70.00', 'paid_at' => '2026-06-02']);
        $this->assertEquals(120, DB::table('loan_insurance_premium_schedules')->where('id', $premium)->value('amount_paid'));
    }

    public function test_demo_seeder_is_repeatable_without_duplicate_records(): void
    {
        (new LoanManagementDemoSeeder())->run();
        (new LoanManagementDemoSeeder())->run();
        $this->assertSame(1, DB::table('loan_contracts')->where('contract_number', 'DEMO-LOAN-20261110-01')->count());
        $this->assertSame(8, DB::table('loan_contracts')->where('contract_number', 'like', 'DEMO-LOAN-20261110-%')->count());
        $this->assertSame(8, DB::table('loan_lenders')->count());
        $this->assertSame(4, DB::table('loan_insurance_policies')->count());
        $this->assertSame(5, DB::table('loan_insurance_premium_schedules')->count());
        $this->assertSame(9, DB::table('loan_disbursements')->count());
        $this->assertSame(9, DB::table('loan_payments')->count());
        $this->assertSame(16, DB::table('loan_repayment_schedules')->count());
        $this->assertEqualsCanonicalizing(
            ['individual', 'company', 'credit_institution'],
            DB::table('loan_lenders')->distinct()->pluck('type')->all()
        );
        $this->assertSame(1, DB::table('loan_contracts')->where('status', 'draft')->count());
        $this->assertSame(1, DB::table('loan_contracts')->where('status', 'closed')->count());
    }

    public function test_demo_financial_invariants_and_insurance_variants(): void
    {
        (new LoanManagementDemoSeeder())->run();
        $contracts = DB::table('loan_contracts')->get();
        foreach ($contracts as $contract) {
            $disbursed = (string) DB::table('loan_disbursements')->where('loan_contract_id', $contract->id)->sum('amount');
            $repaid = (string) DB::table('loan_payments')->where('loan_contract_id', $contract->id)->sum('principal_paid');
            $scheduled = (string) DB::table('loan_repayment_schedules')->where('loan_contract_id', $contract->id)->sum('principal_due');
            $this->assertLessThanOrEqual(0, bccomp($disbursed, (string) $contract->committed_amount, 2));
            $this->assertLessThanOrEqual(0, bccomp($repaid, $disbursed, 2));
            $this->assertLessThanOrEqual(0, bccomp($scheduled, (string) $contract->committed_amount, 2));
        }
        $this->assertSame(4, DB::table('loan_contracts')->whereIn('id', DB::table('loan_insurance_policies')->select('loan_contract_id'))->count());
        $this->assertSame(4, DB::table('loan_contracts')->whereNotIn('id', DB::table('loan_insurance_policies')->select('loan_contract_id'))->count());
        $this->assertSame(1, DB::table('loan_insurance_premium_schedules')->whereColumn('amount_due', 'amount_paid')->count());
        $this->assertSame(1, DB::table('loan_insurance_premium_schedules')->where('amount_paid', '>', 0)->whereColumn('amount_paid', '<', 'amount_due')->count());
        $this->assertSame(3, DB::table('loan_insurance_premium_schedules')->where('amount_paid', 0)->count());
        $closed = DB::table('loan_contracts')->where('status', 'closed')->first();
        $this->assertNotNull($closed);
        $this->assertEquals(
            DB::table('loan_disbursements')->where('loan_contract_id', $closed->id)->sum('amount'),
            DB::table('loan_payments')->where('loan_contract_id', $closed->id)->sum('principal_paid')
        );
    }

    public function test_demo_seed_does_not_modify_existing_non_demo_contract(): void
    {
        $id = $this->contract('REAL-CONTRACT-DO-NOT-TOUCH', '1000.00');
        $before = DB::table('loan_contracts')->where('id', $id)->first();
        (new LoanManagementDemoSeeder())->run();
        (new LoanManagementDemoSeeder())->run();
        $this->assertEquals($before, DB::table('loan_contracts')->where('id', $id)->first());
        $this->assertSame(1, DB::table('loan_contracts')->where('contract_number', 'REAL-CONTRACT-DO-NOT-TOUCH')->count());
    }
}
