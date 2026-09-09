<?php

namespace Modules\Inventory\Services;

use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\Transfer;
use Modules\Inventory\Models\TransferLine;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Support\DecimalQuantity;

class TransferPostingService
{
    public function __construct(
        private readonly StockPostingService $stockPosting,
        private readonly InventoryAuthorizationService $authorization,
    ) {}

    public function confirm(int $transferId, User $actor): Transfer
    {
        $this->authorization->authorize($actor, 'inventory.transfer.confirm');

        return DB::transaction(function () use ($transferId, $actor): Transfer {
            $transfer = Transfer::query()->lockForUpdate()->findOrFail($transferId);

            if ($transfer->status === 'CONFIRMED') {
                return $transfer;
            }

            if ($transfer->status !== 'DRAFT') {
                throw new DomainException('Only draft transfers can be confirmed.');
            }

            if ((int) $transfer->source_warehouse_id === (int) $transfer->destination_warehouse_id) {
                throw new DomainException('Transfer source and destination warehouses must differ.');
            }

            $warehouses = Warehouse::query()
                ->whereIn('id', [$transfer->source_warehouse_id, $transfer->destination_warehouse_id])
                ->get()
                ->keyBy('id');

            if ($warehouses->count() !== 2 || $warehouses->contains(fn (Warehouse $warehouse): bool => ! $warehouse->is_active)) {
                throw new DomainException('Both transfer warehouses must exist and be active.');
            }

            $lines = TransferLine::query()->where('transfer_id', $transfer->getKey())->orderBy('id')->lockForUpdate()->get();
            if ($lines->isEmpty()) {
                throw new DomainException('Transfer must contain at least one line.');
            }

            $movements = [];

            foreach ($lines as $line) {
                $item = $line->item()->firstOrFail();

                if (! DecimalQuantity::isPositive((string) $line->base_quantity)) {
                    throw new DomainException('Transfer quantity must be positive.');
                }

                if ($line->base_uom !== $item->base_uom) {
                    throw new DomainException('Transfer line base UOM must match the inventory item base UOM.');
                }

                if ($item->lot_tracking && $line->lot_id === null) {
                    throw new DomainException('Lot selection is required for lot-tracked inventory items.');
                }

                if ($line->lot_id !== null && (int) $line->lot()->firstOrFail()->inventory_item_id !== (int) $item->getKey()) {
                    throw new DomainException('Transfer lot does not belong to the selected inventory item.');
                }

                $common = [
                    'inventory_item_id' => $item->getKey(),
                    'lot_id' => $line->lot_id,
                    'base_uom' => $item->base_uom,
                    'document_type' => 'transfer',
                    'document_id' => $transfer->getKey(),
                    'document_line_id' => $line->getKey(),
                    'occurred_at' => $transfer->document_date ?? now(),
                    'posted_by' => $actor->getKey(),
                ];

                $movements[] = [
                    ...$common,
                    'movement_type' => 'TRANSFER_OUT',
                    'warehouse_id' => $transfer->source_warehouse_id,
                    'quantity_delta' => DecimalQuantity::negate((string) $line->base_quantity),
                    'movement_role' => 'OUT',
                ];
                $movements[] = [
                    ...$common,
                    'movement_type' => 'TRANSFER_IN',
                    'warehouse_id' => $transfer->destination_warehouse_id,
                    'quantity_delta' => (string) $line->base_quantity,
                    'movement_role' => 'IN',
                ];
            }

            $this->stockPosting->postBatch($movements);

            $transfer->status = 'CONFIRMED';
            $transfer->confirmed_by = $actor->getKey();
            $transfer->confirmed_at = now();
            $transfer->save();

            return $transfer->refresh();
        }, 3);
    }
}
