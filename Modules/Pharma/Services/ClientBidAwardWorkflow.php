<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Pharma\Models\OfficialSourceFacility;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\DrugBidAwardAllocation;
use Modules\Pharma\Models\DrugBidAwardProductPolicy;

final class ClientBidAwardWorkflow
{
    public function __construct(
        private readonly DrugBidAwardResultGroupService $groups,
        private readonly DrugBidAwardDistributionScopeService $distributionScopes,
        private readonly DrugBidAwardAllocationService $allocations,
        private readonly DrugBidAwardCommercialPolicyService $commercialPolicies,
    ) {}

    public function contextAward(string $scopeKey): ?DrugBidAward
    {
        return DrugBidAward::query()->get()->first(
            fn (DrugBidAward $award): bool => sha1($this->groups->resultKey($award)) === $scopeKey
        );
    }

    public function distributionSetup(DrugBidAward $award): array
    {
        $scope = $this->distributionScopes->findForAward($award);
        if (! $scope) {
            return ['scope' => null, 'provinces' => [], 'facility_ids' => [], 'selected_facilities' => collect()];
        }
        $provinces = DB::table('pharma_drug_bid_award_distribution_scope_provinces')
            ->where('distribution_scope_id', $scope->id)->orderBy('province_name')->pluck('province_name')->all();
        if ($provinces === [] && filled($scope->province_code)) $provinces = [(string) $scope->province_code];
        $externalIds = $scope->partners->flatMap(fn ($partner) => $partner->sourceReferences
            ->where('source', 'official_source_facility')->pluck('external_id'))->filter()->all();
        $facilityIds = OfficialSourceFacility::query()->whereIn('external_id', $externalIds)->pluck('id')->map(fn ($id) => (int) $id)->all();

        return [
            'scope' => $scope, 'provinces' => $provinces, 'facility_ids' => $facilityIds,
            'selected_facilities' => OfficialSourceFacility::query()->whereIn('id', $facilityIds)->orderBy('facility_name')->get(),
        ];
    }

    public function provinceOptions(): Collection
    {
        return OfficialSourceFacility::query()->where('is_active', true)->whereNotNull('province_name')->where('province_name', '!=', '')
            ->distinct()->orderBy('province_name')->pluck('province_name');
    }

    public function facilitiesForProvinces(array $provinces): Collection
    {
        return OfficialSourceFacility::query()->where('is_active', true)->whereIn('province_name', $provinces)
            ->orderBy('province_name')->orderBy('facility_name')->limit(600)->get(['id','external_id','facility_name','district_name','province_name']);
    }

    public function saveDistributionSetup(DrugBidAward $award, array $data, ?int $actorId): void
    {
        $this->distributionScopes->save($award, [
            'province_names' => $data['province_names'],
            'facility_ids' => $data['facility_ids'],
            'effective_from' => $data['effective_from'],
            'effective_until' => $data['effective_until'],
        ], $actorId);
    }

    public function hospitals(DrugBidAward $award): Collection
    {
        return $this->distributionScopes->findForAward($award)?->partners
            ?->where('status', 'active')->sortBy('name')->values() ?? collect();
    }

    public function products(DrugBidAward $award): Collection
    {
        return $this->groups->awardsQuery($award)
            ->orderBy('medicine_name')
            ->get(['id', 'medicine_name', 'active_ingredient', 'concentration', 'quantity', 'winning_price', 'unit_price']);
    }

    public function existingAllocations(DrugBidAward $award): Collection
    {
        return DrugBidAwardAllocation::query()
            ->whereIn('drug_bid_award_id', $this->groups->awardsQuery($award)->pluck('id'))
            ->where('status', DrugBidAwardAllocation::STATUS_ACTIVE)
            ->get()
            ->keyBy(fn (DrugBidAwardAllocation $allocation): string => $allocation->drug_bid_award_id.':'.$allocation->partner_id);
    }

    public function hospital(DrugBidAward $award, int $partnerId): ?object
    {
        return $this->hospitals($award)->first(fn ($partner) => (int) $partner->id === $partnerId);
    }

    public function hospitalAllocations(DrugBidAward $award, int $partnerId): Collection
    {
        return DrugBidAwardAllocation::query()
            ->whereIn('drug_bid_award_id', $this->groups->awardsQuery($award)->pluck('id'))
            ->where('partner_id', $partnerId)->where('status', DrugBidAwardAllocation::STATUS_ACTIVE)
            ->get()->keyBy('drug_bid_award_id');
    }

    public function productAllocationCards(DrugBidAward $award): Collection
    {
        $products = $this->products($award);
        $allocated = DrugBidAwardAllocation::query()
            ->whereIn('drug_bid_award_id', $products->pluck('id'))
            ->where('status', DrugBidAwardAllocation::STATUS_ACTIVE)
            ->selectRaw('drug_bid_award_id, SUM(allocated_quantity) as allocated_quantity')
            ->groupBy('drug_bid_award_id')
            ->pluck('allocated_quantity', 'drug_bid_award_id');

        return $products->map(function ($product) use ($allocated) {
            $winning = (float) ($product->quantity ?? 0);
            $used = (float) ($allocated[$product->id] ?? 0);
            $product->pwa_allocated_quantity = $used;
            $product->pwa_remaining_quantity = max($winning - $used, 0);
            $product->pwa_fully_allocated = $winning > 0 && $product->pwa_remaining_quantity <= 0;
            return $product;
        });
    }

