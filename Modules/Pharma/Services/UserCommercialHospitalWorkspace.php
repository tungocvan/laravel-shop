<?php

namespace Modules\Pharma\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Modules\Pharma\Contracts\PriceResolver;
use Modules\Partner\Models\Partner;
use Modules\Pharma\Models\DrugBidAwardAllocation;
use Modules\Pharma\Models\DrugBidAwardManagementAssignment;
use Modules\Pharma\Models\MedicineVariant;
use Modules\Pharma\Models\SupplierTracking;

final class UserCommercialHospitalWorkspace
{
    public function __construct(private readonly PriceResolver $priceResolver) {}

    public function assignedUsers(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('pharma_drug_bid_award_management_assignments as workspace_assignments')
                ->join('pharma_drug_bid_award_allocations as workspace_allocations', function ($join): void {
                    $join->on('workspace_allocations.drug_bid_award_id', '=', 'workspace_assignments.drug_bid_award_id')
                        ->on('workspace_allocations.partner_id', '=', 'workspace_assignments.partner_id');
                })
                ->whereColumn('workspace_assignments.user_id', 'users.id')
                ->where('workspace_assignments.status', DrugBidAwardManagementAssignment::STATUS_ACTIVE)
                ->where('workspace_allocations.status', DrugBidAwardAllocation::STATUS_ACTIVE))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    public function assignedAwardScopes(int $userId): Collection
    {
        return DB::table('pharma_drug_bid_award_management_assignments as workspace_assignments')
            ->join('pharma_drug_bid_award_allocations as workspace_allocations', function ($join): void {
                $join->on('workspace_allocations.drug_bid_award_id', '=', 'workspace_assignments.drug_bid_award_id')
                    ->on('workspace_allocations.partner_id', '=', 'workspace_assignments.partner_id');
            })
            ->join('pharma_drug_bid_awards as awards', 'awards.id', '=', 'workspace_assignments.drug_bid_award_id')
            ->where('workspace_assignments.user_id', $userId)
            ->where('workspace_assignments.status', DrugBidAwardManagementAssignment::STATUS_ACTIVE)
            ->where('workspace_allocations.status', DrugBidAwardAllocation::STATUS_ACTIVE)
            ->select('awards.id', 'awards.investor_name', 'awards.bidding_notice_code', 'awards.decision_number')
            ->distinct()
            ->get()
            ->groupBy(fn ($award) => $award->bidding_notice_code
                ? 'tbmt:'.$award->bidding_notice_code
                : ($award->decision_number ? 'decision:'.$award->decision_number : 'award:'.$award->id))
            ->map(function (Collection $awards, string $identity) {
                $award = $awards->first();
                [$type, $value] = explode(':', $identity, 2);

                return (object) [
                    'scope_key' => sha1($identity),
                    'type' => $type,
                    'value' => $value,
                    'investor_name' => $award->investor_name,
                    'bidding_notice_code' => $award->bidding_notice_code,
                    'decision_number' => $award->decision_number,
                    'products_count' => $awards->count(),
                ];
            })
            ->sortBy(fn ($scope) => mb_strtolower((string) ($scope->investor_name ?: $scope->bidding_notice_code ?: $scope->decision_number)))
            ->values();
    }

