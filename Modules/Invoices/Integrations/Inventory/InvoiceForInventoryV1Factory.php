<?php

namespace Modules\Invoices\Integrations\Inventory;

use DomainException;
use Modules\Invoices\Models\InvoiceInventorySnapshot;
use Modules\Invoices\Models\Invoices;
use Modules\Invoices\Models\InvoiceSourceRecord;
use Modules\Invoices\Services\GdtPdfService;

final class InvoiceForInventoryV1Factory
{
    public function __construct(
        private readonly GdtPdfService $gdtDetailService,
        private readonly InvoiceLineNormalizer $lineNormalizer,
    ) {}

    public function build(Invoices $invoice): array
    {
        if ($invoice->invoice_type !== 'purchase') {
            throw new DomainException('Chỉ hóa đơn mua vào mới được đưa sang Inventory.');
        }

        $snapshot = InvoiceInventorySnapshot::query()
            ->with('lines')
            ->where('invoice_id', $invoice->id)
            ->where('source', 'gdt_detail')
            ->first();

        if ($snapshot?->status === 'NORMALIZED' && $snapshot->lines->isNotEmpty()) {
            return $this->buildFromSnapshot($snapshot);
        }

        $detail = $this->gdtDetailService->fetchDetail($invoice);

        return $this->buildFromDetail($invoice, $detail);
    }

    public function buildFromSnapshot(InvoiceInventorySnapshot $snapshot): array
    {
        $snapshot->loadMissing(['invoice', 'lines']);
        $invoice = $snapshot->invoice;
        if ($invoice === null || $invoice->invoice_type !== 'purchase') {
            throw new DomainException('Snapshot không thuộc hóa đơn mua vào hợp lệ.');
        }
        if ($snapshot->status !== 'NORMALIZED' || $snapshot->lines->isEmpty()) {
            throw new DomainException('Snapshot chưa chuẩn hóa xong.');
        }

        $detail = $this->gdtDetailService->fetchDetail($invoice);
        $identity = $this->identity($invoice, $detail);
        $sourceAnnotation = $this->sourceAnnotation($invoice);
        $lines = $snapshot->lines->sortBy('line_number')->map(function ($line) use ($identity, $sourceAnnotation): array {
            return [
                'line_number' => (int) $line->line_number,
                'source_line_key' => hash('sha256', $identity.'|'.$line->source_line_key),
                'product_code' => $line->source_product_code,
                'description' => $line->normalized_name ?: $line->raw_description,
                'uom' => $line->normalized_uom ?: $line->source_uom,
                'quantity' => $line->source_quantity,
                'unit_price' => $line->unit_price,
                'line_amount' => $line->line_amount,
                'lot_number' => $line->lot_number,
                'expiry_date' => $line->expiry_date?->toDateString(),
                'manufacture_date' => $line->manufacture_date?->toDateString(),
                'metadata' => array_merge([
                    'tax_rate' => $line->tax_rate,
                    'raw_description' => $line->raw_description,
                    'strength' => $line->strength,
                    'dosage_form' => $line->dosage_form,
                    'package_spec' => $line->package_spec,
                    'manufacturer' => $line->manufacturer,
                    'staging_line_id' => $line->id,
                    'raw_gdt_line' => $line->raw_payload,
                ], $sourceAnnotation),
            ];
        })->filter(fn (array $line) => is_numeric($line['quantity']) && (float) $line['quantity'] > 0)->values()->all();

        if ($lines === []) {
            throw new DomainException('Snapshot không có dòng hàng hóa hợp lệ để đưa sang Inventory.');
        }

        return $this->contract(
            $invoice,
            $detail,
            $identity,
            $lines,
            array_merge([
                'snapshot_id' => $snapshot->id,
                'payload_hash' => $snapshot->payload_hash,
            ], $sourceAnnotation),
        );
    }

    private function buildFromDetail(Invoices $invoice, array $detail): array
    {
        $rawLines = is_array($detail['hdhhdvu'] ?? null) ? $detail['hdhhdvu'] : [];
        if ($rawLines === []) {
            throw new DomainException('GDT không trả chi tiết hàng hóa cho hóa đơn này.');
        }

        $identity = $this->identity($invoice, $detail);
        $sourceAnnotation = $this->sourceAnnotation($invoice);
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

            $normalizedLine = $this->lineNormalizer->normalize($line);
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
                'lot_number' => $normalizedLine['lot_number'] ?? null,
                'expiry_date' => $normalizedLine['expiry_date'] ?? null,
                'manufacture_date' => $normalizedLine['manufacture_date'] ?? null,
                'metadata' => array_merge([
                    'tax_rate' => $line['tsuat'] ?? $line['ltsuat'] ?? null,
                    'raw_gdt_line' => $line,
                    'normalizer_version' => InvoiceLineNormalizer::VERSION,
                ], $sourceAnnotation),
            ];
        }

        if ($lines === []) {
            throw new DomainException('Không có dòng hàng hóa hợp lệ để đưa sang Inventory.');
        }

        return $this->contract($invoice, $detail, $identity, $lines, $sourceAnnotation);
    }

    private function contract(Invoices $invoice, array $detail, string $identity, array $lines, array $extraMetadata = []): array
    {
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
            'metadata' => array_merge([
                'lookup_code' => $invoice->lookup_code,
                'gdt_message_id' => $detail['mhdon'] ?? null,
                'source' => 'gdt_detail',
            ], $extraMetadata),
            'lines' => $lines,
        ];
    }

    private function sourceAnnotation(Invoices $invoice): array
    {
        $source = InvoiceSourceRecord::query()
            ->where('invoice_id', $invoice->id)
            ->where('provider', 'gdt')
            ->first();

        return [
            'source_business_classification' => $source?->business_classification ?? 'UNCLASSIFIED',
            'source_business_note' => $source?->business_note,
            'source_classified_by' => $source?->classified_by,
            'source_classified_at' => $source?->classified_at?->toIso8601String(),
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
