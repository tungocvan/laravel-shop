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

    public function test_installment_payment_action_is_permission_scoped_and_secondary_forms_are_collapsed(): void
    {
        $blade = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/dashboard.blade.php'));
        $partial = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/partials/contract-history.blade.php'));
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/routes.php'));
        foreach (['client.loans.installments.pay', 'data-loan-installment-form', 'Thanh toán kỳ', 'Thanh toán một phần', 'Còn phải trả theo kỳ'] as $fragment) {
            $this->assertStringContainsString($fragment, $partial.$blade);
        }
        $this->assertStringContainsString('Thao tác khác', $blade);
        $this->assertStringContainsString("whereNumber('schedule')", $routes);
        $this->assertStringContainsString("@can('client.loans.payments.create')", $partial);
    }

    public function test_guided_loan_creation_supports_three_lender_types_and_forecast_only(): void
    {
        $blade = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/dashboard.blade.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanWorkspaceController.php'));
        $service = file_get_contents(base_path('Modules/LoanManagement/Services/LoanScheduleGenerator.php'));
        foreach (['data-loan-wizard', 'data-loan-lender-type', 'data-loan-wizard-preview', 'interest_monthly', 'all_at_maturity', 'manual'] as $fragment) {
            $this->assertStringContainsString($fragment, $blade);
        }
        $this->assertStringContainsString('createContractWithOptionalSchedule', $controller);
        $this->assertStringContainsString('LoanInterestCalculator', $service);
        $this->assertStringNotContainsString("DB::table('loan_payments')->insert", $service);
        $this->assertStringNotContainsString("DB::table('loan_disbursements')->insert", $service);
    }

    public function test_contract_profile_and_transaction_history_are_rendered_from_domain_service(): void
    {
        $blade = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/dashboard.blade.php'));
        $partial = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/partials/contract-history.blade.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanApplicationController.php'));
        $insurance = file_get_contents(base_path('Modules/LoanManagement/Services/LoanInsuranceService.php'));

        $this->assertStringContainsString('contract-history', $blade);
        $this->assertStringContainsString("lender_type !== 'individual'", $blade);
        foreach (['Thông tin hồ sơ vay', 'Lịch sử giải ngân', 'Lịch trả nợ ·', 'Lịch sử thanh toán thực tế', 'principal_repaid', 'annual_rate_percent'] as $fragment) {
            $this->assertStringContainsString($fragment, $partial);
        }
        $this->assertStringContainsString('LoanContractHistoryService', $controller);
        $this->assertStringContainsString("lenderType === 'individual'", $insurance);
    }

    public function test_contract_filters_and_collapsible_actions_remain_scoped(): void
    {
        $blade = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/dashboard.blade.php'));
        foreach (['name="search"', 'name="type"', 'name="status"', 'name="insurance"', 'data-loan-filters', 'data-loan-delete-confirm', 'data-loan-restore-confirm', 'requestSubmit', 'loan-contract-card', 'Xóa bộ lọc', 'Mở chi tiết hợp đồng', 'Xem các kỳ phí và thao tác bảo hiểm'] as $fragment) {
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
