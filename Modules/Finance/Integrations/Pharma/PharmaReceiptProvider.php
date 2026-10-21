<?php

namespace Modules\Finance\Integrations\Pharma;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Contracts\FinancialDocumentProvider;
use Modules\Finance\Data\FinancialDocumentSnapshot;

final class PharmaReceiptProvider implements FinancialDocumentProvider
{
    public function module(): string
    {
        return 'Pharma';
    }

    public function documentType(): string
    {
        return 'inventory_receipt';
    }

    public function resolveForPosting(string $documentId): FinancialDocumentSnapshot
    {
        if (! (bool) config('modules.registry.Pharma.enabled', false)) {
            throw ValidationException::withMessages(['source' => 'Pharma module is disabled.']);
        }

        if (! ctype_digit($documentId) || $documentId === '0') {
            throw ValidationException::withMessages(['source' => 'Invalid Pharma receipt identifier.']);
        }

        $receipt = DB::table('pharma_inventory_receipts')->where('id', $documentId)->lockForUpdate()->first();

        if (! $receipt || $receipt->status !== 'posted'
            || empty($receipt->invoice_number) || empty($receipt->invoice_date)) {
            throw ValidationException::withMessages(['source' => 'Receipt must be posted and have an invoice reference.']);
        }

        $items = DB::table('pharma_inventory_receipt_items')->where('receipt_id', $receipt->id)->lockForUpdate();
        if (! (clone $items)->exists()
            || (clone $items)->where(function ($query): void {
                $query->whereNull('invoice_unit_price_ex_vat')
                    ->orWhere('invoice_unit_price_ex_vat', '<=', 0)
                    ->orWhere('quantity', '<=', 0);
            })->exists()) {
            throw ValidationException::withMessages(['source' => 'Receipt invoice prices and quantities must be complete.']);
        }

        // Calculation stays in SQL DECIMAL; never convert monetary values to PHP floats.
        $total = (clone $items)->selectRaw(
            'ROUND(SUM(quantity * invoice_unit_price_ex_vat * (1 + vat_rate / 100.0)), 2) AS total'
        )->value('total');

        if ($total === null || ! preg_match('/^\d+(?:\.\d{1,2})?$/', (string) $total)) {
            throw ValidationException::withMessages(['source' => 'Invoice total cannot be resolved.']);
        }

        return new FinancialDocumentSnapshot(
            module: 'Pharma',
            type: 'inventory_receipt',
            documentId: (string) $receipt->id,
            documentCode: (string) $receipt->number,
            direction: 'payable',
            currency: 'VND',
            amount: (string) $total,
            counterparty: $receipt->supplier_name,
        );
    }
}
