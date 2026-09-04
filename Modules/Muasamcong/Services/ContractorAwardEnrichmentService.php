<?php

namespace Modules\Muasamcong\Services;

use Illuminate\Support\Facades\DB;
use Modules\Muasamcong\Models\ContractorManualLot;

class ContractorAwardEnrichmentService
{
    public function __construct(private readonly SmartPricingAwardService $smartPricing) {}

    /**
     * Persist every Smart Pricing award row matched to one TBMT + contractor.
     *
     * Export remains DB-only: this service is invoked explicitly during sync,
     * never while generating a workbook.
     */
    public function sync(string $notifyNo, string $contractorCode): array
    {
        $notifyNo = trim($notifyNo);
        $contractorCode = mb_strtolower(trim($contractorCode));

        $result = $this->smartPricing->forContractor($notifyNo, $contractorCode);
        $items = is_array($result['items'] ?? null) ? $result['items'] : [];
        $keys = [];

        DB::transaction(function () use ($notifyNo, $contractorCode, $items, &$keys): void {
            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $sourceKey = trim((string) ($item['source_key'] ?? ''));
                if ($sourceKey === '') {
                    continue;
                }

                $lotKey = 'smart:'.$sourceKey;
                $keys[] = $lotKey;
                $quantity = $this->numeric($item['quantity'] ?? null);
                $unitPrice = $this->numeric($item['winning_unit_price'] ?? null);

                ContractorManualLot::query()->updateOrCreate([
                    'contractor_code' => $contractorCode,
                    'notify_no' => $notifyNo,
                    'lot_key' => $lotKey,
                ], [
                    'lot_no' => null,
                    'lot_name' => $item['medicine_name'] ?? $item['active_ingredient'] ?? null,
                    'medicine_name' => $item['medicine_name'] ?? null,
                    'active_ingredient' => $item['active_ingredient'] ?? null,
                    'quantity' => $quantity,
                    'price_plan' => null,
                    'lot_price' => $unitPrice,
                    'plan_amount' => $quantity !== null && $unitPrice !== null ? $quantity * $unitPrice : null,
                    'source' => 'smart_pricing_verified',
                    'confirmed_by' => null,
                    'confirmed_at' => now(),
                    'raw_payload' => $item,
                ]);
            }

            $stale = ContractorManualLot::query()
                ->where('notify_no', $notifyNo)
                ->where('contractor_code', $contractorCode)
                ->where('source', 'smart_pricing_verified');

            if ($keys === []) {
                $stale->delete();
            } else {
                $stale->whereNotIn('lot_key', $keys)->delete();
            }
        });

        return [
            'count' => count($keys),
            'keys' => $keys,
            'total_source' => (int) ($result['total_source'] ?? 0),
            'pages_fetched' => (int) ($result['pages_fetched'] ?? 0),
            'total_pages' => (int) ($result['total_pages'] ?? 0),
            'truncated' => (bool) ($result['truncated'] ?? false),
        ];
    }

    private function numeric(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
