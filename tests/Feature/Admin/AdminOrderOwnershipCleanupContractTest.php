<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminOrderOwnershipCleanupContractTest extends TestCase
{
    public function test_order_admin_routes_are_owned_by_order_module_and_keep_compatibility(): void
    {
        $expected = [
            'admin.orders.index' => ['GET', 'admin/orders'],
            'admin.orders.show' => ['GET', 'admin/orders/{id}'],
            'admin.orders.print' => ['GET', 'admin/orders/{id}/print'],
            'admin.orders.pdf' => ['GET', 'admin/orders/{id}/pdf'],
        ];

        foreach ($expected as $name => [$method, $uri]) {
            $route = app('router')->getRoutes()->getByName($name);

            $this->assertNotNull($route, "Missing route [{$name}].");
            $this->assertStringContainsString('Modules\\Order\\Http\\Controllers\\OrderController', $route->getActionName());
            $this->assertSame($uri, $route->uri());
            $this->assertContains($method, $route->methods());
            $this->assertContains('auth:admin', $route->gatherMiddleware());
        }
    }

    public function test_canonical_order_pages_mount_order_livewire_components(): void
    {
        $index = file_get_contents(base_path('Modules/Order/resources/views/pages/orders/index.blade.php'));
        $show = file_get_contents(base_path('Modules/Order/resources/views/pages/orders/show.blade.php'));

        $this->assertStringContainsString("@livewire('order.orders.order-table')", $index);
        $this->assertStringContainsString("@livewire('order.orders.order-detail'", $show);
    }

    public function test_legacy_admin_order_management_runtime_remains_absent(): void
    {
        $legacyFiles = [
            'Modules/Admin/Livewire/Orders/OrderTable.php',
            'Modules/Admin/Livewire/Orders/OrderDetail.php',
            'Modules/Admin/resources/views/livewire/orders/order-table.blade.php',
            'Modules/Admin/resources/views/livewire/orders/order-detail.blade.php',
            'Modules/Admin/resources/views/pages/orders/index.blade.php',
            'Modules/Admin/resources/views/pages/orders/show.blade.php',
            'Modules/Admin/resources/views/pages/orders/invoice.blade.php',
        ];

        foreach ($legacyFiles as $file) {
            $this->assertFileDoesNotExist(base_path($file), "Legacy Admin Order runtime returned: {$file}");
        }
    }

    public function test_canonical_order_management_runtime_exists(): void
    {
        foreach ([
            'Modules/Order/Http/Controllers/OrderController.php',
            'Modules/Order/Livewire/Orders/OrderTable.php',
            'Modules/Order/Livewire/Orders/OrderDetail.php',
            'Modules/Order/resources/views/livewire/orders/order-table.blade.php',
            'Modules/Order/resources/views/livewire/orders/order-detail.blade.php',
            'Modules/Order/resources/views/pages/orders/index.blade.php',
            'Modules/Order/resources/views/pages/orders/show.blade.php',
            'Modules/Order/resources/views/pages/orders/invoice.blade.php',
        ] as $file) {
            $this->assertFileExists(base_path($file), "Missing canonical Order runtime: {$file}");
        }
    }

    public function test_order_status_history_captures_old_status_before_mutation(): void
    {
        $detail = file_get_contents(base_path('Modules/Order/Livewire/Orders/OrderDetail.php'));

        $capture = strpos($detail, '$oldStatus = $this->order->status;');
        $mutation = strpos($detail, '$this->order->status = $this->newStatus;');

        $this->assertNotFalse($capture);
        $this->assertNotFalse($mutation);
        $this->assertLessThan($mutation, $capture, 'Old Order status must be captured before mutating the model.');
    }

    public function test_affiliate_cross_domain_runtime_is_not_silently_removed_by_order_cleanup(): void
    {
        foreach ([
            'Modules/Admin/Livewire/Orders/OrderDetailModal.php',
            'Modules/Admin/resources/views/livewire/orders/order-detail-modal.blade.php',
            'Modules/Admin/Services/AdminAffiliateService.php',
        ] as $file) {
            $this->assertFileExists(base_path($file), "Affiliate compatibility surface changed without dedicated migration proof: {$file}");
        }
    }

    public function test_p0_database_service_remains_quarantined_and_present(): void
    {
        $this->assertFileExists(base_path('Modules/Admin/Services/DatabaseService.php'));

        $adminRoutes = file_get_contents(base_path('Modules/Admin/routes/web.php'));
        $adminApiRoutes = file_get_contents(base_path('Modules/Admin/routes/api.php'));

        $this->assertStringNotContainsString('DatabaseService', $adminRoutes);
        $this->assertStringNotContainsString('DatabaseService', $adminApiRoutes);
    }
}
