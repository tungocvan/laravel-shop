<?php

namespace Modules\Pharma\Services;

use Modules\Pharma\Models\Medicine;

/**
 * MOH traditional medicine import identity: normalized name + registration + unit.
 * Keep DAV matching unchanged. Price is mutable and is not part of identity.
 */
final class MohTraditionalMedicineMatcher
{
    public function __construct(private readonly MedicineCatalogNormalizer $normalizer) {}

    public function inspect(array $candidate): array
    {
        $name = $this->normalizer->text($candidate['name'] ?? null);
        $registration = $this->normalizer->registration($candidate['registration'] ?? null);
        $unit = $this->normalizer->text($candidate['unit'] ?? null);

        if ($name === null || $registration === null || $unit === null) {
            return ['status' => 'incomplete', 'medicine_id' => null];
        }

        $medicines = Medicine::query()
            ->where(fn ($query) => $query
                ->where('registration_number', 'like', '%'.addcslashes((string) ($candidate['registration'] ?? ''), '%_\\').'%')
                ->orWhere('registration_number_primary', 'like', '%'.addcslashes((string) ($candidate['registration'] ?? ''), '%_\\').'%')
                ->orWhere('name', 'like', '%'.addcslashes((string) ($candidate['name'] ?? ''), '%_\\').'%'))
            ->get(['id', 'name', 'unit', 'product_type', 'registration_number', 'registration_number_primary', 'registration_number_raw', 'packaging_specification']);

        $samePair = $medicines->filter(fn (Medicine $medicine) =>
            $this->normalizer->text($medicine->name) === $name &&
            $this->hasRegistration($medicine, $registration));

        $exact = $samePair->filter(fn (Medicine $medicine) =>
            $this->normalizer->text($medicine->unit) === $unit);

        if ($exact->count() > 1) {
            return ['status' => 'ambiguous', 'medicine_id' => null];
        }

        if ($exact->count() === 1) {
            $medicine = $exact->first();
            if ($medicine->product_type !== Medicine::PRODUCT_TYPE_TRADITIONAL ||
                ($this->normalizer->text($medicine->packaging_specification) !==
                 $this->normalizer->text($candidate['package'] ?? null))) {
                return ['status' => 'review', 'medicine_id' => (int) $medicine->id];
            }

            return ['status' => 'existing', 'medicine_id' => (int) $medicine->id];
        }

        // Different units of the same name and registration are distinct products.
        if ($samePair->isNotEmpty()) {
            return ['status' => 'new', 'medicine_id' => null];
        }

        // Do not auto-import suspicious partial matches.
        if ($medicines->contains(fn (Medicine $medicine) =>
            $this->hasRegistration($medicine, $registration) ||
            $this->normalizer->text($medicine->name) === $name)) {
            return ['status' => 'review', 'medicine_id' => null];
        }

        return ['status' => 'new', 'medicine_id' => null];
    }

    private function hasRegistration(Medicine $medicine, string $registration): bool
    {
        foreach ([$medicine->registration_number, $medicine->registration_number_primary, $medicine->registration_number_raw] as $value) {
            if ($this->normalizer->registration($value) === $registration) {
                return true;
            }
        }

        return false;
    }
}
