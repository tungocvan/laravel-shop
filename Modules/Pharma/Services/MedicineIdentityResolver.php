<?php

namespace Modules\Pharma\Services;

use Modules\Pharma\Data\DrugAwardProjectionData;
use Modules\Pharma\Data\MedicineResolution;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\Medicine;

class MedicineIdentityResolver
{
    public function __construct(private ?MedicineCatalogNormalizer $normalizer = null)
    {
        $this->normalizer ??= new MedicineCatalogNormalizer;
    }

    public function resolve(DrugAwardProjectionData $source): MedicineResolution
    {
        $registration = $this->normalize($source->registrationOrImportLicense);
        $packaging = $this->normalize($source->packagingSpec);

        if ($registration !== null) {
            $matches = Medicine::query()
                ->where('profile_status', Medicine::PROFILE_VERIFIED)
                ->whereNotNull('registration_number')
                ->get()
                ->filter(fn (Medicine $medicine) => $this->normalize($medicine->registration_number) === $registration
                    && ($packaging === null || $this->normalize($medicine->packaging_specification) === $packaging)
                )
                ->values();

            if ($matches->count() === 1) {
                return new MedicineResolution(
                    $matches->first(),
                    DrugBidAward::MATCH_VERIFIED,
                    'registration_exact',
                    100,
                );
            }

            if ($matches->count() > 1) {
                return new MedicineResolution(null, DrugBidAward::MATCH_AMBIGUOUS, 'registration_ambiguous', 0);
            }
        }

        $identity = $this->compositeIdentity($source);
        if ($identity !== null) {
            $matches = Medicine::query()
                ->where('profile_status', Medicine::PROFILE_VERIFIED)
                ->get()
                ->filter(fn (Medicine $medicine) => $this->medicineCompositeIdentity($medicine) === $identity)
                ->values();

            if ($matches->count() === 1) {
                return new MedicineResolution(
                    $matches->first(),
                    DrugBidAward::MATCH_VERIFIED,
                    'normalized_composite_exact',
                    95,
                );
            }

            if ($matches->count() > 1) {
                return new MedicineResolution(null, DrugBidAward::MATCH_AMBIGUOUS, 'normalized_composite_ambiguous', 0);
            }
        }

        return new MedicineResolution(null, DrugBidAward::MATCH_UNRESOLVED);
    }

    /**
     * Medicine-level identity intentionally excludes strength and packaging.
     * A product may have multiple strengths/presentations under one canonical medicine.
     */
    public function canonicalMedicineIdentity(array $attributes): ?string
    {
        $registration = $this->normalizer->registration($attributes['registration_number'] ?? null);
        $name = $this->normalize($attributes['name'] ?? $attributes['brand_name'] ?? null);

        if ($registration !== null && $name !== null) {
            return hash('sha256', implode('|', ['medicine-v2', $registration, $name]));
        }

        $parts = [
            $name,
            $this->normalize($attributes['active_ingredients'] ?? null),
            $this->normalize($attributes['dosage_form'] ?? null),
            $this->normalize($attributes['manufacturing_company'] ?? null),
        ];

        if (count(array_filter($parts, fn ($value) => $value !== null)) < 3) {
            return null;
        }

        return hash('sha256', implode('|', array_map(fn ($value) => $value ?? '-', $parts)));
    }

    /**
     * Variant identity distinguishes the same brand/registration by strength,
     * dosage form and presentation. Packaging quantity is handled separately.
     */
    public function canonicalVariantIdentity(array $attributes): string
    {
        return (new MedicineSkuGenerator($this->normalizer))->variantIdentity($attributes);
    }

    private function compositeIdentity(DrugAwardProjectionData $source): ?string
    {
        $parts = [
            $this->normalize($source->medicineName),
            $this->normalize($source->activeIngredient),
            $this->normalize($source->concentration),
            $this->normalize($source->dosageForm),
            $this->normalize($source->manufacturer),
        ];

        if (count(array_filter($parts, fn ($value) => $value !== null)) < 3) {
            return null;
        }

        return implode('|', array_map(fn ($value) => $value ?? '-', $parts));
    }

    private function medicineCompositeIdentity(Medicine $medicine): ?string
    {
        $parts = [
            $this->normalize($medicine->name),
            $this->normalize($medicine->active_ingredients),
            $this->normalize($medicine->concentration),
            $this->normalize($medicine->dosage_form),
            $this->normalize($medicine->manufacturing_company),
        ];

        if (count(array_filter($parts, fn ($value) => $value !== null)) < 3) {
            return null;
        }

        return implode('|', array_map(fn ($value) => $value ?? '-', $parts));
    }

    private function normalize(?string $value): ?string
    {
        return $this->normalizer->text($value);
    }
}
