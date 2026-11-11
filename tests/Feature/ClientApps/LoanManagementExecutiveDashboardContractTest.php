<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class LoanManagementExecutiveDashboardContractTest extends TestCase
{
    public function test_overview_is_read_only_and_uses_the_canonical_domain_read_model(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanApplicationController.php'));
        $service = file_get_contents(base_path('Modules/LoanManagement/Services/LoanContractService.php'));

        $this->assertStringContainsString('$loans->executiveOverview()', $controller);
        $this->assertStringContainsString("'executive' =>", $controller);
        $this->assertStringNotContainsString('$history->forContracts(', $controller);
        $this->assertStringNotContainsString('$insurance->overview()', $controller);
        foreach ([
            'public function executiveOverview(): array',
            'loan_disbursements',
            'loan_payments',
            'loan_repayment_schedules',
            'loan_payment_allocations',
            'loan_insurance_premium_schedules',
            'loan_insurance_policies',
            'leftJoinSub(',
            "->limit(5)->get()",
        ] as $fragment) {
            $this->assertStringContainsString($fragment, $service);
        }
        $this->assertStringContainsString('COALESCE(SUM(GREATEST(', $service);
    }

    public function test_executive_dashboard_is_compact_responsive_and_routes_to_existing_workspaces(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/dashboard.blade.php'));
        foreach ([
            'data-loan-executive-dashboard',
            '$overviewPresentation',
            "'contractCount'",
            "'committed'",
            "'disbursed'",
            "'repaid'",
            "'outstanding'",
            "'upcoming'",
            "'premiumAlerts'",
            "'recent'",
            'grid-cols-2',
            "route('client.loans.contracts.create')",
            "route('client.loans.contracts.index')",
            "route('client.loans.reminders.index')",
            '#loan-schedules',
            '#loan-insurance',
            '@can(',
        ] as $fragment) {
            $this->assertStringContainsString($fragment, $view);
        }
        $this->assertStringNotContainsString('<form method="POST"', $view);
        $this->assertStringNotContainsString('data-loan-wizard', $view);
        $this->assertStringNotContainsString('Tối đa 100 hợp đồng mới nhất', $view);
    }
}
