<?php

namespace Tests\Feature\Inventory;

use App\Modules\ModuleCatalog;
use Tests\TestCase;

class InventoryModuleBootstrapTest extends TestCase
{
    public function test_inventory_manifest_matches_approved_batch_a_contract(): void
    {
        $config = require base_path('Modules/Inventory/config/module.php');

        $this->assertSame('Inventory', $config['name']);
        $this->assertSame('domain', $config['type']);
        $this->assertFalse($config['default_enabled']);
        $this->assertSame(['Shared'], $config['depends']);
        $this->assertTrue($config['permissions_required']);
        $this->assertContains('inventory.receipt.confirm', $config['permissions']);
        $this->assertContains('inventory.issue.confirm', $config['permissions']);
        $this->assertContains('inventory.transfer.confirm', $config['permissions']);
        $this->assertContains('inventory.stocktake.confirm', $config['permissions']);
    }

    public function test_inventory_has_no_hard_dependency_on_optional_domain_modules(): void
    {
        $config = require base_path('Modules/Inventory/config/module.php');

        $this->assertNotContains('Invoices', $config['depends']);
        $this->assertNotContains('Product', $config['depends']);
        $this->assertNotContains('Partner', $config['depends']);
        $this->assertNotContains('Pharma', $config['depends']);
    }

    public function test_catalog_discovers_inventory_without_custom_provider(): void
    {
        $inventory = collect(app(ModuleCatalog::class)->discover())->firstWhere('name', 'Inventory');

        $this->assertNotNull($inventory);
        $this->assertSame(['Shared'], $inventory['depends']);
        $this->assertFalse($inventory['default_enabled']);
        $this->assertFileDoesNotExist(base_path('Modules/Inventory/Providers/InventoryServiceProvider.php'));
    }
}
