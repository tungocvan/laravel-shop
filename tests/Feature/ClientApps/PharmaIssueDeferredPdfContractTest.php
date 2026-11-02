<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class PharmaIssueDeferredPdfContractTest extends TestCase
{
    public function test_pdf_keeps_deferred_supply_separate_from_posted_stock(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/issue-pdf.blade.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/InventoryIssueDocumentService.php'));

        $this->assertStringContainsString('$postedItems=$issue->items->reject(', $view);
        $this->assertStringContainsString('$deferredItems=$issue->items->filter(', $view);
        $this->assertStringContainsString('$totalValue=$postedItems->sum(', $view);
        $this->assertStringContainsString('@foreach($postedItems as $item)', $view);
        $this->assertStringContainsString('@foreach($deferredItems as $item)', $view);
        $this->assertStringContainsString('HÀNG CHỜ CUNG ỨNG', $view);
        $this->assertStringContainsString('Tổng giá trị thực xuất:', $view);
        $this->assertStringContainsString('Tổng giá trị nhu cầu đơn hàng:', $view);
        $this->assertStringContainsString("'deferred_supplies'=>\$deferred", $service);
        $this->assertStringContainsString("abort_unless(\$issue->status===InventoryIssue::POSTED", $service);
    }

    public function test_client_review_labels_unavailable_stock_without_posting(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/order-form.blade.php'));
        $this->assertStringContainsString('Chưa có hàng / Chờ cung ứng', $view);
        $this->assertStringContainsString('Tổng nhu cầu', $view);
        $this->assertStringContainsString('Thực xuất chỉ tính sau ghi sổ', $view);
    }
}
