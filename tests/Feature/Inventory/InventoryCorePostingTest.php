<?php

namespace Tests\Feature\Inventory;

use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use LogicException;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\Issue;
use Modules\Inventory\Models\IssueLine;
use Modules\Inventory\Models\Receipt;
use Modules\Inventory\Models\ReceiptLine;
use Modules\Inventory\Models\StockMovement;
use Modules\Inventory\Models\Stocktake;
use Modules\Inventory\Models\StocktakeLine;
use Modules\Inventory\Models\Transfer;
use Modules\Inventory\Models\TransferLine;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\IssuePostingService;
use Modules\Inventory\Services\MovementReversalService;
use Modules\Inventory\Services\ReceiptPostingService;
use Modules\Inventory\Services\StocktakePostingService;
use Modules\Inventory\Services\TransferPostingService;
use Tests\TestCase;

class InventoryCorePostingTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', [
            '--path' => 'Modules/Inventory/database/migrations',
            '--force' => true,
        ]);

        foreach ([
            'inventory.receipt.confirm',
            'inventory.issue.confirm',
            'inventory.transfer.confirm',
            'inventory.stocktake.confirm',
            'inventory.movement.reverse',
        ] as $ability) {
            Gate::define($ability, fn (User $user): bool => true);
        }

        $this->actor = User::factory()->create();
    }

    public function test_receipt_confirmation_is_retry_idempotent_and_updates_projection_once(): void
    {
        [$warehouse, $item] = $this->foundation();
        $receipt = Receipt::query()->create([
            'number' => 'RCV-001',
            'warehouse_id' => $warehouse->id,
            'status' => 'DRAFT',
            'source_type' => 'manual',
        ]);
        ReceiptLine::query()->create([
            'receipt_id' => $receipt->id,
            'line_number' => 1,
            'inventory_item_id' => $item->id,
            'classification' => 'STOCK',
            'source_quantity' => '10.000000',
            'conversion_factor' => '1.00000000',
            'base_quantity' => '10.000000',
            'base_uom' => 'EA',
        ]);

        app(ReceiptPostingService::class)->confirm($receipt->id, $this->actor);
        app(ReceiptPostingService::class)->confirm($receipt->id, $this->actor);

        $this->assertDatabaseCount('inventory_movements', 1);
        $this->assertDatabaseHas('inventory_balances', [
            'warehouse_id' => $warehouse->id,
            'inventory_item_id' => $item->id,
            'quantity_on_hand' => '10.000000',
        ]);
        $this->assertSame('CONFIRMED', $receipt->refresh()->status);
    }

    public function test_issue_cannot_make_stock_negative_and_rolls_back_confirmation(): void
    {
        [$warehouse, $item] = $this->foundation();
        $this->seedStock($warehouse, $item, '5.000000');

        $issue = Issue::query()->create([
            'number' => 'ISS-001',
            'warehouse_id' => $warehouse->id,
            'status' => 'DRAFT',
        ]);
        IssueLine::query()->create([
            'issue_id' => $issue->id,
            'line_number' => 1,
            'inventory_item_id' => $item->id,
            'base_quantity' => '6.000000',
            'base_uom' => 'EA',
        ]);

        try {
            app(IssuePostingService::class)->confirm($issue->id, $this->actor);
            $this->fail('Negative stock confirmation should fail.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('Negative inventory stock', $exception->getMessage());
        }

        $this->assertSame('DRAFT', $issue->refresh()->status);
        $this->assertDatabaseHas('inventory_balances', [
            'warehouse_id' => $warehouse->id,
            'inventory_item_id' => $item->id,
            'quantity_on_hand' => '5.000000',
        ]);
        $this->assertDatabaseCount('inventory_movements', 1);
    }

    public function test_transfer_posts_atomic_out_and_in_pair(): void
    {
        [$source, $item] = $this->foundation();
        $destination = Warehouse::query()->create(['code' => 'WH-B', 'name' => 'Warehouse B', 'is_active' => true]);
        $this->seedStock($source, $item, '8.000000');

        $transfer = Transfer::query()->create([
            'number' => 'TRF-001',
            'source_warehouse_id' => $source->id,
            'destination_warehouse_id' => $destination->id,
            'status' => 'DRAFT',
        ]);
        TransferLine::query()->create([
            'transfer_id' => $transfer->id,
            'line_number' => 1,
            'inventory_item_id' => $item->id,
            'base_quantity' => '3.000000',
            'base_uom' => 'EA',
        ]);

        app(TransferPostingService::class)->confirm($transfer->id, $this->actor);

        $this->assertDatabaseHas('inventory_balances', [
            'warehouse_id' => $source->id,
            'inventory_item_id' => $item->id,
            'quantity_on_hand' => '5.000000',
        ]);
        $this->assertDatabaseHas('inventory_balances', [
            'warehouse_id' => $destination->id,
            'inventory_item_id' => $item->id,
            'quantity_on_hand' => '3.000000',
        ]);
        $this->assertSame(2, DB::table('inventory_movements')->where('document_type', 'transfer')->count());
        $this->assertSame('CONFIRMED', $transfer->refresh()->status);
    }

    public function test_stocktake_posts_only_variance(): void
    {
        [$warehouse, $item] = $this->foundation();
        $this->seedStock($warehouse, $item, '10.000000');

        $stocktake = Stocktake::query()->create([
            'number' => 'STK-001',
            'warehouse_id' => $warehouse->id,
            'status' => 'COUNTED',
        ]);
        StocktakeLine::query()->create([
            'stocktake_id' => $stocktake->id,
            'line_number' => 1,
            'inventory_item_id' => $item->id,
            'system_quantity_snapshot' => '10.000000',
            'counted_quantity' => '7.000000',
            'base_uom' => 'EA',
        ]);

        app(StocktakePostingService::class)->confirm($stocktake->id, $this->actor);

        $this->assertDatabaseHas('inventory_movements', [
            'document_type' => 'stocktake',
            'movement_type' => 'STOCKTAKE_ADJUSTMENT',
            'quantity_delta' => '-3.000000',
        ]);
        $this->assertDatabaseHas('inventory_balances', [
            'warehouse_id' => $warehouse->id,
            'inventory_item_id' => $item->id,
            'quantity_on_hand' => '7.000000',
        ]);
    }

    public function test_confirmed_receipt_and_its_lines_are_immutable(): void
    {
        [$warehouse, $item] = $this->foundation();
        $receipt = $this->seedStock($warehouse, $item, '1.000000');
        $line = $receipt->lines()->firstOrFail();

        try {
            $receipt->notes = 'illegal edit';
            $receipt->save();
            $this->fail('Confirmed receipt should be immutable.');
        } catch (LogicException) {
            $this->assertTrue(true);
        }

        $this->expectException(LogicException::class);
        $line->base_quantity = '2.000000';
        $line->save();
    }

    public function test_stock_movement_is_immutable_and_reversal_is_compensating_and_idempotent(): void
    {
        [$warehouse, $item] = $this->foundation();
        $this->seedStock($warehouse, $item, '4.000000');
        $movement = StockMovement::query()->firstOrFail();

        try {
            $movement->quantity_delta = '9.000000';
            $movement->save();
            $this->fail('Stock movement should be immutable.');
        } catch (LogicException) {
            $this->assertTrue(true);
        }

        app(MovementReversalService::class)->reverse($movement->id, 'Correct posting', $this->actor);
        app(MovementReversalService::class)->reverse($movement->id, 'Retry same correction', $this->actor);

        $this->assertDatabaseCount('inventory_movements', 2);
        $this->assertDatabaseHas('inventory_movements', [
            'movement_type' => 'REVERSAL',
            'reversal_of_movement_id' => $movement->id,
            'quantity_delta' => '-4.000000',
        ]);
        $this->assertDatabaseHas('inventory_balances', [
            'warehouse_id' => $warehouse->id,
            'inventory_item_id' => $item->id,
            'quantity_on_hand' => '0.000000',
        ]);
    }

    private function foundation(): array
    {
        $warehouse = Warehouse::query()->create(['code' => 'WH-A', 'name' => 'Warehouse A', 'is_active' => true]);
        $item = InventoryItem::query()->create([
            'sku' => 'SKU-001',
            'display_name' => 'Item 1',
            'base_uom' => 'EA',
            'is_active' => true,
        ]);

        return [$warehouse, $item];
    }

    private function seedStock(Warehouse $warehouse, InventoryItem $item, string $quantity): Receipt
    {
        $receipt = Receipt::query()->create([
            'number' => 'RCV-'.str()->uuid(),
            'warehouse_id' => $warehouse->id,
            'status' => 'DRAFT',
            'source_type' => 'manual',
        ]);
        ReceiptLine::query()->create([
            'receipt_id' => $receipt->id,
            'line_number' => 1,
            'inventory_item_id' => $item->id,
            'classification' => 'STOCK',
            'source_quantity' => $quantity,
            'conversion_factor' => '1.00000000',
            'base_quantity' => $quantity,
            'base_uom' => $item->base_uom,
        ]);
        app(ReceiptPostingService::class)->confirm($receipt->id, $this->actor);

        return $receipt->refresh();
    }
}
