<?php

namespace Modules\Pharma\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use LogicException;
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
        ?string $matchStatus = null,
    ): LengthAwarePaginator {
        return DrugBidAward::query()->with(['medicine', 'sources'])
            ->when($search, fn ($query, $value) => $query->where(fn ($nested) => $nested
                ->where('medicine_name', 'like', "%{$value}%")
                ->orWhere('active_ingredient', 'like', "%{$value}%")
                ->orWhere('medicine_code', 'like', "%{$value}%")
                ->orWhere('bidding_notice_code', 'like', "%{$value}%")
                ->orWhere('lot_name', 'like', "%{$value}%")
                ->orWhere('decision_number', 'like', "%{$value}%")))
            ->when($investor, fn ($query, $value) => $query->where('investor_name', 'like', "%{$value}%"))
            ->when($company, fn ($query, $value) => $query->where('winning_company_name', 'like', "%{$value}%"))
            ->when($sourceType, fn ($query, $value) => $query->where(fn ($nested) => $nested
                ->where('source_type', $value)
                ->orWhereHas('sources', fn ($source) => $source->where('source_system', $value))))
            ->when($matchStatus, fn ($query, $value) => $query->where('medicine_match_status', $value))
            ->latest('published_at')
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', max(1, $page));
    }

    public function findOrFail(int $id): DrugBidAward { return DrugBidAward::query()->findOrFail($id); }

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
        if (trim($source->sourceId) === '') {
            throw new \InvalidArgumentException('External projection requires a source id.');
        }

        return DB::transaction(function () use ($source): DrugBidAward {
            $existingSource = DrugBidAward::query()->where('source_type', $source->sourceType)->where('source_id', $source->sourceId)->first();
            $businessConflict = DrugBidAward::query()
                ->where('bidding_notice_code', $source->biddingNoticeCode)
                ->where('medicine_name', $source->medicineName)
                ->where('winning_company_name', $source->winningCompanyName)
                ->when($existingSource, fn ($query) => $query->whereKeyNot($existingSource->getKey()))->first();

            if ($businessConflict) {
                throw new LogicException('Drug bid award source projection conflicts with an existing business-key record.');
            }

            $attributes = $source->toAwardAttributes();
            if ($existingSource) {
                $existingSource->update($attributes);
                return $existingSource->refresh();
            }
            return DrugBidAward::query()->create($attributes)->refresh();
        });
    }

    public function delete(int $id): bool { return DB::transaction(fn () => (bool) $this->findOrFail($id)->delete()); }

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
