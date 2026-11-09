<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\InventoryBalance;
use Modules\Pharma\Models\InventoryIssue;
use Modules\Pharma\Models\InventoryReceipt;
use Modules\Pharma\Models\InventoryTransaction;
use Modules\Pharma\Models\InventoryWarehouse;
use Modules\Pharma\Services\OpeningInventoryResetService;
use Tests\TestCase;

/**
 * Isolated in-memory database: never operates on application stock tables.
 */
final class PharmaOpeningResetIntegrationTest extends TestCase
{
    private string $previousConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousConnection = DB::getDefaultConnection();
        config()->set('database.connections.pharma_opening_reset_test', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('pharma_opening_reset_test');
        DB::setDefaultConnection('pharma_opening_reset_test');

        Schema::create('pharma_inventory_warehouses', function (Blueprint $t): void {
            $t->id(); $t->string('code')->nullable(); $t->dateTime('opening_cutoff_at')->nullable();
            $t->timestamps();
        });
        Schema::create('pharma_inventory_receipts', function (Blueprint $t): void {
            $t->id(); $t->timestamps();
        });
        Schema::create('pharma_inventory_issues', function (Blueprint $t): void {
            $t->id(); $t->timestamps();
        });
        Schema::create('pharma_inventory_balances', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('warehouse_id'); $t->unsignedBigInteger('medicine_id');
            $t->string('batch_number'); $t->date('expiry_date');
            $t->decimal('opening_quantity', 15, 3); $t->decimal('quantity_on_hand', 15, 3);
            $t->timestamps();
        });
        Schema::create('pharma_inventory_transactions', function (Blueprint $t): void {
            $t->id(); $t->unsignedBigInteger('warehouse_id'); $t->unsignedBigInteger('medicine_id');
            $t->string('batch_number'); $t->date('expiry_date'); $t->string('type');
            $t->decimal('quantity_delta', 15, 3); $t->decimal('balance_after', 15, 3);
            $t->string('source_type')->nullable(); $t->unsignedBigInteger('source_id')->nullable();
            $t->timestamps();
        });
        InventoryWarehouse::create(['code' => 'MAIN', 'opening_cutoff_at' => '2026-11-02 00:00:00']);
        InventoryBalance::create([
            'warehouse_id' => 1, 'medicine_id' => 1, 'batch_number' => 'LOT-A',
            'expiry_date' => '2028-01-01', 'opening_quantity' => 100, 'quantity_on_hand' => 100,
        ]);
        $this->movement('opening', 100, null, null);
    }

    protected function tearDown(): void
    {
        DB::setDefaultConnection($this->previousConnection);
        DB::disconnect('pharma_opening_reset_test');
        DB::purge('pharma_opening_reset_test');
        parent::tearDown();
    }

    private function movement(string $type, float $quantity, ?string $sourceType, ?int $sourceId): void
    {
        InventoryTransaction::create([
            'warehouse_id' => 1, 'medicine_id' => 1, 'batch_number' => 'LOT-A',
            'expiry_date' => '2028-01-01', 'type' => $type, 'quantity_delta' => $quantity,
            'balance_after' => 100, 'source_type' => $sourceType, 'source_id' => $sourceId,
        ]);
    }

    public function test_pristine_opening_reset_clears_cutoff_and_allows_reimport(): void
    {
        $this->assertSame(1, app(OpeningInventoryResetService::class)->reset());
        $this->assertSame(0, InventoryBalance::count());
        $this->assertSame(0, InventoryTransaction::count());
        $this->assertNull(InventoryWarehouse::findOrFail(1)->opening_cutoff_at);
        InventoryBalance::create([
            'warehouse_id' => 1, 'medicine_id' => 1, 'batch_number' => 'LOT-A',
            'expiry_date' => '2028-01-01', 'opening_quantity' => 200, 'quantity_on_hand' => 200,
        ]);
        $this->assertSame(1, InventoryBalance::count());
    }

    public function test_fully_reversed_orphan_history_can_reset(): void
    {
        $this->movement('issue', -5, InventoryIssue::class, 123);
        $this->movement('issue_reversal', 5, InventoryIssue::class, 123);
        $this->assertSame(1, app(OpeningInventoryResetService::class)->reset());
        $this->assertSame(0, InventoryTransaction::count());
    }

    public function test_existing_document_blocks_reset_without_mutation(): void
    {
        DB::table('pharma_inventory_receipts')->insert(['id' => 1]);
        $this->assertFalse(app(OpeningInventoryResetService::class)->eligibility()['allowed']);
        $this->assertSame(1, InventoryTransaction::count());
    }

    public function test_unmatched_reversal_blocks_reset(): void
    {
        $this->movement('issue', -5, InventoryIssue::class, 123);
        $this->assertFalse(app(OpeningInventoryResetService::class)->eligibility()['allowed']);
        $this->assertSame(2, InventoryTransaction::count());
    }

    public function test_cross_document_net_zero_does_not_count_as_reversed(): void
    {
        $this->movement('issue', -5, InventoryIssue::class, 123);
        $this->movement('issue_reversal', 5, InventoryIssue::class, 456);
        $this->assertFalse(app(OpeningInventoryResetService::class)->eligibility()['allowed']);
    }

    public function test_wrong_source_type_blocks_reset(): void
    {
        $this->movement('issue', -5, InventoryReceipt::class, 123);
        $this->movement('issue_reversal', 5, InventoryReceipt::class, 123);
        $this->assertFalse(app(OpeningInventoryResetService::class)->eligibility()['allowed']);
    }

    public function test_opening_balance_mismatch_blocks_reset(): void
    {
        InventoryBalance::firstOrFail()->update(['quantity_on_hand' => 99]);
        $this->assertFalse(app(OpeningInventoryResetService::class)->eligibility()['allowed']);
    }
}
