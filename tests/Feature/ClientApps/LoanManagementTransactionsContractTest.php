<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class LoanManagementTransactionsContractTest extends TestCase
{
    public function test_transaction_service_enforces_financial_invariants(): void
    {
        $source = file_get_contents(base_path('Modules/LoanManagement/Services/LoanTransactionService.php'));

        $this->assertStringContainsString('DB::transaction(', $source);
        $this->assertStringContainsString('lockForUpdate()', $source);
        $this->assertStringContainsString("bccomp(bcadd(\$disbursed", $source);
        $this->assertStringContainsString("bcsub(\$disbursed, \$paid, 2)", $source);
        $this->assertStringContainsString("bccomp((string) \$data['principal_paid'], \$remaining, 2)", $source);
        $this->assertStringContainsString("where('installment', \$data['installment'])", $source);
        $this->assertStringNotContainsString("status' => 'closed'", $source);
    }

    public function test_transaction_routes_and_pwa_forms_are_protected(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/routes.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanWorkspaceController.php'));
        $blade = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/partials/contract-operations.blade.php'));
        $detail = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/contract-detail.blade.php'));

        $history = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/partials/contract-history.blade.php'));

        foreach (['disbursements.store', 'schedules.store', 'payments.store'] as $name) {
            $this->assertStringContainsString($name, $routes);
        }
        $this->assertStringContainsString('client.loans.disbursements.store', $history);
        foreach (['schedules.store', 'payments.store'] as $name) {
            $this->assertStringContainsString("client.loans.{$name}", $blade);
        }
        $this->assertStringContainsString("partials.contract-operations", $detail);
        $this->assertStringContainsString("@can('client.loans.payments.create')", $history);
        $this->assertStringContainsString("can('client.loans.payments.create')", $controller);
        $this->assertStringContainsString("can('client.loans.contracts.edit')", $controller);
        $this->assertStringContainsString('border-slate-200', $blade);
        $this->assertStringContainsString('<x-pwa-date', $blade);
    }
}
