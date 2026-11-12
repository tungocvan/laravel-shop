<?php

namespace Tests\Feature\ClientApps;

use Modules\ClientPortal\Services\ApplicationPermissionService;
use Tests\TestCase;

final class LoanManagementPermissionRegistryContractTest extends TestCase
{
    private const EXPECTED = [
        'client.loans.access',
        'client.loans.overview.view',
        'client.loans.contracts.view',
        'client.loans.contracts.create',
        'client.loans.contracts.edit',
        'client.loans.payments.create',
        'client.loans.insurance.view',
        'client.loans.insurance.manage',
        'client.loans.reports.view',
    ];

    public function test_all_domain_loan_permissions_are_discoverable_for_web_guard(): void
    {
        $config = require base_path('Modules/LoanManagement/config/module.php');

        $this->assertSame($config['permissions'], $config['permissions_by_guard']['web']);
        $this->assertSame(self::EXPECTED, $config['permissions_by_guard']['web']);

        $definitions = app(ApplicationPermissionService::class)->domainDefinitions()
            ->where('group', 'LoanManagement')->pluck('name')->all();

        $this->assertEqualsCanonicalizing(self::EXPECTED, $definitions);
    }

    public function test_loan_mutation_permissions_are_declared_as_managed_feature_actions(): void
    {
        $manifest = require base_path('Modules/ClientPortal/Applications/LoanManagement/manifest.php');
        $actions = collect($manifest['features'])
            ->flatMap(fn (array $feature): array => array_values($feature['actions'] ?? []))
            ->pluck('permission')->all();

        $this->assertEqualsCanonicalizing([
            'client.loans.contracts.create',
            'client.loans.contracts.edit',
            'client.loans.payments.create',
            'client.loans.insurance.manage',
            'client.loans.reports.view',
        ], $actions);
    }

    public function test_create_contract_remains_server_side_permission_protected(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanCapabilityController.php'));
        $workspace = file_get_contents(base_path('Modules/ClientPortal/Applications/LoanManagement/Http/Controllers/LoanWorkspaceController.php'));

        $this->assertStringContainsString("can('client.loans.contracts.create')", $controller);
        $this->assertStringContainsString("can('client.loans.contracts.create')", $workspace);
    }
}