    public function hospitalCards(DrugBidAward $award): Collection
    {
        $productCount = $this->products($award)->count();
        $allocations = $this->existingAllocations($award)->groupBy('partner_id');
        return $this->hospitals($award)->map(function ($hospital) use ($productCount, $allocations) {
            $rows = $allocations->get($hospital->id, collect());
            $allocatedProducts = $rows->pluck('drug_bid_award_id')->unique()->count();
            $policyProducts = $rows->filter(fn ($row) => $row->commercial_policy_percentage !== null)->pluck('drug_bid_award_id')->unique()->count();
            $hospital->pwa_product_count = $productCount;
            $hospital->pwa_allocated_products = $allocatedProducts;
            $hospital->pwa_policy_products = $policyProducts;
            return $hospital;
        });
    }

    public function saveHospitalAllocations(DrugBidAward $award, int $partnerId, array $quantities, ?int $actorId): int
    {
        if (! $this->hospital($award, $partnerId)) {
            throw ValidationException::withMessages(['hospital' => 'Bệnh viện không thuộc phạm vi phân bổ hiện tại.']);
        }
        $payload = [];
        foreach ($quantities as $awardId => $quantity) $payload[(int) $awardId] = [$partnerId => $quantity];
        return $this->saveAllocations($award, [$partnerId], $payload, $actorId);
    }

    public function saveHospitalPolicies(DrugBidAward $award, int $partnerId, array $percentages, ?int $actorId): int
    {
        if (! $this->hospital($award, $partnerId)) {
            throw ValidationException::withMessages(['hospital' => 'Bệnh viện không thuộc phạm vi phân bổ hiện tại.']);
        }
        $saved = 0;
        foreach ($percentages as $awardId => $percentage) {
            if ($percentage === '' || $percentage === null) continue;
            $this->commercialPolicies->saveHospitalPolicyOverride($award, (int) $awardId, $partnerId, $percentage, $actorId);
            $saved++;
        }
        if ($saved === 0) throw ValidationException::withMessages(['commercial_policy' => 'Nhập chính sách cho ít nhất một sản phẩm đã phân bổ.']);
        return $saved;
    }

    public function saveAllocations(DrugBidAward $award, array $selectedHospitalIds, array $quantities, ?int $actorId): int
    {
        $allowedHospitalIds = $this->hospitals($award)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $hospitalIds = array_values(array_intersect($allowedHospitalIds, array_unique(array_map('intval', $selectedHospitalIds))));
        if ($hospitalIds === []) {
            throw ValidationException::withMessages(['hospitals' => 'Hãy hoàn tất Bước 1 và chọn ít nhất một bệnh viện.']);
        }

        $productIds = $this->products($award)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $saved = 0;
        foreach ($quantities as $awardId => $hospitalRows) {
            $awardId = (int) $awardId;
            if (! in_array($awardId, $productIds, true) || ! is_array($hospitalRows)) continue;
            foreach ($hospitalRows as $partnerId => $quantity) {
                $partnerId = (int) $partnerId;
                if (! in_array($partnerId, $hospitalIds, true) || $quantity === '' || $quantity === null) continue;
                $this->allocations->save($awardId, null, [
                    'partner_id' => $partnerId,
                    'allocated_quantity' => $quantity,
                    'notes' => 'Phân bổ từ Pharma PWA',
                ], $actorId);
                $saved++;
            }
        }

        if ($saved === 0) {
            throw ValidationException::withMessages(['allocations' => 'Nhập số lượng cho ít nhất một sản phẩm/bệnh viện.']);
        }

        return $saved;
    }

    public function hasActiveAllocation(DrugBidAward $award): bool
    {
        return DrugBidAwardAllocation::query()
            ->whereIn('drug_bid_award_id', $this->groups->awardsQuery($award)->pluck('id'))
            ->where('status', DrugBidAwardAllocation::STATUS_ACTIVE)
            ->exists();
    }

    public function productPolicies(DrugBidAward $award): Collection
    {
        return DrugBidAwardProductPolicy::query()
            ->whereIn('drug_bid_award_id', $this->groups->awardsQuery($award)->pluck('id'))
            ->get()->keyBy('drug_bid_award_id');
    }

    public function saveProductPolicies(DrugBidAward $award, array $percentages, ?int $actorId): void
    {
        if (! $this->hasActiveAllocation($award)) {
            throw ValidationException::withMessages(['commercial_policy' => 'Cần hoàn tất phân bổ số lượng trước khi thiết lập chính sách kinh doanh.']);
        }
        $allocatedAwardIds = DrugBidAwardAllocation::query()
            ->whereIn('drug_bid_award_id', $this->groups->awardsQuery($award)->pluck('id'))
            ->where('status', DrugBidAwardAllocation::STATUS_ACTIVE)
            ->pluck('drug_bid_award_id')->map(fn ($id) => (int) $id)->unique()->all();
        foreach ($percentages as $awardId => $percentage) {
            if ($percentage !== '' && $percentage !== null && ! in_array((int) $awardId, $allocatedAwardIds, true)) {
                throw ValidationException::withMessages(["percentages.$awardId" => 'Sản phẩm phải được phân bổ số lượng trước khi thiết lập chính sách kinh doanh.']);
            }
        }
        $this->commercialPolicies->saveProductPolicies($award, $percentages, $actorId);
    }
}
