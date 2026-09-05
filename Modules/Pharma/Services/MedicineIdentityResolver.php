<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Str;
use Modules\Pharma\Data\DrugAwardProjectionData;
use Modules\Pharma\Data\MedicineResolution;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\Medicine;

class MedicineIdentityResolver
{
    public function resolve(DrugAwardProjectionData $source): MedicineResolution
    {
        $registration = $this->normalize($source->registrationOrImportLicense);
        $packaging = $this->normalize($source->packagingSpec);

        if ($registration !== null) {
            $query = Medicine::query()->whereNotNull('registration_number');
            $matches = $query->get()->filter(fn (Medicine $medicine) => $this->normalize($medicine->registration_number) === $registration
                && ($packaging === null || $this->normalize($medicine->packaging_specification) === $packaging)
            )->values();

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
            $matches = Medicine::query()->get()->filter(fn (Medicine $medicine) => $this->medicineCompositeIdentity($medicine) === $identity)->values();

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

    public function canonicalMedicineIdentity(array $attributes): ?string
    {
        $registration = $this->normalize($attributes['registration_number'] ?? null);
        $packaging = $this->normalize($attributes['packaging_specification'] ?? null);

        if ($registration !== null) {
            return hash('sha256', implode('|', ['registration', $registration, $packaging ?? '-']));
        }

        $parts = [
            $this->normalize($attributes['name'] ?? null),
            $this->normalize($attributes['active_ingredients'] ?? null),
            $this->normalize($attributes['concentration'] ?? null),
            $this->normalize($attributes['dosage_form'] ?? null),
            $this->normalize($attributes['manufacturing_company'] ?? null),
        ];

        if (count(array_filter($parts, fn ($value) => $value !== null)) < 3) {
            return null;
        }

        return hash('sha256', implode('|', array_map(fn ($value) => $value ?? '-', $parts)));
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
        if ($value === null || trim($value) === '') {
            return null;
        }

        return preg_replace('/\s+/u', ' ', Str::lower(Str::ascii(trim($value))));
    }
}
