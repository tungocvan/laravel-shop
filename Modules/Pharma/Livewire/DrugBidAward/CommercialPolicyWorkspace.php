<?php

namespace Modules\Pharma\Livewire\DrugBidAward;

use App\Models\User;
use Livewire\Component;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\DrugBidAwardCommercialAssignment;
use Modules\Pharma\Models\DrugBidAwardCommercialPolicy;
use Modules\Pharma\Services\DrugBidAwardCommercialPolicyService;
use Modules\Pharma\Services\DrugBidAwardDistributionScopeService;
use Modules\Pharma\Services\DrugBidAwardResultGroupService;

class CommercialPolicyWorkspace extends Component
{
    public int $awardId;
    public string $name = '';
    public string $commissionType = 'percentage';
    public string $commissionValue = '';
    public string $commissionBasis = 'stock_out_revenue';
    public string $effectiveFrom = '';
    public string $effectiveUntil = '';
    public string $notes = '';
    public array $selectedAwardIds = [];
    public string $userSearch = '';
    public string $selectedUserId = '';
    public string $sharePercentage = '100';
    public string $assignmentFrom = '';
    public string $assignmentUntil = '';
    public string $productSearch = '';

    public function mount(int $awardId): void
    {
        abort_unless(auth('admin')->user()?->can('view_pharma_commercial_policies'), 403);
        $award = DrugBidAward::query()->findOrFail($awardId);
        $this->awardId = $awardId;
        $policy = app(DrugBidAwardCommercialPolicyService::class)->currentForAward($award);
        if ($policy) {
            $this->name = $policy->name;
            $this->commissionType = $policy->commission_type;
            $this->commissionValue = (string) $policy->commission_value;
            $this->commissionBasis = $policy->commission_basis;
            $this->effectiveFrom = $policy->effective_from?->format('Y-m-d') ?? '';
            $this->effectiveUntil = $policy->effective_until?->format('Y-m-d') ?? '';
            $this->notes = (string) $policy->notes;
        } else {
            $scope = app(DrugBidAwardDistributionScopeService::class)->findForAward($award);
            $this->effectiveFrom = $scope?->effective_from?->format('Y-m-d') ?? '';
            $this->effectiveUntil = $scope?->effective_until?->format('Y-m-d') ?? '';
        }
        $this->assignmentFrom = $this->effectiveFrom;
        $this->assignmentUntil = $this->effectiveUntil;
    }

    public function saveDraft(DrugBidAwardCommercialPolicyService $service): void
    {
        $this->authorizeManage();
        $data = $this->validate([
            'name' => ['required','string','max:255'],
            'commissionType' => ['required','in:percentage,amount_per_unit,fixed'],
            'commissionValue' => ['required','numeric','min:0'],
            'commissionBasis' => ['required','in:stock_out_revenue,collected_revenue,other'],
            'effectiveFrom' => ['required','date'],
            'effectiveUntil' => ['nullable','date','after_or_equal:effectiveFrom'],
            'notes' => ['nullable','string','max:5000'],
        ]);
        if ($data['commissionType'] === 'percentage' && (float) $data['commissionValue'] > 100) $this->addError('commissionValue', 'Hoa hồng phần trăm không được vượt 100%.');
        if ($this->getErrorBag()->has('commissionValue')) return;
        $service->saveDraft($this->award(), [
            'name'=>$data['name'],'commission_type'=>$data['commissionType'],'commission_value'=>$data['commissionValue'],
            'commission_basis'=>$data['commissionBasis'],'effective_from'=>$data['effectiveFrom'],
            'effective_until'=>$data['effectiveUntil'] ?: null,'notes'=>$data['notes'] ?: null,
        ], auth('admin')->id());
        session()->flash('success', 'Đã lưu bản nháp chính sách kinh doanh.');
    }

