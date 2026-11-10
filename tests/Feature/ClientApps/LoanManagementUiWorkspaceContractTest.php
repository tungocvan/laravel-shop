<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class LoanManagementUiWorkspaceContractTest extends TestCase
{
    public function test_loan_workspace_has_soft_scoped_visual_system_and_navigation(): void
    {
        $blade = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/dashboard.blade.php'));

        $this->assertStringContainsString("@push('application-head')", $blade);
        $this->assertStringContainsString('.loan-workspace .loan-panel', $blade);
        $this->assertStringContainsString('border:1px solid #e2e8f0 !important', $blade);
        $this->assertStringContainsString('id="loan-contracts"', $blade);
        $this->assertStringContainsString('id="loan-create"', $blade);
        $this->assertStringContainsString('id="loan-insurance"', $blade);
        $this->assertStringContainsString('aria-label="Điều hướng khoản vay"', $blade);
        $this->assertStringContainsString('max-w-7xl', $blade);
        $this->assertStringNotContainsString('border:2px solid #0f172a', $blade);
    }

    public function test_workspace_preserves_transaction_and_insurance_actions(): void
    {
        $blade = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/dashboard.blade.php'));
        foreach (['lenders.store','contracts.store','contracts.update','disbursements.store','schedules.store','payments.store','insurance.store','premiums.store','premiums.pay'] as $name) {
            $this->assertStringContainsString('client.loans.'.$name, $blade);
        }
        $this->assertStringContainsString("client.loans.contracts.create", $blade);
        $this->assertStringContainsString("client.loans.insurance.view", $blade);
        $this->assertStringContainsString('<x-pwa-date', $blade);
    }
}
