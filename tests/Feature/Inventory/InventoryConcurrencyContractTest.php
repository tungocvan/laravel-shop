<?php

namespace Tests\Feature\Inventory;

use Tests\TestCase;

class InventoryConcurrencyContractTest extends TestCase
{
    public function test_posting_uses_deterministic_keys_unique_constraints_and_sorted_row_locks(): void
    {
        $posting = file_get_contents(base_path('Modules/Inventory/Services/StockPostingService.php'));
        $ledgerMigration = file_get_contents(base_path('Modules/Inventory/database/migrations/2026_09_09_140003_create_inventory_ledger_tables.php'));

        $this->assertStringContainsString("string('movement_key', 64)->unique()", $ledgerMigration);
        $this->assertStringContainsString("string('dimension_key', 64)->unique()", $ledgerMigration);
        $this->assertStringContainsString("hash('sha256'", $posting);
        $this->assertStringContainsString("->orderBy('dimension_key')", $posting);
        $this->assertStringContainsString('->lockForUpdate()', $posting);
        $this->assertStringContainsString('insertOrIgnore', $posting);
        $this->assertStringContainsString("where('movement_key'", $posting);
    }

    public function test_all_confirm_services_lock_documents_and_retry_transactions(): void
    {
        foreach ([
            'ReceiptPostingService.php',
            'IssuePostingService.php',
            'TransferPostingService.php',
            'StocktakePostingService.php',
        ] as $file) {
            $source = file_get_contents(base_path('Modules/Inventory/Services/'.$file));

            $this->assertStringContainsString('lockForUpdate()', $source, $file);
            $this->assertStringContainsString('}, 3);', $source, $file);
        }
    }

    public function test_stocktake_locks_balance_dimensions_in_deterministic_order(): void
    {
        $source = file_get_contents(base_path('Modules/Inventory/Services/StocktakePostingService.php'));

        $this->assertStringContainsString('->unique()->sort()->values()->all()', $source);
        $this->assertStringContainsString("->orderBy('dimension_key')", $source);
        $this->assertStringContainsString('Stocktake contains duplicate stock dimensions.', $source);
    }
}
