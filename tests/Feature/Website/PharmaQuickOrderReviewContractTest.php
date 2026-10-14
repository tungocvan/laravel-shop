<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

final class PharmaQuickOrderReviewContractTest extends TestCase
{
    public function test_review_routes_are_admin_authenticated_and_permission_guarded(): void
    {
        $routes = file_get_contents(base_path('Modules/Website/routes/web.php'));
        $this->assertStringContainsString("Route::middleware(['web', 'auth:admin'])", $routes);
        $this->assertStringContainsString("PharmaQuickOrderReviewController::class, 'index'", $routes);
        $this->assertStringContainsString("PharmaQuickOrderReviewController::class, 'show'", $routes);
        $this->assertStringContainsString("PharmaQuickOrderReviewController::class, 'review'", $routes);
        $this->assertStringContainsString("->middleware('permission:website.settings.manage,admin')->name('website.pharma-quick-order.requests.review')", $routes);
    }

    public function test_review_is_locked_pending_only_and_writes_audit_without_inventory_issue(): void
    {
        $controller = file_get_contents(base_path('Modules/Website/Http/Controllers/Admin/PharmaQuickOrderReviewController.php'));
        $this->assertStringContainsString('lockForUpdate()', $controller);
        $this->assertStringContainsString('PharmaQuickOrderRequest::PENDING_REVIEW', $controller);
        $this->assertStringContainsString("'eligibility_confirmed' => ['required_if:decision,accepted', 'nullable', 'accepted']", $controller);
        $this->assertStringContainsString("DB::table('website_pharma_quick_order_reviews')->insert(", $controller);
        $this->assertStringNotContainsString('InventoryIssue::', $controller);
        $this->assertStringNotContainsString('createDraft(', $controller);
    }

    public function test_review_views_show_buyer_products_audit_and_admin_navigation(): void
    {
        $list = file_get_contents(base_path('Modules/Website/resources/views/pages/admin/pharma-quick-order/requests.blade.php'));
        $detail = file_get_contents(base_path('Modules/Website/resources/views/pages/admin/pharma-quick-order/request-detail.blade.php'));
        $settings = file_get_contents(base_path('Modules/Website/resources/views/pages/admin/pharma-quick-order/index.blade.php'));
        $this->assertStringContainsString('Chờ xét duyệt', $list);
        $this->assertStringContainsString('{{ $line[\'title\'] }}', $detail);
        $this->assertStringContainsString('{{ $line[\'quantity\'] }}', $detail);
        $this->assertStringContainsString('Danh sách thuốc yêu cầu', $detail);
        $this->assertStringContainsString('lg:sticky', $detail);
        $this->assertStringContainsString('tax_code', $detail);
        $this->assertStringContainsString('eligibility_confirmed', $detail);
        $this->assertStringContainsString('Lịch sử xét duyệt', $detail);
        $this->assertStringContainsString('admin.website.pharma-quick-order.requests.index', $settings);
    }
}
