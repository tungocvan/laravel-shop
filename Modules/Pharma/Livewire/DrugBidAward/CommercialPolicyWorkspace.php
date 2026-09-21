<?php

namespace Modules\Pharma\Livewire\DrugBidAward;

use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\DrugBidAwardAllocation;
use Modules\Pharma\Models\DrugBidAwardManagementAssignment;
use Modules\Pharma\Models\DrugBidAwardProductPolicy;
use Modules\Pharma\Services\DrugBidAwardCommercialPolicyService;
use Modules\Pharma\Services\DrugBidAwardResultGroupService;

class CommercialPolicyWorkspace extends Component
{
    use WithFileUploads;

    public int $awardId;
    public array $productPolicies = [];
    public string $bulkPercentage = '';
    public array $selectedPolicyAwardIds = [];
    public array $selectedManagementAwardIds = [];
    public string $selectedPartnerId = '';
    public string $selectedUserId = '';
    public string $userSearch = '';
    public string $productSearch = '';
    public $importFile;

    public function mount(int $awardId): void
    {
        abort_unless(auth('admin')->user()?->can('view_pharma_commercial_policies'), 403);
        $this->awardId = $awardId;
        $this->award();
        $this->loadPolicyValues();
    }

    public function selectAllPolicies(): void { $this->selectedPolicyAwardIds = $this->visibleProductIds(); }
    public function clearAllPolicies(): void { $this->selectedPolicyAwardIds = []; }

    public function selectAllManagement(): void
    {
        if (! $this->selectedPartnerId) return;
        $this->selectedManagementAwardIds = $this->allocatedProductIds((int) $this->selectedPartnerId);
    }
    public function clearAllManagement(): void { $this->selectedManagementAwardIds = []; }

    public function applyBulkPercentage(): void
    {
        $this->authorizeManage();
        $this->validate(['bulkPercentage' => ['required','numeric','min:0','max:100'], 'selectedPolicyAwardIds' => ['required','array','min:1']]);
        foreach ($this->selectedPolicyAwardIds as $id) $this->productPolicies[(int) $id] = $this->bulkPercentage;
    }

    public function saveProductPolicies(DrugBidAwardCommercialPolicyService $service): void
    {
        $this->authorizeManage();
        foreach ($this->productPolicies as $id => $value) {
            if ($value === '' || $value === null) continue;
            if (! is_numeric($value) || (float)$value < 0 || (float)$value > 100) {
                $this->addError("productPolicies.$id", 'Chính sách % phải từ 0 đến 100.'); return;
            }
        }
        $service->saveProductPolicies($this->award(), $this->productPolicies, auth('admin')->id());
        $this->loadPolicyValues();
        session()->flash('success', 'Đã lưu chính sách % theo sản phẩm.');
    }

    public function assignManager(int $awardId, DrugBidAwardCommercialPolicyService $service): void
    {
        $this->authorizeManage();
        $data=$this->validate(['selectedPartnerId'=>['required','integer','exists:partners,id'],'selectedUserId'=>['required','integer','exists:users,id']]);
        $service->assignManager($this->award(),$awardId,(int)$data['selectedPartnerId'],(int)$data['selectedUserId'],auth('admin')->id());
        session()->flash('success','Đã lưu User quản lý bệnh viện/sản phẩm.');
    }

    public function assignSelectedManagers(DrugBidAwardCommercialPolicyService $service): void
    {
        $this->authorizeManage();
        $data=$this->validate([
            'selectedPartnerId'=>['required','integer','exists:partners,id'],
            'selectedUserId'=>['required','integer','exists:users,id'],
            'selectedManagementAwardIds'=>['required','array','min:1'],
        ]);
        $service->assignManagers($this->award(),$data['selectedManagementAwardIds'],(int)$data['selectedPartnerId'],(int)$data['selectedUserId'],auth('admin')->id());
        $this->selectedManagementAwardIds=[];
        session()->flash('success','Đã gán User hàng loạt cho các sản phẩm đã chọn.');
    }

