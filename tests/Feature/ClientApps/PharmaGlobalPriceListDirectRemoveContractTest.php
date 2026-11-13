<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class PharmaGlobalPriceListDirectRemoveContractTest extends TestCase
{
    public function test_direct_removal_is_transactional_scoped_and_audited(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/GlobalPriceListRevisionWorkflow.php'));
        $this->assertStringContainsString('function removeActiveItem(', $service);
        $this->assertStringContainsString('DB::transaction(function () use ($priceListId, $itemId, $actorId)', $service);
        $this->assertStringContainsString('$this->assertGlobalActive($list)', $service);
        $this->assertStringContainsString('$list->items()->whereKey($itemId)->lockForUpdate()->first()', $service);
        $this->assertStringContainsString('$list->items()->count() <= 1', $service);
        $this->assertStringContainsString("'pharma_price_list_approval_item_audits'", $service);
        $this->assertStringContainsString("'action' => 'removed'", $service);
        $this->assertStringContainsString('$item->delete()', $service);
    }

    public function test_remove_route_and_ui_require_approval_permission_and_confirmation(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/price-list-show.blade.php'));

        $this->assertStringContainsString("->name('price-lists.items.remove')", $routes);
        $this->assertStringContainsString('function removeActiveGlobalPriceListItem(', $controller);
        $this->assertStringContainsString("userCan($user, 'client.pharma.price-lists.approve')", $controller);
        $this->assertStringContainsString('price-lists.items.remove', $view);
        $this->assertStringContainsString("@method('DELETE')", $view);
        $this->assertStringContainsString("confirm('Gỡ SKU này", $view);
        $this->assertStringContainsString('PriceList::STATUS_ACTIVE', $view);
    }
}
