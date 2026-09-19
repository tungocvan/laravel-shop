<?php

namespace Modules\Pharma\Services;

use App\Dossiers\Models\Dossier;
use Modules\Pharma\Models\MedicineProfile;

class HsspMedicineValidityService
{
    public function forMedicine(int $medicineId): array
    {
        $profileIds = MedicineProfile::query()
            ->where('medicine_id', $medicineId)
            ->orderByDesc('is_current')
            ->orderByDesc('id')
            ->pluck('id');

        if ($profileIds->isEmpty()) {
            return [];
        }

        $dossier = Dossier::query()
            ->where('owner_type', MedicineProfile::class)
            ->whereIn('owner_id', $profileIds)
            ->orderByDesc('is_current')
            ->orderByDesc('id')
            ->with('items')
            ->first();

        if (! $dossier) {
            return [];
        }

        return [
            'visa_validity_date' => $this->effectiveTo($dossier, 'registration'),
            'gmp_certification_date' => $this->effectiveTo($dossier, 'gmp'),
        ];
    }

    private function effectiveTo(Dossier $dossier, string $code): ?string
    {
        $item = $dossier->items->firstWhere('code', $code);
        $value = $item?->metadata['effective_to'] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
