<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\LoanManagement\Database\Seeders\LoanManagementDemoSeeder;
use Modules\LoanManagement\Services\LoanInsuranceService;
use Modules\LoanManagement\Services\LoanTransactionService;
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
        $this->assertSame(4, DB::table('loan_insurance_premium_schedules')->count());
        $this->assertSame(10, DB::table('loan_disbursements')->count());
        $this->assertSame(9, DB::table('loan_payments')->count());
        $this->assertSame(14, DB::table('loan_repayment_schedules')->count());
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
        $this->assertSame(2, DB::table('loan_insurance_premium_schedules')->where('amount_paid', 0)->count());
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
