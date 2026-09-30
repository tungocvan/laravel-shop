<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class PharmaOrderApprovalCapabilityTest extends TestCase
{
    public function test_order_approval_has_independent_permission_routes_and_canonical_service(): void
    {
        $root = base_path();
        $manifest = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/manifest.php');
        $routes = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/routes.php');
        $service = file_get_contents($root.'/Modules/Pharma/Services/UserOrderApprovalService.php');

        $this->assertSame(2, substr_count($manifest, "'permission' => 'client.pharma.orders.approve'"));
        $this->assertStringContainsString("Route::post('/orders/{issue}/approve'", $routes);
        $this->assertStringContainsString("Route::post('/orders/{issue}/reject'", $routes);
        $this->assertStringContainsString('final class UserOrderApprovalService', $service);
        $this->assertStringContainsString('InventoryIssue::PENDING_APPROVAL', $service);
        $this->assertStringContainsString('InventoryIssue::APPROVED', $service);
        $this->assertStringContainsString('InventoryIssue::REJECTED', $service);
        $this->assertStringContainsString("'approved_by' => \$actorUserId", $service);
        $this->assertStringContainsString("'rejected_by' => \$actorUserId", $service);
        $this->assertStringNotContainsString('postIssue(', $service);
        $this->assertStringNotContainsString('InventoryTransaction', $service);
        $this->assertStringNotContainsString('quantity_on_hand', $service);
    }

    public function test_approver_can_see_pending_queue_and_detail_actions_without_warehouse_posting(): void
    {
        $root = base_path();
        $workspace = file_get_contents($root.'/Modules/Pharma/Services/UserInventoryIssueWorkspace.php');
        $controller = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php');
        $view = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory-issue-show.blade.php');

        $this->assertStringContainsString('includePendingApproval', $workspace);
        $this->assertStringContainsString('findPendingForApproval', $workspace);
        $this->assertStringContainsString("client.pharma.orders.approve", $controller);
        $this->assertStringContainsString("'canApproveOrder' => \$canApproveOrder", $controller);
        $this->assertStringContainsString("&& \$visibleIssue->status === \\Modules\\Pharma\\Models\\InventoryIssue::PENDING_APPROVAL", $controller);
        $this->assertStringContainsString('approveOrder', $controller);
        $this->assertStringContainsString('rejectOrder', $controller);
        $this->assertStringContainsString('Phê duyệt', $view);
        $this->assertStringContainsString('Từ chối', $view);
        $this->assertStringContainsString('Lý do từ chối', $view);
        $this->assertStringContainsString('rejection_reason', $view);
    }

    public function test_approval_schema_is_audit_only_and_does_not_add_stock_fields(): void
    {
        $root = base_path();
        $migration = file_get_contents($root.'/Modules/Pharma/database/migrations/2026_09_30_140000_add_order_approval_audit_to_inventory_issues.php');

        $this->assertStringContainsString("'approved_by'", $migration);
        $this->assertStringContainsString("'approved_at'", $migration);
        $this->assertStringContainsString("'rejected_by'", $migration);
        $this->assertStringContainsString("'rejected_at'", $migration);
        $this->assertStringContainsString("'rejection_reason'", $migration);
        $this->assertStringNotContainsString('batch_number', $migration);
        $this->assertStringNotContainsString('expiry_date', $migration);
        $this->assertStringNotContainsString('quantity_on_hand', $migration);
    }
}
