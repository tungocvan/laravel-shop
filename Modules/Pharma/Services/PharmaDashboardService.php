<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\Log;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\PriceList;
use Modules\Pharma\Models\SupplierTracking;
use Throwable;

final class PharmaDashboardService
{
    public function forUser(mixed $user): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'capabilities' => [
                'view' => $this->can($user, 'view_pharma'),
                'create' => $this->can($user, 'create_pharma'),
                'edit' => $this->can($user, 'edit_pharma'),
                'delete' => $this->can($user, 'delete_pharma'),
                'official_facilities' => $this->can($user, 'view_pharma_official_facilities'),
            ],
            'metrics' => [
                'medicines' => $this->count(Medicine::class, 'medicines'),
                'drug_bid_awards' => $this->count(DrugBidAward::class, 'drug_bid_awards'),
                'supplier_trackings' => $this->count(SupplierTracking::class, 'supplier_trackings'),
                'price_lists' => $this->count(PriceList::class, 'price_lists'),
            ],
            'price_lists' => $this->priceListSummary(),
        ];
    }

    private function count(string $modelClass, string $section): array
    {
        try {
            return ['available' => true, 'count' => $modelClass::query()->count()];
        } catch (Throwable $exception) {
            Log::warning('Pharma Dashboard metric is unavailable.', [
                'section' => $section,
                'exception_class' => $exception::class,
            ]);

            return ['available' => false, 'count' => 0];
        }
    }

    private function priceListSummary(): array
    {
        try {
            return [
                'available' => true,
                'active' => PriceList::query()->where('status', PriceList::STATUS_ACTIVE)->count(),
                'draft' => PriceList::query()->where('status', PriceList::STATUS_DRAFT)->count(),
                'customer' => PriceList::query()->where('type', PriceList::TYPE_CUSTOMER)->count(),
                'global' => PriceList::query()->where('type', PriceList::TYPE_GLOBAL)->count(),
            ];
        } catch (Throwable $exception) {
            Log::warning('Pharma Dashboard price-list summary is unavailable.', [
                'exception_class' => $exception::class,
            ]);

            return ['available' => false, 'active' => 0, 'draft' => 0, 'customer' => 0, 'global' => 0];
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
