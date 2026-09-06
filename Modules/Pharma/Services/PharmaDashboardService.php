<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\Log;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\Medicine;
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
            ],
            'price_list' => $this->priceListReadiness(),
        ];
    }

    private function count(string $modelClass, string $section): array
    {
        try {
            return [
                'available' => true,
                'count' => $modelClass::query()->count(),
            ];
        } catch (Throwable $exception) {
            Log::warning('Pharma Dashboard metric is unavailable.', [
                'section' => $section,
                'exception_class' => $exception::class,
            ]);

            return [
                'available' => false,
                'count' => 0,
            ];
        }
    }

    private function priceListReadiness(): array
    {
        $path = storage_path('app/'.PriceListService::DEFAULT_SOURCE);

        return [
            'source' => basename(PriceListService::DEFAULT_SOURCE),
            'ready' => is_file($path) && is_readable($path),
        ];
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
