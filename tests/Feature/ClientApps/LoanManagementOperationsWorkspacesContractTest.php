<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class LoanManagementOperationsWorkspacesContractTest extends TestCase
{
    public function test_three_operations_workspaces_are_routed_to_distinct_blade_views(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanCapabilityController.php'));
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/routes.php'));

        foreach (['payments', 'reminders', 'insurance'] as $workspace) {
            $this->assertStringContainsString("'".$workspace."' => 'ClientPortal::applications.loans.".$workspace."'", $controller);
            $this->assertStringContainsString("'".$workspace."'", $routes);
            $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/'.$workspace.'.blade.php'));
            $this->assertStringContainsString("@include('ClientPortal::applications.loans.partials.operations-shell')", $view);
            $this->assertStringContainsString("@section('loan-operations-records')", $view);
            $this->assertLessThan(strpos($view, "@section('content')"), strpos($view, "@section('loan-operations-records')"));
            $this->assertStringNotContainsString('Mở chức năng hiện tại', $view);
            $this->assertStringContainsString("route('client.loans.contracts.show'", $view);
            $this->assertStringContainsString('#loan-', $view);
        }
        $this->assertStringContainsString("abort_unless($request->user()?->can(self::WORKSPACES[", str_replace('$request', '\$request', $controller));
    }

    public function test_search_filter_pagination_and_managed_presentation_are_preserved(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanCapabilityController.php'));
        $shell = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/partials/operations-shell.blade.php'));
        foreach (["'payments' =>", "'reminders' =>", "'insurance' =>", "->paginate(12)->withQueryString()", "mb_substr(", "featurePresentation('loans'"] as $fragment) {
            $this->assertStringContainsString($fragment, $controller);
        }
        foreach (['$presentation', 'role="search"', 'name="search"', 'name="status"', '$operations->links()', '$operations->total()'] as $fragment) {
            $this->assertStringContainsString($fragment, $shell);
        }
        $this->assertStringNotContainsString('Mở chức năng hiện tại', $shell);
    }

    public function test_insurance_and_reminder_workspaces_reuse_existing_domain_data(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanCapabilityController.php'));
        foreach ([
            "loan_disbursements", "loan_payments", "loan_repayment_schedules",
            "loan_payment_allocations", "loan_reminder_settings", "loan_reminder_deliveries",
            "loan_insurance_policies", "loan_insurance_premium_schedules",
        ] as $table) {
            $this->assertStringContainsString($table, $controller);
        }
        $this->assertStringNotContainsString('DB::table(\'loan_contracts\')->get()', $controller);
        $this->assertStringContainsString("whereRaw('ps.amount_paid > 0 AND ps.amount_paid < ps.amount_due')", $controller);
    }
}
