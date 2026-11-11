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
        $this->assertStringContainsString('data-lender-search', $view);
        $this->assertStringContainsString('data-lender-filter', $view);
        $this->assertStringContainsString('aria-modal="true"', $view);
        $this->assertStringContainsString('data-lender-overlay', $view);
        $this->assertStringContainsString("overlay.hidden=false", $view);
        $this->assertStringContainsString("overlay.hidden=true", $view);
        $this->assertStringNotContainsString('x-data=', $view);
        $this->assertStringContainsString("route('client.loans.lenders.store')", $view);
        $this->assertStringContainsString("->orderBy('name')->get()", $controller);
        $this->assertStringContainsString('data-lender-edit', $view);
        $this->assertStringContainsString('data-lender-delete-form', $view);
        $this->assertStringContainsString('contracts_count === 0', $view);
    }

    public function test_lender_mutations_are_authorized_and_delete_is_domain_guarded(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/routes.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanWorkspaceController.php'));
        $service = file_get_contents(base_path('Modules/LoanManagement/Services/LoanContractService.php'));
        $this->assertStringContainsString("Route::put('/lenders/{lender}'", $routes);
        $this->assertStringContainsString("Route::delete('/lenders/{lender}'", $routes);
        $this->assertStringContainsString("can('client.loans.contracts.edit')", $controller);
        $this->assertStringContainsString('updateLender(', $service);
        $this->assertStringContainsString('deleteUnusedLender(', $service);
        $this->assertStringContainsString("DB::table('loan_contracts')->where('lender_id', $id)->exists()", $service);
    }

    public function test_contract_creation_has_three_steps_and_reuses_existing_preview(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/contract-create.blade.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanWorkspaceController.php'));
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/routes.php'));
        $dashboard = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/dashboard.blade.php'));
        $shared = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/partials/wizard-preview-script.blade.php'));

        foreach (['data-loan-step="1"', 'data-loan-step="2"', 'data-loan-step="3"',
            'data-loan-wizard-preview', 'data-loan-lender', 'data-wizard-lender-form',
            'Lưu hợp đồng nháp', "route('client.loans.contracts.store')"] as $fragment) {
            $this->assertStringContainsString($fragment, $view);
        }
        $this->assertStringContainsString("Route::get('/contracts/create'", $routes);
        $this->assertStringContainsString("createContractWithOptionalSchedule", $controller);
        $this->assertStringContainsString("expectsJson()", $controller);
        $this->assertStringContainsString('const previewSchedule = () =>', $shared);
        $this->assertStringContainsString('partials.wizard-preview-script', $dashboard);
        $this->assertStringContainsString('partials.wizard-preview-script', $view);
        $this->assertStringNotContainsString('x-data=', $view);
        $this->assertStringContainsString("form.addEventListener('submit'", $view);
        $this->assertStringContainsString("lenderForm.addEventListener('submit'", $view);
    }
}
