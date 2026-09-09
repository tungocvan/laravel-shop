<?php

namespace Modules\Invoices\Integrations\Inventory;

use DomainException;
use Modules\Invoices\Models\Invoices;
use Modules\Invoices\Services\GdtPdfService;

final class InvoiceForInventoryV1Factory
{
    public function __construct(private readonly GdtPdfService $gdtDetailService) {}

    public function build(Invoices $invoice): array
    {
        if ($invoice->invoice_type !== 'purchase') {
            throw new DomainException('Chỉ hóa đơn mua vào mới được đưa sang Inventory.');
        }

        $detail = $this->gdtDetailService->fetchDetail($invoice);
        $rawLines = is_array($detail['hdhhdvu'] ?? null) ? $detail['hdhhdvu'] : [];
        if ($rawLines === []) {
            throw new DomainException('GDT không trả chi tiết hàng hóa cho hóa đơn này.');
        }

        $identity = $this->identity($invoice, $detail);
        $lines = [];

        foreach (array_values($rawLines) as $index => $line) {
            if (! is_array($line)) {
                continue;
            }

            $description = trim((string) ($line['ten'] ?? ''));
            $quantity = $line['sluong'] ?? null;
            if ($description === '' || ! is_numeric($quantity) || (float) $quantity <= 0) {
                continue;
            }

            $lineNumber = (int) ($line['stt'] ?? ($index + 1));
            $lines[] = [
                'line_number' => $lineNumber,
                'source_line_key' => hash('sha256', $identity.'|'.$lineNumber.'|'.$description.'|'.$quantity.'|'.($line['dvtinh'] ?? '')),
                'product_code' => $this->nullableString($line['mhhdvu'] ?? $line['ma'] ?? null),
                'description' => $description,
                'uom' => $this->nullableString($line['dvtinh'] ?? null),
                'quantity' => $quantity,
                'unit_price' => $this->nullableNumber($line['dgia'] ?? null),
                'line_amount' => $this->nullableNumber($line['thtien'] ?? null),
                'lot_number' => $this->nullableString($line['solo'] ?? $line['lot'] ?? null),
                'expiry_date' => $this->nullableString($line['hsd'] ?? $line['expiry_date'] ?? null),
                'manufacture_date' => $this->nullableString($line['nsx'] ?? $line['manufacture_date'] ?? null),
                'metadata' => [
                    'tax_rate' => $line['tsuat'] ?? $line['ltsuat'] ?? null,
                    'raw_gdt_line' => $line,
                ],
            ];
        }

        if ($lines === []) {
            throw new DomainException('Không có dòng hàng hóa hợp lệ để đưa sang Inventory.');
        }

        return [
            'contract_version' => '1.0',
            'source_invoice_identity' => $identity,
            'source_invoice_id' => $invoice->getKey(),
            'invoice_number' => $invoice->invoice_number,
            'invoice_symbol' => $invoice->symbol,
            'issued_at' => $invoice->issued_date?->toDateString(),
            'seller' => [
                'name' => $this->nullableString($detail['nbten'] ?? $invoice->name),
                'tax_code' => $this->nullableString($detail['nbmst'] ?? $invoice->tax_code),
                'address' => $this->nullableString($detail['nbdchi'] ?? $invoice->address),
            ],
            'totals' => [
                'amount_before_vat' => $this->nullableNumber($detail['tgtcthue'] ?? $invoice->amount_before_vat),
                'vat_amount' => $this->nullableNumber($detail['tgtthue'] ?? $invoice->vat_amount),
                'total_amount' => $this->nullableNumber($detail['tgtttbso'] ?? $invoice->total_amount),
            ],
            'metadata' => [
                'lookup_code' => $invoice->lookup_code,
                'gdt_message_id' => $detail['mhdon'] ?? null,
                'source' => 'gdt_detail',
            ],
            'lines' => $lines,
        ];
    }

    private function identity(Invoices $invoice, array $detail): string
    {
        $lookup = trim((string) $invoice->lookup_code);
        if ($lookup !== '') {
            return 'gdt:purchase:lookup:'.$lookup;
        }

        return 'gdt:purchase:'.implode(':', [
            trim((string) ($detail['nbmst'] ?? $invoice->tax_code)),
            trim((string) ($detail['khmshdon'] ?? '')),
            trim((string) ($detail['khhdon'] ?? $invoice->symbol)),
            trim((string) ($detail['shdon'] ?? $invoice->invoice_number)),
        ]);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableNumber(mixed $value): int|float|string|null
    {
        return is_numeric($value) ? $value : null;
    }
}
