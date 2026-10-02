<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class PharmaP5SecurityRegressionTest extends TestCase
{
    public function test_pharma_pwa_routes_keep_web_guard_and_feature_boundary(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));

        $this->assertStringContainsString("'auth:web'", $routes);
        $this->assertStringContainsString("'client.application:pharma'", $routes);
        $this->assertStringNotContainsString("'auth:admin'", $routes);

        foreach (['overview', 'products', 'bid-awards', 'inventory', 'orders', 'commercial', 'price-lists'] as $feature) {
            $this->assertStringContainsString("client.feature:pharma,{$feature}", $routes, $feature);
        }
    }

    public function test_order_authoring_re_resolves_scope_customer_and_canonical_prices_server_side(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/UserOrderAuthoringService.php'));

        $this->assertStringContainsString('$this->resolvePriceListItems($managerUserId, $date, $data)', $service);
        $this->assertStringContainsString('$this->resolveBidItems($managerUserId, $date, $data)', $service);
        $this->assertStringContainsString("withPartnerType('customer')", $service);
        $this->assertStringContainsString("'unit_price' => (float) $item->company_sale_price", $service);
        $this->assertStringContainsString("'unit_price' => $row->unit_price", $service);
        $this->assertStringContainsString('$this->guardPriceListDraftCurrent($issue);', $service);
        $this->assertStringContainsString('$this->guardBidDraftCurrent($issue);', $service);
        $this->assertStringContainsString('lockForUpdate()', $service);
    }

    public function test_order_approval_and_posting_are_independent_server_authorized_transitions(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $approval = file_get_contents(base_path('Modules/Pharma/Services/UserOrderApprovalService.php'));
        $inventory = file_get_contents(base_path('Modules/Pharma/Services/InventoryService.php'));

        foreach ([
            'client.pharma.orders.submit',
            'client.pharma.orders.approve',
            'client.pharma.orders.post',
        ] as $permission) {
            $this->assertStringContainsString("userCan($user, '{$permission}')", $controller, $permission);
        }

        $this->assertStringContainsString('InventoryIssue::query()->lockForUpdate()->findOrFail', $approval);
        $this->assertStringNotContainsString('postIssue(', $approval);
        $this->assertStringContainsString('postApprovedIssueFromAvailableStock', $controller);
        $this->assertStringContainsString("->where('quantity_on_hand', '>', 0)", $inventory);
        $this->assertStringContainsString('->lockForUpdate()', $inventory);
        $this->assertStringContainsString('$quantity = min($remaining, (float) $balance->quantity_on_hand);', $inventory);
        $this->assertStringContainsString('đủ tổng tồn khả dụng qua các lô còn hạn', $controller);
        $this->assertStringNotContainsString('Mỗi sản phẩm phải có một lô còn hạn đủ số lượng', $controller);
    }

    public function test_receipt_workflow_keeps_create_submit_approve_and_post_permissions_separate(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $workspace = file_get_contents(base_path('Modules/Pharma/Services/UserInventoryReceiptWorkspace.php'));

        foreach ([
            'client.pharma.inventory.receipts.create',
            'client.pharma.inventory.receipts.submit',
            'client.pharma.inventory.receipts.approve',
            'client.pharma.inventory.receipts.post',
        ] as $permission) {
            $this->assertStringContainsString("userCan($user, '{$permission}')", $controller, $permission);
        }

        $this->assertStringContainsString('InventoryReceipt::query()->lockForUpdate()->findOrFail', $workspace);
        $this->assertStringContainsString('InventoryReceipt::PENDING_APPROVAL', $workspace);
        $this->assertStringContainsString('InventoryReceipt::APPROVED', $workspace);
        $this->assertStringContainsString('$this->inventory->postReceipt($locked, $userId);', $workspace);
    }

    public function test_admin_presentation_settings_cannot_replace_code_owned_security_contracts(): void
    {
        $settings = file_get_contents(base_path('Modules/ClientPortal/Services/ClientPortalSettingsService.php'));
        $admin = file_get_contents(base_path('Modules/ClientPortal/resources/views/admin/application-presentation.blade.php'));

        $this->assertStringContainsString("'eyebrow' =>", $settings);
        $this->assertStringContainsString("'page_title' =>", $settings);
        $this->assertStringContainsString("'page_description' =>", $settings);
        $this->assertStringNotContainsString('name="route"', $admin);
        $this->assertStringNotContainsString('name="permission"', $admin);
        $this->assertStringContainsString('Route, permission và nghiệp vụ vẫn do source code kiểm soát.', $admin);
    }
}
