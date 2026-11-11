<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class LoanManagementFoundationContractTest extends TestCase
{
    public function test_manifest_registers_isolated_source_module_and_managed_overview(): void
    {
        $manifest = require base_path('Modules/ClientPortal/Applications/LoanManagement/manifest.php');

        $this->assertSame('LoanManagement', $manifest['source_module']);
        $this->assertSame('client.loans.access', $manifest['permission']);
        $this->assertSame('client.loans.dashboard', $manifest['route']);
        $this->assertSame('client.loans.overview.view', $manifest['features']['overview']['permission']);
        foreach (['eyebrow', 'page_title', 'page_description'] as $field) {
            $this->assertNotEmpty($manifest['features']['overview'][$field]);
        }
    }

    public function test_domain_has_no_finance_dependency(): void
    {
        $config = require base_path('Modules/LoanManagement/config/module.php');

        $this->assertNotContains('Finance', $config['depends']);
        $this->assertFalse($config['default_enabled']);
    }
}
