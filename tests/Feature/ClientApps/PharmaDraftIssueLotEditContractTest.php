<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class PharmaDraftIssueLotEditContractTest extends TestCase
{
    public function test_draft_edit_is_scoped_and_lot_selection_is_checked_against_inventory(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $authoring = file_get_contents(base_path('Modules/Pharma/Services/UserOrderAuthoringService.php'));
        $index = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/inventory-issues.blade.php'));
        $form = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/order-form.blade.php'));

        $this->assertStringContainsString('public function editOrder(', $controller);
        $this->assertStringContainsString('public function updateOrder(', $controller);
        $this->assertStringContainsString('findVisible((int) $user->id, $issue)', $controller);
        $this->assertStringContainsString('InventoryIssue::DRAFT', $controller);
        $this->assertStringContainsString("'lot_balances' => ['sometimes', 'array']", $controller);
        $this->assertStringContainsString("where('warehouse_id', \$issue->warehouse_id)", $authoring);
        $this->assertStringContainsString("where('medicine_id', \$row['medicine_id'])", $authoring);
        $this->assertStringContainsString("where('quantity_on_hand', '>=', \$row['quantity'])", $authoring);
        $this->assertStringContainsString("whereDate('expiry_date', '>=', now()->toDateString())", $authoring);
        $this->assertStringContainsString("route('client.pharma.orders.edit', \$issue)", $index);
        $this->assertStringContainsString('Sửa phiếu / chọn lô', $index);
        $this->assertStringContainsString('lot_balances[{{ $item->medicine_id }}]', $form);
    }
}
