<?php

namespace Modules\Inventory\Services;

use DomainException;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\Lot;
use Modules\Inventory\Models\ReceiptLine;

class LotService
{
    public function resolveForReceiptLine(ReceiptLine $line, InventoryItem $item): ?Lot
    {
        if (! $item->lot_tracking && blank($line->lot_number)) {
            return null;
        }

        if ($item->lot_tracking && blank($line->lot_number)) {
            throw new DomainException('Lot number is required for lot-tracked inventory items.');
        }

        if ($item->expiry_tracking && $line->expiry_date === null) {
            throw new DomainException('Expiry date is required for expiry-tracked inventory items.');
        }

        $identityKey = hash('sha256', implode('|', [
            (string) $item->getKey(),
            mb_strtoupper(trim((string) $line->lot_number)),
            $line->expiry_date?->format('Y-m-d') ?? 'NO_EXPIRY',
        ]));

        return Lot::query()->firstOrCreate(
            ['identity_key' => $identityKey],
            [
                'inventory_item_id' => $item->getKey(),
                'lot_number' => trim((string) $line->lot_number),
                'expiry_date' => $line->expiry_date,
                'manufacture_date' => $line->manufacture_date,
                'source_receipt_line_id' => $line->getKey(),
                'status' => 'ACTIVE',
            ]
        );
    }
}
