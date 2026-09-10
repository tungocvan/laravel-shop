<?php

namespace Tests\Feature\Inventory;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InventoryBatchCInvoiceIntegrationContractTest extends TestCase
{
    #[Test]
    public function invoices_owns_source_detail_and_inventory_consumes_a_versioned_contract(): void
    {
        $factory = file_get_contents(base_path('Modules/Invoices/Integrations/Inventory/InvoiceForInventoryV1Factory.php'));
        $consumer = file_get_contents(base_path('Modules/Inventory/Services/InventoryInvoiceIntegrationService.php'));

        $this->assertStringContainsString("'contract_version' => '1.0'", $factory);
        $this->assertStringContainsString('GdtPdfService', $factory);
        $this->assertStringContainsString('fetchDetail($invoice)', $factory);
        $this->assertStringContainsString("str_starts_with(\$version, '1.')", $consumer);
        $this->assertStringNotContainsString('storage/app/invoices/pdf', $consumer);
        $this->assertStringNotContainsString('InvoicePdfService', $consumer);
    }

    #[Test]
    public function inbox_schema_has_idempotent_source_identity_and_payload_hash(): void
    {
        $migration = file_get_contents(base_path('Modules/Inventory/database/migrations/2026_09_09_160001_create_inventory_invoice_inbox_tables.php'));

        $this->assertStringContainsString('normalized_payload_hash', $migration);
        $this->assertStringContainsString('source_invoice_identity', $migration);
        $this->assertStringContainsString('integration_purpose', $migration);
        $this->assertStringContainsString('inventory_invoice_inbox_source_unique', $migration);
        $this->assertStringContainsString('inventory_invoice_inbox_lines', $migration);
    }

    #[Test]
    public function matching_is_deterministic_and_requires_human_review_for_unresolved_lines(): void
    {
        $matching = file_get_contents(base_path('Modules/Inventory/Services/InventoryItemMatchingService.php'));
        $workspace = file_get_contents(base_path('Modules/Inventory/Livewire/InvoiceInboxWorkspace.php'));

        $this->assertStringContainsString('confirmed_supplier_alias', $matching);
        $this->assertStringContainsString('exact_source_product_code', $matching);
        $this->assertStringContainsString('exact_display_name', $matching);
        $this->assertStringContainsString("'UNRESOLVED'", $matching);
        $this->assertStringContainsString('markNonStock', $workspace);
        $this->assertStringContainsString('beginCreateItem', $workspace);
        $this->assertStringContainsString('saveCreatedItem', $workspace);
        $this->assertStringContainsString('itemForm', $workspace);
        $this->assertStringNotContainsString('createStandaloneItem', $workspace);
        $this->assertStringNotContainsString('firstOrCreate([\'sku\' => \'INV-LINE-\'', $workspace);
        $this->assertStringNotContainsString('fuzzy', strtolower($matching));
        $this->assertStringNotContainsString('openai', strtolower($matching));
    }

    #[Test]
    public function invoice_proposal_creates_draft_receipt_without_stock_posting(): void
    {
        $proposal = file_get_contents(base_path('Modules/Inventory/Services/InvoiceReceiptProposalService.php'));
        $workspace = file_get_contents(base_path('Modules/Inventory/Livewire/InvoiceInboxWorkspace.php'));

        $this->assertStringContainsString("'status' => 'DRAFT'", $proposal);
        $this->assertStringContainsString("'source_type' => 'invoice'", $proposal);
        $this->assertStringContainsString('RECEIPT_CREATED', $proposal);
        $this->assertStringNotContainsString('ReceiptPostingService', $proposal);
        $this->assertStringNotContainsString('StockPostingService', $proposal);
        $this->assertStringContainsString('Tồn kho chưa thay đổi', $workspace);
    }

    #[Test]
    public function invoice_inbox_ui_uses_bounded_pagination_and_admin_visual_contract(): void
    {
        $routes = file_get_contents(base_path('Modules/Inventory/routes/web.php'));
        $component = file_get_contents(base_path('Modules/Inventory/Livewire/InvoiceInboxWorkspace.php'));
        $view = file_get_contents(base_path('Modules/Inventory/resources/views/livewire/invoice-inbox-workspace.blade.php'));

        $this->assertStringContainsString("name('invoice-inbox')", $routes);
        $this->assertStringContainsString('permission:inventory.receipt.view', $routes);
        $this->assertStringContainsString('private const PAGE_SIZES = [10, 25, 50, 100];', $component);
        $this->assertStringContainsString('border border-gray-300 bg-white', $view);
        $this->assertStringContainsString('wire:loading.attr="disabled"', $view);
        $this->assertStringContainsString("links('Inventory::vendor.pagination.admin-inventory')", $view);
    }
}
