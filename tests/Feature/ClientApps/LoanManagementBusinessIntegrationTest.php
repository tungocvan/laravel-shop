<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\LoanManagement\Database\Seeders\LoanManagementDemoSeeder;
use Modules\LoanManagement\Services\LoanInsuranceService;
use Modules\LoanManagement\Services\LoanContractService;
use Modules\LoanManagement\Services\LoanContractHistoryService;
use Modules\LoanManagement\Services\LoanTransactionService;
use Modules\LoanManagement\Services\LoanInstallmentPaymentService;
use Modules\LoanManagement\Services\LoanReminderService;
use Modules\LoanManagement\Services\LoanReminderHistoryService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Mail;
use Modules\LoanManagement\Jobs\SendLoanReminder;
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
        $allocationsMigration = require base_path('Modules/LoanManagement/database/migrations/2026_11_10_000002_create_loan_payment_allocations_table.php');
        $allocationsMigration->up();
        $remindersMigration = require base_path('Modules/LoanManagement/database/migrations/2026_11_10_000003_create_loan_reminders_tables.php');
        $remindersMigration->up();
        $recipientsMigration = require base_path('Modules/LoanManagement/database/migrations/2026_11_10_000004_add_loan_reminder_recipients.php');
        $recipientsMigration->up();
        $adjustmentsMigration = require base_path('Modules/LoanManagement/database/migrations/2026_11_10_000005_create_loan_schedule_adjustments_table.php');
        $adjustmentsMigration->up();
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


    public function test_future_unpaid_installment_can_be_corrected_with_audit_and_ordering(): void
    {
        $contract = $this->contract('CORRECTION-01');
        DB::table('loan_contracts')->where('id', $contract)->update(['matures_at' => '2028-12-31']);
        $first = DB::table('loan_repayment_schedules')->insertGetId([
            'loan_contract_id' => $contract, 'installment' => 1, 'due_at' => '2027-06-30',
            'principal_due' => '500.00', 'interest_due' => '50.00', 'fees_due' => '0.00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $second = DB::table('loan_repayment_schedules')->insertGetId([
            'loan_contract_id' => $contract, 'installment' => 2, 'due_at' => '2027-11-30',
            'principal_due' => '500.00', 'interest_due' => '50.00', 'fees_due' => '0.00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $service = app(\Modules\LoanManagement\Services\LoanScheduleAdjustmentService::class);
        $data = ['due_at' => '2027-12-15', 'principal_due' => '500.00', 'interest_due' => '55.00', 'fees_due' => '0.00'];
        $service->update($contract, $second, $data, null);
        $this->assertSame('2027-12-15', DB::table('loan_repayment_schedules')->where('id', $second)->value('due_at'));
        $this->assertSame(1, DB::table('loan_schedule_adjustments')->where('loan_repayment_schedule_id', $second)->count());
        $this->assertSame('2027-11-30', json_decode(DB::table('loan_schedule_adjustments')->first()->before_json, true)['due_at']);

        try {
            $service->update($contract, $second, ['due_at' => '2027-06-01'] + $data, null);
            $this->fail('Out-of-order installment should be rejected');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('due_at', $exception->errors());
        }
        DB::table('loan_payment_allocations')->insert([
            'loan_payment_id' => DB::table('loan_payments')->insertGetId([
                'loan_contract_id' => $contract, 'paid_at' => '2026-11-01',
                'principal_paid' => '0.00', 'interest_paid' => '1.00', 'fees_paid' => '0.00',
                'created_at' => now(), 'updated_at' => now(),
            ]),
            'loan_repayment_schedule_id' => $second,
            'principal_allocated' => '0.00', 'interest_allocated' => '1.00', 'fees_allocated' => '0.00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        try {
            $service->update($contract, $second, $data, null);
            $this->fail('Allocated installment should be immutable');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('schedule', $exception->errors());
        }
        $this->assertSame(1, DB::table('loan_schedule_adjustments')->count());
    }

    public function test_eight_percent_rate_is_preserved_exactly_on_create_and_draft_update(): void
    {
        $lender = (int) DB::table('loan_lenders')->insertGetId([
            'name' => 'Rate precision lender', 'type' => 'individual',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $data = [
            'lender_id' => $lender, 'contract_number' => 'RATE-EXACT-8',
            'committed_amount' => '200000000.00', 'annual_rate_percent' => '8.00000',
            'day_count_basis' => 365, 'signed_at' => '2026-11-01',
            'matures_at' => '2027-11-01',
        ];
        $service = app(LoanContractService::class);
        $id = $service->createContract($data);
        $this->assertSame('8', rtrim(rtrim((string) DB::table('loan_contracts')->where('id', $id)->value('annual_rate_percent'), '0'), '.'));
        $service->updateContract($id, $data);
        $this->assertSame('8', rtrim(rtrim((string) DB::table('loan_contracts')->where('id', $id)->value('annual_rate_percent'), '0'), '.'));
    }

    public function test_lender_category_dashboard_counts_and_debt_cover_all_contracts(): void
    {
        $service = app(LoanContractService::class);
        $types = ['individual', 'company', 'credit_institution'];
        $ids = [];
        foreach ($types as $index => $type) {
            $lender = (int) DB::table('loan_lenders')->insertGetId([
                'name' => 'Category '.$type, 'type' => $type,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $ids[$type] = $service->createContract([
                'lender_id' => $lender, 'contract_number' => 'CATEGORY-'.$index,
                'committed_amount' => '1000.00', 'annual_rate_percent' => '8.00000',
                'day_count_basis' => 365, 'signed_at' => '2026-01-01', 'matures_at' => '2027-01-01',
            ]);
        }
        $tx = app(LoanTransactionService::class);
        $tx->disburse($ids['individual'], ['amount' => '500.00', 'disbursed_at' => '2026-02-01']);
        $tx->disburse($ids['company'], ['amount' => '200.00', 'disbursed_at' => '2026-02-01']);
        $tx->pay($ids['individual'], [
            'paid_at' => '2026-02-02', 'principal_paid' => '100.00',
            'interest_paid' => '0.00', 'fees_paid' => '0.00',
        ]);
        $summary = $service->dashboard()['lenderTypeSummary'];
        $this->assertSame(1, $summary['individual']['contractCount']);
        $this->assertSame(1, $summary['company']['contractCount']);
        $this->assertSame(1, $summary['credit_institution']['contractCount']);
        $this->assertSame(0, bccomp('400.00', $summary['individual']['outstanding'], 2));
        $this->assertSame(0, bccomp('200.00', $summary['company']['outstanding'], 2));
        $this->assertSame(0, bccomp('0.00', $summary['credit_institution']['outstanding'], 2));
    }

    public function test_disabling_reminder_skips_a_previously_reserved_job_without_email(): void
    {
        Queue::fake();
        Mail::fake();
        $today = \Carbon\CarbonImmutable::parse('2026-06-03');
        \Illuminate\Support\Facades\Date::setTestNow($today);
        try {
            $id = $this->contract('DISABLED-REMINDER');
            $tx = app(LoanTransactionService::class);
            $tx->disburse($id, ['amount' => '500.00', 'disbursed_at' => '2026-02-01']);
            $tx->schedule($id, ['installment' => 1, 'due_at' => '2026-06-10',
                'principal_due' => '200.00', 'interest_due' => '20.00', 'fees_due' => '0.00']);
            $service = app(LoanReminderService::class);
            $service->save($id, ['enabled' => true, 'recipient_emails' => ['test@example.test'], 'days_before' => [7]]);
            $this->assertSame(1, $service->reserveDueReminders($today));
            $deliveryId = (int) DB::table('loan_reminder_deliveries')->value('id');
            $service->save($id, ['enabled' => false, 'recipient_emails' => ['test@example.test'], 'days_before' => [7]]);
            config()->set('loan_reminders.dispatch_enabled', true);
            (new SendLoanReminder($deliveryId))->handle();
            Mail::assertNothingSent();
            $this->assertSame('skipped', DB::table('loan_reminder_deliveries')->where('id', $deliveryId)->value('status'));
            $this->assertSame(0, $service->reserveDueReminders($today));
        } finally {
            \Illuminate\Support\Facades\Date::setTestNow();
        }
    }

    public function test_recipient_migration_can_resume_without_dropping_existing_data(): void
    {
        $migration = require base_path('Modules/LoanManagement/database/migrations/2026_11_10_000004_add_loan_reminder_recipients.php');
        $migration->up();
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('loan_reminder_recipients'));
        $indexes = collect(\Illuminate\Support\Facades\Schema::getIndexes('loan_reminder_deliveries'));
        $this->assertTrue($indexes->contains(fn (array $index): bool => $index['name'] === 'loan_reminder_delivery_recipient_uq'));
        $this->assertFalse($indexes->contains(fn (array $index): bool => $index['name'] === 'loan_reminder_delivery_once_uq'));
    }

    public function test_reminder_history_is_read_only_and_scoped_to_visible_contracts(): void
    {
        Queue::fake();
        $first = $this->contract('HISTORY-ONE');
        $second = $this->contract('HISTORY-TWO');
        $tx = app(LoanTransactionService::class);
        foreach ([$first, $second] as $id) {
            $tx->disburse($id, ['amount' => '500.00', 'disbursed_at' => '2026-02-01']);
            $tx->schedule($id, ['installment' => 1, 'due_at' => '2026-12-10',
                'principal_due' => '100.00', 'interest_due' => '10.00', 'fees_due' => '0.00']);
            app(LoanReminderService::class)->save($id, [
                'enabled' => true, 'recipient_emails' => ['history@example.test'],
                'days_before' => [7],
            ]);
        }
        $service = app(LoanReminderHistoryService::class);
        $before = $service->forContracts([$first]);
        $this->assertCount(1, $before['upcoming'][$first]);
        $this->assertArrayNotHasKey($second, $before['upcoming']);
        $this->assertEmpty($before['deliveries']);
        $this->assertSame(0, DB::table('loan_reminder_deliveries')->count());

        $schedule = DB::table('loan_repayment_schedules')->where('loan_contract_id', $first)->first();
        DB::table('loan_reminder_deliveries')->insert([
            'loan_repayment_schedule_id' => $schedule->id,
            'days_before' => 7, 'due_at_snapshot' => '2026-12-10',
            'recipient_email' => 'history@example.test', 'status' => 'reserved',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $after = $service->forContracts([$first]);
        $this->assertCount(1, $after['deliveries'][$first]);
        $this->assertSame('reserved', $after['deliveries'][$first][0]->status);
        $this->assertSame(1, DB::table('loan_reminder_deliveries')->count());
        Queue::assertNothingPushed();
    }

    public function test_multiple_recipients_are_deduplicated_and_stale_overdue_jobs_are_skipped(): void
    {
        Queue::fake();
        Mail::fake();
        $id = $this->contract('MULTI-REMINDER');
        $tx = app(LoanTransactionService::class);
        $tx->disburse($id, ['amount' => '500.00', 'disbursed_at' => '2026-02-01']);
        $tx->schedule($id, ['installment' => 1, 'due_at' => '2026-06-10',
            'principal_due' => '200.00', 'interest_due' => '20.00', 'fees_due' => '0.00']);
        $service = app(LoanReminderService::class);
        $service->save($id, ['enabled' => true,
            'recipient_emails' => [' First@Example.test ', 'second@example.test', 'first@example.test'],
            'days_before' => [7, 5, 2, 0]]);
        $this->assertSame(['first@example.test', 'second@example.test'], $service->recipientsForContracts([$id])[$id]);
        $this->assertSame(2, $service->reserveDueReminders(\Carbon\CarbonImmutable::parse('2026-06-03')));
        $this->assertSame(0, $service->reserveDueReminders(\Carbon\CarbonImmutable::parse('2026-06-03')));
        Queue::assertPushed(SendLoanReminder::class, 2);
        $this->assertSame(2, DB::table('loan_reminder_deliveries')->count());

        config()->set('loan_reminders.dispatch_enabled', true);
        $deliveryId = (int) DB::table('loan_reminder_deliveries')->value('id');
        (new SendLoanReminder($deliveryId))->handle();
        $this->assertSame('skipped', DB::table('loan_reminder_deliveries')->where('id', $deliveryId)->value('status'));
        Mail::assertNothingSent();

        $service->save($id, ['enabled' => false,
            'recipient_emails' => ['second@example.test'], 'days_before' => [7]]);
        $this->assertSame(['second@example.test'], $service->recipientsForContracts([$id])[$id]);
        $this->assertSame(0, $service->reserveDueReminders(\Carbon\CarbonImmutable::parse('2026-06-05')));
    }

    public function test_opt_in_reminders_respect_offsets_partial_payment_and_deduplication(): void
    {
        Queue::fake();
        $id = $this->contract('REMINDER-01');
        $tx = app(LoanTransactionService::class);
        $tx->disburse($id, ['amount' => '500.00', 'disbursed_at' => '2026-02-01']);
        $tx->schedule($id, [
            'installment' => 1, 'due_at' => '2026-06-10',
            'principal_due' => '200.00', 'interest_due' => '20.00', 'fees_due' => '0.00',
        ]);
        $service = app(LoanReminderService::class);
        $service->save($id, ['enabled' => true, 'recipient_emails' => ['demo@example.test'], 'days_before' => [7, 5, 2, 0]]);
        $this->assertSame(1, $service->reserveDueReminders(\Carbon\CarbonImmutable::parse('2026-06-03')));
        $this->assertSame(0, $service->reserveDueReminders(\Carbon\CarbonImmutable::parse('2026-06-03')));
        Queue::assertPushed(SendLoanReminder::class, 1);
        $scheduleId = (int) DB::table('loan_repayment_schedules')->where('loan_contract_id', $id)->value('id');
        app(LoanInstallmentPaymentService::class)->record($id, $scheduleId, [
            'paid_at' => '2026-06-04', 'principal_paid' => '100.00', 'interest_paid' => '20.00', 'fees_paid' => '0.00',
        ]);
        $this->assertSame(1, $service->reserveDueReminders(\Carbon\CarbonImmutable::parse('2026-06-05')));
        app(LoanInstallmentPaymentService::class)->record($id, $scheduleId, [
            'paid_at' => '2026-06-06', 'principal_paid' => '100.00', 'interest_paid' => '0.00', 'fees_paid' => '0.00',
        ]);
        $this->assertSame(0, $service->reserveDueReminders(\Carbon\CarbonImmutable::parse('2026-06-08')));
        $this->assertSame(0, $service->reserveDueReminders(\Carbon\CarbonImmutable::parse('2026-06-10')));
        $this->assertSame(2, DB::table('loan_reminder_deliveries')->count());
        Queue::assertPushed(SendLoanReminder::class, 2);
    }

    public function test_installment_payment_allocates_partial_and_rejects_overpayment_atomically(): void
    {
        $id = $this->contract('INSTALLMENT-01');
        $tx = app(LoanTransactionService::class);
        $tx->disburse($id, ['amount' => '500.00', 'disbursed_at' => '2026-02-01']);
        $tx->schedule($id, [
            'installment' => 1, 'due_at' => '2026-06-01',
            'principal_due' => '200.00', 'interest_due' => '20.00', 'fees_due' => '5.00',
        ]);
        $scheduleId = (int) DB::table('loan_repayment_schedules')->where('loan_contract_id', $id)->value('id');
        $service = app(LoanInstallmentPaymentService::class);
        $service->record($id, $scheduleId, [
            'paid_at' => '2026-03-01', 'principal_paid' => '100.00',
            'interest_paid' => '10.00', 'fees_paid' => '0.00', 'reference' => 'PART-1',
        ]);
        $allocated = $service->forSchedules([$scheduleId]);
        $this->assertSame('100', (string) (int) $allocated[$scheduleId]->principal);
        $this->assertSame('10', (string) (int) $allocated[$scheduleId]->interest);

        $before = DB::table('loan_payments')->count();
        try {
            $service->record($id, $scheduleId, [
                'paid_at' => '2026-03-02', 'principal_paid' => '101.00',
                'interest_paid' => '0.00', 'fees_paid' => '0.00',
            ]);
            $this->fail('Overpayment of a scheduled component must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('principal_paid', $exception->errors());
        }
        $this->assertSame($before, DB::table('loan_payments')->count());

        $service->record($id, $scheduleId, [
            'paid_at' => '2026-03-02', 'principal_paid' => '100.00',
            'interest_paid' => '10.00', 'fees_paid' => '5.00',
        ]);
        $this->assertSame(2, DB::table('loan_payment_allocations')->where('loan_repayment_schedule_id', $scheduleId)->count());
        $this->assertSame('200', (string) (int) $service->forSchedules([$scheduleId])[$scheduleId]->principal);
        $this->assertSame(2, DB::table('loan_payments')->where('loan_contract_id', $id)->count());
    }

    public function test_legacy_unallocated_payments_are_not_automatically_matched_to_installments(): void
    {
        $id = $this->contract('LEGACY-PAYMENT');
        $tx = app(LoanTransactionService::class);
        $tx->disburse($id, ['amount' => '500.00', 'disbursed_at' => '2026-02-01']);
        $tx->schedule($id, ['installment' => 1, 'due_at' => '2026-06-01', 'principal_due' => '200.00', 'interest_due' => '10.00', 'fees_due' => '0.00']);
        $tx->pay($id, ['paid_at' => '2026-03-01', 'principal_paid' => '100.00', 'interest_paid' => '10.00', 'fees_paid' => '0.00']);
        $this->assertSame([], app(LoanInstallmentPaymentService::class)->forSchedules([(int) DB::table('loan_repayment_schedules')->where('loan_contract_id', $id)->value('id')]));
        $this->assertSame(1, DB::table('loan_payments')->count());
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
    public function test_demo_delete_removes_scenarios_and_preserves_unrelated_contract_and_shared_lender(): void
    {
        (new LoanManagementDemoSeeder())->run();
        $demo = DB::table('loan_contracts')->where('contract_number', 'DEMO-LOAN-20261110-01')->first();
        $realId = $this->contract('REAL-SURVIVES-RESET');
        DB::table('loan_contracts')->where('id', $realId)->update(['lender_id' => $demo->lender_id]);
        $scheduleId = DB::table('loan_repayment_schedules')->where('loan_contract_id', $demo->id)->value('id');
        $paymentId = DB::table('loan_payments')->where('loan_contract_id', $demo->id)->value('id');
        DB::table('loan_payment_allocations')->insert([
            'loan_payment_id' => $paymentId, 'loan_repayment_schedule_id' => $scheduleId,
            'principal_allocated' => '1.00', 'interest_allocated' => '0.00', 'fees_allocated' => '0.00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('loan_schedule_adjustments')->insert([
            'loan_repayment_schedule_id' => $scheduleId, 'actor_id' => null,
            'before_json' => '{}', 'after_json' => '{}', 'created_at' => now(),
        ]);
        DB::table('loan_reminder_settings')->insert([
            'loan_contract_id' => $demo->id, 'enabled' => false,
            'recipient_email' => 'demo@example.test', 'days_before' => '[7]',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('loan_reminder_recipients')->insert([
            'loan_contract_id' => $demo->id, 'email' => 'demo@example.test',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('loan_reminder_deliveries')->insert([
            'loan_repayment_schedule_id' => $scheduleId, 'days_before' => 7,
            'due_at_snapshot' => '2026-12-01', 'recipient_email' => 'demo@example.test',
            'status' => 'skipped', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $reset = app(\Modules\LoanManagement\Services\LoanDemoResetService::class);
        $this->assertSame(8, $reset->preview()['contracts']);
        $this->assertSame(1, $reset->preview()['schedule_adjustments']);
        $reset->deleteDemo();

        $this->assertSame(0, DB::table('loan_contracts')->where('contract_number', 'like', 'DEMO-LOAN-20261110-%')->count());
        $this->assertSame(1, DB::table('loan_contracts')->where('id', $realId)->count());
        $this->assertSame((int) $demo->lender_id, (int) DB::table('loan_contracts')->where('id', $realId)->value('lender_id'));
        $this->assertSame(0, DB::table('loan_payment_allocations')->count());
        $this->assertSame(0, DB::table('loan_schedule_adjustments')->count());
        $this->assertSame(0, DB::table('loan_reminder_deliveries')->count());
        $this->assertSame(0, DB::table('loan_reminder_recipients')->count());
        $reset->deleteDemo();
        $this->assertSame(1, DB::table('loan_contracts')->count());
    }

    public function test_demo_reset_refuses_cross_contract_payment_allocations_without_mutation(): void
    {
        (new LoanManagementDemoSeeder())->run();
        $demoSchedule = DB::table('loan_repayment_schedules')->whereIn('loan_contract_id',
            DB::table('loan_contracts')->where('contract_number', 'DEMO-LOAN-20261110-01')->select('id')
        )->value('id');
        $real = $this->contract('REAL-CROSS-LINK');
        $realPayment = DB::table('loan_payments')->insertGetId([
            'loan_contract_id' => $real, 'paid_at' => '2026-11-01',
            'principal_paid' => '1.00', 'interest_paid' => '0.00', 'fees_paid' => '0.00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('loan_payment_allocations')->insert([
            'loan_payment_id' => $realPayment, 'loan_repayment_schedule_id' => $demoSchedule,
            'principal_allocated' => '1.00', 'interest_allocated' => '0.00', 'fees_allocated' => '0.00',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        try {
            app(\Modules\LoanManagement\Services\LoanDemoResetService::class)->deleteDemo();
            $this->fail('Cross-contract allocation must block reset.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('cross-contract', $exception->getMessage());
        }
        $this->assertSame(8, DB::table('loan_contracts')->where('contract_number', 'like', 'DEMO-LOAN-20261110-%')->count());
        $this->assertSame(1, DB::table('loan_payment_allocations')->count());
    }

    public function test_demo_delete_command_requires_interactive_confirmation(): void
    {
        (new LoanManagementDemoSeeder())->run();
        $code = \Illuminate\Support\Facades\Artisan::call('loans:demo-seed', [
            '--delete' => true, '--no-interaction' => true,
        ]);
        $this->assertSame(1, $code);
        $this->assertSame(8, DB::table('loan_contracts')->where('contract_number', 'like', 'DEMO-LOAN-20261110-%')->count());
    }


    public function test_full_loan_data_clean_removes_real_and_demo_records_without_reseeding(): void
    {
        (new LoanManagementDemoSeeder())->run();
        $this->contract('REAL-LOAN-CLEAN-ME');
        $this->assertSame(9, DB::table('loan_contracts')->count());

        $cleaner = app(\Modules\LoanManagement\\Services\\LoanDataCleanService::class);
        $before = $cleaner->preview();
        $this->assertSame(9, $before['loan_contracts']);
        $this->assertGreaterThan(0, $before['loan_lenders']);
        $this->assertGreaterThan(0, $before['loan_repayment_schedules']);

        $cleaner->deleteAll();
        foreach ($cleaner->preview() as $table => $count) {
            $this->assertSame(0, $count, "Expected {$table} to be empty after full cleanup");
        }

        $cleaner->deleteAll();
        $this->assertSame(0, DB::table('loan_contracts')->count());
        $this->assertSame(0, DB::table('loan_lenders')->count());
    }

    public function test_full_loan_data_clean_requires_explicit_all_and_interactive_confirmation(): void
    {
        $this->contract('REAL-LOAN-MUST-SURVIVE-CANCEL');
        $this->assertSame(1, \Illuminate\Support\\Facades\\Artisan::call('loans:data-clean'));
        $this->assertSame(1, \Illuminate\Support\\Facades\\Artisan::call('loans:data-clean', [
            '--all' => true, '--no-interaction' => true,
        ]));
        $this->assertSame(1, DB::table('loan_contracts')->count());
    }

}
