<?php

namespace Modules\Inventory\Services;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Inventory\Models\InvoiceInbox;
use Modules\Inventory\Models\Receipt;
use Modules\Inventory\Models\ReceiptLine;
use Modules\Inventory\Models\Warehouse;

final class InvoiceReceiptProposalService
{
    public function createOrRefresh(int $inboxId, int $warehouseId, int $actorId): Receipt
    {
        return DB::transaction(function () use ($inboxId, $warehouseId, $actorId): Receipt {
            $inbox = InvoiceInbox::query()->with(['lines.item', 'receipt'])->lockForUpdate()->findOrFail($inboxId);
            $warehouse = Warehouse::query()->where('is_active', true)->findOrFail($warehouseId);

            if ($inbox->lines->contains(fn ($line) => $line->classification === 'UNRESOLVED')) {
                throw new DomainException('Còn dòng hóa đơn chưa được phân loại/matching.');
            }

            $stockLines = $inbox->lines->where('classification', 'STOCK')->values();
            if ($stockLines->isEmpty()) {
                throw new DomainException('Hóa đơn không có dòng STOCK để tạo phiếu nhập.');
            }

            foreach ($stockLines as $line) {
                if ($line->inventory_item_id === null || $line->base_quantity === null || $line->base_uom === null) {
                    throw new DomainException('Dòng STOCK chưa đủ item/base quantity/base UOM.');
                }

                if ((float) $line->base_quantity <= 0) {
                    throw new DomainException('Dòng STOCK phải có số lượng nhập lớn hơn 0.');
                }

                if ($this->uomKey((string) $line->source_uom) !== $this->uomKey((string) $line->base_uom)
                    && (string) $line->conversion_factor === '1.00000000') {
                    throw new DomainException('ĐVT nguồn khác ĐVT cơ sở nhưng chưa có hệ số quy đổi được review.');
                }

                if ($line->item?->expiry_tracking && $line->expiry_date === null) {
                    throw new DomainException('Mặt hàng theo dõi HSD nhưng dòng nhập chưa có hạn dùng.');
                }

                if ($line->item?->lot_tracking && trim((string) $line->lot_number) === '') {
                    throw new DomainException('Mặt hàng theo dõi lô nhưng dòng nhập chưa có số lô.');
                }
            }

            $receipt = $inbox->receipt;
            if ($receipt !== null && $receipt->status !== 'DRAFT') {
                throw new DomainException('Phiếu nhập từ hóa đơn này đã được xác nhận hoặc không còn ở trạng thái DRAFT.');
            }

            if ($receipt !== null && $receipt->lines()->exists()) {
                throw new DomainException('Phiếu nhập DRAFT đã có dữ liệu review. Không refresh tự động để tránh ghi đè thay đổi của người vận hành.');
            }

            if ($receipt === null) {
                $receipt = Receipt::query()->create([
                    'number' => 'INV-'.str_pad((string) $inbox->id, 8, '0', STR_PAD_LEFT),
                    'warehouse_id' => $warehouse->id,
                    'status' => 'DRAFT',
                    'source_type' => 'invoice',
                    'source_identity_key' => $inbox->source_invoice_identity,
                    'partner_id' => $inbox->partner_id,
                    'seller_name_snapshot' => $inbox->seller_name_snapshot,
                    'seller_tax_code_snapshot' => $inbox->seller_tax_code_snapshot,
                    'seller_address_snapshot' => $inbox->seller_address_snapshot,
                    'document_date' => $inbox->issued_at_snapshot,
                    'notes' => 'Draft receipt proposal từ Invoices contract '.$inbox->contract_version.'.',
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);
            } else {
                $receipt->forceFill([
                    'warehouse_id' => $warehouse->id,
                    'partner_id' => $inbox->partner_id,
                    'seller_name_snapshot' => $inbox->seller_name_snapshot,
                    'seller_tax_code_snapshot' => $inbox->seller_tax_code_snapshot,
                    'seller_address_snapshot' => $inbox->seller_address_snapshot,
                    'document_date' => $inbox->issued_at_snapshot,
                    'updated_by' => $actorId,
                ])->save();
            }

            foreach ($stockLines as $index => $line) {
                ReceiptLine::query()->create([
                    'receipt_id' => $receipt->id,
                    'line_number' => $index + 1,
                    'inventory_item_id' => $line->inventory_item_id,
                    'classification' => 'STOCK',
                    'source_quantity' => $line->source_quantity,
                    'source_uom' => $line->source_uom,
                    'conversion_factor' => $line->conversion_factor,
                    'base_quantity' => $line->base_quantity,
                    'base_uom' => $line->base_uom,
                    'unit_cost' => $line->unit_price,
                    'lot_number' => $line->lot_number,
                    'expiry_date' => $line->expiry_date,
                    'manufacture_date' => $line->manufacture_date,
                    'source_line_key' => $line->source_line_key,
                    'description_snapshot' => $line->description_snapshot,
                    'metadata' => [
                        'invoice_inbox_id' => $inbox->id,
                        'invoice_inbox_line_id' => $line->id,
                        'source_invoice_identity' => $inbox->source_invoice_identity,
                        'match_reason' => $line->match_reason,
                    ],
                ]);
            }

            $inbox->forceFill([
                'receipt_id' => $receipt->id,
                'processing_status' => 'RECEIPT_CREATED',
                'last_seen_at' => now(),
            ])->save();

            return $receipt->fresh(['warehouse', 'lines']);
        });
    }

    private function uomKey(string $value): string
    {
        return Str::of($value)->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->toString();
    }
}
