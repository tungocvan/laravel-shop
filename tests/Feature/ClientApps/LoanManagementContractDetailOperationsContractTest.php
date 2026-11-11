<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class LoanManagementContractDetailOperationsContractTest extends TestCase
{
    public function test_detail_uses_existing_canonical_mutation_routes_and_permission_guards(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/partials/contract-operations.blade.php'));
        $detail = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/contract-detail.blade.php'));
        foreach ([
            'client.loans.disbursements.store',
            'client.loans.schedules.store',
            'client.loans.schedules.update',
            'client.loans.installments.pay',
            'client.loans.payments.store',
            'client.loans.reminders.update',
            'client.loans.insurance.store',
            'client.loans.premiums.store',
            'client.loans.premiums.pay',
        ] as $route) {
            $this->assertStringContainsString($route, $view);
        }
        foreach (['client.loans.contracts.edit', 'client.loans.payments.create', 'client.loans.insurance.manage', 'client.loans.insurance.view'] as $permission) {
            $this->assertStringContainsString("@can('".$permission."')", $view);
        }
        $this->assertStringContainsString('partials.contract-operations', $detail);
        $this->assertStringContainsString('data-loan-detail-mutation', $view);
        $this->assertStringContainsString('data-loan-detail-email-list', $view);
        $this->assertStringContainsString('data-loan-detail-email-row', $view);
        $this->assertStringContainsString('data-loan-operations', str_replace('id="loan-operations"', 'data-loan-operations', $view));
        $this->assertStringNotContainsString('Thao tác nghiệp vụ hiện tại', $detail);
    }

    public function test_detail_uses_per_contract_history_allocations_reminders_and_insurance(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanCapabilityController.php'));
        foreach ([
            'LoanInstallmentPaymentService',
            'LoanReminderService',
            'LoanReminderHistoryService',
            "'installmentAllocations'",
            "'loanReminderSettings'",
            "'loanReminderRecipients'",
            "'loanReminderTimeline'",
            "'policies'",
            "'premiums'",
        ] as $needle) {
            $this->assertStringContainsString($needle, $controller);
        }
        $this->assertStringContainsString("->where('loan_contract_id', \$contract)", str_replace('$contract', '\$contract', $controller));
    }

    public function test_operations_return_to_detail_without_changing_legacy_dashboard_redirect(): void
    {
        $workspace = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanWorkspaceController.php'));
        $insurance = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanInsuranceController.php'));
        $reminder = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanReminderController.php'));

        $this->assertSame(5, substr_count($workspace, '$this->operationRedirect($request, $contract,'));
        $this->assertStringContainsString("input('source') === 'detail'", $workspace);
        $this->assertStringContainsString("input('source') === 'detail'", $insurance);
        $this->assertStringContainsString("input('source') === 'detail'", $reminder);
        $this->assertStringContainsString("route('client.loans.dashboard')", $workspace);
        $this->assertStringContainsString("route('client.loans.dashboard')", $insurance);
        $this->assertStringContainsString("route('client.loans.dashboard')", $reminder);
        $this->assertStringContainsString("where('id', \$policy)", str_replace('$policy', '\$policy', $insurance));
    }
}
