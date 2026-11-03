<?php

namespace Tests\Feature\Pharma;

use Tests\TestCase;

class InventoryIssueDeferredSupplyPdfContractTest extends TestCase
{
    public function test_pdf_separates_actual_issue_movements_from_deferred_supply(): void
    {
        $root = base_path('Modules/Pharma/');
        $lines = file_get_contents($root.'Services/InventoryIssueDocumentLines.php');
        $pdf = file_get_contents($root.'resources/views/pages/inventory/issue-pdf.blade.php');
        $documents = file_get_contents($root.'Services/InventoryIssueDocumentService.php');

        $this->assertStringContainsString("->where('source_type', InventoryIssue::class)", $lines);
        $this->assertStringContainsString("->where('type', 'issue')", $lines);
        $this->assertStringContainsString("->where('quantity_delta', '<', 0)", $lines);
        $this->assertStringContainsString('InventoryIssueDeferredSupply::PENDING', $lines);
        $this->assertStringContainsString("['posted_total']", $pdf);
        $this->assertStringContainsString("['pending_total']", $pdf);
        $this->assertStringContainsString('Tổng thực xuất', $pdf);
        $this->assertStringContainsString('HÀNG CHỜ CUNG ỨNG', $pdf);
        $this->assertStringContainsString('show_deferred_supply', $pdf);
        $this->assertStringContainsString("'pending'=>", $documents);
        $this->assertStringContainsString("'posted'=>", $documents);
    }

    public function test_admin_toggle_is_persisted_and_defaults_to_enabled(): void
    {
        $root = base_path('Modules/Pharma/');
        $settings = file_get_contents($root.'Models/InventoryIssueDocumentSetting.php');
        $controller = file_get_contents($root.'Http/Controllers/InventoryController.php');
        $view = file_get_contents($root.'resources/views/pages/inventory/issue-settings.blade.php');
        $migration = file_get_contents($root.'database/migrations/2026_11_03_000001_add_deferred_supply_toggle_to_issue_document_settings.php');

        $this->assertStringContainsString("'show_deferred_supply'=>true", $settings);
        $this->assertStringContainsString("'show_deferred_supply','show_issuer_signature'", $controller);
        $this->assertStringContainsString("'show_deferred_supply' => 'Hàng chờ cung ứng'", $view);
        $this->assertStringContainsString("boolean('show_deferred_supply')->default(true)", $migration);
    }
}
