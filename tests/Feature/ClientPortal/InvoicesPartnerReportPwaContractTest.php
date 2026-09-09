<?php

namespace Tests\Feature\ClientPortal;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoicesPartnerReportPwaContractTest extends TestCase
{
    #[Test]
    public function invoices_manifest_exposes_partner_report_as_client_feature(): void
    {
        $manifest = require base_path('Modules/ClientPortal/Applications/Invoices/manifest.php');

        $this->assertSame('client.invoices.partners', $manifest['features']['partners']['route']);
        $this->assertSame('client.invoices.partners.view', $manifest['features']['partners']['permission']);
        $this->assertSame('client.invoices.partners', $manifest['navigation']['partners']['route']);
        $this->assertSame('client.invoices.partners.view', $manifest['navigation']['partners']['permission']);
    }

    #[Test]
    public function partner_report_route_stays_inside_clientportal_auth_boundary(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Invoices/routes.php'));

        $this->assertStringContainsString("Route::get('/partners'", $routes);
        $this->assertStringContainsString("'client.feature:invoices,partners'", $routes);
        $this->assertStringContainsString("->name('partners')", $routes);
        $this->assertStringContainsString("'auth:web'", $routes);
        $this->assertStringNotContainsString('/admin/invoices/reports/partners', $routes);
        $this->assertStringNotContainsString('auth:admin', $routes);
    }

    #[Test]
    public function client_workspace_reuses_invoices_partner_report_service(): void
    {
        $workspace = file_get_contents(base_path('Modules/ClientPortal/Applications/Invoices/Services/ClientInvoiceWorkspaceService.php'));
        $reportService = file_get_contents(base_path('Modules/Invoices/Services/InvoicePartnerReportService.php'));

        $this->assertStringContainsString('InvoicePartnerReportService $partnerReports', $workspace);
        $this->assertStringContainsString('public function partnerReportData(Request $request): array', $workspace);
        $this->assertStringContainsString('$this->partnerReports->paginate(', $workspace);
        $this->assertStringContainsString('$this->partnerReports->summary(', $workspace);
        $this->assertStringContainsString('$this->partnerReports->partnerDetail(', $workspace);
        $this->assertStringContainsString("filled(\$filters['partner'] ?? null)", $reportService);
        $this->assertStringContainsString("->orWhere('tax_code', 'like'", $reportService);
    }

    #[Test]
    public function partner_report_is_executive_and_responsive_instead_of_reusing_admin_view(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Invoices/Http/Controllers/InvoicesApplicationController.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/invoices/partners.blade.php'));

        $this->assertStringContainsString("applicationView('partners'", $controller);
        $this->assertStringContainsString('Tổng quan đối tác', $view);
        $this->assertStringContainsString('Top bán ra', $view);
        $this->assertStringContainsString('Top mua vào', $view);
        $this->assertStringContainsString('Tên hoặc MST đối tác', $view);
        $this->assertStringContainsString('Chênh lệch', $view);
        $this->assertStringContainsString('md:hidden', $view);
        $this->assertStringContainsString('hidden overflow-x-auto md:block', $view);
        $this->assertStringNotContainsString("@livewire('invoices.partner-report')", $view);
        $this->assertStringNotContainsString("@extends('Admin::layouts.master')", $view);
    }
}
