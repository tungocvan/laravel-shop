<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class PharmaOrderAuthoringCapabilityTest extends TestCase
{
    public function test_order_authoring_routes_and_permissions_are_separate_from_inventory(): void
    {
        $root = base_path();
        $routes = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/routes.php');
        $manifest = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/manifest.php');

        $this->assertStringContainsString("Route::get('/orders/create'", $routes);
        $this->assertStringContainsString("Route::post('/orders'", $routes);
        $this->assertStringContainsString("Route::get('/orders/{issue}/edit'", $routes);
        $this->assertStringContainsString("Route::put('/orders/{issue}'", $routes);
        $this->assertStringContainsString("Route::post('/orders/{issue}/submit'", $routes);
        $this->assertStringContainsString("middleware('client.feature:pharma,orders')", $routes);
        $this->assertSame(2, substr_count($manifest, "'permission' => 'client.pharma.orders.create'"));
        $this->assertSame(2, substr_count($manifest, "'permission' => 'client.pharma.orders.submit'"));
        $this->assertSame(2, substr_count($manifest, "'permission' => 'client.pharma.orders.create-for-user'"));
        $this->assertStringContainsString("'permission' => 'client.pharma.orders.create'", $manifest);
        $this->assertStringContainsString("'permission' => 'client.pharma.orders.submit'", $manifest);
        $this->assertStringNotContainsString('client.pharma.inventory.issues', $routes);
    }

    public function test_canonical_service_owns_scope_price_and_draft_mutations(): void
    {
        $root = base_path();
        $service = file_get_contents($root.'/Modules/Pharma/Services/UserOrderAuthoringService.php');
        $controller = file_get_contents($root.'/Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php');

        $this->assertStringContainsString('final class UserOrderAuthoringService', $service);
        $this->assertStringContainsString("->activeAt(\$date)", $service);
        $this->assertStringContainsString("->where('type', PriceList::TYPE_GLOBAL)", $service);
        $this->assertStringContainsString("->whereDoesntHave('globalUsers')", $service);
        $this->assertStringContainsString("->orWhereHas('globalUsers', fn (\$users) => \$users->whereKey(\$userId))", $service);
        $this->assertStringContainsString("->whereNotExists(fn (\$assigned)", $service);
        $this->assertStringContainsString("->where('type', PriceList::TYPE_CUSTOMER)", $service);
        $this->assertStringContainsString("->where('manager_user_id', \$userId)", $service);
        $this->assertStringContainsString('DrugBidAwardManagementAssignment::STATUS_ACTIVE', $service);
        $this->assertStringContainsString("'unit_price' => (float) \$item->company_sale_price", $service);
        $this->assertStringContainsString("'unit_price' => \$row->unit_price", $service);
        $this->assertStringContainsString('InventoryIssue::PENDING_APPROVAL', $service);
        $this->assertStringContainsString("'submitted_by' => \$userId", $service);
        $this->assertStringContainsString('UserOrderAuthoringService $authoring', $controller);
        $this->assertStringContainsString('orderManagers()', $service);
        $this->assertStringContainsString('createDraft(int $actorUserId, int $managerUserId', $service);
        $this->assertStringContainsString('guardSubmittable($userId, $issue)', $service);
        $this->assertStringContainsString('(int) $issue->manager_user_id', $service);
        $this->assertStringContainsString("'manager_user_id' => \$managerUserId", $service);
        $this->assertStringContainsString("'created_by' => \$actorUserId", $service);
        $this->assertStringContainsString("'allocated_quantity' => (float) \$allocation->allocated_quantity", $service);
        $this->assertStringContainsString("client.pharma.orders.create-for-user", $controller);
        $this->assertStringNotContainsString('PriceList::query()', $controller);
        $this->assertStringNotContainsString('DrugBidAwardAllocation::query()', $controller);
    }

    public function test_order_form_is_mobile_first_and_browser_cannot_authoritative_price(): void
    {
        $root = base_path();
        $view = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/order-form.blade.php');
        $list = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory-issues.blade.php');
        $detail = file_get_contents($root.'/Modules/ClientPortal/resources/views/applications/pharma/inventory-issue-show.blade.php');

        $this->assertStringContainsString('Thêm mới đơn hàng', $view);
        $this->assertStringContainsString('data-order-stepper', $view);
        $this->assertStringContainsString('step-track', $view);
        $this->assertStringContainsString('step-dot', $view);
        $this->assertStringContainsString('is-active', $view);
        $this->assertStringContainsString('max-w-3xl space-y-0', $view);
        $this->assertStringContainsString('data-order-wizard', $view);
        $this->assertStringContainsString('data-order-step-panel="1"', $view);
        $this->assertStringContainsString('data-order-step-panel="2"', $view);
        $this->assertStringContainsString('data-order-step-panel="3"', $view);
        $this->assertStringContainsString('Thiết lập', $view);
        $this->assertStringContainsString('Sản phẩm', $view);
        $this->assertStringContainsString('Xem lại', $view);
        $this->assertStringContainsString('id="order-step-back"', $view);
        $this->assertStringContainsString('id="order-step-next"', $view);
        $this->assertStringContainsString('id="order-submit"', $view);
        $this->assertStringContainsString('pb-[calc(12px+env(safe-area-inset-bottom,0px))]', $view);
        $this->assertStringContainsString('id="review-products"', $view);
        $this->assertStringContainsString('id="review-summary"', $view);
        $this->assertStringContainsString('refreshReview', $view);
        $this->assertStringContainsString('setOrderStep', $view);
        $this->assertStringContainsString('Theo bảng giá', $view);
        $this->assertStringContainsString('Theo trúng thầu', $view);
        $this->assertStringContainsString('id="price-list-unassigned-warning"', $view);
        $this->assertStringContainsString('Bạn chưa được phân công bảng giá đang hiệu lực', $view);
        $this->assertStringContainsString('Không thể lập đơn theo bảng giá.', $view);
        $this->assertStringContainsString('data-order-source-picker', $view);
        $this->assertStringContainsString('data-source-card', $view);
        $this->assertStringContainsString('input:checked + [data-source-card]', $view);
        $this->assertStringContainsString('input[value="bid"]:checked + [data-source-card]', $view);
        $this->assertStringContainsString('min-h-[calc(100dvh-250px)]', $view);
        $this->assertStringContainsString('data-customer-field', $view);
        $this->assertStringContainsString('max-h-[min(300px,38dvh)]', $view);
        $this->assertStringContainsString('customerField?.classList.toggle(\'is-open\',has)', $view);
        $this->assertStringContainsString('data-order-actions', $view);
        $this->assertStringContainsString('z-[100]', $view);
        $this->assertStringContainsString('id="order-source-hint"', $view);
        $this->assertStringContainsString("hint.textContent=b?'Giá và số lượng theo phân bổ trúng thầu':'Giá bán theo bảng giá đang hiệu lực'", $view);
        $this->assertStringContainsString('Giá bán theo bảng giá đang hiệu lực', $view);
        $this->assertStringContainsString('Giá và số lượng theo phân bổ trúng thầu', $view);
        $this->assertStringContainsString('Tìm tên / MST khách hàng...', $view);
        $this->assertStringContainsString('Tìm User phụ trách...', $view);
        $this->assertStringContainsString('data-manager-combobox', $view);
        $this->assertStringContainsString('manager-toggle', $view);
        $this->assertStringContainsString("!managerBox.contains(e.target)", $view);
        $this->assertStringContainsString("!customerBox.contains(e.target)", $view);
        $this->assertStringContainsString("!productBox.contains(e.target)", $view);
        $this->assertStringContainsString('Tìm tên thuốc / mã thuốc / hoạt chất...', $view);
        $this->assertStringContainsString('data-product-picker', $view);
        $this->assertStringContainsString('Chọn sản phẩm từ bảng giá...', $view);
        $this->assertStringContainsString('+ Thêm vào đơn', $view);
        $this->assertStringContainsString('data-remove-product', $view);
        $this->assertStringContainsString('aria-label="Số lượng"', $view);
        $this->assertStringContainsString('h-9 w-24 rounded-xl', $view);
        $this->assertStringContainsString('lg:left-1/2', $view);
        $this->assertStringContainsString('lg:w-[min(760px,calc(100%-48px))]', $view);
        $this->assertStringContainsString('selected-products-empty', $view);
        $this->assertStringContainsString("((int)(\$issue?->price_list_id ?? 0)===(int)\$pl->id)", $view);
        $this->assertStringContainsString("!eligible||q<=0", $view);
        $this->assertStringContainsString('Sản phẩm theo bảng giá', $view);
        $this->assertStringContainsString('Xuất bán hàng thầu', $view);
        $this->assertStringContainsString('Chủ đầu tư *', $view);
        $this->assertStringContainsString('Khách hàng / Bệnh viện *', $view);
        $this->assertStringContainsString('Sản phẩm trúng thầu', $view);
        $this->assertStringContainsString('id="bid-investor"', $view);
        $this->assertStringContainsString('id="bid-partner"', $view);
        $this->assertStringContainsString('data-bid-investor-combobox', $view);
        $this->assertStringContainsString('placeholder="Tìm chủ đầu tư..."', $view);
        $this->assertStringContainsString('data-bid-partner-combobox', $view);
        $this->assertStringContainsString('placeholder="Tìm khách hàng / bệnh viện..."', $view);
        $this->assertStringContainsString('data-bid-product-picker', $view);
        $this->assertStringContainsString('Chọn sản phẩm trúng thầu...', $view);
        $this->assertStringContainsString('SL phân bổ:', $view);
        $this->assertStringContainsString('SL còn lại:', $view);
        $this->assertStringContainsString('renderBidProducts', $view);
        $this->assertStringContainsString("!bidPartnerBox.contains(e.target)", $view);
        $this->assertStringContainsString('data-investor=', $view);
        $this->assertStringContainsString('hydrateBidContext', $view);
        $this->assertStringContainsString('Lưu nháp', $view);
        $this->assertStringContainsString('order-summary', $view);
        $this->assertStringContainsString("el.disabled=b", $view);
        $this->assertStringContainsString("el.disabled=!b", $view);
        $this->assertStringNotContainsString('name="unit_price', $view);
        $this->assertStringNotContainsString('name="company_sale_price', $view);
        $this->assertStringNotContainsString('name="winning_price', $view);
        $this->assertStringContainsString('name="price_list_id"', $view);
        $this->assertStringContainsString('data-create-order', $list);
        $this->assertStringContainsString('Chờ duyệt', $list);
        $this->assertStringContainsString('Sửa đơn', $detail);
        $this->assertStringContainsString('Gửi duyệt', $detail);
    }

    public function test_order_authoring_migration_preserves_stock_posting_boundary(): void
    {
        $root = base_path();
        $migration = file_get_contents($root.'/Modules/Pharma/database/migrations/2026_09_30_120000_add_order_authoring_to_inventory_issues.php');
        $service = file_get_contents($root.'/Modules/Pharma/Services/UserOrderAuthoringService.php');

        $this->assertStringContainsString("'recipient_partner_id'", $migration);
        $this->assertStringContainsString("'submitted_by'", $migration);
        $this->assertStringContainsString("'submitted_at'", $migration);
        $this->assertStringNotContainsString('posted_by', $migration);
        $this->assertStringNotContainsString('postIssue(', $service);
        $this->assertStringNotContainsString('InventoryTransaction', $service);
        $this->assertStringNotContainsString('quantity_on_hand', $service);
    }
}
