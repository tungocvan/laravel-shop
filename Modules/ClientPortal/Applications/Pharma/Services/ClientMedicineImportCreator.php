<?php

namespace Modules\ClientPortal\Applications\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\MedicineImportBatch;
use Modules\Pharma\Models\MedicineImportRow;
use Modules\Pharma\Models\MedicineVariant;
use Modules\Pharma\Services\MedicineService;
use Modules\Pharma\Services\MedicineVariantCreationService;

final class ClientMedicineImportCreator
{
    public function __construct(
        private readonly MedicineService $medicines,
        private readonly MedicineVariantCreationService $variants,
    ) {}

    /**
     * Explicit approval of NEW medicines only. Existing records are never modified.
     * MedicineService allocates MED-<id>; MedicineVariantCreationService allocates MED-<id>-V01.
     */
    public function createNew(MedicineImportBatch $batch): int
    {
        return DB::transaction(function () use ($batch): int {
            $lockedBatch = MedicineImportBatch::query()->lockForUpdate()->findOrFail($batch->id);
            if ($lockedBatch->status !== MedicineImportBatch::STATUS_READY) {
                throw ValidationException::withMessages(['batch' => 'Batch không còn ở trạng thái chờ xử lý.']);
            }

            $rows = $lockedBatch->rows()
                ->where('classification', MedicineImportRow::CLASS_NEW)
                ->where('selected', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($rows->isEmpty()) {
                throw ValidationException::withMessages(['batch' => 'Không có thuốc mới hợp lệ để tạo.']);
            }

            // Do not commit partially if a row became unsafe after staging.
            foreach ($rows as $row) {
                $data = $row->normalized_payload ?? [];
                if (! empty($data['medicine_code_reference']) || ! empty($data['sku_reference'])
                    || ! empty($row->matched_medicine_id) || ! empty($row->matched_variant_id)
                    || empty($data['name']) || empty($data['concentration'])
                    || empty($data['unit']) || empty($data['packaging_specification'])) {
                    throw ValidationException::withMessages(['batch' => "Dòng {$row->source_row} cần rà soát trước khi tạo thuốc."]);
                }
                if (($row->medicine_identity_key && Medicine::query()->where('canonical_identity_key', $row->medicine_identity_key)->exists())
                    || MedicineVariant::query()->where('variant_identity_key', $row->variant_identity_key)->exists()) {
                    throw ValidationException::withMessages(['batch' => "Dòng {$row->source_row} đã có thuốc/SKU tương ứng. Hãy import lại để phân loại."]);
                }
                if (! empty($data['registration_number']) && Medicine::query()
                    ->where('registration_number', $data['registration_number'])->exists()) {
                    throw ValidationException::withMessages(['batch' => "Dòng {$row->source_row} có khả năng trùng thuốc hiện có."]);
                }
            }

            foreach ($rows as $row) {
                $data = $row->normalized_payload;
                $medicine = $this->medicines->store([
                    'name' => $data['name'],
                    'product_type' => $data['product_type'] ?: Medicine::PRODUCT_TYPE_MODERN,
                    'active_ingredients' => $data['active_ingredients'] ?? null,
                    'concentration' => $data['concentration'],
                    'dosage_form' => $data['dosage_form'] ?? null,
                    'route_of_administration' => $data['route_of_administration'] ?? null,
                    'unit' => $data['unit'],
                    'packaging_specification' => $data['packaging_specification'],
                    'registration_number' => $data['registration_number'] ?? null,
                    'circular_group' => $data['circular_group'] ?? null,
                    'shelf_life' => $data['shelf_life'] ?? null,
                    'manufacturing_company' => $data['manufacturing_company'] ?? null,
                    'manufacturing_country' => $data['manufacturing_country'] ?? null,
                    'declared_price' => $data['declared_price'] ?? null,
                    'is_special_control' => (bool) ($data['is_special_control'] ?? false),
                ]);
                $variant = $this->variants->createFirst((int) $medicine->id, [
                    'strength_text' => $data['concentration'],
                    'presentation_text' => $data['packaging_specification'],
                    'base_unit' => $data['unit'],
                    'declared_price' => $data['declared_price'] ?? null,
                ]);
                $row->forceFill([
                    'classification' => MedicineImportRow::CLASS_UPDATE,
                    'resolution_reason' => 'created_from_client_import',
                    'matched_medicine_id' => $medicine->id,
                    'matched_variant_id' => $variant->id,
                    'selected' => false,
                ])->save();
            }

            $lockedBatch->forceFill([
                'new_rows' => $lockedBatch->rows()->where('classification', MedicineImportRow::CLASS_NEW)->count(),
                'update_rows' => $lockedBatch->rows()->where('classification', MedicineImportRow::CLASS_UPDATE)->count(),
            ])->save();

            return $rows->count();
        });
    }
}
