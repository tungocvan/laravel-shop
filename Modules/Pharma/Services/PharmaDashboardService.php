<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\Log;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\DrugBidAwardManagementAssignment;
use Modules\Pharma\Models\DrugBidAwardProductPolicy;
use Modules\Pharma\Models\InventoryBalance;
use Modules\Pharma\Models\InventoryIssue;
use Modules\Pharma\Models\InventoryIssueCommission;
use Modules\Pharma\Models\InventoryIssueDeferredSupply;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\MedicineProfile;
use Modules\Pharma\Models\PriceList;
use Modules\Pharma\Models\SupplierTracking;
use Throwable;

final class PharmaDashboardService
{
    public function forUser(mixed $user): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'capabilities' => $this->capabilities($user),
            'master_data' => $this->masterDataSummary(),
            'inventory' => $this->inventorySummary(),
            'commercial' => $this->commercialSummary(),
            'sales' => $this->salesSummary(),
            'attention' => $this->attentionSummary(),
            'price_lists' => $this->priceListSummary(),
        ];
    }

    private function capabilities(mixed $user): array
    {
        return [
            'view' => $this->can($user, 'view_pharma'),
            'create' => $this->can($user, 'create_pharma'),
            'edit' => $this->can($user, 'edit_pharma'),
            'delete' => $this->can($user, 'delete_pharma'),
            'official_facilities' => $this->can($user, 'view_pharma_official_facilities'),
            'allocations' => $this->can($user, 'view_pharma_allocations'),
            'commercial_policies' => $this->can($user, 'view_pharma_commercial_policies'),
            'approve_issue' => $this->can($user, 'approve_pharma_inventory_issue'),
        ];
    }

    private function masterDataSummary(): array
    {
        return $this->section('master_data', function (): array {
            return [
                'available' => true,
                'medicines' => Medicine::query()->count(),
                'hssp_current' => MedicineProfile::query()->where('is_current', true)->count(),
                'hssp_attention' => MedicineProfile::query()->where('is_current', true)
                    ->whereIn('profile_status', [MedicineProfile::STATUS_NEEDS_REVIEW, MedicineProfile::STATUS_EXPIRED, MedicineProfile::STATUS_INCOMPLETE])->count(),
                'bid_awards' => DrugBidAward::query()->count(),
                'bid_unlinked' => DrugBidAward::query()->whereNull('medicine_id')->count(),
                'supplier_trackings' => SupplierTracking::query()->count(),
            ];
        });
    }

    private function inventorySummary(): array
    {
        return $this->section('inventory', function (): array {
            return [
                'available' => true,
                'stock_quantity' => (float) InventoryBalance::query()->where('quantity_on_hand', '>', 0)->sum('quantity_on_hand'),
                'stock_lots' => InventoryBalance::query()->where('quantity_on_hand', '>', 0)->count(),
                'expiring_lots' => InventoryBalance::query()->where('quantity_on_hand', '>', 0)
                    ->whereNotNull('expiry_date')->whereDate('expiry_date', '<=', now()->addDays(90))->count(),
                'draft_issues' => InventoryIssue::query()->where('status', InventoryIssue::DRAFT)->count(),
                'deferred_supply' => InventoryIssueDeferredSupply::query()->where('status', InventoryIssueDeferredSupply::PENDING)->count(),
            ];
        });
    }

    private function commercialSummary(): array
    {
        return $this->section('commercial', function (): array {
            $activeAssignments = DrugBidAwardManagementAssignment::query()
                ->where('status', DrugBidAwardManagementAssignment::STATUS_ACTIVE);

            return [
                'available' => true,
                'active_assignments' => (clone $activeAssignments)->count(),
                'managed_hospitals' => (clone $activeAssignments)->distinct()->count('partner_id'),
                'managed_users' => (clone $activeAssignments)->distinct()->count('user_id'),
                'product_policies' => DrugBidAwardProductPolicy::query()->whereNotNull('commission_percentage')->count(),
                'draft_price_lists' => PriceList::query()->where('status', PriceList::STATUS_DRAFT)->count(),
            ];
        });
    }

    private function salesSummary(): array
    {
        return $this->section('sales', function (): array {
            $from = now()->startOfMonth();
            $to = now()->endOfMonth();
            $commissions = InventoryIssueCommission::query()->whereBetween('calculated_at', [$from, $to]);

            return [
                'available' => true,
                'period_from' => $from->toDateString(),
                'period_to' => $to->toDateString(),
                'revenue' => (float) (clone $commissions)->sum('revenue_amount'),
                'commission' => (float) (clone $commissions)->sum('commission_amount'),
                'unresolved_commissions' => (clone $commissions)->where('status', InventoryIssueCommission::STATUS_UNRESOLVED)->count(),
                'posted_bid_issues' => InventoryIssue::query()->where('issue_source', 'bid')
                    ->where('status', InventoryIssue::POSTED)->whereBetween('posted_at', [$from, $to])->count(),
            ];
        });
    }

    private function attentionSummary(): array
    {
        return $this->section('attention', function (): array {
            return [
                'available' => true,
                'bid_unlinked' => DrugBidAward::query()->whereNull('medicine_id')->count(),
                'hssp_attention' => MedicineProfile::query()->where('is_current', true)
                    ->whereIn('profile_status', [MedicineProfile::STATUS_NEEDS_REVIEW, MedicineProfile::STATUS_EXPIRED, MedicineProfile::STATUS_INCOMPLETE])->count(),
                'expiring_lots' => InventoryBalance::query()->where('quantity_on_hand', '>', 0)
                    ->whereNotNull('expiry_date')->whereDate('expiry_date', '<=', now()->addDays(90))->count(),
                'deferred_supply' => InventoryIssueDeferredSupply::query()->where('status', InventoryIssueDeferredSupply::PENDING)->count(),
                'unresolved_commissions' => InventoryIssueCommission::query()->where('status', InventoryIssueCommission::STATUS_UNRESOLVED)->count(),
                'draft_price_lists' => PriceList::query()->where('status', PriceList::STATUS_DRAFT)->count(),
            ];
        });
    }

    private function priceListSummary(): array
    {
        return $this->section('price_lists', fn (): array => [
            'available' => true,
            'total' => PriceList::query()->count(),
            'active' => PriceList::query()->where('status', PriceList::STATUS_ACTIVE)->count(),
            'draft' => PriceList::query()->where('status', PriceList::STATUS_DRAFT)->count(),
            'customer' => PriceList::query()->where('type', PriceList::TYPE_CUSTOMER)->count(),
            'global' => PriceList::query()->where('type', PriceList::TYPE_GLOBAL)->count(),
        ]);
    }

    private function section(string $section, callable $resolver): array
    {
        try {
            return $resolver();
        } catch (Throwable $exception) {
            Log::warning('Pharma Dashboard section is unavailable.', [
                'section' => $section,
                'exception_class' => $exception::class,
            ]);

            return ['available' => false];
        }
    }

    private function can(mixed $user, string $permission): bool
    {
        try {
            return method_exists($user, 'can') && $user->can($permission);
        } catch (Throwable) {
            return false;
        }
    }
}
