<?php

namespace Tests\Feature\Invoices;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceDashboardClassificationContractTest extends TestCase
{
    #[Test]
    public function dashboard_exposes_source_data_classification_and_purchase_value_metrics(): void
    {
        $service = file_get_contents(base_path('Modules/Invoices/Services/InvoiceDashboardService.php'));
        $view = file_get_contents(base_path('Modules/Invoices/resources/views/pages/invoices/dashboard.blade.php'));

        $this->assertStringContainsString("'invoice_source_records' => \$this->tableExists('invoice_source_records')", $service);
        $this->assertStringContainsString("'invoice_expense_categories' => \$this->tableExists('invoice_expense_categories')", $service);
        $this->assertStringContainsString("'classification' => \$classificationMetrics", $service);
        $this->assertStringContainsString('private function classificationMetrics(bool $expenseCategoriesAvailable): array', $service);
        $this->assertStringContainsString("->where('invoices.invoice_type', 'purchase')", $service);
        $this->assertStringContainsString('SUM(invoices.amount_before_vat)', $service);
        $this->assertStringContainsString("business_classification = 'GOODS'", $service);
        $this->assertStringContainsString("business_classification = 'SERVICE_EXPENSE'", $service);
        $this->assertStringContainsString("business_classification = 'MIXED'", $service);
        $this->assertStringContainsString("business_classification = 'UNCLASSIFIED'", $service);
        $this->assertStringContainsString("DB::table('invoice_expense_categories')", $service);

        $this->assertStringContainsString("route('admin.invoices.source-data')", $view);
        $this->assertStringContainsString('Cơ cấu hàng hóa & chi phí', $view);
        $this->assertStringContainsString('Giá trị sử dụng số tiền trước VAT', $view);
        $this->assertStringContainsString('Phân loại chi phí cấp 2', $view);
        $this->assertStringContainsString("['businessClassification' => 'GOODS']", $view);
        $this->assertStringContainsString("['businessClassification' => 'SERVICE_EXPENSE']", $view);
        $this->assertStringContainsString("['businessClassification' => 'MIXED']", $view);
        $this->assertStringContainsString("['businessClassification' => 'UNCLASSIFIED']", $view);
    }

    #[Test]
    public function dashboard_drilldown_context_is_honored_by_source_data_workspace(): void
    {
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/SourceDataManager.php'));
        $page = file_get_contents(base_path('Modules/Invoices/resources/views/pages/invoices/source-data.blade.php'));

        $this->assertStringContainsString('public function mount(?string $year = null, ?string $month = null, ?string $businessClassification = null): void', $component);
        $this->assertStringContainsString("\$this->businessClassification = \$businessClassification ?? 'all';", $component);
        $this->assertStringContainsString('InvoiceSourceRecord::CLASSIFICATIONS', $component);
        $this->assertStringContainsString("\$classificationDrilldown = request()->filled('businessClassification');", $page);
        $this->assertStringContainsString(":year=\"request('year', \$classificationDrilldown ? 'all' : now()->format('Y'))\"", $page);
        $this->assertStringContainsString(":month=\"request('month', \$classificationDrilldown ? 'all' : now()->format('n'))\"", $page);
        $this->assertStringContainsString(":business-classification=\"request('businessClassification', 'all')\"", $page);
    }
}
