<?php

namespace Modules\Inventory\Services;

use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Inventory\Models\InvoiceInbox;
use Modules\Inventory\Models\InvoiceInboxLine;

final class InventoryInvoiceIntegrationService
{
    public function ingest(array $contract): InvoiceInbox
    {
        $version = trim((string) Arr::get($contract, 'contract_version', ''));
        if (! str_starts_with($version, '1.')) {
            throw new DomainException('Inventory chỉ hỗ trợ InvoiceForInventory contract major version 1.');
        }

        $identity = trim((string) Arr::get($contract, 'source_invoice_identity', ''));
        if ($identity === '') {
            throw new DomainException('Thiếu source_invoice_identity.');
        }

        $lines = Arr::get($contract, 'lines', []);
        if (! is_array($lines) || $lines === []) {
            throw new DomainException('Hóa đơn không có dòng hàng hóa normalized để tạo đề xuất nhập kho.');
        }

        $normalized = $this->normalizedContract($contract);
        $payloadHash = hash('sha256', json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($normalized, $payloadHash): InvoiceInbox {
            $inbox = InvoiceInbox::query()->lockForUpdate()->firstOrNew([
                'source_module' => 'Invoices',
                'source_invoice_identity' => $normalized['source_invoice_identity'],
                'integration_purpose' => 'purchase_receipt',
            ]);

            if ($inbox->exists && $inbox->normalized_payload_hash === $payloadHash) {
                $inbox->forceFill(['last_seen_at' => now()])->save();

                return $inbox->fresh(['lines', 'receipt']);
            }

            if ($inbox->exists && $inbox->receipt_id !== null && $inbox->receipt?->status !== 'DRAFT') {
                throw new DomainException('Hóa đơn đã sinh phiếu nhập được xác nhận; không thể ghi đè bằng payload thay đổi.');
            }

            $inbox->fill([
                'contract_version' => $normalized['contract_version'],
                'source_invoice_id' => $normalized['source_invoice_id'],
                'normalized_payload_hash' => $payloadHash,
                'processing_status' => 'MATCHING',
                'partner_id' => $normalized['partner_id'],
                'seller_name_snapshot' => $normalized['seller']['name'],
                'seller_tax_code_snapshot' => $normalized['seller']['tax_code'],
                'seller_address_snapshot' => $normalized['seller']['address'],
                'invoice_number_snapshot' => $normalized['invoice_number'],
                'invoice_symbol_snapshot' => $normalized['invoice_symbol'],
                'issued_at_snapshot' => $normalized['issued_at'],
                'amount_before_vat_snapshot' => $normalized['totals']['amount_before_vat'],
                'vat_amount_snapshot' => $normalized['totals']['vat_amount'],
                'total_amount_snapshot' => $normalized['totals']['total_amount'],
                'metadata' => $normalized['metadata'],
                'received_at' => $inbox->exists ? $inbox->received_at : now(),
                'last_seen_at' => now(),
            ])->save();

            $existing = $inbox->lines()->get()->keyBy('source_line_key');
            $seen = [];

            foreach ($normalized['lines'] as $line) {
                $seen[] = $line['source_line_key'];
                $record = $existing->get($line['source_line_key']) ?? new InvoiceInboxLine(['inbox_id' => $inbox->id]);

                if ($record->exists && $record->classification === 'STOCK' && $record->inventory_item_id !== null) {
                    $line['classification'] = $record->classification;
                    $line['inventory_item_id'] = $record->inventory_item_id;
                    $line['match_reason'] = $record->match_reason;
                    $line['conversion_factor'] = $record->conversion_factor;
                    $line['base_quantity'] = $record->base_quantity;
                    $line['base_uom'] = $record->base_uom;
                }

                $record->fill($line)->save();
            }

            $inbox->lines()->whereNotIn('source_line_key', $seen)->delete();
            app(InventoryItemMatchingService::class)->matchInbox($inbox->fresh('lines'));

            return $inbox->fresh(['lines.item', 'receipt']);
        });
    }

