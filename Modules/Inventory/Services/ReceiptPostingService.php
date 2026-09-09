<?php

namespace Modules\Inventory\Services;

use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\Receipt;
use Modules\Inventory\Models\ReceiptLine;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Support\DecimalQuantity;

class ReceiptPostingService
{
    public function __construct(
        private readonly StockPostingService $stockPosting,
        private readonly LotService $lots,
        private readonly InventoryAuthorizationService $authorization,
    ) {}

    public function confirm(int $receiptId, User $actor): Receipt
    {
        $this->authorization->authorize($actor, 'inventory.receipt.confirm');

        return DB::transaction(function () use ($receiptId, $actor): Receipt {
            $receipt = Receipt::query()->lockForUpdate()->findOrFail($receiptId);

            if ($receipt->status === 'CONFIRMED') {
                return $receipt;
            }

            if ($receipt->status !== 'DRAFT') {
                throw new DomainException('Only draft receipts can be confirmed.');
            }

            $warehouse = Warehouse::query()->findOrFail($receipt->warehouse_id);
            if (! $warehouse->is_active) {
                throw new DomainException('Receipt warehouse must be active.');
            }

            $lines = ReceiptLine::query()
                ->where('receipt_id', $receipt->getKey())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($lines->isEmpty()) {
                throw new DomainException('Receipt must contain at least one line.');
            }

            $movements = [];

            foreach ($lines as $line) {
                if ($line->classification === 'NON_STOCK') {
                    continue;
                }

                if ($line->classification !== 'STOCK' || $line->inventory_item_id === null) {
                    throw new DomainException('Receipt contains unresolved stock lines.');
                }

                $item = $line->item()->firstOrFail();
                if (! $item->is_active) {
                    throw new DomainException('Receipt inventory item must be active.');
                }

                if (! DecimalQuantity::isPositive((string) $line->base_quantity)) {
                    throw new DomainException('Receipt quantity must be positive.');
                }

                if (! $item->allow_fractional_quantity && DecimalQuantity::hasFractionalPart((string) $line->base_quantity)) {
                    throw new DomainException('Fractional quantity is not allowed for this inventory item.');
                }

                if ($line->base_uom !== $item->base_uom) {
                    throw new DomainException('Receipt line base UOM must match the inventory item base UOM.');
                }

                $lot = $this->lots->resolveForReceiptLine($line, $item);
                if ($lot !== null && $line->lot_id !== $lot->getKey()) {
                    $line->lot_id = $lot->getKey();
                    $line->save();
                }

                $movements[] = [
                    'movement_type' => 'RECEIPT',
                    'warehouse_id' => $receipt->warehouse_id,
                    'inventory_item_id' => $item->getKey(),
                    'lot_id' => $lot?->getKey(),
                    'quantity_delta' => (string) $line->base_quantity,
                    'base_uom' => $item->base_uom,
                    'document_type' => 'receipt',
                    'document_id' => $receipt->getKey(),
                    'document_line_id' => $line->getKey(),
                    'movement_role' => 'IN',
                    'source_type' => $receipt->source_type,
                    'source_identity_key' => $receipt->source_identity_key,
                    'occurred_at' => $receipt->document_date ?? now(),
                    'posted_by' => $actor->getKey(),
                ];
            }

            if ($movements === []) {
                throw new DomainException('Receipt has no stock lines to post.');
            }

            $this->stockPosting->postBatch($movements);

            $receipt->status = 'CONFIRMED';
            $receipt->confirmed_by = $actor->getKey();
            $receipt->confirmed_at = now();
            $receipt->save();

            return $receipt->refresh();
        }, 3);
    }
}
