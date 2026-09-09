<?php

namespace Modules\Inventory\Services;

use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\StockMovement;
use Modules\Inventory\Support\DecimalQuantity;

class StockPostingService
{
    /**
     * Post one atomic batch while the caller owns the surrounding DB transaction.
     *
     * @param  array<int, array<string, mixed>>  $movements
     * @return Collection<int, StockMovement>
     */
    public function postBatch(array $movements): Collection
    {
        $prepared = collect($movements)->map(fn (array $movement): array => $this->prepare($movement));
        $dimensionKeys = $prepared->pluck('dimension_key')->unique()->sort()->values();

        foreach ($prepared->unique('dimension_key') as $movement) {
            DB::table('inventory_balances')->insertOrIgnore([
                'warehouse_id' => $movement['warehouse_id'],
                'inventory_item_id' => $movement['inventory_item_id'],
                'lot_id' => $movement['lot_id'],
                'dimension_key' => $movement['dimension_key'],
                'quantity_on_hand' => '0.000000',
                'updated_at' => now(),
            ]);
        }

        $balances = DB::table('inventory_balances')
            ->whereIn('dimension_key', $dimensionKeys->all())
            ->orderBy('dimension_key')
            ->lockForUpdate()
            ->get()
            ->keyBy('dimension_key');

        $posted = collect();

        foreach ($prepared as $movement) {
            $existing = StockMovement::query()->where('movement_key', $movement['movement_key'])->first();
            if ($existing !== null) {
                $posted->push($existing);

                continue;
            }

            $balance = $balances->get($movement['dimension_key']);
            if ($balance === null) {
                throw new DomainException('Unable to lock inventory balance dimension.');
            }

            $newQuantity = DecimalQuantity::add((string) $balance->quantity_on_hand, $movement['quantity_delta']);
            if (DecimalQuantity::isNegative($newQuantity)) {
                throw new DomainException('Negative inventory stock is not allowed.');
            }

            $stockMovement = StockMovement::query()->create($movement);

            DB::table('inventory_balances')
                ->where('dimension_key', $movement['dimension_key'])
                ->update([
                    'quantity_on_hand' => $newQuantity,
                    'updated_at' => now(),
                ]);

            $balance->quantity_on_hand = $newQuantity;
            $posted->push($stockMovement);
        }

        return $posted;
    }

    /** @param array<string, mixed> $movement */
    private function prepare(array $movement): array
    {
        $required = [
            'movement_type', 'warehouse_id', 'inventory_item_id', 'quantity_delta', 'base_uom',
            'document_type', 'document_id', 'movement_role', 'occurred_at',
        ];

        foreach ($required as $key) {
            if (! array_key_exists($key, $movement)) {
                throw new DomainException("Missing stock movement field: {$key}");
            }
        }

        $lotId = $movement['lot_id'] ?? null;
        $documentLineId = $movement['document_line_id'] ?? null;
        $dimensionKey = hash('sha256', implode('|', [
            (string) $movement['warehouse_id'],
            (string) $movement['inventory_item_id'],
            $lotId === null ? 'NO_LOT' : (string) $lotId,
        ]));
        $movementKey = hash('sha256', implode('|', [
            (string) $movement['document_type'],
            (string) $movement['document_id'],
            $documentLineId === null ? 'NO_LINE' : (string) $documentLineId,
            (string) $movement['movement_role'],
        ]));

        return [
            ...$movement,
            'lot_id' => $lotId,
            'document_line_id' => $documentLineId,
            'dimension_key' => $dimensionKey,
            'movement_key' => $movementKey,
            'quantity_delta' => DecimalQuantity::normalize((string) $movement['quantity_delta']),
            'source_type' => $movement['source_type'] ?? null,
            'source_identity_key' => $movement['source_identity_key'] ?? null,
            'posted_by' => $movement['posted_by'] ?? null,
            'reversal_of_movement_id' => $movement['reversal_of_movement_id'] ?? null,
            'metadata' => $movement['metadata'] ?? null,
        ];
    }
}