    private function normalizedContract(array $contract): array
    {
        $classifier = app(InvoiceLineStockClassifier::class);

        $lines = collect((array) $contract['lines'])->values()->map(function (array $line, int $index) use ($classifier): array {
            $description = trim((string) ($line['description'] ?? ''));
            if ($description === '') {
                throw new DomainException('Dòng hóa đơn thiếu description.');
            }

            $quantity = (string) ($line['quantity'] ?? '');
            if (! is_numeric($quantity) || (float) $quantity <= 0) {
                throw new DomainException('Dòng hóa đơn có quantity không hợp lệ.');
            }

            $lineNumber = (int) ($line['line_number'] ?? ($index + 1));
            $sourceLineKey = trim((string) ($line['source_line_key'] ?? ''));
            if ($sourceLineKey === '') {
                $sourceLineKey = hash('sha256', $lineNumber.'|'.$description.'|'.$quantity.'|'.($line['uom'] ?? ''));
            }

            $metadata = is_array($line['metadata'] ?? null) ? $line['metadata'] : [];
            $classification = $classifier->classify(
                $description,
                $this->nullableString($line['uom'] ?? null),
                $metadata,
            );
            $metadata['stock_classification'] = $classification;

            return [
                'line_number' => $lineNumber,
                'source_line_key' => $sourceLineKey,
                'source_product_code' => $this->nullableString($line['product_code'] ?? null),
                'description_snapshot' => $description,
                'normalized_description_key' => $this->descriptionKey($description),
                'source_uom' => $this->nullableString($line['uom'] ?? null),
                'source_quantity' => $quantity,
                'unit_price' => $this->nullableNumber($line['unit_price'] ?? null),
                'line_amount' => $this->nullableNumber($line['line_amount'] ?? null),
                'lot_number' => $this->nullableString($line['lot_number'] ?? null),
                'expiry_date' => $this->nullableString($line['expiry_date'] ?? null),
                'manufacture_date' => $this->nullableString($line['manufacture_date'] ?? null),
                'classification' => $classification['classification'],
                'inventory_item_id' => null,
                'match_reason' => 'classifier:'.$classification['reason'],
                'conversion_factor' => 1,
                'base_quantity' => null,
                'base_uom' => null,
                'metadata' => $metadata,
            ];
        })->all();

        return [
            'contract_version' => (string) $contract['contract_version'],
            'source_invoice_identity' => trim((string) $contract['source_invoice_identity']),
            'source_invoice_id' => isset($contract['source_invoice_id']) ? (int) $contract['source_invoice_id'] : null,
            'partner_id' => isset($contract['partner_id']) ? (int) $contract['partner_id'] : null,
            'invoice_number' => $this->nullableString($contract['invoice_number'] ?? null),
            'invoice_symbol' => $this->nullableString($contract['invoice_symbol'] ?? null),
            'issued_at' => $this->nullableString($contract['issued_at'] ?? null),
            'seller' => [
                'name' => $this->nullableString(Arr::get($contract, 'seller.name')),
                'tax_code' => $this->nullableString(Arr::get($contract, 'seller.tax_code')),
                'address' => $this->nullableString(Arr::get($contract, 'seller.address')),
            ],
            'totals' => [
                'amount_before_vat' => $this->nullableNumber(Arr::get($contract, 'totals.amount_before_vat')),
                'vat_amount' => $this->nullableNumber(Arr::get($contract, 'totals.vat_amount')),
                'total_amount' => $this->nullableNumber(Arr::get($contract, 'totals.total_amount')),
            ],
            'metadata' => is_array($contract['metadata'] ?? null) ? $contract['metadata'] : null,
            'lines' => $lines,
        ];
    }

    private function descriptionKey(string $value): string
    {
        return Str::of($value)->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->substr(0, 191)->toString();
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
