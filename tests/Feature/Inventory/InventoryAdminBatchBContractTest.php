<?php

namespace Tests\Feature\Inventory;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InventoryAdminBatchBContractTest extends TestCase
{
    #[Test]
    public function inventory_batch_b_exposes_the_approved_admin_route_family(): void
    {
        $routes = file_get_contents(base_path('Modules/Inventory/routes/web.php'));

        foreach (['dashboard', 'warehouses', 'items', 'receipts', 'issues', 'transfers', 'stocktakes', 'stock', 'lots', 'movements'] as $route) {
            $this->assertStringContainsString("name('{$route}')", $routes);
        }

        $this->assertStringContainsString("prefix('admin/inventory')", $routes);
        $this->assertStringContainsString("auth:admin", $routes);
        $this->assertStringNotContainsString('invoices/pdf', $routes);
    }

    #[Test]
    public function admin_workspace_follows_bounded_pagination_and_visual_contract(): void
    {
        $component = file_get_contents(base_path('Modules/Inventory/Livewire/AdminWorkspace.php'));
        $view = file_get_contents(base_path('Modules/Inventory/resources/views/livewire/admin-workspace.blade.php'));
        $pagination = file_get_contents(base_path('Modules/Inventory/resources/views/vendor/pagination/admin-inventory.blade.php'));

        $this->assertStringContainsString('private const PAGE_SIZES = [10, 25, 50, 100];', $component);
        $this->assertStringNotContainsString("'all' =>", $component);
        $this->assertStringContainsString('border border-gray-300 bg-white', $view);
        $this->assertStringContainsString("links('Inventory::vendor.pagination.admin-inventory')", $view);
        $this->assertStringContainsString('bg-indigo-600', $pagination);
        $this->assertStringContainsString('bg-white', $pagination);
    }

    #[Test]
    public function high_risk_confirm_actions_delegate_to_batch_a_posting_services(): void
    {
        $component = file_get_contents(base_path('Modules/Inventory/Livewire/AdminWorkspace.php'));

        $this->assertStringContainsString('ReceiptPostingService::class', $component);
        $this->assertStringContainsString('IssuePostingService::class', $component);
        $this->assertStringContainsString('TransferPostingService::class', $component);
        $this->assertStringContainsString('StocktakePostingService::class', $component);
        $this->assertStringContainsString("wire:loading.attr=\"disabled\"", file_get_contents(base_path('Modules/Inventory/resources/views/livewire/admin-workspace.blade.php')));
    }
}
