<?php

namespace Tests\Feature\Invoices;

use Tests\TestCase;

class InvoiceNetRevenueReportingContractTest extends TestCase
{
    public function test_invoice_revenue_aggregates_use_amount_before_vat(): void
    {
        $service = file_get_contents(base_path('Modules/Invoices/Services/InvoiceService.php'));

        $this->assertStringContainsString("invoice_type = 'sold' THEN amount_before_vat ELSE 0 END", $service);
        $this->assertStringContainsString("invoice_type = 'purchase' THEN amount_before_vat ELSE 0 END", $service);
        $this->assertStringNotContainsString("invoice_type = 'sold' THEN total_amount ELSE 0 END", $service);
        $this->assertStringNotContainsString("invoice_type = 'purchase' THEN total_amount ELSE 0 END", $service);

        // The invoice-level payable amount remains intentionally VAT-inclusive.
        $this->assertStringContainsString("COALESCE(SUM(total_amount), 0) as total_amount_sum", $service);
        $this->assertStringContainsString("COALESCE(SUM(vat_amount), 0) as vat_amount_sum", $service);
    }

    public function test_partner_reporting_uses_net_amount_and_keeps_vat_separate(): void
    {
        $service = file_get_contents(base_path('Modules/Invoices/Services/InvoicePartnerReportService.php'));

        $this->assertStringContainsString("invoice_type = 'sold' THEN amount_before_vat ELSE 0 END", $service);
        $this->assertStringContainsString("invoice_type = 'purchase' THEN amount_before_vat ELSE 0 END", $service);
        $this->assertStringContainsString("invoice_type = 'sold' THEN amount_before_vat ELSE -amount_before_vat END", $service);
        $this->assertStringContainsString("invoice_type = 'sold' THEN vat_amount ELSE 0 END", $service);
        $this->assertStringContainsString("invoice_type = 'purchase' THEN vat_amount ELSE 0 END", $service);
        $this->assertStringNotContainsString("invoice_type = 'sold' THEN total_amount ELSE 0 END", $service);
        $this->assertStringNotContainsString("invoice_type = 'purchase' THEN total_amount ELSE 0 END", $service);
    }

    public function test_admin_and_client_surfaces_label_net_revenue_explicitly(): void
    {
        $adminList = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/hoadon-list.blade.php'));
        $adminPartners = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/partner-report.blade.php'));
        $clientDashboard = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/invoices/dashboard.blade.php'));
        $clientPartners = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/invoices/partners.blade.php'));

        $this->assertStringContainsString('Doanh thu bán ra chưa VAT', $adminList);
        $this->assertStringContainsString('Giá trị mua vào chưa VAT', $adminList);
        $this->assertStringContainsString('Tổng bán ra · chưa VAT', $adminPartners);
        $this->assertStringContainsString('Tổng mua vào · chưa VAT', $adminPartners);
        $this->assertStringContainsString('Doanh thu chưa VAT', $clientDashboard);
        $this->assertStringContainsString('Bán ra · chưa VAT', $clientPartners);
        $this->assertStringContainsString('Mua vào · chưa VAT', $clientPartners);
    }
}
