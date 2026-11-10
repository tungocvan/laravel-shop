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

    public function test_contract_filters_and_collapsible_actions_remain_scoped(): void
    {
        $blade = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/dashboard.blade.php'));
        foreach (['name="search"', 'name="type"', 'name="status"', 'name="insurance"', 'Áp dụng bộ lọc', 'loan-contract-card', 'Xóa bộ lọc', 'Mở chi tiết hợp đồng', 'Xem các kỳ phí và thao tác bảo hiểm'] as $fragment) {
            $this->assertStringContainsString($fragment, $blade);
        }
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanApplicationController.php'));
        $this->assertStringContainsString('insuredContractIds', $controller);
        $this->assertStringContainsString('visibleContracts', $controller);
        $this->assertStringContainsString('mb_stripos', $controller);
        $this->assertStringNotContainsString('x-show=', $blade);
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
