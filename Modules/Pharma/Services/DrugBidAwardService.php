<?php

namespace Modules\Pharma\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        ?string $tbmt = null,
    ): LengthAwarePaginator {
        $lineageAvailable = Schema::hasTable('pharma_drug_bid_award_sources');
        $query = DrugBidAward::query()->with('medicine');

        if ($lineageAvailable) {
            $query->with('sources');
        }

        return $query
            ->when($search, fn ($query, $value) => $query->where(fn ($nested) => $nested
                ->where('medicine_name', 'like', "%{$value}%")
                ->orWhere('active_ingredient', 'like', "%{$value}%")
                ->orWhere('medicine_code', 'like', "%{$value}%")
                ->orWhere('lot_name', 'like', "%{$value}%")
                ->orWhere('decision_number', 'like', "%{$value}%")))
            ->when($tbmt, fn ($query, $value) => $query->where('bidding_notice_code', 'like', "%{$value}%"))
            ->when($investor, fn ($query, $value) => $query->where('investor_name', 'like', "%{$value}%"))
            ->when($company, fn ($query, $value) => $query->where('winning_company_name', 'like', "%{$value}%"))
            ->when($sourceType, function ($query, $value) use ($lineageAvailable): void {
                if (! $lineageAvailable) {
                    $query->where('source_type', $value);

                    return;
                }

                $query->where(fn ($nested) => $nested
                    ->where('source_type', $value)
                    ->orWhereHas('sources', fn ($source) => $source->where('source_system', $value)));
            })
            ->when($matchStatus, fn ($query, $value) => $query->where('medicine_match_status', $value))
            ->latest('published_at')
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', max(1, $page));
    }

    public function getResultGroupsPaginated(
        ?string $search = null,
        ?string $investor = null,
        ?string $company = null,
        int $perPage = 10,
        int $page = 1,
        ?string $sourceType = null,
        ?string $matchStatus = null,
        ?string $tbmt = null,
        ?string $valueSort = null,
    ): LengthAwarePaginator {
        $groupKey = "COALESCE(NULLIF(bidding_notice_code, ''), CONCAT('award-', id))";

        return DrugBidAward::query()
            ->selectRaw("{$groupKey} as result_key")
            ->selectRaw('MAX(id) as representative_id')
            ->selectRaw('MAX(bidding_notice_code) as bidding_notice_code')
            ->selectRaw('MAX(investor_name) as investor_name')
            ->selectRaw('MAX(investor_code) as investor_code')
            ->selectRaw('MAX(decision_number) as decision_number')
            ->selectRaw('MAX(decision_date) as decision_date')
            ->selectRaw('COUNT(*) as product_count')
            ->selectRaw('SUM(COALESCE(quantity, 0)) as total_quantity')
            ->selectRaw('SUM(COALESCE(amount, COALESCE(winning_price, unit_price, 0) * COALESCE(quantity, 0))) as total_value')
            ->selectRaw('COUNT(DISTINCT NULLIF(winning_company_name, \'\')) as contractor_count')
            ->selectRaw('MAX(winning_company_name) as winning_company_name')
            ->selectRaw('MAX(published_at) as latest_published_at')
            ->selectRaw('MAX(published_at) as published_at')
            ->selectRaw('MAX(contract_duration_months) as contract_duration_months')
            ->selectRaw('MAX(contract_period) as contract_period')
            ->selectRaw('MAX(contract_period_unit) as contract_period_unit')
            ->selectRaw('MAX(contract_period_text) as contract_period_text')
            ->selectRaw("SUM(CASE WHEN EXISTS (SELECT 1 FROM pharma_drug_bid_award_allocations a WHERE a.drug_bid_award_id = pharma_drug_bid_awards.id AND a.status = 'active') THEN 1 ELSE 0 END) as allocated_product_count")
            ->selectRaw("SUM(CASE WHEN EXISTS (SELECT 1 FROM pharma_drug_bid_award_management_assignments m WHERE m.drug_bid_award_id = pharma_drug_bid_awards.id AND m.status = 'active') THEN 1 ELSE 0 END) as managed_product_count")
            ->when($search, fn ($query, $value) => $query->where(fn ($nested) => $nested
                ->where('medicine_name', 'like', "%{$value}%")
                ->orWhere('active_ingredient', 'like', "%{$value}%")
                ->orWhere('medicine_code', 'like', "%{$value}%")
                ->orWhere('lot_name', 'like', "%{$value}%")
                ->orWhere('decision_number', 'like', "%{$value}%")))
            ->when($tbmt, fn ($query, $value) => $query->where('bidding_notice_code', 'like', "%{$value}%"))
            ->when($investor, fn ($query, $value) => $query->where('investor_name', 'like', "%{$value}%"))
            ->when($company, fn ($query, $value) => $query->where('winning_company_name', 'like', "%{$value}%"))
            ->when($sourceType, fn ($query, $value) => $query->where('source_type', $value))
            ->when($matchStatus, fn ($query, $value) => $query->where('medicine_match_status', $value))
            ->groupByRaw($groupKey)
            ->when($valueSort === 'desc', fn ($query) => $query->orderByDesc('total_value'))
            ->when($valueSort === 'asc', fn ($query) => $query->orderBy('total_value'))
            ->orderByDesc('latest_published_at')
            ->orderByDesc('representative_id')
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

    public function productsForResultGroup(int $representativeId): Collection
    {
        $representative = $this->findOrFail($representativeId);

        return DrugBidAward::query()
            ->with('medicine')
            ->when(
                filled($representative->bidding_notice_code),
                fn ($query) => $query->where('bidding_notice_code', $representative->bidding_notice_code),
                fn ($query) => $query->whereKey($representative->id)
            )
            ->orderByRaw("CASE WHEN lot_no IS NULL OR lot_no = '' THEN 1 ELSE 0 END")
            ->orderBy('lot_no')
            ->orderBy('id')
            ->get();
    }

    public function findProductInResultGroupOrFail(int $representativeId, int $productId): DrugBidAward
    {
        $product = $this->productsForResultGroup($representativeId)->firstWhere('id', $productId);

        abort_unless($product, 404);

        return $product;
    }

    public function updateResultGroupLegalInfo(int $representativeId, array $data): void
    {
        DB::transaction(function () use ($representativeId, $data): void {
            $representative = $this->findOrFail($representativeId);
            DrugBidAward::query()
                ->when(
                    filled($representative->bidding_notice_code),
                    fn ($query) => $query->where('bidding_notice_code', $representative->bidding_notice_code),
                    fn ($query) => $query->whereKey($representative->id)
                )
                ->update($data);
        });
    }

    public function updateProductInResultGroup(int $representativeId, int $productId, array $data): DrugBidAward
    {
        return DB::transaction(function () use ($representativeId, $productId, $data): DrugBidAward {
            $product = $this->findProductInResultGroupOrFail($representativeId, $productId);

            if (array_key_exists('medicine_id', $data)) {
                if ($data['medicine_id']) {
                    $medicine = \Modules\Pharma\Models\Medicine::query()->findOrFail((int) $data['medicine_id']);
                    $data['medicine_code'] = $medicine->medicine_code;
                    $data['medicine_match_status'] = DrugBidAward::MATCH_VERIFIED;
                } else {
                    $data['medicine_code'] = null;
                    $data['medicine_match_status'] = DrugBidAward::MATCH_UNRESOLVED;
                }
            }

            $product->update($data);

            return $product->refresh();
        });
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
            $existingSource = DrugBidAward::query()
                ->where('source_type', $source->sourceType)
                ->where('source_id', $source->sourceId)
                ->first();

            $businessConflict = DrugBidAward::query()
                ->where('bidding_notice_code', $source->biddingNoticeCode)
                ->where('medicine_name', $source->medicineName)
                ->where('winning_company_name', $source->winningCompanyName)
                ->when($existingSource, fn ($query) => $query->whereKeyNot($existingSource->getKey()))
                ->first();

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

    public function delete(int $id): bool
    {
        return DB::transaction(fn () => (bool) $this->findOrFail($id)->delete());
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
