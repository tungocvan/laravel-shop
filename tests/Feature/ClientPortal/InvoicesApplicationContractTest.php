<?php

namespace Tests\Feature\ClientPortal;

use Modules\ClientPortal\Applications\Invoices\Http\Controllers\InvoicesApplicationController;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoicesApplicationContractTest extends TestCase
{
    #[Test]
    public function invoices_application_manifest_declares_client_permissions_and_workspace_capabilities(): void
    {
        $manifest = require base_path('Modules/ClientPortal/Applications/Invoices/manifest.php');

        $this->assertSame('invoices', $manifest['key']);
        $this->assertSame('Invoices', $manifest['source_module']);
        $this->assertSame('client.invoices.access', $manifest['permission']);
        $this->assertSame('workspace', $manifest['layout']['mode']);
        $this->assertContains('search', $manifest['capabilities']);
        $this->assertContains('filter', $manifest['capabilities']);
        $this->assertContains('export', $manifest['capabilities']);
        $this->assertSame('client.invoices.list.view', $manifest['features']['list']['permission']);
        $this->assertSame('client.invoices.detail.view', $manifest['features']['list']['actions']['detail']['permission']);
        $this->assertSame('client.invoices.export', $manifest['features']['list']['actions']['export']['permission']);
        $this->assertSame('client.invoices.pdf.download', $manifest['features']['list']['actions']['pdf']['permission']);
        $this->assertSame('client.invoices.sync', $manifest['features']['sync']['permission']);
    }

    #[Test]
    public function invoices_routes_are_module_gated_and_do_not_reuse_admin_routes(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Invoices/routes.php'));

        $this->assertStringContainsString("config('modules.registry.Invoices.enabled', false)", $routes);
        $this->assertStringContainsString("'auth:web'", $routes);
        $this->assertStringContainsString("'client.application:invoices'", $routes);
        $this->assertStringContainsString("prefix('apps/invoices')", $routes);
        $this->assertStringNotContainsString('auth:admin', $routes);
        $this->assertStringNotContainsString('/admin/invoices', $routes);
    }

    #[Test]
    public function invoices_controller_uses_domain_services_and_authorized_file_handoff(): void
    {
        $this->assertTrue(class_exists(InvoicesApplicationController::class));

        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Invoices/Http/Controllers/InvoicesApplicationController.php'));

        $this->assertStringContainsString('ClientInvoiceWorkspaceService $workspace', $controller);
        $this->assertStringContainsString('InvoicePdfService $pdf', $controller);
        $this->assertStringContainsString('InvoiceFileService $files', $controller);
        $this->assertStringContainsString("'client.invoices.pdf.download'", $controller);
        $this->assertStringContainsString("'client.invoices.export'", $controller);
        $this->assertStringNotContainsString('storage_path(', $controller);
        $this->assertStringNotContainsString('GoogleDrive', $controller);
    }

    #[Test]
    public function invoices_dashboard_is_executive_first_with_automatic_period_drilldown(): void
    {
        $adapter = file_get_contents(base_path('Modules/ClientPortal/Applications/Invoices/Services/ClientInvoiceWorkspaceService.php'));
        $dashboard = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/invoices/dashboard.blade.php'));
        $invoiceService = file_get_contents(base_path('Modules/Invoices/Services/InvoiceService.php'));

        $this->assertStringContainsString('$request->integer(\'year\'', $adapter);
        $this->assertStringContainsString('$request->query(\'month\')', $adapter);
        $this->assertStringContainsString('\'periodScope\' => $month === null ? \'year\' : \'month\'', $adapter);
        $this->assertStringContainsString('$this->invoices->monthlyPerformance($year)', $adapter);
        $this->assertStringContainsString('public function monthlyPerformance(int $year): array', $invoiceService);

        $this->assertStringContainsString('Cả năm', $dashboard);
        $this->assertStringContainsString('onchange="this.form.submit()"', $dashboard);
        $this->assertStringNotContainsString('>Xem</button>', $dashboard);
        $this->assertStringContainsString('Tổng quan kinh doanh', $dashboard);
        $this->assertStringContainsString('Tình trạng tài liệu', $dashboard);
        $this->assertStringContainsString('Xu hướng 12 tháng', $dashboard);
        $this->assertStringContainsString('Tình hình doanh thu qua các năm', $dashboard);
        $this->assertStringContainsString('Tháng hiện tại trong năm đang xem', $dashboard);
    }

    #[Test]
    public function invoices_export_preserves_selected_or_filtered_contract(): void
    {
        $adapter = file_get_contents(base_path('Modules/ClientPortal/Applications/Invoices/Services/ClientInvoiceWorkspaceService.php'));

        $this->assertStringContainsString('$request->input(\'selected\', [])', $adapter);
        $this->assertStringContainsString('$selected === []', $adapter);
        $this->assertStringContainsString('$this->invoices->filter($this->filters($request))', $adapter);
        $this->assertStringContainsString('$this->invoices->selected($selected)', $adapter);
    }

    #[Test]
    public function invoices_pwa_is_mobile_first_and_does_not_cache_gdt_secrets(): void
    {
        $list = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/invoices/index.blade.php'));
        $sync = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/invoices/sync.blade.php'));

        $this->assertStringContainsString('md:hidden', $list);
        $this->assertStringContainsString('hidden overflow-hidden', $list);
        $this->assertStringContainsString('env(safe-area-inset-bottom)', $list);
        $this->assertStringContainsString('Có chọn: xuất phần chọn', $list);
        $this->assertStringContainsString('Không chọn: xuất toàn bộ kết quả lọc', $list);
        $this->assertStringContainsString('Token, mật khẩu và thông tin xác thực GDT không được đưa xuống trình duyệt', $sync);

        foreach (['localStorage', 'indexedDB', 'Authorization'] as $secretSurface) {
            $this->assertStringNotContainsString($secretSurface, $sync);
        }
    }
}
