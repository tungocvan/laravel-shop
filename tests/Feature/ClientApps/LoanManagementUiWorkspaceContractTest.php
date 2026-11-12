<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

/**
 * Contract for the current split LoanManagement workspaces.
 * The executive dashboard is navigation/summary only; mutation forms live
 * in dedicated views and are still authorized in their controllers.
 */
final class LoanManagementUiWorkspaceContractTest extends TestCase
{
    private function view(string $name): string
    {
        $source = file_get_contents(base_path(
            'Modules/ClientPortal/resources/views/applications/loans/'.$name.'.blade.php'
        ));
        $this->assertIsString($source);

        return $source;
    }

    private function source(string $path): string
    {
        $source = file_get_contents(base_path($path));
        $this->assertIsString($source);

        return $source;
    }

    public function test_executive_dashboard_is_scoped_and_links_to_dedicated_workspaces(): void
    {
        $dashboard = $this->view('dashboard');
        $this->assertStringContainsString('data-loan-executive-dashboard', $dashboard);
        $this->assertStringContainsString('aria-label="Điều hướng khoản vay"', $dashboard);
        foreach (['contracts.index', 'payments.index', 'reminders.index', 'insurance.index'] as $route) {
            $this->assertStringContainsString('client.loans.'.$route, $dashboard);
        }
        $this->assertStringNotContainsString('client.loans.payments.store', $dashboard);
        $this->assertStringNotContainsString('client.loans.insurance.store', $dashboard);
    }

    public function test_lenders_and_contract_create_are_separate_permission_scoped_workspaces(): void
    {
        $lenders = $this->view('lenders');
        $wizard = $this->view('contract-create');
        $controller = $this->source('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanWorkspaceController.php');
        $this->assertStringContainsString('data-loan-lenders-workspace', $lenders);
        $this->assertStringContainsString("@can('client.loans.contracts.create')", $lenders);
        $this->assertStringContainsString('client.loans.lenders.store', $lenders);
        foreach (['data-loan-wizard', 'data-loan-lender-type', 'data-loan-wizard-preview', 'client.loans.contracts.store'] as $fragment) {
            $this->assertStringContainsString($fragment, $wizard);
        }
        $this->assertStringContainsString("'decimal:0,2'", $controller);
    }

    public function test_schedule_mutations_keep_domain_validation_and_permission_guards(): void
    {
        $routes = $this->source('Modules/ClientPortal/Applications/LoanManagement/routes.php');
        $controller = $this->source('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanWorkspaceController.php');
        $operations = $this->view('partials/contract-operations');
        $service = $this->source('Modules/LoanManagement/Services/LoanScheduleAdjustmentService.php');
        $this->assertStringContainsString("name('schedules.update')", $routes);
        $this->assertStringContainsString("can('client.loans.contracts.edit')", $controller);
        $this->assertStringContainsString('client.loans.schedules.update', $operations);
        $this->assertStringContainsString('loan_payment_allocations', $service);
        $this->assertStringContainsString('loan_schedule_adjustments', $service);
        $this->assertStringContainsString('loan_reminder_deliveries', $service);
    }

    public function test_contract_detail_holds_mutations_outside_the_dashboard(): void
    {
        $detail = $this->view('contract-detail');
        $operations = $this->view('partials/contract-operations');
        $this->assertStringContainsString('data-loan-contract-detail', $detail);
        $this->assertStringContainsString('client.loans.contracts.update', $detail);
        $this->assertStringContainsString('client.loans.contracts.destroy', $detail);
        $this->assertStringContainsString('client.loans.contracts.restore-draft', $detail);
        $this->assertStringContainsString('partials.contract-operations', $detail);
        foreach (['client.loans.disbursements.store', 'client.loans.schedules.store', 'client.loans.payments.store'] as $route) {
            $this->assertStringContainsString($route, $operations);
        }
    }