    public function browseHospitals(
        int $userId,
        ?string $search = null,
        int $perPage = 25,
        int $page = 1,
        ?object $awardScope = null,
    ): LengthAwarePaginator {
        $search = trim((string) $search);
        $perPage = in_array($perPage, [25, 50, 100], true) ? $perPage : 25;

        return $this->hospitalQuery($userId, $awardScope)
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $nested) use ($search): void {
                $nested->where('partners.name', 'like', "%{$search}%")
                    ->orWhere('partners.address', 'like', "%{$search}%")
                    ->orWhere('partners.province_code', 'like', "%{$search}%");
            }))
            ->select('partners.*')
            ->selectSub($this->assignedProductCountQuery($userId, $awardScope), 'assigned_products_count')
            ->selectSub($this->allocatedAwardValueQuery($userId, $awardScope), 'allocated_award_value')
            ->orderBy('partners.name')
            ->paginate($perPage, ['*'], 'page', max(1, $page));
    }

    /** @return array{hospitals:int,products:int} */
    /** @return array{hospitals:int,products:int,allocated_value:float} */
    public function summary(int $userId, ?object $awardScope = null): array
    {
        if ($awardScope === null) {
            return ['hospitals' => 0, 'products' => 0, 'allocated_value' => 0.0];
        }

        $rows = $this->assignedAwardQueryForUser($userId, $awardScope);

        return [
            'hospitals' => $this->hospitalQuery($userId, $awardScope)->count(),
            'products' => (clone $rows)->distinct()->count('awards.id'),
            'allocated_value' => (float) (clone $rows)
                ->selectRaw('SUM(workspace_allocations.allocated_quantity * COALESCE(awards.winning_price, awards.unit_price, 0)) as total')
                ->value('total'),
        ];
    }

    public function findHospital(int $userId, int $partnerId, ?object $awardScope = null): ?Partner
    {
        return $this->hospitalQuery($userId, $awardScope)
            ->select('partners.*')
            ->selectSub($this->assignedProductCountQuery($userId, $awardScope), 'assigned_products_count')
            ->selectSub($this->allocatedAwardValueQuery($userId, $awardScope), 'allocated_award_value')
            ->find($partnerId);
    }

    public function assignedProducts(
        int $userId,
        int $partnerId,
        ?string $search = null,
        int $perPage = 25,
        int $page = 1,
        bool $includeSupplierPricing = false,
        ?object $awardScope = null,
    ): LengthAwarePaginator {
        $search = trim((string) $search);
        $perPage = in_array($perPage, [25, 50, 100], true) ? $perPage : 25;

        return DB::table('pharma_drug_bid_award_management_assignments as workspace_assignments')
            ->join('pharma_drug_bid_award_allocations as workspace_allocations', function ($join): void {
                $join->on('workspace_allocations.drug_bid_award_id', '=', 'workspace_assignments.drug_bid_award_id')
                    ->on('workspace_allocations.partner_id', '=', 'workspace_assignments.partner_id');
            })
            ->join('pharma_drug_bid_awards as awards', 'awards.id', '=', 'workspace_assignments.drug_bid_award_id')
            ->leftJoin('pharma_drug_bid_award_product_policies as product_policies', 'product_policies.drug_bid_award_id', '=', 'awards.id')
            ->where('workspace_assignments.user_id', $userId)
            ->where('workspace_assignments.partner_id', $partnerId)
            ->where('workspace_assignments.status', DrugBidAwardManagementAssignment::STATUS_ACTIVE)
            ->where('workspace_allocations.status', DrugBidAwardAllocation::STATUS_ACTIVE)
            ->when($awardScope !== null, fn ($query) => $this->applyAwardScope($query, $awardScope))
            ->when($search !== '', fn ($query) => $query->where(function ($nested) use ($search): void {
                $nested->where('awards.medicine_name', 'like', "%{$search}%")
                    ->orWhere('awards.active_ingredient', 'like', "%{$search}%")
                    ->orWhere('awards.registration_or_import_license', 'like', "%{$search}%");
            }))
            ->select([
                'awards.id',
                'awards.medicine_id',
                'awards.medicine_name',
                'awards.active_ingredient',
                'awards.concentration',
                'awards.unit',
                'awards.packaging_specification',
                'awards.winning_price',
                'awards.unit_price',
                'awards.quantity as winning_quantity',
                'awards.bidding_notice_code',
                'awards.decision_number',
                'awards.decision_date',
                'workspace_allocations.allocated_quantity',
                'workspace_allocations.effective_from',
                'workspace_allocations.effective_until',
                'workspace_allocations.commercial_policy_percentage as hospital_policy_percentage',
                'product_policies.commission_percentage as product_policy_percentage',
            ])
            ->selectRaw('COALESCE(workspace_allocations.commercial_policy_percentage, product_policies.commission_percentage) as effective_policy_percentage')
            ->orderBy('awards.medicine_name')
            ->orderBy('awards.id')
            ->paginate($perPage, ['*'], 'page', max(1, $page))
            ->through(function ($product) use ($userId, $partnerId, $includeSupplierPricing) {
                $context = $this->commercialContext($userId, $partnerId, (int) $product->id, $includeSupplierPricing);
                $product->sale_price = $context['sale_price'];
                $product->supplier = $context['supplier'];

                return $product;
            });
    }

    /**
     * @return array{sale_price:?array,supplier:?array}
     */
    public function commercialContext(int $userId, int $partnerId, int $awardId, bool $includeSupplierPricing = false): array
    {
        $award = $this->assignedAwardQuery($userId, $partnerId)
            ->where('awards.id', $awardId)
            ->select('awards.id', 'awards.medicine_id')
            ->first();

        if ($award === null || $award->medicine_id === null) {
            return ['sale_price' => null, 'supplier' => null];
        }

        $medicineId = (int) $award->medicine_id;

        return [
            'sale_price' => $this->resolveUnambiguousSalePrice($medicineId, $partnerId),
            'supplier' => $this->resolveCurrentSupplier($medicineId, $includeSupplierPricing),
        ];
    }

    private function assignedAwardQuery(int $userId, int $partnerId)
    {
        return DB::table('pharma_drug_bid_award_management_assignments as workspace_assignments')
            ->join('pharma_drug_bid_award_allocations as workspace_allocations', function ($join): void {
                $join->on('workspace_allocations.drug_bid_award_id', '=', 'workspace_assignments.drug_bid_award_id')
                    ->on('workspace_allocations.partner_id', '=', 'workspace_assignments.partner_id');
            })
            ->join('pharma_drug_bid_awards as awards', 'awards.id', '=', 'workspace_assignments.drug_bid_award_id')
            ->where('workspace_assignments.user_id', $userId)
            ->where('workspace_assignments.partner_id', $partnerId)
            ->where('workspace_assignments.status', DrugBidAwardManagementAssignment::STATUS_ACTIVE)
            ->where('workspace_allocations.status', DrugBidAwardAllocation::STATUS_ACTIVE);
    }

    private function resolveUnambiguousSalePrice(int $medicineId, int $partnerId): ?array
    {
        $variants = MedicineVariant::query()
            ->where('medicine_id', $medicineId)
            ->where('status', 'active')
            ->with(['packages' => fn ($query) => $query->where('is_orderable', true)->orderBy('id')])
            ->orderBy('id')
            ->get();

        if ($variants->count() !== 1) {
            return null;
        }

        $variant = $variants->first();
        $packages = $variant->packages;

        if ($packages->count() > 1) {
            return null;
        }

        $packageId = $packages->count() === 1 ? (int) $packages->first()->id : null;
        $resolved = $this->priceResolver->resolve((int) $variant->id, $packageId, $partnerId);

        if ($resolved === null) {
            return null;
        }

        return [
            'company_sale_price' => $resolved->companySalePrice,
            'actual_receivable_price' => $resolved->actualReceivablePrice,
            'invoice_price' => $resolved->invoicePrice,
            'currency' => $resolved->currency,
            'source_type' => $resolved->sourceType,
            'effective_from' => $resolved->effectiveFrom,
            'effective_to' => $resolved->effectiveTo,
        ];
    }

    private function resolveCurrentSupplier(int $medicineId, bool $includePricing): ?array
    {
        $today = now()->toDateString();
        $tracking = SupplierTracking::query()
            ->with('partner:id,name')
            ->where('medicine_id', $medicineId)
            ->where('status', 'active')
            ->where(function (Builder $query) use ($today): void {
                $query->whereNull('start_date')->orWhereDate('start_date', '<=', $today);
            })
            ->where(function (Builder $query) use ($today): void {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
            })
            ->orderByDesc('working_date')
            ->orderByDesc('id')
            ->first();

        if ($tracking === null) {
            return null;
        }

        return [
            'supplier_name' => $tracking->partner?->name ?: $tracking->supplier_name,
            'import_price' => $includePricing ? $tracking->import_price : null,
            'invoice_price' => $includePricing ? $tracking->invoice_price : null,
            'cost_price' => $includePricing ? $tracking->cost_price : null,
            'pricing_visible' => $includePricing,
            'working_date' => $tracking->working_date?->toDateString(),
            'effective_from' => $tracking->start_date?->toDateString(),
            'effective_to' => $tracking->end_date?->toDateString(),
        ];
    }

    private function hospitalQuery(int $userId, ?object $awardScope = null): Builder
    {
        return Partner::query()
            ->whereExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('pharma_drug_bid_award_management_assignments as workspace_assignments')
                ->join('pharma_drug_bid_award_allocations as workspace_allocations', function ($join): void {
                    $join->on('workspace_allocations.drug_bid_award_id', '=', 'workspace_assignments.drug_bid_award_id')
                        ->on('workspace_allocations.partner_id', '=', 'workspace_assignments.partner_id');
                })
                ->join('pharma_drug_bid_awards as awards', 'awards.id', '=', 'workspace_assignments.drug_bid_award_id')
                ->whereColumn('workspace_assignments.partner_id', 'partners.id')
                ->where('workspace_assignments.user_id', $userId)
                ->where('workspace_assignments.status', DrugBidAwardManagementAssignment::STATUS_ACTIVE)
                ->where('workspace_allocations.status', DrugBidAwardAllocation::STATUS_ACTIVE)
                ->when($awardScope !== null, fn ($query) => $this->applyAwardScope($query, $awardScope)));
    }

    private function assignedAwardQueryForUser(int $userId, object $awardScope)
    {
        return DB::table('pharma_drug_bid_award_management_assignments as workspace_assignments')
            ->join('pharma_drug_bid_award_allocations as workspace_allocations', function ($join): void {
                $join->on('workspace_allocations.drug_bid_award_id', '=', 'workspace_assignments.drug_bid_award_id')
                    ->on('workspace_allocations.partner_id', '=', 'workspace_assignments.partner_id');
            })
            ->join('pharma_drug_bid_awards as awards', 'awards.id', '=', 'workspace_assignments.drug_bid_award_id')
            ->where('workspace_assignments.user_id', $userId)
            ->where('workspace_assignments.status', DrugBidAwardManagementAssignment::STATUS_ACTIVE)
            ->where('workspace_allocations.status', DrugBidAwardAllocation::STATUS_ACTIVE)
            ->tap(fn ($query) => $this->applyAwardScope($query, $awardScope));
    }

    private function assignedProductCountQuery(int $userId, ?object $awardScope = null)
    {
        return DB::table('pharma_drug_bid_award_management_assignments as product_assignments')
            ->join('pharma_drug_bid_award_allocations as product_allocations', function ($join): void {
                $join->on('product_allocations.drug_bid_award_id', '=', 'product_assignments.drug_bid_award_id')
                    ->on('product_allocations.partner_id', '=', 'product_assignments.partner_id');
            })
            ->join('pharma_drug_bid_awards as awards', 'awards.id', '=', 'product_assignments.drug_bid_award_id')
            ->whereColumn('product_assignments.partner_id', 'partners.id')
            ->where('product_assignments.user_id', $userId)
            ->where('product_assignments.status', DrugBidAwardManagementAssignment::STATUS_ACTIVE)
            ->where('product_allocations.status', DrugBidAwardAllocation::STATUS_ACTIVE)
            ->when($awardScope !== null, fn ($query) => $this->applyAwardScope($query, $awardScope))
            ->selectRaw('COUNT(DISTINCT product_assignments.drug_bid_award_id)');
    }

    private function allocatedAwardValueQuery(int $userId, ?object $awardScope = null)
    {
        return DB::table('pharma_drug_bid_award_management_assignments as value_assignments')
            ->join('pharma_drug_bid_award_allocations as value_allocations', function ($join): void {
                $join->on('value_allocations.drug_bid_award_id', '=', 'value_assignments.drug_bid_award_id')
                    ->on('value_allocations.partner_id', '=', 'value_assignments.partner_id');
            })
            ->join('pharma_drug_bid_awards as awards', 'awards.id', '=', 'value_assignments.drug_bid_award_id')
            ->whereColumn('value_assignments.partner_id', 'partners.id')
            ->where('value_assignments.user_id', $userId)
            ->where('value_assignments.status', DrugBidAwardManagementAssignment::STATUS_ACTIVE)
            ->where('value_allocations.status', DrugBidAwardAllocation::STATUS_ACTIVE)
            ->when($awardScope !== null, fn ($query) => $this->applyAwardScope($query, $awardScope))
            ->selectRaw('COALESCE(SUM(value_allocations.allocated_quantity * COALESCE(awards.winning_price, awards.unit_price, 0)), 0)');
    }

    private function applyAwardScope($query, object $awardScope): void
    {
        match ($awardScope->type) {
            'tbmt' => $query->where('awards.bidding_notice_code', $awardScope->value),
            'decision' => $query->where('awards.decision_number', $awardScope->value),
            default => $query->where('awards.id', (int) $awardScope->value),
        };
    }
}
