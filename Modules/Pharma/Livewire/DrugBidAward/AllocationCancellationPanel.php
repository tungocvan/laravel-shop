<?php

namespace Modules\Pharma\Livewire\DrugBidAward;

use Livewire\Component;
use Modules\Pharma\Models\DrugBidAwardAllocation;
use Modules\Pharma\Models\DrugBidAwardContract;
use Modules\Pharma\Services\DrugBidAwardAllocationService;
use Modules\Pharma\Services\DrugBidAwardContractService;

class AllocationCancellationPanel extends Component
{
    public int $awardId;
    public string $allocationId = '';
    public string $allocationReason = '';
    public string $contractId = '';
    public string $contractReason = '';

    public function mount(int $awardId): void
    {
        $this->awardId = $awardId;
        abort_unless(auth('admin')->check(), 403);
    }

    public function cancelAllocation(DrugBidAwardAllocationService $service): void
    {
        $this->authorizePermission('cancel_pharma_allocations');
        $data = $this->validate(['allocationId' => ['required', 'integer'], 'allocationReason' => ['required', 'string', 'min:3', 'max:1000']]);
        $service->cancel($this->awardId, (int) $data['allocationId'], $data['allocationReason'], auth('admin')->id());
        $this->reset(['allocationId', 'allocationReason']);
        session()->flash('success', 'Đã hủy phân bổ có audit reason.');
    }

    public function cancelContract(DrugBidAwardContractService $service): void
    {
        $this->authorizePermission('cancel_pharma_contracts');
        $data = $this->validate(['contractId' => ['required', 'integer'], 'contractReason' => ['required', 'string', 'min:3', 'max:1000']]);
        $contract = DrugBidAwardContract::query()->with('allocation')->findOrFail((int) $data['contractId']);
        abort_unless((int) $contract->allocation?->drug_bid_award_id === $this->awardId, 404);
        $service->cancel($contract->drug_bid_award_allocation_id, $contract->id, $data['contractReason'], auth('admin')->id());
        $this->reset(['contractId', 'contractReason']);
        session()->flash('success', 'Đã hủy hợp đồng có audit reason.');
    }

    public function render()
    {
        return view('Pharma::livewire.drug-bid-award.allocation-cancellation-panel', [
            'allocations' => DrugBidAwardAllocation::query()->with('partner')->where('drug_bid_award_id', $this->awardId)->where('status', DrugBidAwardAllocation::STATUS_ACTIVE)->latest('id')->limit(100)->get(),
            'contracts' => DrugBidAwardContract::query()->with('allocation.partner')->whereHas('allocation', fn ($query) => $query->where('drug_bid_award_id', $this->awardId))->where('status', '!=', DrugBidAwardContract::STATUS_CANCELLED)->latest('id')->limit(100)->get(),
        ]);
    }

    private function authorizePermission(string $permission): void
    {
        abort_unless(auth('admin')->user()?->can($permission), 403);
    }
}
