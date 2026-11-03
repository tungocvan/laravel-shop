<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class PharmaBidManualLotCapabilityTest extends TestCase
{
    public function test_bid_lot_posting_is_owned_by_pharma_and_exposed_through_authorized_client_routes(): void
    {
        $root = base_path();
        $routes = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/routes.php');
        $controller = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php');
        $service = file_get_contents($root.'/Modules/Pharma/Services/BidIssueManualLotPostingService.php');
        $view = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/order-lots.blade.php');

        $this->assertStringContainsString("Route::get('/orders/{issue}/lots'", $routes);
        $this->assertStringContainsString("Route::post('/orders/{issue}/lots'", $routes);
        $this->assertStringContainsString("client.feature:pharma,orders", $routes);
        $this->assertStringContainsString("userCan(\$user, 'client.pharma.orders.post')", $controller);
        $this->assertStringContainsString('function orderLots(', $controller);
        $this->assertStringContainsString('function postOrderLots(', $controller);
        $this->assertStringContainsString("if (\$approved->issue_source === 'bid')", $controller);
        $this->assertStringContainsString('DB::transaction(', $service);
        $this->assertStringContainsString('lockForUpdate()', $service);
        $this->assertStringContainsString("whereDate('expiry_date', '>=', now()->toDateString())", $service);
        $this->assertStringContainsString('$lotTotals[$balanceId]', $service);
        $this->assertStringContainsString('$inventory->postIssue(', $service);
        $this->assertStringContainsString('orders.lots.post', $view);
        $this->assertStringContainsString('batches[{{ $item->id }}]', $view);
    }

    public function test_draft_bid_actions_are_visible_and_manager_delegation_is_checked_by_domain_service(): void
    {
        $root = base_path();
        $index = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory-issues.blade.php');
        $detail = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory-issue-show.blade.php');
        $controller = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php');
        $service = file_get_contents($root.'/Modules/Pharma/Services/UserOrderAuthoringService.php');

        $this->assertStringContainsString('Sửa đơn', $index);
        $this->assertStringContainsString('Gửi duyệt', $index);
        $this->assertStringContainsString('Xem & phê duyệt', $index);
        $this->assertStringContainsString('canSubmitOrders', $controller);
        $this->assertStringContainsString('canManageOrders', $controller);
        $this->assertStringContainsString('canManageOrders', $service);
        $this->assertStringContainsString('guardEditable($actorUserId, $issue, $canManageOrders)', $service);
        $this->assertStringContainsString('guardSubmittable($userId, $issue, $canManageOrders)', $service);
        $this->assertStringContainsString('canEditOrder || $canSubmitOrder', $detail);
    }

    public function test_manual_lot_ui_keeps_pwa_shell_and_does_not_change_non_bid_fefo(): void
    {
        $root = base_path();
        $detail = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory-issue-show.blade.php');
        $index = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory-issues.blade.php');
        $view = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/order-lots.blade.php');
        $controller = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php');

        $this->assertStringContainsString("route('client.pharma.orders.lots', \$issue)", $detail);
        $this->assertStringContainsString("route('client.pharma.orders.lots',\$issue)", $index);
        $this->assertStringContainsString("@section('hide-mobile-navigation', true)", $view);
        $this->assertStringContainsString("@section('hide-application-header', true)", $view);
        $this->assertStringContainsString('postApprovedIssueFromAvailableStock($approved', $controller);
    }
}
