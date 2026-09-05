<?php

namespace Modules\Pharma\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Pharma\Models\Medicine;

class MedicineService
{
    public function __construct(
        private readonly MedicineImportExport $importExport,
        private readonly MedicineIdentityResolver $identityResolver,
    ) {}

    public function getPaginatedMedicines(
        ?string $search = null,
        int $perPage = 10,
        int $page = 1,
        ?string $circularGroup = null,
        ?string $specialControl = null,
        ?string $profileStatus = null,
    ): LengthAwarePaginator {
        return Medicine::query()
            ->withCount(['sources', 'drugBidAwards'])
            ->when($search, fn ($query, $value) => $query->where(fn ($nested) => $nested
                ->where('name', 'like', "%{$value}%")
                ->orWhere('active_ingredients', 'like', "%{$value}%")
                ->orWhere('registration_number', 'like', "%{$value}%")
                ->orWhere('concentration', 'like', "%{$value}%")
                ->orWhere('manufacturing_company', 'like', "%{$value}%")
                ->orWhere('manufacturing_country', 'like', "%{$value}%")))
            ->when($circularGroup, fn ($query, $value) => $query->where('circular_group', $value))
            ->when($specialControl, fn ($query, $value) => $query->where('is_special_control', $value === 'yes'))
            ->when($profileStatus, fn ($query, $value) => $query->where('profile_status', $value))
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function getUniqueCircularGroups(): array
    {
        return Medicine::query()
            ->whereNotNull('circular_group')
            ->where('circular_group', '!=', '')
            ->distinct()
            ->pluck('circular_group')
            ->all();
    }

    public function findOrFail(int $id): Medicine
    {
        return Medicine::query()->findOrFail($id);
    }

    public function store(array $data): Medicine
    {
        return DB::transaction(function () use ($data): Medicine {
            $data = $this->normalizeQualityState($data);

            return Medicine::query()->create($data);
        });
    }

    public function update(int $id, array $data): Medicine
    {
        return DB::transaction(function () use ($id, $data) {
            $medicine = $this->findOrFail($id);
            $data = $this->normalizeQualityState($data, $medicine);
            $medicine->update($data);

            return $medicine->refresh();
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(fn () => (bool) $this->findOrFail($id)->delete());
    }

    public function importFromCsv(string $filePath): int
    {
        $report = $this->importExport->import($filePath, ['mode' => 'update_or_create']);

        return (int) ($report['success_rows'] ?? 0);
    }

    public function exportToCsv(
        ?string $search = null,
        ?string $circularGroup = null,
        ?string $specialControl = null
    ): string {
        $path = $this->importExport->export([
            'search' => $search,
            'circular_group' => $circularGroup,
            'is_special_control' => $specialControl === null ? null : $specialControl === 'yes',
        ]);

        return storage_path('app/public/'.$path);
    }

    private function normalizeQualityState(array $data, ?Medicine $existing = null): array
    {
        $identityKey = $this->identityResolver->canonicalMedicineIdentity($data + ($existing?->toArray() ?? []));
        $data['canonical_identity_key'] = $identityKey;

        if ($existing?->identity_status === Medicine::IDENTITY_VERIFIED_REGISTRATION) {
            $data['identity_status'] = $existing->identity_status;
        } elseif (! empty($data['registration_number'])) {
            $data['identity_status'] = Medicine::IDENTITY_UNVERIFIED;
        } elseif ($identityKey) {
            $data['identity_status'] = Medicine::IDENTITY_PROVISIONAL;
        } else {
            $data['identity_status'] = Medicine::IDENTITY_UNVERIFIED;
        }

        $merged = $data + ($existing?->toArray() ?? []);
        $requiredForComplete = [
            'active_ingredients',
            'concentration',
            'dosage_form',
            'route_of_administration',
            'unit',
            'packaging_specification',
            'registration_number',
            'shelf_life',
            'registered_company',
            'manufacturing_company',
            'manufacturing_country',
        ];
        $complete = collect($requiredForComplete)->every(
            fn ($field) => isset($merged[$field]) && trim((string) $merged[$field]) !== ''
        );

        if ($existing?->profile_status === Medicine::PROFILE_VERIFIED && $complete) {
            $data['profile_status'] = Medicine::PROFILE_VERIFIED;
        } else {
            $data['profile_status'] = $complete ? Medicine::PROFILE_COMPLETE : Medicine::PROFILE_INCOMPLETE;
        }

        return $data;
    }
}
