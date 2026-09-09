<?php

namespace Modules\Invoices\Integrations\Inventory;

use Illuminate\Support\Facades\DB;
use Modules\Invoices\Models\InvoiceInventorySnapshot;
use Modules\Invoices\Models\Invoices;
use Modules\Invoices\Services\GdtPdfService;

final class InvoiceInventoryStagingService
{
    public function __construct(
        private readonly GdtPdfService $gdtDetailService,
        private readonly InvoiceLineNormalizer $normalizer,
    ) {}

    public function stage(Invoices $invoice, bool $refresh = false): InvoiceInventorySnapshot
    {
        if ($invoice->invoice_type !== 'purchase') {
            throw new \DomainException('Chỉ staging hóa đơn mua vào.');
        }

        $existing = InvoiceInventorySnapshot::query()->where('invoice_id', $invoice->id)->where('source', 'gdt_detail')->first();
        if ($existing && ! $refresh && $existing->status === 'NORMALIZED') {
            return $existing;
        }

        $detail = $this->gdtDetailService->fetchDetail($invoice);
        $rawLines = is_array($detail['hdhhdvu'] ?? null) ? $detail['hdhhdvu'] : [];
        if ($rawLines === []) {
            throw new \DomainException('GDT không trả chi tiết hàng hóa cho hóa đơn này.');
        }

        $hash = hash('sha256', json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        if ($existing && $existing->payload_hash === $hash && $existing->status === 'NORMALIZED') {
            return $existing;
        }

        return DB::transaction(function () use ($invoice, $detail, $rawLines, $hash): InvoiceInventorySnapshot {
            $snapshot = InvoiceInventorySnapshot::query()->updateOrCreate(
                ['invoice_id' => $invoice->id, 'source' => 'gdt_detail'],
                ['payload_hash' => $hash, 'status' => 'FETCHED', 'raw_payload' => $detail, 'fetched_at' => now(), 'last_error' => null],
            );

            $seen = [];
            foreach (array_values($rawLines) as $index => $line) {
                if (! is_array($line)) {
                    continue;
                }
                $description = trim((string) ($line['ten'] ?? ''));
                if ($description === '') {
                    continue;
                }
                $number = (int) ($line['stt'] ?? ($index + 1));
                $key = hash('sha256', $number.'|'.$description.'|'.($line['sluong'] ?? '').'|'.($line['dvtinh'] ?? ''));
                $seen[] = $key;
                $normalized = $this->normalizer->normalize($line);
                $snapshot->lines()->updateOrCreate(['source_line_key' => $key], array_merge([
                    'line_number' => $number,
                    'raw_description' => $description,
                    'source_product_code' => $line['mhhdvu'] ?? $line['ma'] ?? null,
                    'source_quantity' => is_numeric($line['sluong'] ?? null) ? $line['sluong'] : null,
                    'source_uom' => $line['dvtinh'] ?? null,
                    'unit_price' => is_numeric($line['dgia'] ?? null) ? $line['dgia'] : null,
                    'line_amount' => is_numeric($line['thtien'] ?? null) ? $line['thtien'] : null,
                    'raw_payload' => $line,
                ], $normalized));
            }

            $snapshot->lines()->whereNotIn('source_line_key', $seen)->delete();
            $snapshot->update(['status' => 'NORMALIZED', 'normalized_at' => now()]);

            return $snapshot->fresh('lines');
        });
    }
}
