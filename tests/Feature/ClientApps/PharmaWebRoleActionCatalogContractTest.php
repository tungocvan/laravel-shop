<?php

namespace Tests\Feature\ClientApps;

use Modules\ClientPortal\Services\ApplicationPermissionService;
use Tests\TestCase;

final class PharmaWebRoleActionCatalogContractTest extends TestCase
{
    public function test_all_pharma_product_actions_are_discoverable_by_web_role_sync(): void
    {
        config()->set('modules.registry.Pharma.enabled', true);

        $definitions = app(ApplicationPermissionService::class)->applicationDefinitions()->keyBy('name');
        $expected = [
            'create', 'edit', 'delete', 'create-variant', 'import', 'excel-share',
            'excel-config', 'export', 'hssp.view', 'hssp.update', 'hssp.create',
            'supplier-conditions.manage', 'supplier-pricing',
        ];

        $this->assertSame('products', $definitions->get('client.pharma.products.view')['feature']);

        foreach ($expected as $action) {
            $permission = 'client.pharma.products.'.$action;
            $this->assertTrue($definitions->has($permission), $permission.' must be available to Web Role sync');
            $this->assertSame('products', $definitions->get($permission)['feature']);
        }

        $this->assertSame(
            'Danh mục thuốc · Xem giá nhà cung cấp',
            $definitions->get('client.pharma.products.supplier-pricing')['label']
        );
    }

    public function test_navigation_actions_do_not_bypass_feature_permission_catalog(): void
    {
        $manifest = require base_path('Modules/ClientPortal/Applications/Pharma/manifest.php');

        foreach (['products', 'price-lists', 'orders'] as $key) {
            $this->assertArrayNotHasKey('actions', $manifest['navigation'][$key]);
            $this->assertNotEmpty($manifest['features'][$key]['actions']);
        }

        $this->assertSame(
            'client.pharma.products.supplier-pricing',
            $manifest['features']['products']['actions']['supplier-pricing']['permission']
        );
    }

    public function test_existing_price_list_and_order_actions_remain_in_catalog(): void
    {
        config()->set('modules.registry.Pharma.enabled', true);

        $definitions = app(ApplicationPermissionService::class)->applicationDefinitions()->pluck('name');

        foreach ([
            'client.pharma.price-lists.create',
            'client.pharma.price-lists.submit',
            'client.pharma.price-lists.approve',
            'client.pharma.orders.create',
            'client.pharma.orders.submit',
            'client.pharma.orders.create-for-user',
            'client.pharma.orders.approve',
            'client.pharma.orders.post',
        ] as $permission) {
            $this->assertContains($permission, $definitions->all());
        }
    }
}
