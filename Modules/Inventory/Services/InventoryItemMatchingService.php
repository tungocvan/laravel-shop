<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Str;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\InventoryItemAlias;
use Modules\Inventory\Models\InvoiceInbox;
use Modules\Inventory\Models\InvoiceInboxLine;

final class InventoryItemMatchingService
{
    public function matchInbox(InvoiceInbox $inbox): void
    {
        foreach ($inbox->lines as $line) {
            if ($line->classification === 'NON_STOCK' || ($line->classification === 'STOCK' && $line->inventory_item_id !== null)) {
                continue;
            }

            $match = $this->matchLine($inbox, $line);
            if ($match === null) {
                $classifiedStock = $line->classification === 'STOCK';
                $line->forceFill([
                    'classification' => $classifiedStock ? 'STOCK' : 'UNRESOLVED',
                    'inventory_item_id' => null,
                    'match_reason' => $classifiedStock ? $line->match_reason : ($line->match_reason ?: 'classifier:insufficient_deterministic_evidence'),
                    'base_quantity' => null,
                    'base_uom' => null,
                ])->save();

                continue;
            }

            [$item, $reason] = $match;
            $line->forceFill([
                'classification' => 'STOCK',
                'inventory_item_id' => $item->id,
                'match_reason' => $reason,
                'conversion_factor' => 1,
                'base_quantity' => $line->source_quantity,
                'base_uom' => $item->base_uom,
            ])->save();
        }

        $this->refreshInboxStatus($inbox);
    }

    public function assign(InvoiceInboxLine $line, InventoryItem $item, int $actorId, bool $rememberAlias = true): void
    {
        $line->forceFill([
            'classification' => 'STOCK',
            'inventory_item_id' => $item->id,
            'match_reason' => 'manual_review',
            'conversion_factor' => 1,
            'base_quantity' => $line->source_quantity,
            'base_uom' => $item->base_uom,
        ])->save();

        if ($rememberAlias) {
            $inbox = $line->inbox;
            $identity = [
                'source' => 'invoices',
                'supplier_tax_code' => trim((string) $inbox->seller_tax_code_snapshot),
                'source_product_code' => trim((string) $line->source_product_code),
                'normalized_description_key' => $line->normalized_description_key,
                'uom_key' => $this->key((string) $line->source_uom),
                'package_key' => '',
            ];

            InventoryItemAlias::query()->updateOrCreate([
                'alias_key' => hash('sha256', implode('|', $identity)),
            ], $identity + [
                'inventory_item_id' => $item->id,
                'supplier_partner_id' => $inbox->partner_id,
                'confirmed_by' => $actorId,
                'confirmed_at' => now(),
                'metadata' => ['source_invoice_identity' => $inbox->source_invoice_identity],
            ]);
        }

        $this->refreshInboxStatus($line->inbox);
    }

    public function markNonStock(InvoiceInboxLine $line): void
    {
        $line->forceFill([
            'classification' => 'NON_STOCK',
            'inventory_item_id' => null,
            'match_reason' => 'manual_non_stock',
            'base_quantity' => null,
            'base_uom' => null,
        ])->save();

        $this->refreshInboxStatus($line->inbox);
    }

    private function matchLine(InvoiceInbox $inbox, InvoiceInboxLine $line): ?array
    {
        $supplierTaxCode = trim((string) $inbox->seller_tax_code_snapshot);
        $sourceProductCode = trim((string) $line->source_product_code);
        $uomKey = $this->key((string) $line->source_uom);

        $aliasQuery = InventoryItemAlias::query()
            ->with('item')
            ->where('source', 'invoices')
            ->where('supplier_tax_code', $supplierTaxCode)
            ->where('normalized_description_key', $line->normalized_description_key)
            ->where('uom_key', $uomKey);

        if ($alias = (clone $aliasQuery)->first()) {
            return $alias->item?->is_active ? [$alias->item, 'confirmed_supplier_alias'] : null;
        }

        if ($sourceProductCode !== '') {
            $alias = InventoryItemAlias::query()
                ->with('item')
                ->where('source', 'invoices')
                ->where('supplier_tax_code', $supplierTaxCode)
                ->where('source_product_code', $sourceProductCode)
                ->first();

            if ($alias?->item?->is_active) {
                return [$alias->item, 'exact_source_product_code'];
            }
        }

        $items = InventoryItem::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(display_name) = ?', [mb_strtolower(trim((string) $line->description_snapshot))])
            ->limit(2)
            ->get();

        if ($items->count() === 1) {
            return [$items->first(), 'exact_display_name'];
        }

        return null;
    }

    private function refreshInboxStatus(InvoiceInbox $inbox): void
    {
        if ($inbox->receipt_id !== null) {
            $inbox->forceFill(['processing_status' => 'RECEIPT_CREATED'])->save();

            return;
        }

        $reviewRequired = $inbox->lines()
            ->where(function ($query): void {
                $query->where('classification', 'UNRESOLVED')
                    ->orWhere(function ($stockQuery): void {
                        $stockQuery->where('classification', 'STOCK')
                            ->whereNull('inventory_item_id');
                    });
            })
            ->exists();

        $inbox->forceFill(['processing_status' => $reviewRequired ? 'REVIEW_REQUIRED' : 'READY'])->save();
    }

    private function key(string $value): string
    {
        return Str::of($value)->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->substr(0, 100)->toString();
    }
}
