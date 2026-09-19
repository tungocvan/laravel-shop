<?php

namespace Tests\Feature\Invoices;

use Tests\TestCase;

class InvoicePartnerSyncDashboardContractTest extends TestCase
{
    public function test_dashboard_exposes_partner_sync_workspace_and_safe_post_action(): void
    {
        $routes = file_get_contents(base_path('Modules/Invoices/routes/web.php'));
        $dashboard = file_get_contents(base_path('Modules/Invoices/resources/views/pages/invoices/dashboard.blade.php'));
        $service = file_get_contents(base_path('Modules/Invoices/Services/InvoicePartnerCandidateService.php'));

        $this->assertStringContainsString("Route::post('/partners/sync'", $routes);
        $this->assertStringContainsString("->middleware('permission:invoices-create')", $routes);
        $this->assertStringContainsString('Đồng bộ đối tác', $dashboard);
        $this->assertStringContainsString('Quét & chuẩn bị đồng bộ', $dashboard);
        $this->assertStringContainsString("route('admin.partners.invoice-candidates')", $dashboard);
        $this->assertStringContainsString('PartnerCandidateIntakeService', $service);
        $this->assertStringContainsString("->intake('invoices'", $service);
        $this->assertStringNotContainsString('Partner::updateOrCreate', $service);
        $this->assertStringNotContainsString('Partner::query()->create', $service);
    }
}
