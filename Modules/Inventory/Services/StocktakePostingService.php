<?php

namespace Modules\Inventory\Services;

use DomainException;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\Stocktake;
use Modules\Inventory\Models\StocktakeLine;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Support\DecimalQuantity;

class StocktakePostingService
{
    public function __construct(private readonly StockPostingService $stockPosting) {}

    public function confirm(int $stocktakeId, ?int $actorId = null): Stocktake
    {
        return DB::transaction(function () use ($stocktakeId, $actorId): Stocktake {
            $stocktake = Stocktake::query()->lockForUpdate()->findOrFail($stocktakeId);

            if ($stocktake->status === 'CONFIRMED') {
                return $stocktake;
            }

            if (! in_array($stocktake->status, ['DRAFT', 'COUNTED'], true)) {
                throw new DomainException('Only draft or counted stocktakes can be confirmed.');
            }

            $warehouse = Warehouse::query()->findOrFail($stocktake->warehouse_id);
            if (! $warehouse->is_active) {
                throw new DomainException('Stocktake warehouse must be active.');
            }

            $lines = StocktakeLine::query()
                ->where('stocktake_id', $stocktake->getKey())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($lines->isEmpty()) {
                throw new DomainException('Stocktake must contain at least one line.');
            }

            $prepared = [];

            foreach ($lines as $line) {
                $item = $line->item()->firstOrFail();

                if (DecimalQuantity::isNegative((string) $line->counted_quantity)) {
                    throw new DomainException('Counted stock quantity cannot be negative.');
                }

                if ($line->base_uom !== $item->base_uom) {
                    throw new DomainException('Stocktake line base UOM must match the inventory item base UOM.');
                }

                if ($item->lot_tracking && $line->lot_id === null) {
                    throw new DomainException('Lot selection is required for lot-tracked inventory items.');
                }

                if ($line->lot_id !== null && (int) $line->lot()->firstOrFail()->inventory_item_id !== (int) $item->getKey()) {
                    throw new DomainException('Stocktake lot does not belong to the selected inventory item.');
                }

                $dimensionKey = hash('sha256', implode('|', [
                    (string) $stocktake->warehouse_id,
                    (string) $item->getKey(),
                    $line->lot_id === null ? 'NO_LOT' : (string) $line->lot_id,
                ]));

                DB::table('inventory_balances')->insertOrIgnore([
                    'warehouse_id' => $stocktake->warehouse_id,
                    'inventory_item_id' => $item->getKey(),
                    'lot_id' => $line->lot_id,
                    'dimension_key' => $dimensionKey,
                    'quantity_on_hand' => '0.000000',
                    'updated_at' => now(),
                ]);

                $prepared[] = ['line' => $line, 'item' => $item, 'dimension_key' => $dimensionKey];
            }

            $balances = DB::table('inventory_balances')
                ->whereIn('dimension_key', collect($prepared)->pluck('dimension_key')->unique()->sort()->values()->all())
                ->orderBy('dimension_key')
                ->lockForUpdate()
                ->get()
                ->keyBy('dimension_key');

            $movements = [];

            foreach ($prepared as $entry) {
                /** @var StocktakeLine $line */
                $line = $entry['line'];
                $item = $entry['item'];
                $current = DecimalQuantity::normalize((string) $balances->get($entry['dimension_key'])->quantity_on_hand);
                $variance = DecimalQuantity::add((string) $line->counted_quantity, DecimalQuantity::negate($current));

                $line->system_quantity_snapshot = $current;
                $line->posted_variance = $variance;
                $line->save();

                if (DecimalQuantity::isZero($variance)) {
                    continue;
                }

                $movements[] = [
                    'movement_type' => 'STOCKTAKE_ADJUSTMENT',
                    'warehouse_id' => $stocktake->warehouse_id,
                    'inventory_item_id' => $item->getKey(),
                    'lot_id' => $line->lot_id,
                    'quantity_delta' => $variance,
                    'base_uom' => $item->base_uom,
                    'document_type' => 'stocktake',
                    'document_id' => $stocktake->getKey(),
                    'document_line_id' => $line->getKey(),
                    'movement_role' => 'VARIANCE',
                    'occurred_at' => now(),
                    'posted_by' => $actorId,
                ];
            }

            if ($movements !== []) {
                $this->stockPosting->postBatch($movements);
            }

            $stocktake->status = 'CONFIRMED';
            $stocktake->confirmed_by = $actorId;
            $stocktake->confirmed_at = now();
            $stocktake->save();

            return $stocktake->refresh();
        }, 3);
    }
}
