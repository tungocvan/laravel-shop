<?php

namespace Modules\ClientPortal\Tests\Unit;

use Modules\ClientPortal\Services\ApplicationPermissionService;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Tests\TestCase;

class NavigationActionPermissionTest extends TestCase
{
    public function test_pharma_navigation_actions_are_available_to_web_permission_management(): void
    {
        config()->set('modules.registry.Pharma.enabled', true);

        $definitions = app(ApplicationPermissionService::class)->applicationDefinitions();
        $products = $definitions->where('group', 'pharma')->keyBy('name');

        foreach ([
            'client.pharma.products.create',
            'client.pharma.products.edit',
            'client.pharma.products.delete',
            'client.pharma.products.create-variant',
            'client.pharma.products.import',
            'client.pharma.products.export',
            'client.pharma.products.excel-config',
            'client.pharma.products.hssp.update',
            'client.pharma.products.supplier-conditions.manage',
        ] as $permission) {
            $this->assertTrue($products->has($permission), "Missing managed Web permission: {$permission}");
        }

        $this->assertSame('products', $products->get('client.pharma.products.export')['feature']);
        $this->assertSame('export', $products->get('client.pharma.products.export')['action']);
        $this->assertSame('Danh mục thuốc · Xuất danh mục thuốc Excel', $products->get('client.pharma.products.export')['label']);

        // Navigation and feature declarations may share the same permission.
        $this->assertSame($definitions->count(), $definitions->pluck('name')->unique()->count());
    }

    public function test_navigation_actions_are_normalized_without_changing_navigation_routing(): void
    {
        config()->set('modules.registry.Pharma.enabled', true);

        $pharma = app(ApplicationRegistry::class)->find('pharma');
        $this->assertNotNull($pharma);

        $products = collect($pharma['navigation'])->firstWhere('key', 'products');
        $this->assertSame('client.pharma.products', $products['route']);
        $this->assertSame('client.pharma.products.view', $products['permission']);

        $actions = collect($products['actions'])->keyBy('key');
        $this->assertSame('client.pharma.products.export', $actions['export']['permission']);
        $this->assertSame('client.pharma.products.import', $actions['import']['permission']);
        $this->assertSame('client.pharma.products.excel-config', $actions['excel-config']['permission']);
    }
}
