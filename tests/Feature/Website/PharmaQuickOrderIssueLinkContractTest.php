<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

final class PharmaQuickOrderIssueLinkContractTest extends TestCase
{
    public function test_missing_issue_is_not_rendered_as_a_valid_link(): void
    {
        $controller = file_get_contents(base_path('Modules/Website/Http/Controllers/Admin/PharmaQuickOrderReviewController.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/pages/admin/pharma-quick-order/request-detail.blade.php'));
        $this->assertStringContainsString("'linkedIssue' =>", $controller);
        $this->assertStringContainsString('@if($linkedIssue)', $view);
        $this->assertStringContainsString('không còn tồn tại', $view);
        $this->assertStringContainsString('requests.unlink', $view);
    }

    public function test_unlink_is_guarded_and_audited_without_deleting_issue(): void
    {
        $controller = file_get_contents(base_path('Modules/Website/Http/Controllers/Admin/PharmaQuickOrderLifecycleController.php'));
        $routes = file_get_contents(base_path('Modules/Website/routes/web.php'));
        $this->assertStringContainsString('lockForUpdate()', $controller);
        $this->assertStringContainsString('InventoryIssue::DRAFT', $controller);
        $this->assertStringContainsString("'inventory_issue_id' => null", $controller);
        $this->assertStringContainsString('website_pharma_quick_order_reviews', $controller);
        $this->assertStringNotContainsString('$issue->delete()', $controller);
        $this->assertStringContainsString('permission:website.settings.manage,admin', $routes);
        $this->assertStringContainsString('requests.unlink', $routes);
    }

    public function test_inventory_issue_list_badges_only_active_linked_requests(): void
    {
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/documents.blade.php'));
        $this->assertStringContainsString('quickOrderIssueIds', $controller);
        $this->assertStringContainsString('PharmaQuickOrderRequest::query()', $controller);
        $this->assertStringContainsString('Đặt hàng nhanh', $view);
        $this->assertStringContainsString('quickOrderIssueIds', $view);
    }
}
