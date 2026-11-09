<?php

namespace Modules\ClientPortal\Applications\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\MedicineImportBatch;
use Modules\Pharma\Models\MedicineImportRow;
use Modules\Pharma\Models\MedicineVariant;

/**
 * Explicit staging-only resolution. Never updates an existing Medicine Master.
 */
final class ClientMedicineImportReviewResolver
{
    public function resolve(MedicineImportBatch $batch, int $rowId, string $action, ?int $medicineId = null, ?int $variantId = null): void
    {
        DB::transaction(function () use ($batch, $rowId, $action, $medicineId, $variantId): void {
            $locked = MedicineImportBatch::query()->lockForUpdate()->findOrFail($batch->id);
            if ($locked->status !== MedicineImportBatch::STATUS_READY) {
                throw ValidationException::withMessages(['resolution' => 'Batch không còn ở trạng thái chờ xử lý.']);
            }

            $row = $locked->rows()->whereKey($rowId)->lockForUpdate()->firstOrFail();
            if (! in_array($row->classification, [MedicineImportRow::CLASS_NEEDS_REVIEW, MedicineImportRow::CLASS_CONFLICT], true)) {
                throw ValidationException::withMessages(['resolution' => 'Dòng đã được xử lý hoặc không cần rà soát.']);
            }

            $data = $row->normalized_payload ?? [];
            if ($action === 'link') {
                $medicine = Medicine::query()->find($medicineId);
                $variant = MedicineVariant::query()->find($variantId);
                if (! $medicine || ! $variant || (int) $variant->medicine_id !== (int) $medicine->id) {
                    throw ValidationException::withMessages(['resolution' => 'Thuốc và SKU không tồn tại hoặc không thuộc cùng Medicine Master.']);
                }
                $registration = trim((string) ($data['registration_number'] ?? ''));
                $existingRegistration = trim((string) ($medicine->registration_number ?? ''));
                if ($registration === '' || $existingRegistration === '' || $registration !== $existingRegistration) {
                    throw ValidationException::withMessages(['resolution' => 'GPLH không trùng khớp. Không thể liên kết tự động.']);
                }
                if (mb_strtolower(trim((string) ($data['name'] ?? '')), 'UTF-8') !== mb_strtolower(trim((string) $medicine->name), 'UTF-8')) {
                    throw ValidationException::withMessages(['resolution' => 'Tên thuốc không trùng khớp. Vui lòng rà soát Medicine Master.']);
                }
                if ($variant->variant_identity_key !== $row->variant_identity_key) {
                    throw ValidationException::withMessages(['resolution' => 'Quy cách/hàm lượng SKU không khớp. Không thể liên kết sai biến thể.']);
                }
                $row->forceFill([
                    'classification' => MedicineImportRow::CLASS_DUPLICATE,
                    'resolution_reason' => 'client_review_linked_existing',
                    'matched_medicine_id' => $medicine->id,
                    'matched_variant_id' => $variant->id,
                    'selected' => false,
                ])->save();
            } elseif ($action === 'create') {
                if ($row->resolution_reason !== 'roundtrip_reference_not_found') {
                    throw ValidationException::withMessages(['resolution' => 'Chỉ hỗ trợ tạo mới khi tham chiếu Excel không còn tồn tại. Các xung đột khác cần rà soát riêng.']);
                }
                foreach (['name', 'concentration', 'unit', 'packaging_specification'] as $field) {
                    if (blank($data[$field] ?? null)) {
                        throw ValidationException::withMessages(['resolution' => "Thiếu trường bắt buộc: {$field}."]);
                    }
                }
                if (($row->matched_medicine_id || $row->matched_variant_id)
                    || ($row->medicine_identity_key && Medicine::query()->where('canonical_identity_key', $row->medicine_identity_key)->exists())
                    || MedicineVariant::query()->where('variant_identity_key', $row->variant_identity_key)->exists()
                    || (filled($data['registration_number'] ?? null) && Medicine::query()->where('registration_number', $data['registration_number'])->exists())) {
                    throw ValidationException::withMessages(['resolution' => 'Phát hiện thuốc/SKU/GPLH đã tồn tại. Hãy liên kết thay vì tạo mới.']);
                }
                if ($locked->rows()->where('id', '!=', $row->id)
                    ->where('variant_identity_key', $row->variant_identity_key)
                    ->whereIn('classification', [MedicineImportRow::CLASS_NEW, MedicineImportRow::CLASS_UPDATE])
                    ->exists()) {
                    throw ValidationException::withMessages(['resolution' => 'SKU đã có dòng chờ tạo/cập nhật trong cùng batch.']);
                }
                $data['medicine_code_reference'] = null;
                $data['sku_reference'] = null;
                $row->forceFill([
                    'normalized_payload' => $data,
                    'classification' => MedicineImportRow::CLASS_NEW,
                    'resolution_reason' => 'client_review_approved_new',
                    'selected' => true,
                    'matched_medicine_id' => null,
                    'matched_variant_id' => null,
                ])->save();
            } else {
                throw ValidationException::withMessages(['resolution' => 'Hành động rà soát không hợp lệ.']);
            }

            $locked->forceFill([
                'new_rows' => $locked->rows()->where('classification', MedicineImportRow::CLASS_NEW)->count(),
                'update_rows' => $locked->rows()->where('classification', MedicineImportRow::CLASS_UPDATE)->count(),
                'duplicate_rows' => $locked->rows()->where('classification', MedicineImportRow::CLASS_DUPLICATE)->count(),
                'review_rows' => $locked->rows()->where('classification', MedicineImportRow::CLASS_NEEDS_REVIEW)->count(),
                'conflict_rows' => $locked->rows()->where('classification', MedicineImportRow::CLASS_CONFLICT)->count(),
            ])->save();
        });
    }
}
