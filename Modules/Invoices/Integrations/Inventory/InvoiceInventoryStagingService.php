<?php

namespace Modules\Invoices\Integrations\Inventory;

use DomainException;
use Illuminate\Support\Facades\DB;
use Modules\Invoices\Models\InvoiceInventorySnapshot;
use Modules\Invoices\Models\Invoices;
use Modules\Invoices\Services\GdtPdfService;
use Throwable;

final class InvoiceInventoryStagingService
{
    public function __construct(
        private readonly GdtPdfService $gdtDetailService,
        private readonly InvoiceLineNormalizer $normalizer,
    ) {}

    public function stage(Invoices $invoice, bool $refresh = false): InvoiceInventorySnapshot
    {
        if ($invoice->invoice_type !== 'purchase') {
            throw new DomainException('Chỉ staging hóa đơn mua vào.');
        }

        $snapshot = InvoiceInventorySnapshot::query()->firstOrCreate(
            ['invoice_id' => $invoice->id, 'source' => 'gdt_detail'],
            ['status' => 'PENDING'],
        );

        if (! $refresh && $snapshot->status === 'NORMALIZED') {
            return $snapshot->loadMissing('lines');
        }

        $snapshot->forceFill([
            'status' => 'FETCHING',
            'attempt_count' => ((int) $snapshot->attempt_count) + 1,
            'last_attempt_at' => now(),
            'last_error' => null,
        ])->save();

        try {
            $detail = $this->gdtDetailService->fetchDetail($invoice);
            $rawLines = is_array($detail['hdhhdvu'] ?? null) ? $detail['hdhhdvu'] : [];
            if ($rawLines === []) {
                throw new DomainException('GDT không trả chi tiết hàng hóa cho hóa đơn này.');
            }

            $hash = hash('sha256', json_encode($detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            if ($snapshot->payload_hash === $hash && $snapshot->normalized_at !== null) {
                $snapshot->forceFill([
                    'status' => 'NORMALIZED',
                    'fetched_at' => now(),
                    'last_error' => null,
                ])->save();

                return $snapshot->fresh('lines');
            }

            return DB::transaction(function () use ($snapshot, $detail, $rawLines, $hash): InvoiceInventorySnapshot {
                $snapshot->forceFill([
                    'payload_hash' => $hash,
                    'status' => 'FETCHED',
                    'raw_payload' => $detail,
                    'fetched_at' => now(),
                    'normalized_at' => null,
                    'last_error' => null,
                ])->save();

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
                $snapshot->forceFill(['status' => 'NORMALIZED', 'normalized_at' => now()])->save();

                return $snapshot->fresh('lines');
            });
        } catch (Throwable $exception) {
            $snapshot->forceFill([
                'status' => 'ERROR',
                'last_error' => mb_substr($exception->getMessage(), 0, 4000),
            ])->save();

            throw $exception;
        }
    }
}
