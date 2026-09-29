<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Modules\Pharma\Models\OfficialSourceFacility;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\DrugBidAwardAllocation;
use Modules\Pharma\Models\DrugBidAwardProductPolicy;
use Modules\Pharma\Models\DrugBidAwardManagementAssignment;

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
        $allocatedAwardIds = $this->hospitalAllocations($award, $partnerId)->keys()->map(fn ($id) => (int) $id)->all();
        $saved = 0;
        foreach ($percentages as $awardId => $percentage) {
            $awardId = (int) $awardId;
            if (! in_array($awardId, $allocatedAwardIds, true)) continue;
            $this->commercialPolicies->saveHospitalPolicyOverride($award, $awardId, $partnerId, $percentage, $actorId);
            $saved++;
        }
        if ($saved === 0) throw ValidationException::withMessages(['commercial_policy' => 'Không có sản phẩm đã phân bổ để cập nhật chính sách.']);
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

    public function commercialPolicyReady(DrugBidAward $award): bool
    {
        $awardIds = $this->groups->awardsQuery($award)->pluck('id');
        $allocations = DrugBidAwardAllocation::query()
            ->whereIn('pharma_drug_bid_award_allocations.drug_bid_award_id', $awardIds)
            ->where('pharma_drug_bid_award_allocations.status', DrugBidAwardAllocation::STATUS_ACTIVE);

        if (! (clone $allocations)->exists()) return false;

        return ! (clone $allocations)
            ->leftJoin('pharma_drug_bid_award_product_policies as product_policies', 'product_policies.drug_bid_award_id', '=', 'pharma_drug_bid_award_allocations.drug_bid_award_id')
            ->whereNull('pharma_drug_bid_award_allocations.commercial_policy_percentage')
            ->whereNull('product_policies.commission_percentage')
            ->exists();
    }

    /** @return array{persisted_mode:string,allocation_count:int,assignment_count:int,user_count:int} */
    public function managementAssignmentState(DrugBidAward $award): array
    {
        $awardIds = $this->groups->awardsQuery($award)->pluck('id');
        $allocationCount = DrugBidAwardAllocation::query()
            ->whereIn('drug_bid_award_id', $awardIds)
            ->where('status', DrugBidAwardAllocation::STATUS_ACTIVE)
            ->get(['drug_bid_award_id','partner_id'])
            ->unique(fn ($row) => $row->drug_bid_award_id.':'.$row->partner_id)->count();
        $assignments = DrugBidAwardManagementAssignment::query()
            ->whereIn('drug_bid_award_id', $awardIds)
            ->where('status', DrugBidAwardManagementAssignment::STATUS_ACTIVE)
            ->get(['drug_bid_award_id','partner_id','user_id']);
        $mode = 'unassigned';
        if ($assignments->isNotEmpty()) {
            $completeSingle = $allocationCount > 0
                && $assignments->count() >= $allocationCount
                && $assignments->pluck('user_id')->unique()->count() === 1;
            $mode = $completeSingle ? 'single' : 'multiple';
        }

        return [
            'persisted_mode' => $mode,
            'allocation_count' => $allocationCount,
            'assignment_count' => $assignments->count(),
            'user_count' => $assignments->pluck('user_id')->unique()->count(),
        ];
    }

    public function managementAssignmentSummary(DrugBidAward $award): Collection
    {
        $awardIds = $this->groups->awardsQuery($award)->pluck('id');
        $rows = DrugBidAwardManagementAssignment::query()
            ->with('user:id,name,email')
            ->whereIn('drug_bid_award_id', $awardIds)
            ->where('status', DrugBidAwardManagementAssignment::STATUS_ACTIVE)
            ->get(['id','drug_bid_award_id','partner_id','user_id','status']);

        return $rows->groupBy('user_id')->map(function (Collection $assignments) {
            $first = $assignments->first();
            return (object) [
                'user_id' => (int) $first->user_id,
                'user' => $first->user,
                'assignment_count' => $assignments->count(),
                'product_count' => $assignments->pluck('drug_bid_award_id')->unique()->count(),
                'hospital_count' => $assignments->pluck('partner_id')->unique()->count(),
            ];
        })->values()->sortBy(fn ($row) => str($row->user?->name ?? '')->lower())->values();
    }

    public function managerAssignmentWorkspace(DrugBidAward $award, int $userId): ?object
    {
        $manager = User::query()->whereKey($userId)->first(['id','name','email','is_active']);
        if (! $manager) return null;

        $awardIds = $this->groups->awardsQuery($award)->pluck('id');
        $products = $this->products($award)->keyBy('id');
        $hospitals = $this->hospitals($award)->keyBy('id');
        $rows = DrugBidAwardManagementAssignment::query()
            ->whereIn('drug_bid_award_id', $awardIds)
            ->where('user_id', $userId)
            ->where('status', DrugBidAwardManagementAssignment::STATUS_ACTIVE)
            ->orderBy('partner_id')->orderBy('drug_bid_award_id')
            ->get(['id','drug_bid_award_id','partner_id','user_id']);

        if ($rows->isEmpty()) return null;

        return (object) [
            'user' => $manager,
            'assignment_count' => $rows->count(),
            'hospital_count' => $rows->pluck('partner_id')->unique()->count(),
            'product_count' => $rows->pluck('drug_bid_award_id')->unique()->count(),
            'hospitals' => $rows->groupBy('partner_id')->map(function (Collection $assignments, $partnerId) use ($hospitals, $products) {
                return (object) [
                    'hospital' => $hospitals->get((int) $partnerId),
                    'assignments' => $assignments->map(function ($assignment) use ($products) {
                        $assignment->pwa_product = $products->get((int) $assignment->drug_bid_award_id);
                        return $assignment;
                    })->values(),
                ];
            })->filter(fn ($group) => $group->hospital !== null)->sortBy(fn ($group) => str($group->hospital->name)->lower())->values(),
        ];
    }

    public function transferManagerAssignments(DrugBidAward $award, int $fromUserId, array $assignmentIds, int $toUserId, ?int $actorId): int
    {
        if ($fromUserId === $toUserId) {
            throw ValidationException::withMessages(['to_user_id' => 'Hãy chọn User khác User hiện tại.']);
        }
        if (! User::query()->whereKey($toUserId)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['to_user_id' => 'User nhận phân công không hoạt động hoặc không tồn tại.']);
        }

        return $this->commercialPolicies->transferManagerAssignments($award, $fromUserId, $assignmentIds, $toUserId, $actorId);
    }

    public function removeManagerAssignments(DrugBidAward $award, int $userId, array $assignmentIds): int
    {
        return $this->commercialPolicies->removeManagerAssignments($award, $userId, $assignmentIds);
    }

    public function removeAllManagers(DrugBidAward $award): int
    {
        return $this->commercialPolicies->removeAllManagers($award);
    }

    public function managementUsers(): Collection
    {
        return User::query()->where('is_active', true)->orderBy('name')->get(['id','name','email']);
    }

    public function managementProducts(DrugBidAward $award): Collection
    {
        $products = $this->products($award);
        $awardIds = $products->pluck('id');
        $allocationCounts = DrugBidAwardAllocation::query()
            ->whereIn('drug_bid_award_id', $awardIds)->where('status', DrugBidAwardAllocation::STATUS_ACTIVE)
            ->selectRaw('drug_bid_award_id, COUNT(DISTINCT partner_id) as hospital_count')
            ->groupBy('drug_bid_award_id')->pluck('hospital_count','drug_bid_award_id');
        $assignmentCounts = DrugBidAwardManagementAssignment::query()
            ->whereIn('drug_bid_award_id', $awardIds)->where('status', DrugBidAwardManagementAssignment::STATUS_ACTIVE)
            ->selectRaw('drug_bid_award_id, COUNT(DISTINCT partner_id) as assigned_hospital_count')
            ->groupBy('drug_bid_award_id')->pluck('assigned_hospital_count','drug_bid_award_id');

        return $products->filter(fn ($product) => (int) ($allocationCounts[$product->id] ?? 0) > 0)
            ->each(function ($product) use ($allocationCounts, $assignmentCounts) {
                $product->pwa_hospital_count = (int) ($allocationCounts[$product->id] ?? 0);
                $product->pwa_assigned_hospital_count = (int) ($assignmentCounts[$product->id] ?? 0);
            })->values();
    }

    public function assignSingleManager(DrugBidAward $award, int $userId, ?int $actorId): int
    {
        if (! User::query()->whereKey($userId)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['user_id' => 'User quản lý không hoạt động hoặc không tồn tại.']);
        }
        if (! $this->commercialPolicyReady($award)) {
            throw ValidationException::withMessages(['assignment' => 'Hãy hoàn tất chính sách kinh doanh trước khi phân công User quản lý.']);
        }
        $state = $this->managementAssignmentState($award);
        if ($state['persisted_mode'] === 'multiple') {
            throw ValidationException::withMessages(['assignment' => 'Đang ở chế độ nhiều User. Hãy gỡ toàn bộ phân công trước khi đổi cách phân công.']);
        }
        return $this->commercialPolicies->assignManagerToAllAllocations($award, $userId, $actorId);
    }

    public function managementHospitalCards(DrugBidAward $award): Collection
    {
        $awardIds = $this->groups->awardsQuery($award)->pluck('id');
        $allocations = DrugBidAwardAllocation::query()
            ->whereIn('drug_bid_award_id', $awardIds)
            ->where('status', DrugBidAwardAllocation::STATUS_ACTIVE)
            ->get(['drug_bid_award_id','partner_id'])->groupBy('partner_id');
        $assignments = DrugBidAwardManagementAssignment::query()
            ->whereIn('drug_bid_award_id', $awardIds)
            ->where('status', DrugBidAwardManagementAssignment::STATUS_ACTIVE)
            ->get(['drug_bid_award_id','partner_id'])->groupBy('partner_id');

        return $this->hospitals($award)->map(function ($hospital) use ($allocations, $assignments) {
            $allocatedIds = $allocations->get($hospital->id, collect())->pluck('drug_bid_award_id')->unique();
            $assignedIds = $assignments->get($hospital->id, collect())->pluck('drug_bid_award_id')->unique();
            $hospital->pwa_management_allocated_count = $allocatedIds->count();
            $hospital->pwa_management_assigned_count = $assignedIds->intersect($allocatedIds)->count();
            $hospital->pwa_management_remaining_count = max($hospital->pwa_management_allocated_count - $hospital->pwa_management_assigned_count, 0);
            $hospital->pwa_management_complete = $hospital->pwa_management_allocated_count > 0 && $hospital->pwa_management_remaining_count === 0;
            return $hospital;
        })->filter(fn ($hospital) => $hospital->pwa_management_allocated_count > 0)
            ->sortBy(fn ($hospital) => ($hospital->pwa_management_complete ? '1' : '0').'|'.str($hospital->name)->lower())
            ->values();
    }

    public function unassignedHospitalProducts(DrugBidAward $award, int $partnerId): Collection
    {
        if (! $this->hospital($award, $partnerId)) return collect();

        $allocations = $this->hospitalAllocations($award, $partnerId);
        $assignedAwardIds = DrugBidAwardManagementAssignment::query()
            ->whereIn('drug_bid_award_id', $allocations->keys())
            ->where('partner_id', $partnerId)
            ->where('status', DrugBidAwardManagementAssignment::STATUS_ACTIVE)
            ->pluck('drug_bid_award_id')->map(fn ($id) => (int) $id);

        return $this->products($award)
            ->filter(fn ($product) => $allocations->has($product->id) && ! $assignedAwardIds->contains((int) $product->id))
            ->values();
    }

    public function assignManagerToHospitalProducts(DrugBidAward $award, int $partnerId, array $awardIds, int $userId, ?int $actorId): int
    {
        if (! User::query()->whereKey($userId)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['user_id' => 'User quản lý không hoạt động hoặc không tồn tại.']);
        }
        if (! $this->commercialPolicyReady($award)) {
            throw ValidationException::withMessages(['assignment' => 'Hãy hoàn tất chính sách kinh doanh trước khi phân công User quản lý.']);
        }
        if ($this->managementAssignmentState($award)['persisted_mode'] === 'single') {
            throw ValidationException::withMessages(['assignment' => 'Đang ở chế độ một User. Hãy gỡ toàn bộ phân công trước khi đổi cách phân công.']);
        }
        if (! $this->hospital($award, $partnerId)) {
            throw ValidationException::withMessages(['hospital_id' => 'Bệnh viện không thuộc phạm vi phân bổ hiện tại.']);
        }

        $availableIds = $this->unassignedHospitalProducts($award, $partnerId)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $selectedIds = array_values(array_intersect($availableIds, array_unique(array_map('intval', $awardIds))));
        if ($selectedIds === []) {
            throw ValidationException::withMessages(['award_ids' => 'Các sản phẩm đã chọn không còn khả dụng để phân công tại bệnh viện này.']);
        }
        if (count($selectedIds) !== count(array_unique(array_map('intval', $awardIds)))) {
            throw ValidationException::withMessages(['award_ids' => 'Có sản phẩm đã được User khác phụ trách hoặc không được phân bổ tại bệnh viện này.']);
        }

        $this->commercialPolicies->assignManagers($award, $selectedIds, $partnerId, $userId, $actorId);
        return count($selectedIds);
    }

    public function assignManagerToProducts(DrugBidAward $award, array $awardIds, int $userId, ?int $actorId): int
    {
        if (! User::query()->whereKey($userId)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['user_id' => 'User quản lý không hoạt động hoặc không tồn tại.']);
        }
        if (! $this->commercialPolicyReady($award)) {
            throw ValidationException::withMessages(['assignment' => 'Hãy hoàn tất chính sách kinh doanh trước khi phân công User quản lý.']);
        }
        $state = $this->managementAssignmentState($award);
        if ($state['persisted_mode'] === 'single') {
            throw ValidationException::withMessages(['assignment' => 'Đang ở chế độ một User. Hãy gỡ toàn bộ phân công trước khi đổi cách phân công.']);
        }
        return $this->commercialPolicies->assignManagerToProductAllocations($award, $awardIds, $userId, $actorId);
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
