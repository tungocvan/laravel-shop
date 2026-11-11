<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class LoanManagementContractsWorkspaceContractTest extends TestCase
{
    public function test_contracts_workspace_uses_server_pagination_and_authorized_detail(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanCapabilityController.php'));
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/routes.php'));
        $list = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/contracts.blade.php'));
        $detail = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/contract-detail.blade.php'));

        $this->assertStringContainsString("Route::get('/contracts/{contract}'", $routes);
        $this->assertStringContainsString("can('client.loans.contracts.view')", $controller);
        $this->assertStringContainsString("->paginate(15)->withQueryString()", $controller);
        $this->assertStringContainsString("->where('c.id', $contract)", str_replace('$contract', '\$contract', $controller));
        $this->assertStringContainsString('data-loan-contracts-workspace', $list);
        $this->assertStringContainsString("route('client.loans.contracts.show'", $list);
        $this->assertStringContainsString('$contracts->links()', $list);
        $this->assertStringContainsString('data-loan-contract-detail', $detail);
        $this->assertStringContainsString('data-contract-delete', $detail);
        $this->assertStringContainsString('data-contract-restore', $detail);
        $this->assertStringContainsString('!$hasRelatedRecords', $detail);
    }

    public function test_detail_edit_and_delete_reuse_domain_endpoints(): void
    {
        $detail = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/contract-detail.blade.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanWorkspaceController.php'));
        $this->assertStringContainsString("route('client.loans.contracts.update'", $detail);
        $this->assertStringContainsString("route('client.loans.contracts.destroy'", $detail);
        $this->assertStringContainsString("route('client.loans.contracts.restore-draft'", $detail);
        $this->assertStringContainsString("input('source') === 'detail'", $controller);
        $this->assertStringContainsString('LoanContractHistoryService', file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanCapabilityController.php')));
    }
}
