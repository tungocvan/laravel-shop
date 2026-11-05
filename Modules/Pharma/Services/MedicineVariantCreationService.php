<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\MedicineVariant;

final class MedicineVariantCreationService
{
    public function __construct(
        private readonly MedicineSkuGenerator $skuGenerator,
        private readonly MedicineCatalogNormalizer $normalizer,
    ) {}

    /**
     * Explicitly create the first catalog SKU. Never silently create a sellable Product.
     * The medicine row lock serializes concurrent first-SKU requests.
     */
    public function createFirst(int $medicineId, array $attributes): MedicineVariant
    {
        return DB::transaction(function () use ($medicineId, $attributes): MedicineVariant {
            $medicine = Medicine::query()->lockForUpdate()->findOrFail($medicineId);

            if ($medicine->variants()->exists()) {
                throw ValidationException::withMessages([
                    'sku' => 'Thuốc đã có SKU. Không thể tạo lại SKU mặc định.',
                ]);
            }

            $identityAttributes = [
                'registration_number' => $medicine->registration_number,
                'name' => $medicine->name,
                'dosage_form' => $medicine->dosage_form,
                'strength_text' => $attributes['strength_text'],
                'presentation_text' => $attributes['presentation_text'],
            ];
            $identityKey = $this->skuGenerator->variantIdentity($identityAttributes);

            if (MedicineVariant::query()->where('variant_identity_key', $identityKey)->exists()) {
                throw ValidationException::withMessages([
                    'sku' => 'Biến thể với thông tin nhận diện này đã tồn tại.',
                ]);
            }

            $sku = sprintf('MED-%06d-V01', $medicine->getKey());

            if (MedicineVariant::query()->where('sku', $sku)->exists()) {
                throw ValidationException::withMessages([
                    'sku' => 'Mã SKU đã tồn tại. Vui lòng kiểm tra dữ liệu trước khi tiếp tục.',
                ]);
            }

            return $medicine->variants()->create([
                'sku' => $sku,
                'variant_identity_key' => $identityKey,
                'sku_basis_hash' => $this->skuGenerator->generate($identityAttributes)['basis_hash'],
                'strength_normalized' => $this->normalizer->text($attributes['strength_text']),
                'presentation_normalized' => $this->normalizer->text($attributes['presentation_text']),
                'strength_text' => $attributes['strength_text'],
                'presentation_text' => $attributes['presentation_text'],
                'base_unit' => $attributes['base_unit'],
                'declared_price' => $attributes['declared_price'] ?? $medicine->declared_price,
                'status' => 'active',
                'is_default' => true,
            ]);
        });
    }
}
