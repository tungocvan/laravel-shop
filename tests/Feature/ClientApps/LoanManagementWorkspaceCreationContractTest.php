<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class LoanManagementWorkspaceCreationContractTest extends TestCase
{
    public function test_lender_workspace_supports_search_filter_and_inline_creation(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/lenders.blade.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanCapabilityController.php'));
        $this->assertStringContainsString('data-loan-lenders-workspace', $view);
        $this->assertStringContainsString('x-model.debounce.150ms="search"', $view);
        $this->assertStringContainsString('x-model="type"', $view);
        $this->assertStringContainsString('aria-modal="true"', $view);
        $this->assertStringContainsString("route('client.loans.lenders.store')", $view);
        $this->assertStringContainsString("DB::table('loan_lenders')->orderBy('name')->get()", $controller);
    }

    public function test_contract_creation_has_three_steps_and_reuses_existing_preview(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/contract-create.blade.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanWorkspaceController.php'));
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/routes.php'));
        $dashboard = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/dashboard.blade.php'));
        $shared = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/partials/wizard-preview-script.blade.php'));

        foreach (['data-loan-step="1"', 'data-loan-step="2"', 'data-loan-step="3"',
            'data-loan-wizard-preview', 'data-loan-lender', 'saveLender(event)',
            'Lưu hợp đồng nháp', "route('client.loans.contracts.store')"] as $fragment) {
            $this->assertStringContainsString($fragment, $view);
        }
        $this->assertStringContainsString("Route::get('/contracts/create'", $routes);
        $this->assertStringContainsString("createContractWithOptionalSchedule", $controller);
        $this->assertStringContainsString("expectsJson()", $controller);
        $this->assertStringContainsString('const previewSchedule = () =>', $shared);
        $this->assertStringContainsString('partials.wizard-preview-script', $dashboard);
        $this->assertStringContainsString('partials.wizard-preview-script', $view);
    }
}
