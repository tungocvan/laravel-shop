<?php

namespace Modules\Pharma\Livewire\DrugBidAward;

use App\Models\User;
use Livewire\Component;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\DrugBidAwardAllocation;
use Modules\Pharma\Models\DrugBidAwardManagementAssignment;
use Modules\Pharma\Models\DrugBidAwardProductPolicy;
use Modules\Pharma\Services\DrugBidAwardCommercialPolicyService;
use Modules\Pharma\Services\DrugBidAwardResultGroupService;

class CommercialPolicyWorkspace extends Component
{
    public int $awardId;
    public array $productPolicies = [];
    public string $bulkPercentage = '';
    public array $selectedPolicyAwardIds = [];
    public string $selectedPartnerId = '';
    public string $selectedUserId = '';
    public string $userSearch = '';
    public string $productSearch = '';

    public function mount(int $awardId): void
    {
        abort_unless(auth('admin')->user()?->can('view_pharma_commercial_policies'), 403);
        $this->awardId = $awardId;
        $this->award();
        $this->loadPolicyValues();
    }

    public function applyBulkPercentage(): void
    {
        $this->authorizeManage();
        $this->validate([
            'bulkPercentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'selectedPolicyAwardIds' => ['required', 'array', 'min:1'],
        ]);
        foreach ($this->selectedPolicyAwardIds as $id) $this->productPolicies[(int) $id] = $this->bulkPercentage;
    }

    public function saveProductPolicies(DrugBidAwardCommercialPolicyService $service): void
    {
        $this->authorizeManage();
        foreach ($this->productPolicies as $id => $value) {
            if ($value === '' || $value === null) continue;
            if (! is_numeric($value) || (float) $value < 0 || (float) $value > 100) {
                $this->addError("productPolicies.$id", 'Chính sách % phải từ 0 đến 100.');
                return;
            }
        }
        $service->saveProductPolicies($this->award(), $this->productPolicies, auth('admin')->id());
        $this->loadPolicyValues();
        session()->flash('success', 'Đã lưu chính sách % theo sản phẩm.');
    }

    public function assignManager(int $awardId, DrugBidAwardCommercialPolicyService $service): void
    {
        $this->authorizeManage();
        $data = $this->validate([
            'selectedPartnerId' => ['required', 'integer', 'exists:partners,id'],
            'selectedUserId' => ['required', 'integer', 'exists:users,id'],
        ]);
        $service->assignManager($this->award(), $awardId, (int) $data['selectedPartnerId'], (int) $data['selectedUserId'], auth('admin')->id());
        session()->flash('success', 'Đã lưu User quản lý bệnh viện/sản phẩm.');
    }

    public function removeManager(int $assignmentId, DrugBidAwardCommercialPolicyService $service): void
    {
        $this->authorizeManage();
        $service->removeManager($this->award(), $assignmentId);
        session()->flash('success', 'Đã bỏ phân công User.');
    }

    public function updatedSelectedPartnerId(): void
    {
        $this->selectedUserId = '';
    }

    public function render()
    {
        $award = $this->award();
        $group = app(DrugBidAwardResultGroupService::class);
        $awardIds = $group->awardsQuery($award)->pluck('id');

        $products = $group->awardsQuery($award)
            ->with(['allocations' => fn ($q) => $q->where('status', DrugBidAwardAllocation::STATUS_ACTIVE)->with('partner')])
            ->when(trim($this->productSearch) !== '', function ($query) {
                $like = '%'.trim($this->productSearch).'%';
                $query->where(fn ($q) => $q->where('medicine_name', 'like', $like)->orWhere('medicine_code', 'like', $like)->orWhere('lot_name', 'like', $like));
            })->orderBy('id')->limit(200)->get();

        $partners = DrugBidAwardAllocation::query()
            ->with('partner')
            ->whereIn('drug_bid_award_id', $awardIds)
            ->where('status', DrugBidAwardAllocation::STATUS_ACTIVE)
            ->get()->pluck('partner')->filter()->unique('id')->sortBy('name')->values();

        $assignments = DrugBidAwardManagementAssignment::query()
            ->with(['user', 'partner'])
            ->whereIn('drug_bid_award_id', $awardIds)
            ->where('status', DrugBidAwardManagementAssignment::STATUS_ACTIVE)
            ->get()->keyBy(fn ($row) => $row->drug_bid_award_id.':'.$row->partner_id);

        $users = User::query()->where('is_active', true)
            ->when(trim($this->userSearch) !== '', function ($q) {
                $like = '%'.trim($this->userSearch).'%';
                $q->where(fn ($n) => $n->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })->orderBy('name')->limit(50)->get(['id', 'name', 'email']);

        return view('Pharma::livewire.drug-bid-award.commercial-policy-workspace', compact('award', 'products', 'partners', 'assignments', 'users'));
    }

    private function loadPolicyValues(): void
    {
        $ids = app(DrugBidAwardResultGroupService::class)->awardsQuery($this->award())->pluck('id');
        $this->productPolicies = DrugBidAwardProductPolicy::query()->whereIn('drug_bid_award_id', $ids)
            ->pluck('commission_percentage', 'drug_bid_award_id')->map(fn ($v) => (string) $v)->all();
    }

    private function award(): DrugBidAward { return DrugBidAward::query()->findOrFail($this->awardId); }
    private function authorizeManage(): void { abort_unless(auth('admin')->user()?->can('manage_pharma_commercial_policies'), 403); }
}