    public function removeSelectedManagers(DrugBidAwardCommercialPolicyService $service): void
    {
        $this->authorizeManage();
        $this->validate(['selectedPartnerId'=>['required','integer','exists:partners,id'],'selectedManagementAwardIds'=>['required','array','min:1']]);
        $service->removeManagers($this->award(),$this->selectedManagementAwardIds,(int)$this->selectedPartnerId);
        $this->selectedManagementAwardIds=[];
        session()->flash('success','Đã gỡ User hàng loạt.');
    }

    public function removeManager(int $assignmentId, DrugBidAwardCommercialPolicyService $service): void
    {
        $this->authorizeManage(); $service->removeManager($this->award(),$assignmentId);
        session()->flash('success','Đã bỏ phân công User.');
    }

    public function exportExcel()
    {
        $award=$this->award(); $ids=app(DrugBidAwardResultGroupService::class)->awardsQuery($award)->pluck('id');
        $products=DrugBidAward::query()->whereIn('id',$ids)->with(['allocations'=>fn($q)=>$q->where('status',DrugBidAwardAllocation::STATUS_ACTIVE)->with('partner')])->orderBy('id')->get();
        $policies=DrugBidAwardProductPolicy::query()->whereIn('drug_bid_award_id',$ids)->pluck('commission_percentage','drug_bid_award_id');
        $assignments=DrugBidAwardManagementAssignment::query()->with('user')->whereIn('drug_bid_award_id',$ids)->get()->keyBy(fn($x)=>$x->drug_bid_award_id.':'.$x->partner_id);
        $rows=collect();
        foreach($products as $product){
            $allocations=$product->allocations->isEmpty()?collect([null]):$product->allocations;
            foreach($allocations as $allocation){
                $assignment=$allocation?$assignments->get($product->id.':'.$allocation->partner_id):null;
                $rows->push([
                    'Mã TBMT'=>$product->bidding_notice_code,'Award ID'=>$product->id,'Mã hàng'=>$product->medicine_code,
                    'Sản phẩm'=>$product->medicine_name,'Số lượng trúng'=>(float)$product->quantity,'Đơn giá trúng'=>(float)($product->winning_price ?? $product->unit_price ?? 0),
                    'Chính sách (%)'=>isset($policies[$product->id])?(float)$policies[$product->id]:null,
                    'Bệnh viện ID'=>$allocation?->partner_id,'Bệnh viện'=>$allocation?->partner?->name,
                    'User ID'=>$assignment?->user_id,'User email'=>$assignment?->user?->email,
                ]);
            }
        }
        $export=new class($rows) implements FromCollection,WithHeadings {
            public function __construct(private Collection $rows){}
            public function collection(): Collection{return $this->rows;}
            public function headings(): array{return array_keys($this->rows->first() ?? []);}
        };
        $code=preg_replace('/[^A-Za-z0-9_-]+/','_',trim((string)$award->bidding_notice_code)) ?: 'award_'.$award->id;
        return Excel::download($export,'commercial-policy-'.$code.'.xlsx');
    }

    public function importExcel(DrugBidAwardCommercialPolicyService $service): void
    {
        $this->authorizeManage();
        $this->validate(['importFile'=>['required','file','mimes:xlsx,xls','max:10240']]);
        $rows=collect(Excel::toArray(null,$this->importFile)[0] ?? []);
        if($rows->isEmpty()){ $this->addError('importFile','File Excel không có dữ liệu.'); return; }
        $header=array_map(fn($v)=>trim((string)$v),$rows->shift());
        foreach(['Award ID','Chính sách (%)'] as $required) if(!in_array($required,$header,true)){ $this->addError('importFile',"Thiếu cột bắt buộc: $required"); return; }
        $validIds=app(DrugBidAwardResultGroupService::class)->awardsQuery($this->award())->pluck('id')->map(fn($v)=>(int)$v)->all();
        $percentages=[];
        foreach($rows as $index=>$values){
            $row=array_combine($header,array_pad(array_values($values),count($header),null));
            $awardId=(int)($row['Award ID']??0); if(!$awardId) continue;
            if(!in_array($awardId,$validIds,true)){ $this->addError('importFile','Dòng '.($index+2).': Award ID không thuộc TBMT hiện tại.'); return; }
            if(($row['Chính sách (%)']??'')!=='') $percentages[$awardId]=$row['Chính sách (%)'];
            $partnerId=(int)($row['Bệnh viện ID']??0); $email=trim((string)($row['User email']??''));
            if($partnerId && $email){
                $user=User::query()->where('email',$email)->first();
                if(!$user){ $this->addError('importFile','Dòng '.($index+2).": không tìm thấy User email $email."); return; }
                $service->assignManager($this->award(),$awardId,$partnerId,$user->id,auth('admin')->id());
            }
        }
        if($percentages) $service->saveProductPolicies($this->award(),$percentages,auth('admin')->id());
        $this->loadPolicyValues(); $this->reset('importFile');
        session()->flash('success','Đã import cấu hình chính sách kinh doanh từ Excel.');
    }

