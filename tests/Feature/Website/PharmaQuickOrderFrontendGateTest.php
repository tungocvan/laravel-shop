<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class PharmaQuickOrderFrontendGateTest extends TestCase
{
    public function test_quick_order_route_is_web_authenticated_and_not_a_public_price_api(): void
    {
        $routes = file_get_contents(base_path('Modules/Website/routes/web.php'));
        $this->assertStringContainsString("middleware('auth')->get('/quick-order'", $routes);
        $this->assertStringContainsString("PharmaQuickOrderController", $routes);
    }

    public function test_frontend_gate_requires_pharma_template_feature_and_explicit_permission(): void
    {
        $controller = file_get_contents(base_path('Modules/Website/Http/Controllers/PharmaQuickOrderController.php'));
        $this->assertStringContainsString("website.public_template", $controller);
        $this->assertStringContainsString("configuration['enabled']", $controller);
        $this->assertStringContainsString("website.pharma.quick_order.view", $controller);
        $this->assertStringContainsString("customerLinked' => false", $controller);
        $this->assertStringNotContainsString('PriceListItem::query()', $controller);
    }
}
