<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class PharmaQuickOrderFrontendGateTest extends TestCase
{
    public function test_quick_order_route_requires_web_authentication(): void
    {
        $routes = file_get_contents(base_path('Modules/Website/routes/web.php'));
        $this->assertStringContainsString("middleware('auth')->get('/quick-order'", $routes);
        $this->assertStringContainsString("PharmaQuickOrderController", $routes);
    }

    public function test_active_website_accounts_can_browse_but_no_private_customer_prices_are_exposed(): void
    {
        $controller = file_get_contents(base_path('Modules/Website/Http/Controllers/PharmaQuickOrderController.php'));
        $this->assertStringContainsString('website.public_template', $controller);
        $this->assertStringContainsString("configuration['enabled']", $controller);
        $this->assertStringContainsString('user->is_active', $controller);
        $this->assertStringContainsString("where('type', \\Modules\\Pharma\\Models\\PriceList::TYPE_GLOBAL)", $controller);
        $this->assertStringContainsString('eligibleForCustomer(', $controller);
        $this->assertStringContainsString('company_sale_price', $controller);
        $this->assertStringContainsString('medicine_variant_id', $controller);
        $this->assertStringContainsString('medicine_package_id', $controller);
        $this->assertStringContainsString('$matches->count() === 1', $controller);
        $this->assertStringNotContainsString('createDraft(', $controller);
    }

    public function test_quick_order_page_is_catalog_only_until_review_workflow_is_ready(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/pages/pharma-quick-order.blade.php'));
        $this->assertStringContainsString('quick-order-search', $view);
        $this->assertStringContainsString('quick-order-results', $view);
        $this->assertStringContainsString('Đơn giá (Có VAT):', $view);
        $this->assertStringContainsString('setTimeout(() => fetchPage(1), 300)', $view);
        $this->assertStringContainsString('data-quick-order-quantity', $view);
        $this->assertStringContainsString('data-quick-order-total', $view);
        $this->assertStringContainsString('data-quick-order-unpriced', $view);
        $this->assertStringContainsString('knownProducts.set(product.id, product)', $view);
        $this->assertStringContainsString('window.sessionStorage.setItem(draftStorageKey', $view);
        $this->assertStringContainsString('data-quick-order-continue', $view);
        $this->assertStringContainsString('quick-order-buyer-panel', $view);
        $this->assertStringContainsString('quick-order-catalog', $view);
        $this->assertStringContainsString('quick-order-back', $view);
        $this->assertStringContainsString('quick-order-review-toggle', $view);
        $this->assertStringContainsString('aria-expanded="false"', $view);
        $this->assertStringContainsString('showBuyer(true)', $view);
        $this->assertStringContainsString('showBuyer(false)', $view);
        $this->assertStringContainsString('quick-order-tax-code', $view);
        $this->assertStringContainsString('quick-order-address', $view);
        $this->assertStringContainsString('Gửi yêu cầu đặt hàng', $view);
        $this->assertStringContainsString('quick-order-more', $view);
        $this->assertStringContainsString('@json($quickOrderInitialProducts)', $view);
        $this->assertStringContainsString('@json($quickOrderInitialNextPage)', $view);
        $this->assertStringNotContainsString('@json($items->getCollection()->map(', $view);
        $this->assertStringNotContainsString('type="submit" name="order"', $view);
    }
}
