<?php

namespace Modules\Pharma\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Pharma\Data\DrugBidAwardSourceData;
use Modules\Pharma\Models\DrugBidAward;

class DrugBidAwardService
{
    public function __construct(private readonly DrugBidAwardImportExport $importExport) {}

    public function getPaginated(
        ?string $search = null,
        ?string $investor = null,
        ?string $company = null,
        int $perPage = 10,
        int $page = 1,
        ?string $sourceType = null,
    ): LengthAwarePaginator {
        return DrugBidAward::query()->with('medicine')
            ->when($search, fn ($query, $value) => $query->where(fn ($nested) => $nested
                ->where('medicine_name', 'like', "%{$value}%")
                ->orWhere('bidding_notice_code', 'like', "%{$value}%")
                ->orWhere('decision_number', 'like', "%{$value}%")))
            ->when($investor, fn ($query, $value) => $query->where('investor_name', $value))
            ->when($company, fn ($query, $value) => $query->where('winning_company_name', $value))
            ->when($sourceType, fn ($query, $value) => $query->where('source_type', $value))
            ->latest()
            ->paginate($perPage, ['*'], 'page', max(1, $page));
    }

    public function findOrFail(int $id): DrugBidAward
    {
        return DrugBidAward::query()->findOrFail($id);
    }

    public function store(array $data): DrugBidAward
    {
        $data['source_type'] = DrugBidAward::SOURCE_MANUAL;
        $data['source_id'] = null;

        return DB::transaction(fn () => DrugBidAward::query()->create($data));
    }

    public function update(int $id, array $data): DrugBidAward
    {
        return DB::transaction(function () use ($id, $data) {
            $award = $this->findOrFail($id);
            $award->update($data);

            return $award->refresh();
        });
    }

    public function projectFromSource(DrugBidAwardSourceData $source): DrugBidAward
    {
        if ($source->sourceType === DrugBidAward::SOURCE_MANUAL) {
            throw new \InvalidArgumentException('External projection cannot use the manual source type.');
        }

        return DB::transaction(function () use ($source): DrugBidAward {
            return DrugBidAward::query()->updateOrCreate(
                [
                    'source_type' => $source->sourceType,
                    'source_id' => $source->sourceId,
                ],
                $source->toAwardAttributes(),
            )->refresh();
        });
    }

    public function delete(int $id): bool
    {
        return DB::transaction(fn () => (bool) $this->findOrFail($id)->delete());
    }

    public function getUniqueInvestors(): array
    {
        return DrugBidAward::query()->whereNotNull('investor_name')->distinct()->orderBy('investor_name')->pluck('investor_name')->all();
    }

    public function getUniqueCompanies(): array
    {
        return DrugBidAward::query()->whereNotNull('winning_company_name')->distinct()->orderBy('winning_company_name')->pluck('winning_company_name')->all();
    }

    public function importFromCsv(string $filePath): int
    {
        $report = $this->importExport->import($filePath, ['mode' => 'update_or_create']);

        return (int) ($report['success_rows'] ?? 0);
    }

    public function exportToCsv(?string $search = null, ?string $investor = null, ?string $company = null): string
    {
        return $this->importExport->export(compact('search', 'investor', 'company'));
    }
}