    public function test_reminder_dispatch_is_opt_in_and_queue_job_is_idempotent(): void
    {
        $config = $this->source('config/loan_reminders.php');
        $job = $this->source('Modules/LoanManagement/Jobs/SendLoanReminder.php');
        $this->assertStringContainsString("env('LOAN_REMINDERS_DISPATCH_ENABLED', false)", $config);
        $this->assertStringContainsString('->lockForUpdate()->first()', $job);
        $this->assertStringContainsString('DB::transaction(function (): void', $job);
    }

    public function test_reminder_history_is_read_only_and_tracks_delivery_states(): void
    {
        $history = $this->source('Modules/LoanManagement/Services/LoanReminderHistoryService.php');
        $controller = $this->source('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanApplicationController.php');
        $this->assertStringContainsString('loan_reminder_deliveries', $history);
        $this->assertStringContainsString('loan_reminder_recipients', $history);
        $this->assertStringNotContainsString('->insert(', $history);
        $this->assertStringContainsString('LoanReminderHistoryService', $controller);
    }

    public function test_reminder_recipient_controls_and_stale_queue_guards_remain_present(): void
    {
        $operations = $this->view('partials/contract-operations');
        $controller = $this->source('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanReminderController.php');
        $job = $this->source('Modules/LoanManagement/Jobs/SendLoanReminder.php');
        $this->assertStringContainsString('recipient_emails[]', $operations);
        $this->assertStringContainsString('Thêm Email', $operations);
        $this->assertStringContainsString('cloneNode(true)', $operations);
        $this->assertStringContainsString('recipient_emails.*', $controller);
        $this->assertStringContainsString('recipientActive', $job);
        $this->assertStringContainsString('expectedSendDate', $job);
    }

    public function test_installment_and_insurance_forms_are_in_contract_operations(): void
    {
        $operations = $this->view('partials/contract-operations');
        $routes = $this->source('Modules/ClientPortal/Applications/LoanManagement/routes.php');
        foreach (['client.loans.installments.pay', 'client.loans.insurance.store',
            'client.loans.premiums.store', 'client.loans.premiums.pay'] as $route) {
            $this->assertStringContainsString($route, $operations);
        }
        $this->assertStringContainsString("whereNumber('schedule')", $routes);
        $this->assertStringContainsString('<x-pwa-date', $operations);
    }

    public function test_wizard_is_forecast_only_and_uses_domain_calculator(): void
    {
        $wizard = $this->view('contract-create');
        $controller = $this->source('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanWorkspaceController.php');
        $service = $this->source('Modules/LoanManagement/Services/LoanScheduleGenerator.php');
        foreach (['data-loan-wizard', 'data-loan-lender-type', 'data-loan-wizard-preview'] as $fragment) {
            $this->assertStringContainsString($fragment, $wizard);
        }
        $this->assertStringContainsString('createContractWithOptionalSchedule', $controller);
        $this->assertStringContainsString('LoanInterestCalculator', $service);
        $this->assertStringNotContainsString("DB::table('loan_payments')->insert", $service);
        $this->assertStringNotContainsString("DB::table('loan_disbursements')->insert", $service);
    }

    public function test_contract_filters_and_destructive_actions_remain_separated(): void
    {
        $contracts = $this->view('contracts');
        $detail = $this->view('contract-detail');
        $controller = $this->source('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanApplicationController.php');
        foreach (['name="search"', 'name="type"', 'name="status"'] as $filter) {
            $this->assertStringContainsString($filter, $contracts);
        }
        $this->assertStringContainsString('data-contract-delete', $detail);
        $this->assertStringContainsString('data-contract-restore', $detail);
        $this->assertStringContainsString('insuredContractIds', $controller);
        $this->assertStringContainsString('visibleContracts', $controller);
    }

    public function test_insurance_and_payment_navigation_remains_permission_scoped(): void
    {
        $dashboard = $this->view('dashboard');
        $operations = $this->view('partials/contract-operations');
        $this->assertStringContainsString("client.loans.insurance.view", $dashboard);
        $this->assertStringContainsString('client.loans.contracts.create', $dashboard);
        $this->assertStringContainsString('client.loans.payments.store', $operations);
        $this->assertStringContainsString('client.loans.insurance.store', $operations);
    }
}
