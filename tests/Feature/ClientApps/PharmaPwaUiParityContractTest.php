<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class PharmaPwaUiParityContractTest extends TestCase
{
    public function test_order_manifest_actions_are_consistent_between_navigation_and_features(): void
    {
        $manifest = require base_path('Modules/ClientPortal/Applications/Pharma/manifest.php');

        $navigationActions = $manifest['navigation']['orders']['actions'];
        $featureActions = $manifest['features']['orders']['actions'];

        $this->assertSame('client.pharma.orders.post', $navigationActions['post']['permission'] ?? null);
        $this->assertSame('client.pharma.orders.post', $featureActions['post']['permission'] ?? null);
        $this->assertSame(
            array_keys($navigationActions),
            array_keys($featureActions),
            'Order action presentation must not drift between navigation and feature permission trees.'
        );
    }

    public function test_price_list_editor_uses_focused_task_shell_and_canonical_business_selectors(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/price-list-create.blade.php'));

        $this->assertStringContainsString("@section('hide-application-header', true)", $view);
        $this->assertStringContainsString("@section('hide-mobile-navigation', true)", $view);
        $this->assertStringContainsString('aria-label="Quay lại Bảng giá của tôi"', $view);

        $this->assertStringContainsString('<x-select-search id="client-price-list-customer"', $view);
        $this->assertStringContainsString('<x-select-search id="client-price-list-manager"', $view);
        $this->assertStringContainsString('<x-select-search id="source-price-list"', $view);
        $this->assertStringNotContainsString('<x-search-select', $view);

        $this->assertStringContainsString('id="load-source-price-list"', $view);
        $this->assertStringContainsString("document.getElementById('source-price-list')", $view);
    }
}