    public function updatedSelectedPartnerId(): void { $this->selectedUserId=''; $this->selectedManagementAwardIds=[]; }

    public function render()
    {
        $award=$this->award(); $group=app(DrugBidAwardResultGroupService::class); $awardIds=$group->awardsQuery($award)->pluck('id');
        $products=$this->productsQuery()->with(['allocations'=>fn($q)=>$q->where('status',DrugBidAwardAllocation::STATUS_ACTIVE)->with('partner')])->get();
        $partners=DrugBidAwardAllocation::query()->with('partner')->whereIn('drug_bid_award_id',$awardIds)->where('status',DrugBidAwardAllocation::STATUS_ACTIVE)->get()->pluck('partner')->filter()->unique('id')->sortBy('name')->values();
        $assignments=DrugBidAwardManagementAssignment::query()->with(['user','partner'])->whereIn('drug_bid_award_id',$awardIds)->where('status',DrugBidAwardManagementAssignment::STATUS_ACTIVE)->get()->keyBy(fn($row)=>$row->drug_bid_award_id.':'.$row->partner_id);
        $users=User::query()->where('is_active',true)->when(trim($this->userSearch)!=='',function($q){$like='%'.trim($this->userSearch).'%';$q->where(fn($n)=>$n->where('name','like',$like)->orWhere('email','like',$like));})->orderBy('name')->limit(50)->get(['id','name','email']);
        return view('Pharma::livewire.drug-bid-award.commercial-policy-workspace',compact('award','products','partners','assignments','users'));
    }

    private function productsQuery()
    {
        return app(DrugBidAwardResultGroupService::class)->awardsQuery($this->award())
            ->when(trim($this->productSearch)!=='',function($query){$like='%'.trim($this->productSearch).'%';$query->where(fn($q)=>$q->where('medicine_name','like',$like)->orWhere('medicine_code','like',$like)->orWhere('lot_name','like',$like));})
            ->orderBy('id')->limit(200);
    }
    private function visibleProductIds(): array{return $this->productsQuery()->pluck('id')->map(fn($v)=>(string)$v)->all();}
    private function allocatedProductIds(int $partnerId): array{return DrugBidAwardAllocation::query()->whereIn('drug_bid_award_id',app(DrugBidAwardResultGroupService::class)->awardsQuery($this->award())->pluck('id'))->where('partner_id',$partnerId)->where('status',DrugBidAwardAllocation::STATUS_ACTIVE)->pluck('drug_bid_award_id')->map(fn($v)=>(string)$v)->all();}
    private function loadPolicyValues(): void{$ids=app(DrugBidAwardResultGroupService::class)->awardsQuery($this->award())->pluck('id');$this->productPolicies=DrugBidAwardProductPolicy::query()->whereIn('drug_bid_award_id',$ids)->pluck('commission_percentage','drug_bid_award_id')->map(fn($v)=>(string)$v)->all();}
    private function award(): DrugBidAward{return DrugBidAward::query()->findOrFail($this->awardId);}
    private function authorizeManage(): void{abort_unless(auth('admin')->user()?->can('manage_pharma_commercial_policies'),403);}
}
