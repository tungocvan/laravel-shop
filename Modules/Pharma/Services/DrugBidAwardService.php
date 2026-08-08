<?php

namespace Modules\Pharma\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Pharma\Models\DrugBidAward;

class DrugBidAwardService
{
    public function __construct(private readonly DrugBidAwardImportExport $importExport) {}

    public function getPaginated(
        ?string $search = null,
        ?string $investor = null,
        ?string $company = null,
        int $perPage = 10
    ): LengthAwarePaginator {
        return DrugBidAward::query()->with('medicine')
            ->when($search, fn ($query, $value) => $query->where(fn ($nested) => $nested
                ->where('medicine_name', 'like', "%{$value}%")
                ->orWhere('bidding_notice_code', 'like', "%{$value}%")
                ->orWhere('decision_number', 'like', "%{$value}%")))
            ->when($investor, fn ($query, $value) => $query->where('investor_name', $value))
            ->when($company, fn ($query, $value) => $query->where('winning_company_name', $value))
            ->latest()
            ->paginate($perPage);
    }

    public function findOrFail(int $id): DrugBidAward
    {
        return DrugBidAward::query()->findOrFail($id);
    }

    public function store(array $data): DrugBidAward
    {
        return DB::transaction(fn () => DrugBidAward::query()->create($data));
    }

    public function update(int $id, array $data): DrugBidAward
    {
        return DB::transaction(function () use ($id, $data) {
            $award = $this->findOrFail($id);
            $award->update($data);

            return $award;
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(fn () => (bool) $this->findOrFail($id)->delete());
    }

    public function getUniqueInvestors(): array
    {
        return DrugBidAward::query()->whereNotNull('investor_name')->distinct()->pluck('investor_name')->all();
    }

    public function getUniqueCompanies(): array
    {
        return DrugBidAward::query()->whereNotNull('winning_company_name')->distinct()->pluck('winning_company_name')->all();
    }

    public function importFromCsv(string $filePath): int
    {
        $report = $this->importExport->import($filePath, ['mode' => 'update_or_create']);

        return (int) ($report['success_rows'] ?? 0);
    }

    public function exportToCsv(?string $search = null, ?string $investor = null, ?string $company = null): string
    {
        $path = $this->importExport->export(compact('search', 'investor', 'company'));

        return storage_path('app/public/'.$path);
    }
}
