<?php

namespace Modules\Muasamcong\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Modules\Muasamcong\Models\ContractorManualLot;
use Modules\Muasamcong\Services\SmartPricingAwardService;
use Throwable;

class SmartPricingVerifiedLots extends Component
{
    public string $notifyNo = '';

    public string $contractorCode = '';

    public string $contractorName = '';

    public ?string $notice = null;

    public ?string $error = null;

    public bool $checked = false;

    public function mount(string $notifyNo, string $contractorCode, string $contractorName = ''): void
    {
        $this->notifyNo = trim($notifyNo);
        $this->contractorCode = trim($contractorCode);
        $this->contractorName = trim($contractorName);
    }

    public function syncIfNeeded(SmartPricingAwardService $service): void
    {
        if ($this->checked) {
            return;
        }

        $this->checked = true;

        if ($this->savedQuery()->exists()) {
            return;
        }

        $this->sync($service);
    }

    public function sync(SmartPricingAwardService $service): void
    {
        $this->notice = null;
        $this->error = null;

        if ($this->notifyNo === '' || $this->contractorCode === '') {
            $this->error = 'Thiếu TBMT hoặc mã nhà thầu để đối chiếu Smart Pricing.';

            return;
        }

        try {
            $result = $service->forContractor($this->notifyNo, $this->contractorCode);
            $items = is_array($result['items'] ?? null) ? $result['items'] : [];
            $keys = [];

            DB::transaction(function () use ($items, &$keys): void {
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
                        'contractor_code' => $this->contractorCode,
                        'notify_no' => $this->notifyNo,
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

                $stale = $this->savedQuery();
                if ($keys === []) {
                    $stale->delete();
                } else {
                    $stale->whereNotIn('lot_key', $keys)->delete();
                }
            });

            $count = count($keys);
            $this->notice = $count > 0
                ? 'Smart Pricing đã xác minh tự động '.$count.' thuốc cho nhà thầu này.'
                : 'Smart Pricing chưa tìm thấy thuốc có winningCode khớp nhà thầu này.';

            if (! empty($result['truncated'])) {
                $this->notice .= ' Kết quả nguồn vượt giới hạn số trang cấu hình.';
            }
        } catch (Throwable $e) {
            report($e);
            $this->error = 'Không thể đồng bộ danh mục tự xác minh từ Smart Pricing.';
        }
    }

    public function render(): View
    {
        $rows = $this->savedQuery()->orderBy('medicine_name')->get();
        $preview = $rows->take(20);

        return view('Muasamcong::livewire.smart-pricing-verified-lots', [
            'rows' => $preview,
            'total' => $rows->count(),
            'quantity' => (float) $rows->sum('quantity'),
            'amount' => (float) $rows->sum('plan_amount'),
        ]);
    }

    private function savedQuery()
    {
        return ContractorManualLot::query()
            ->where('notify_no', $this->notifyNo)
            ->where('contractor_code', $this->contractorCode)
            ->where('source', 'smart_pricing_verified');
    }

    private function numeric(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
