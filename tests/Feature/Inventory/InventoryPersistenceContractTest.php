<?php

namespace Tests\Feature\Inventory;

use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\InventoryItemAlias;
use Modules\Inventory\Models\Lot;
use Modules\Inventory\Models\Receipt;
use Modules\Inventory\Models\StockBalance;
use Modules\Inventory\Models\StockMovement;
use Modules\Inventory\Models\Warehouse;
use Tests\TestCase;

class InventoryPersistenceContractTest extends TestCase
{
    public function test_models_bind_to_inventory_owned_tables(): void
    {
        $this->assertSame('inventory_warehouses', (new Warehouse)->getTable());
        $this->assertSame('inventory_items', (new InventoryItem)->getTable());
        $this->assertSame('inventory_item_aliases', (new InventoryItemAlias)->getTable());
        $this->assertSame('inventory_lots', (new Lot)->getTable());
        $this->assertSame('inventory_receipts', (new Receipt)->getTable());
        $this->assertSame('inventory_movements', (new StockMovement)->getTable());
        $this->assertSame('inventory_balances', (new StockBalance)->getTable());
    }

    public function test_ledger_migration_declares_immutable_idempotency_and_projection_keys(): void
    {
        $source = file_get_contents(base_path('Modules/Inventory/database/migrations/2026_09_09_140003_create_inventory_ledger_tables.php'));

        $this->assertStringContainsString("string('movement_key', 64)->unique()", $source);
        $this->assertStringContainsString("string('dimension_key', 64)->unique()", $source);
        $this->assertStringContainsString("decimal('quantity_delta', 20, 6)", $source);
        $this->assertStringContainsString("decimal('quantity_on_hand', 20, 6)", $source);
        $this->assertStringContainsString('reversal_of_movement_id', $source);
    }

    public function test_foundation_uses_indexed_optional_integration_references_not_cross_module_foreign_keys(): void
    {
        $source = file_get_contents(base_path('Modules/Inventory/database/migrations/2026_09_09_140001_create_inventory_foundation_tables.php'));

        $this->assertStringContainsString("unsignedBigInteger('product_id')->nullable()->index()", $source);
        $this->assertStringContainsString("unsignedBigInteger('pharma_medicine_id')->nullable()->index()", $source);
        $this->assertStringContainsString("unsignedBigInteger('partner_id')->nullable()->index()", $source);
        $this->assertStringNotContainsString("constrained('wp_products')", $source);
        $this->assertStringNotContainsString("constrained('partners')", $source);
    }

    public function test_batch_a_contains_no_invoice_pdf_or_product_quantity_dual_write(): void
    {
        $paths = [
            base_path('Modules/Inventory/Models'),
            base_path('Modules/Inventory/Services'),
            base_path('Modules/Inventory/Support'),
        ];

        $source = collect($paths)
            ->flatMap(fn (string $path) => glob($path.'/*.php') ?: [])
            ->map(fn (string $path) => file_get_contents($path))
            ->implode("\n");

        $this->assertStringNotContainsString('storage/app/invoices/pdf', $source);
        $this->assertStringNotContainsString('Product.quantity', $source);
        $this->assertStringNotContainsString('->quantity =', $source);
    }
}
