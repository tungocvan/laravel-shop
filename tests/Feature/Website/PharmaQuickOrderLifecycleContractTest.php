<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

final class PharmaQuickOrderLifecycleContractTest extends TestCase
{
    public function test_soft_delete_and_transactional_audit_are_present(): void
    {
        $model = file_get_contents(base_path('Modules/Website/Models/PharmaQuickOrderRequest.php'));
        $service = file_get_contents(base_path('Modules/Website/Services/PharmaQuickOrderLifecycleService.php'));
        $migration = file_get_contents(base_path('Modules/Website/database/migrations/2026_10_14_160000_add_soft_deletes_to_pharma_quick_order_requests.php'));

        $this->assertStringContainsString('use SoftDeletes;', $model);
        $this->assertStringContainsString('softDeletes()', $migration);
        $this->assertStringContainsString('DB::transaction(', $service);
        $this->assertStringContainsString('lockForUpdate()', $service);
        $this->assertStringContainsString('inventory_issue_id !== null', $service);
        $this->assertStringContainsString('$record->delete()', $service);
        $this->assertStringContainsString("website_pharma_quick_order_reviews", $service);
    }

    public function test_admin_actions_and_pharma_hub_are_permission_guarded(): void
    {
        $routes = file_get_contents(base_path('Modules/Website/routes/web.php'));
        $detail = file_get_contents(base_path('Modules/Website/resources/views/pages/admin/pharma-quick-order/request-detail.blade.php'));
        $hub = file_get_contents(base_path('Modules/Pharma/resources/views/pages/dashboard.blade.php'));

        $this->assertStringContainsString('website.pharma-quick-order.requests.revert', $routes);
        $this->assertStringContainsString('website.pharma-quick-order.requests.destroy', $routes);
        $this->assertStringContainsString('permission:website.settings.manage,admin', $routes);
        $this->assertStringContainsString('Hoàn tác trạng thái', $detail);
        $this->assertStringContainsString('Xóa yêu cầu', $detail);
        $this->assertStringContainsString('Duyệt đơn hàng nhanh', $hub);
        $this->assertStringContainsString("hasPermissionTo('website.settings.manage', 'admin')", $hub);
    }
}
