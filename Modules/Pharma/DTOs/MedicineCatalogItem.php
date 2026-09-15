<?php

namespace Modules\Pharma\DTOs;

use Modules\Pharma\Models\MedicineVariant;

final readonly class MedicineCatalogItem
{
    public function __construct(
        public int $medicineId,
        public int $variantId,
        public string $sku,
        public string $brandName,
        public ?string $activeIngredients,
        public ?string $strength,
        public ?string $dosageForm,
        public ?string $route,
        public ?string $unit,
        public ?string $packaging,
        public ?string $registrationNumber,
        public ?string $manufacturer,
    ) {}

    public static function fromVariant(MedicineVariant $variant): self
    {
        $variant->loadMissing(['medicine', 'packages']);
        $medicine = $variant->medicine;
        $package = $variant->packages->first();

        return new self(
            medicineId: (int) $medicine->id,
            variantId: (int) $variant->id,
            sku: (string) $variant->sku,
            brandName: (string) $medicine->name,
            activeIngredients: $medicine->active_ingredients,
            strength: $variant->strength_text ?: $medicine->concentration,
            dosageForm: $medicine->dosage_form,
            route: $medicine->route_of_administration,
            unit: $variant->base_unit ?: $medicine->unit,
            packaging: $package?->packaging_text ?: $medicine->packaging_specification,
            registrationNumber: $medicine->registration_number_primary ?: $medicine->registration_number,
            manufacturer: $medicine->manufacturing_company,
        );
    }

    public function toArray(): array
    {
        return [
            'medicine_id' => $this->medicineId,
            'variant_id' => $this->variantId,
            'sku' => $this->sku,
            'brand_name' => $this->brandName,
            'active_ingredients' => $this->activeIngredients,
            'strength' => $this->strength,
            'dosage_form' => $this->dosageForm,
            'route' => $this->route,
            'unit' => $this->unit,
            'packaging' => $this->packaging,
            'registration_number' => $this->registrationNumber,
            'manufacturer' => $this->manufacturer,
        ];
    }
}
