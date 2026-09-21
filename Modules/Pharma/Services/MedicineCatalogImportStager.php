<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Arr;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\MedicineImportBatch;
use Modules\Pharma\Models\MedicineImportRow;
use Modules\Pharma\Models\MedicineVariant;

class MedicineCatalogImportStager
{
    public function __construct(
        private readonly MedicineCatalogImportMapper $mapper,
        private readonly MedicineSkuGenerator $skuGenerator,
        private readonly MedicineIdentityResolver $identityResolver,
    ) {}

    public function stage(iterable $rows, ?string $sourceFile = null, ?int $createdBy = null): MedicineImportBatch
    {
        $batch = MedicineImportBatch::query()->create([
            'source' => 'owner_master_excel',
            'source_file' => $sourceFile,
            'created_by' => $createdBy,
        ]);

        $seen = [];
        $counts = [
            MedicineImportRow::CLASS_NEW => 0,
            MedicineImportRow::CLASS_UPDATE => 0,
            MedicineImportRow::CLASS_DUPLICATE => 0,
            MedicineImportRow::CLASS_CONFLICT => 0,
            MedicineImportRow::CLASS_NEEDS_REVIEW => 0,
        ];

        foreach ($rows as $offset => $raw) {
            $normalized = $this->mapper->map((array) $raw);
            $sourceRow = (int) ($raw['_source_row'] ?? $offset + 2);
            $payloadHash = $this->mapper->payloadHash($normalized);
            $medicineIdentity = $this->identityResolver->canonicalMedicineIdentity($normalized);
            $variantIdentity = $this->skuGenerator->variantIdentity([
                ...$normalized,
                'strength_text' => $normalized['concentration'] ?? null,
                'presentation_text' => $this->presentation($normalized),
            ]);

            [$classification, $reason, $medicineId, $variantId] = $this->classify(
                $normalized,
                $payloadHash,
                $medicineIdentity,
                $variantIdentity,
                $seen,
            );

            $seen[$variantIdentity][] = $payloadHash;
            $counts[$classification]++;

            $batch->rows()->create([
                'source_row' => $sourceRow,
                'classification' => $classification,
                'resolution_reason' => $reason,
                'matched_medicine_id' => $medicineId,
                'matched_variant_id' => $variantId,
                'raw_payload' => Arr::except((array) $raw, ['_source_row']),
                'normalized_payload' => $normalized,
                'medicine_identity_key' => $medicineIdentity,
                'variant_identity_key' => $variantIdentity,
                'payload_hash' => $payloadHash,
                'selected' => in_array($classification, [MedicineImportRow::CLASS_NEW, MedicineImportRow::CLASS_UPDATE], true),
            ]);
        }

        $batch->forceFill([
            'status' => MedicineImportBatch::STATUS_READY,
            'total_rows' => array_sum($counts),
            'new_rows' => $counts[MedicineImportRow::CLASS_NEW],
            'update_rows' => $counts[MedicineImportRow::CLASS_UPDATE],
            'duplicate_rows' => $counts[MedicineImportRow::CLASS_DUPLICATE],
            'conflict_rows' => $counts[MedicineImportRow::CLASS_CONFLICT],
            'review_rows' => $counts[MedicineImportRow::CLASS_NEEDS_REVIEW],
        ])->save();

        return $batch->refresh();
    }

    private function classify(
        array $normalized,
        string $payloadHash,
        ?string $medicineIdentity,
        string $variantIdentity,
        array $seen,
    ): array {
        if (($normalized['name'] ?? null) === null || ($normalized['concentration'] ?? null) === null) {
            return [MedicineImportRow::CLASS_NEEDS_REVIEW, 'missing_required_identity_fields', null, null];
        }

        if (isset($seen[$variantIdentity])) {
            if (in_array($payloadHash, $seen[$variantIdentity], true)) {
                return [MedicineImportRow::CLASS_DUPLICATE, 'duplicate_inside_batch', null, null];
            }

            return [MedicineImportRow::CLASS_CONFLICT, 'same_variant_identity_different_payload', null, null];
        }

        $variant = MedicineVariant::query()->where('variant_identity_key', $variantIdentity)->first();
        if ($variant) {
            return [MedicineImportRow::CLASS_UPDATE, 'existing_variant', $variant->medicine_id, $variant->id];
        }

        if ($medicineIdentity !== null) {
            $medicine = Medicine::query()->where('canonical_identity_key', $medicineIdentity)->first();
            if ($medicine) {
                return [MedicineImportRow::CLASS_NEW, 'new_variant_for_existing_medicine', $medicine->id, null];
            }
        }

        $possibleDuplicates = $this->possibleExistingMedicines($normalized);
        if ($possibleDuplicates->count() === 1) {
            return [MedicineImportRow::CLASS_NEEDS_REVIEW, 'possible_existing_medicine_identity', $possibleDuplicates->first()->id, null];
        }
        if ($possibleDuplicates->count() > 1) {
            return [MedicineImportRow::CLASS_NEEDS_REVIEW, 'possible_existing_medicine_ambiguous', null, null];
        }

        return [MedicineImportRow::CLASS_NEW, 'new_medicine_and_variant', null, null];
    }

    private function possibleExistingMedicines(array $normalized)
    {
        $name = $this->mapper->normalizeIdentityText($normalized['name'] ?? null);
        if ($name === null) {
            return collect();
        }

        return Medicine::query()->where('name', 'like', '%'.trim((string) $normalized['name']).'%')->get()
            ->filter(function (Medicine $medicine) use ($normalized, $name): bool {
                if ($this->mapper->normalizeIdentityText($medicine->name) !== $name) {
                    return false;
                }

                foreach (['active_ingredients', 'concentration', 'dosage_form', 'manufacturing_company'] as $field) {
                    $incoming = $this->mapper->normalizeIdentityText($normalized[$field] ?? null);
                    $existing = $this->mapper->normalizeIdentityText($medicine->getAttribute($field));
                    if ($incoming !== null && $existing !== null && $incoming !== $existing) {
                        return false;
                    }
                }

                return true;
            })->values();
    }

    private function presentation(array $normalized): ?string
    {
        return implode(' | ', array_filter([
            $normalized['concentration'] ?? null,
            $normalized['unit'] ?? null,
            $normalized['packaging_specification'] ?? null,
        ])) ?: null;
    }
}