    public function assignSelected(DrugBidAwardCommercialPolicyService $service): void
    {
        $this->authorizeManage();
        $data = $this->validate([
            'selectedAwardIds'=>['required','array','min:1'],'selectedAwardIds.*'=>['integer'],
            'selectedUserId'=>['required','integer','exists:users,id'],'sharePercentage'=>['required','numeric','gt:0','lte:100'],
            'assignmentFrom'=>['required','date'],'assignmentUntil'=>['nullable','date','after_or_equal:assignmentFrom'],
        ]);
        $policy = $this->policy();
        if (! $policy) { $this->addError('assignment', 'Hãy lưu chính sách Draft trước khi phân công User.'); return; }
        $service->assign($this->award(), $policy, $data['selectedAwardIds'], (int)$data['selectedUserId'], (float)$data['sharePercentage'], $data['assignmentFrom'], $data['assignmentUntil'] ?: null, auth('admin')->id());
        $this->selectedAwardIds = [];
        session()->flash('success', 'Đã phân công User cho sản phẩm đã chọn.');
    }

    public function endAssignment(int $assignmentId, DrugBidAwardCommercialPolicyService $service): void
    {
        $this->authorizeManage();
        $assignment = DrugBidAwardCommercialAssignment::query()->with('policy')->findOrFail($assignmentId);
        abort_unless($assignment->policy?->result_key === app(DrugBidAwardResultGroupService::class)->resultKey($this->award()), 404);
        $service->endAssignment($assignment, 'Thay đổi phân công từ workspace chính sách kinh doanh.', auth('admin')->id());
        session()->flash('success', 'Đã kết thúc phân công và giữ lại lịch sử.');
    }

    public function activate(DrugBidAwardCommercialPolicyService $service): void
    {
        $this->authorizeManage();
        $policy = $this->policy();
        if (! $policy) { $this->addError('activation', 'Chưa có chính sách Draft để kích hoạt.'); return; }
        $service->activate($this->award(), $policy, auth('admin')->id());
        session()->flash('success', 'Đã kích hoạt chính sách kinh doanh.');
    }

    public function archive(DrugBidAwardCommercialPolicyService $service): void
    {
        $this->authorizeManage();
        $policy = $this->policy();
        if (! $policy) return;
        $service->archive($this->award(), $policy, auth('admin')->id());
        $this->reset(['name','commissionValue','notes','selectedAwardIds','selectedUserId']);
        session()->flash('success', 'Đã lưu trữ chính sách. Có thể tạo Draft mới mà không mất lịch sử.');
    }

    public function render()
    {
        $award = $this->award();
        $group = app(DrugBidAwardResultGroupService::class);
        $products = $group->awardsQuery($award)
            ->with(['allocations'=>fn($q)=>$q->where('status','active')])
            ->when(trim($this->productSearch) !== '', function ($query) {
                $like = '%'.trim($this->productSearch).'%';
                $query->where(fn($q)=>$q->where('medicine_name','like',$like)->orWhere('active_ingredient','like',$like)->orWhere('lot_name','like',$like));
            })->orderBy('id')->limit(200)->get();
        $policy = $this->policy();
        $assignments = $policy ? $policy->assignments()->with('user')->where('status','active')->get()->groupBy('drug_bid_award_id') : collect();
        $users = User::query()->where('is_active', true)
            ->when(trim($this->userSearch) !== '', function($q){$like='%'.trim($this->userSearch).'%';$q->where(fn($n)=>$n->where('name','like',$like)->orWhere('email','like',$like));})
            ->orderBy('name')->limit(50)->get(['id','name','email']);
        $issues = $policy ? app(DrugBidAwardCommercialPolicyService::class)->activationIssues($award,$policy) : [];

        return view('Pharma::livewire.drug-bid-award.commercial-policy-workspace', compact('award','products','policy','assignments','users','issues'));
    }

    private function award(): DrugBidAward { return DrugBidAward::query()->findOrFail($this->awardId); }
    private function policy(): ?DrugBidAwardCommercialPolicy { return app(DrugBidAwardCommercialPolicyService::class)->currentForAward($this->award()); }
    private function authorizeManage(): void { abort_unless(auth('admin')->user()?->can('manage_pharma_commercial_policies'), 403); }
}
