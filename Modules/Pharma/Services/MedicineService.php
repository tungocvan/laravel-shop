<?php

namespace Modules\Pharma\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Pharma\Models\Medicine;

class MedicineService
{
    public function __construct(private readonly MedicineImportExport $importExport) {}

    public function getPaginatedMedicines(
        ?string $search = null,
        int $perPage = 10,
        int $page = 1,
        ?string $circularGroup = null,
        ?string $specialControl = null
    ): LengthAwarePaginator {
        return Medicine::query()
            ->when($search, fn ($query, $value) => $query->where(fn ($nested) => $nested
                ->where('name', 'like', "%{$value}%")
                ->orWhere('active_ingredients', 'like', "%{$value}%")))
            ->when($circularGroup, fn ($query, $value) => $query->where('circular_group', $value))
            ->when($specialControl, fn ($query, $value) => $query->where('is_special_control', $value === 'yes'))
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
        return DB::transaction(fn () => Medicine::query()->create($data));
    }

    public function update(int $id, array $data): Medicine
    {
        return DB::transaction(function () use ($id, $data) {
            $medicine = $this->findOrFail($id);
            $medicine->update($data);

            return $medicine;
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
}
