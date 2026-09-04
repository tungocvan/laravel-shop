<?php

namespace Modules\Muasamcong\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Modules\Muasamcong\Models\ContractorManualLot;
use Modules\Muasamcong\Services\ContractorAwardEnrichmentService;
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

    public function syncIfNeeded(ContractorAwardEnrichmentService $service): void
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

    public function sync(ContractorAwardEnrichmentService $service): void
    {
        $this->notice = null;
        $this->error = null;

        if ($this->notifyNo === '' || $this->contractorCode === '') {
            $this->error = 'Thiếu TBMT hoặc mã nhà thầu để đối chiếu Smart Pricing.';

            return;
        }

        try {
            $result = $service->sync($this->notifyNo, $this->contractorCode);
            $count = (int) ($result['count'] ?? 0);
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
}
