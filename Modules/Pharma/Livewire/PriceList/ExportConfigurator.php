<?php

namespace Modules\Pharma\Livewire\PriceList;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Modules\Pharma\Services\PriceListExportProfileService;

class ExportConfigurator extends Component
{
    public bool $open=false; public array $profiles=[]; public ?int $profileId=null; public string $profileName='Mặc định'; public bool $isDefault=false;
    public array $columnOrder=[],$selectedColumns=[],$headers=[],$alignments=[],$widths=[],$dataTypes=[],$decimals=[],$headerFooter=[],$pageSetup=[];

    public function mount(): void { $this->refreshProfiles(); $this->loadProfile(); }
    public function openConfig(): void { $this->refreshProfiles(); $this->loadProfile(); $this->open=true; }
    public function closeConfig(): void { $this->open=false; $this->loadProfile(); }
    public function updatedProfileId(): void { $this->loadProfile(); }
    public function newProfile(): void { $this->apply(app(PriceListExportProfileService::class)->defaults()); $this->profileName='Cấu hình mới'; }
    public function selectAll(): void { foreach($this->columnOrder as $key)$this->selectedColumns[$key]=true; }
    public function clearAll(): void { foreach($this->columnOrder as $key)$this->selectedColumns[$key]=false; }
    public function moveUp(string $key): void { $i=array_search($key,$this->columnOrder,true); if($i!==false&&$i>0){[$this->columnOrder[$i-1],$this->columnOrder[$i]]=[$this->columnOrder[$i],$this->columnOrder[$i-1]];} }
    public function moveDown(string $key): void { $i=array_search($key,$this->columnOrder,true); if($i!==false&&$i<count($this->columnOrder)-1){[$this->columnOrder[$i+1],$this->columnOrder[$i]]=[$this->columnOrder[$i],$this->columnOrder[$i+1]];} }
    public function duplicate(): void { if(!$this->profileId)return; $this->apply(app(PriceListExportProfileService::class)->duplicate((int)auth('admin')->id(),$this->profileId)); $this->refreshProfiles(); }
    public function delete(): void { if(!$this->profileId)return; app(PriceListExportProfileService::class)->delete((int)auth('admin')->id(),$this->profileId); $this->profileId=null; $this->refreshProfiles(); $this->loadProfile(); }
    public function save(): void
    {
        $this->validate(['profileName'=>'required|string|max:120','headerFooter.email'=>'nullable|email|max:255','pageSetup.paper_size'=>'required|in:A4,A3,LETTER,LEGAL','pageSetup.orientation'=>'required|in:landscape,portrait']);
        $selected=collect($this->selectedColumns)->filter(fn($v)=>(bool)$v)->keys()->all(); if($selected===[]){$this->addError('columns','Chọn ít nhất một cột để xuất.');return;}
        $saved=app(PriceListExportProfileService::class)->save((int)auth('admin')->id(),['name'=>$this->profileName,'is_default'=>$this->isDefault,'column_order'=>$this->columnOrder,'selected_columns'=>$selected,'headers'=>$this->headers,'alignments'=>$this->alignments,'widths'=>$this->widths,'data_types'=>$this->dataTypes,'decimals'=>$this->decimals,'header_footer'=>$this->headerFooter,'page_setup'=>$this->pageSetup],$this->profileId);
        $this->apply($saved); $this->refreshProfiles(); $this->dispatch('pharma-price-list-export-profile-saved',profileId:$this->profileId); session()->flash('export-config-success','Đã lưu cấu hình xuất Excel.');
    }
    private function refreshProfiles(): void { $this->profiles=app(PriceListExportProfileService::class)->profilesForUser((int)auth('admin')->id()); }
    private function loadProfile(): void { $this->apply(app(PriceListExportProfileService::class)->forUser((int)auth('admin')->id(),$this->profileId)); }
    private function apply(array $p): void { $this->profileId=$p['profile_id'];$this->profileName=$p['profile_name'];$this->isDefault=$p['is_default'];$this->columnOrder=$p['column_order'];$lookup=array_fill_keys($p['selected_columns'],true);$this->selectedColumns=[];foreach($this->columnOrder as $k)$this->selectedColumns[$k]=isset($lookup[$k]);$this->headers=$p['headers'];$this->alignments=$p['alignments'];$this->widths=$p['widths'];$this->dataTypes=$p['data_types'];$this->decimals=$p['decimals'];$this->headerFooter=$p['header_footer'];$this->pageSetup=$p['page_setup']; }
    public function render(): View { return view('Pharma::livewire.price-list.export-configurator',['columnDefinitions'=>PriceListExportProfileService::COLUMNS]); }
}
