<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class PharmaBidDraftEditActionContractTest extends TestCase
{
    public function test_draft_bid_edit_action_is_available_on_mobile_and_desktop_with_server_authorization(): void
    {
        $root = base_path('Modules/ClientPortal/');
        $controller = file_get_contents($root.'Applications/Pharma/Http/Controllers/PharmaApplicationController.php');
        $routes = file_get_contents($root.'Applications/Pharma/routes.php');
        $view = file_get_contents($root.'resources/views/applications/pharma/inventory-issues.blade.php');

        $this->assertStringContainsString("'orderEditActions' => $orderEditActions", $controller);
        $this->assertStringContainsString("client.pharma.orders.create-for-user", $controller);
        $this->assertStringContainsString("InventoryIssue::DRAFT", $controller);
        $this->assertStringContainsString("[(int) $issue->created_by, (int) $issue->manager_user_id]", $controller);
        $this->assertSame(2, substr_count($view, "route('client.pharma.orders.edit',\$issue)"));
        $this->assertSame(2, substr_count($view, "Sửa đơn hàng thầu"));
        $this->assertSame(2, substr_count($view, 'orderEditActions[(int) $issue->id]'));
        $this->assertStringContainsString("name('orders.edit')", $routes);
        $this->assertStringContainsString("name('orders.update')", $routes);
        $this->assertStringContainsString('public function editOrder(', $controller);
        $this->assertStringContainsString('public function updateOrder(', $controller);
    }
}
