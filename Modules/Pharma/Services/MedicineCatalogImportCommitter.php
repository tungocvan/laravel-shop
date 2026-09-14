<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\MedicineImportBatch;
use Modules\Pharma\Models\MedicineImportRow;
use Modules\Pharma\Models\MedicinePackage;
use Modules\Pharma\Models\MedicineSource;
use Modules\Pharma\Models\MedicineVariant;

class MedicineCatalogImportCommitter
{
    public function __construct(
        private readonly MedicineSkuGenerator $skuGenerator,
        private readonly MedicineCatalogNormalizer $normalizer,
    ) {}

    public function commit(MedicineImportBatch $batch): array
    {
        return DB::transaction(function () use ($batch): array {
            $created = 0;
            $updated = 0;

            $rows = $batch->rows()
                ->where('selected', true)
                ->whereIn('classification', [MedicineImportRow::CLASS_NEW, MedicineImportRow::CLASS_UPDATE])
                ->lockForUpdate()
                ->get();

            foreach ($rows as $row) {
                $result = $this->commitRow($row);
                $created += $result === 'created' ? 1 : 0;
                $updated += $result === 'updated' ? 1 : 0;
            }

            $batch->forceFill([
                'status' => MedicineImportBatch::STATUS_COMMITTED,
                'committed_at' => now(),
            ])->save();

            return [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $batch->rows()->count() - $rows->count(),
            ];
        });
    }

    private function commitRow(MedicineImportRow $row): string
    {
        $data = $row->normalized_payload ?? [];
        $medicine = $row->matchedMedicine;

        if (! $medicine) {
            $medicine = Medicine::query()->where('canonical_identity_key', $row->medicine_identity_key)->first();
        }

        $createdMedicine = false;
        if (! $medicine) {
            $medicine = Medicine::query()->create([
                'medicine_code' => $this->nextMedicineCode(),
                'canonical_identity_key' => $row->medicine_identity_key,
                'identity_status' => Medicine::IDENTITY_VERIFIED_REGISTRATION,
                'profile_status' => Medicine::PROFILE_COMPLETE,
                'circular_order_number' => $data['circular_order_number'] ?? null,
                'circular_group' => $data['circular_group'] ?? null,
                'active_ingredients' => $data['active_ingredients'] ?? null,
                'concentration' => $data['concentration'] ?? null,
                'name' => $data['name'],
                'dosage_form' => $data['dosage_form'] ?? null,
                'route_of_administration' => $data['route_of_administration'] ?? null,
                'unit' => $data['unit'] ?? null,
                'packaging_specification' => $data['packaging_specification'] ?? null,
                'registration_number_raw' => $data['registration_number_raw'] ?? null,
                'registration_number_primary' => $data['registration_number_primary'] ?? null,
                'registration_number' => $data['registration_number'] ?? null,
                'shelf_life' => $data['shelf_life'] ?? null,
                'manufacturing_company' => $data['manufacturing_company'] ?? null,
                'manufacturing_country' => $data['manufacturing_country'] ?? null,
                'declared_price' => $data['declared_price'] ?? null,
                'is_special_control' => false,
            ]);
            $createdMedicine = true;
        } else {
            $medicine->fill(array_filter([
                'circular_order_number' => $data['circular_order_number'] ?? null,
                'circular_group' => $data['circular_group'] ?? null,
                'active_ingredients' => $data['active_ingredients'] ?? null,
                'name' => $data['name'] ?? null,
                'dosage_form' => $data['dosage_form'] ?? null,
                'route_of_administration' => $data['route_of_administration'] ?? null,
                'registration_number_raw' => $data['registration_number_raw'] ?? null,
                'registration_number_primary' => $data['registration_number_primary'] ?? null,
                'registration_number' => $data['registration_number'] ?? null,
                'shelf_life' => $data['shelf_life'] ?? null,
                'manufacturing_company' => $data['manufacturing_company'] ?? null,
                'manufacturing_country' => $data['manufacturing_country'] ?? null,
            ], fn ($value) => $value !== null));
            $medicine->save();
        }

        $presentation = implode(' | ', array_filter([
            $data['concentration'] ?? null,
            $data['unit'] ?? null,
            $data['packaging_specification'] ?? null,
        ])) ?: null;

        $skuData = $this->skuGenerator->generate([
            ...$data,
            'strength_text' => $data['concentration'] ?? null,
            'presentation_text' => $presentation,
        ]);

        $variant = MedicineVariant::query()->firstOrCreate(
            ['variant_identity_key' => $row->variant_identity_key],
            [
                'medicine_id' => $medicine->id,
                'sku' => $skuData['sku'],
                'strength_text' => $data['concentration'] ?? null,
                'strength_normalized' => $this->normalizer->text($data['concentration'] ?? null),
                'presentation_text' => $presentation,
                'presentation_normalized' => $this->normalizer->text($presentation),
                'base_unit' => $data['unit'] ?? null,
                'sku_basis_hash' => $skuData['basis_hash'],
                'status' => 'active',
                'is_default' => false,
            ],
        );

        $packageText = $data['packaging_specification'] ?? null;
        if ($packageText !== null) {
            $packageIdentity = $this->skuGenerator->packageIdentity($row->variant_identity_key, $packageText);
            MedicinePackage::query()->firstOrCreate(
                ['package_identity_key' => $packageIdentity],
                [
                    'medicine_variant_id' => $variant->id,
                    'package_code' => 'PKG-'.strtoupper(substr($packageIdentity, 0, 10)),
                    'packaging_text' => $packageText,
                    'packaging_normalized' => $this->normalizer->text($packageText),
                    'is_orderable' => true,
                    'is_inventory_unit' => true,
                ],
            );
        }

        MedicineSource::query()->updateOrCreate(
            [
                'medicine_id' => $medicine->id,
                'source_system' => 'owner_master_excel',
                'source_record_type' => 'medicine_catalog_row',
                'source_record_key' => $row->batch_id.':'.$row->source_row,
            ],
            [
                'source_reference' => $row->batch?->source_file,
                'payload_hash' => $row->payload_hash,
                'observed_at' => now(),
                'synced_at' => now(),
                'is_active' => true,
                'match_method' => $row->classification,
                'match_confidence' => 100,
                'metadata' => ['import_row_id' => $row->id],
            ],
        );

        return $createdMedicine ? 'created' : 'updated';
    }

    private function nextMedicineCode(): string
    {
        $nextId = ((int) Medicine::query()->max('id')) + 1;

        return 'MED-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
    }
}
