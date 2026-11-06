<?php

namespace Modules\Pharma\Services;

use Modules\Pharma\Models\Medicine;

final class DavMedicineCandidateMatcher
{
    public function __construct(private readonly MedicineCatalogNormalizer $normalizer) {}

    public function inspect(array $candidate): array
    {
        $name = $this->normalizer->text($candidate['name'] ?? null);
        $registration = $this->normalizer->registration($candidate['registration'] ?? null);

        if ($name === null || $registration === null) {
            return ['status' => 'incomplete', 'medicine_id' => null];
        }

        // Check every registration match, including incomplete/unverified records.
        $medicines = Medicine::query()
            ->where(fn ($query) => $query
                ->where('registration_number', 'like', '%'.addcslashes((string) ($candidate['registration'] ?? ''), '%_\\').'%')
                ->orWhere('registration_number_primary', 'like', '%'.addcslashes((string) ($candidate['registration'] ?? ''), '%_\\').'%')
                ->orWhere('name', 'like', '%'.addcslashes((string) ($candidate['name'] ?? ''), '%_\\').'%'))
            ->get(['id', 'name', 'registration_number', 'registration_number_primary', 'registration_number_raw']);

        // Query above narrows likely candidates; normalize again to avoid punctuation/case drift.
        $exact = $medicines->filter(fn (Medicine $medicine) =>
            $this->normalizer->text($medicine->name) === $name &&
            $this->hasRegistration($medicine, $registration)
        );
        if ($exact->count() === 1) {
            return ['status' => 'existing', 'medicine_id' => (int) $exact->first()->id];
        }
        if ($exact->count() > 1) {
            return ['status' => 'ambiguous', 'medicine_id' => null];
        }

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
