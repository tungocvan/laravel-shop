<?php

namespace Modules\Pharma\Livewire\PriceList;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Modules\Pharma\Services\PriceListExportProfileService;

class ExportConfigurator extends Component
{
    use WithFileUploads;

    public bool $open = false;
    public string $activeSection = 'brand';
    public ?string $activeColumnKey = null;
    public array $profiles = [];
    public ?int $profileId = null;
    public string $profileName = 'Mặc định';
    public bool $isDefault = false;
    public array $columnOrder = [], $columnGroupsMap = [], $selectedColumns = [], $headers = [], $alignments = [], $widths = [], $dataTypes = [], $decimals = [], $headerFooter = [], $pageSetup = [];
    public ?string $logoPath = null;
    public ?string $signaturePath = null;
    public ?string $originalLogoPath = null;
    public ?string $originalSignaturePath = null;
    public bool $removeLogoRequested = false;
    public bool $removeSignatureRequested = false;
    public $logoUpload = null;
    public $signatureUpload = null;
    public $profileJsonUpload = null;

    public function mount(): void { $this->refreshProfiles(); $this->loadProfile(); }
    public function openConfig(): void { $this->refreshProfiles(); $this->loadProfile(); $this->activeSection = 'brand'; $this->open = true; }
    public function closeConfig(): void { $this->open = false; $this->loadProfile(); $this->reset(['logoUpload', 'signatureUpload', 'profileJsonUpload']); }
    public function setSection(string $section): void { if (in_array($section, ['brand', 'columns', 'page'], true)) $this->activeSection = $section; }
    public function editColumn(string $key): void { if (isset(PriceListExportProfileService::COLUMNS[$key])) { $this->activeColumnKey = $key; $this->activeSection = 'columns'; } }
    public function updatedProfileId(): void { $this->loadProfile(); }
    public function newProfile(): void { $this->apply(app(PriceListExportProfileService::class)->defaults()); $this->profileName = 'Cấu hình mới'; }
    public function selectAll(): void { foreach ($this->columnOrder as $key) $this->selectedColumns[$key] = true; }
    public function clearAll(): void { foreach ($this->columnOrder as $key) $this->selectedColumns[$key] = false; }

    public function selectGroup(string $group, bool $selected = true): void
    {
        foreach ($this->columnOrder as $key) {
            if (($this->columnGroupsMap[$key] ?? PriceListExportProfileService::COLUMNS[$key]['group'] ?? null) === $group) $this->selectedColumns[$key] = $selected;
        }
    }

    public function reorderColumns(array $orderedKeys): void
    {
        $known = array_keys(PriceListExportProfileService::COLUMNS);
        $ordered = array_values(array_unique(array_filter($orderedKeys, fn ($key) => is_string($key) && in_array($key, $known, true))));
        foreach ($this->columnOrder as $key) if (! in_array($key, $ordered, true)) $ordered[] = $key;
        $this->columnOrder = $ordered;
    }

    public function moveColumnToGroup(string $key, string $group, ?string $beforeKey = null): void
    {
        if (! isset(PriceListExportProfileService::COLUMNS[$key]) || ! isset(PriceListExportProfileService::GROUPS[$group])) return;
        $this->columnGroupsMap[$key] = $group;
        $order = array_values(array_filter($this->columnOrder, fn ($columnKey) => $columnKey !== $key));
        $targetIndex = $beforeKey ? array_search($beforeKey, $order, true) : false;
        if ($targetIndex === false) {
            $groupKeys = array_values(array_filter($order, fn ($columnKey) => ($this->columnGroupsMap[$columnKey] ?? PriceListExportProfileService::COLUMNS[$columnKey]['group'] ?? 'general') === $group));
            if ($groupKeys === []) $order[] = $key;
            else { $lastGroupKey = end($groupKeys); $lastIndex = array_search($lastGroupKey, $order, true); array_splice($order, $lastIndex + 1, 0, [$key]); }
        } else array_splice($order, $targetIndex, 0, [$key]);
        $this->columnOrder = $order;
        $this->activeColumnKey = $key;
    }

    public function resetColumnGroup(string $key): void
    {
        if (! isset(PriceListExportProfileService::COLUMNS[$key])) return;

        $definition = PriceListExportProfileService::COLUMNS[$key];
        $this->moveColumnToGroup($key, $definition['group']);
        $this->headers[$key] = $definition['label'];
        $this->alignments[$key] = $definition['align'];
        $this->widths[$key] = $definition['width'];
        $this->dataTypes[$key] = $definition['type'];
        $this->decimals[$key] = 0;
        $this->selectedColumns[$key] = in_array($key, PriceListExportProfileService::DEFAULT_SELECTED, true);
    }

    public function duplicate(): void { if (! $this->profileId) return; $this->apply(app(PriceListExportProfileService::class)->duplicate((int) auth('admin')->id(), $this->profileId)); $this->refreshProfiles(); }
    public function delete(): void { if (! $this->profileId) return; app(PriceListExportProfileService::class)->delete((int) auth('admin')->id(), $this->profileId); $this->profileId = null; $this->refreshProfiles(); $this->loadProfile(); }

    public function save(): void
    {
        $this->validate(['profileName'=>'required|string|max:120','headerFooter.email'=>'nullable|email|max:255','pageSetup.paper_size'=>'required|in:A4,A3,LETTER,LEGAL','pageSetup.orientation'=>'required|in:landscape,portrait','logoUpload'=>'nullable|image|max:4096','signatureUpload'=>'nullable|image|max:4096']);
        $selected=collect($this->selectedColumns)->filter(fn($v)=>(bool)$v)->keys()->all(); if($selected===[]){$this->addError('columns','Chọn ít nhất một cột để xuất.');$this->activeSection='columns';return;}
        $newLogo=$this->logoUpload?$this->storeMedia($this->logoUpload,'logo'):($this->removeLogoRequested?null:$this->logoPath);
        $newSignature=$this->signatureUpload?$this->storeMedia($this->signatureUpload,'signature'):($this->removeSignatureRequested?null:$this->signaturePath);
        $saved=app(PriceListExportProfileService::class)->save((int)auth('admin')->id(),['name'=>$this->profileName,'is_default'=>$this->isDefault,'column_order'=>$this->columnOrder,'column_groups'=>$this->columnGroupsMap,'selected_columns'=>$selected,'headers'=>$this->headers,'alignments'=>$this->alignments,'widths'=>$this->widths,'data_types'=>$this->dataTypes,'decimals'=>$this->decimals,'header_footer'=>$this->headerFooter,'page_setup'=>$this->pageSetup,'logo_path'=>$newLogo,'signature_path'=>$newSignature],$this->profileId);
        if($this->originalLogoPath&&$this->originalLogoPath!==$saved['logo_path'])$this->deleteMedia($this->originalLogoPath);
        if($this->originalSignaturePath&&$this->originalSignaturePath!==$saved['signature_path'])$this->deleteMedia($this->originalSignaturePath);
        $this->apply($saved);$this->refreshProfiles();$this->reset(['logoUpload','signatureUpload']);$this->dispatch('pharma-price-list-export-profile-saved',profileId:$this->profileId);session()->flash('export-config-success','Đã lưu cấu hình xuất Excel.');
    }
    public function removeLogo():void{$this->logoUpload=null;$this->logoPath=null;$this->removeLogoRequested=true;}
    public function removeSignature():void{$this->signatureUpload=null;$this->signaturePath=null;$this->removeSignatureRequested=true;}
    public function exportJson(){ $payload=app(PriceListExportProfileService::class)->exportPayload((int)auth('admin')->id(),$this->profileId);$json=json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);return response()->streamDownload(fn()=>print($json),'pharma-price-list-layout-'.now()->format('Ymd-His').'.json',['Content-Type'=>'application/json; charset=UTF-8']); }
    public function importJson():void{$this->validate(['profileJsonUpload'=>'required|file|max:1024']);$payload=json_decode((string)file_get_contents($this->profileJsonUpload->getRealPath()),true);if(!is_array($payload)){$this->addError('profileJsonUpload','File JSON không hợp lệ.');return;}try{$saved=app(PriceListExportProfileService::class)->importPayload((int)auth('admin')->id(),$payload);}catch(\InvalidArgumentException $e){$this->addError('profileJsonUpload',$e->getMessage());return;}$this->apply($saved);$this->refreshProfiles();$this->reset('profileJsonUpload');session()->flash('export-config-success','Đã import cấu hình JSON thành profile mới.');}
    private function storeMedia($upload,string $kind):string{return$upload->store('pharma/price-list-export/'.auth('admin')->id().'/'.$kind,'public');}
    private function deleteMedia(?string $path):void{if($path)Storage::disk('public')->delete($path);}
    private function refreshProfiles():void{$this->profiles=app(PriceListExportProfileService::class)->profilesForUser((int)auth('admin')->id());}
    private function loadProfile():void{$this->apply(app(PriceListExportProfileService::class)->forUser((int)auth('admin')->id(),$this->profileId));}
    private function apply(array $p):void{$this->profileId=$p['profile_id'];$this->profileName=$p['profile_name'];$this->isDefault=$p['is_default'];$this->columnOrder=$p['column_order'];$this->columnGroupsMap=$p['column_groups'];$lookup=array_fill_keys($p['selected_columns'],true);$this->selectedColumns=[];foreach($this->columnOrder as$k)$this->selectedColumns[$k]=isset($lookup[$k]);$this->headers=$p['headers'];$this->alignments=$p['alignments'];$this->widths=$p['widths'];$this->dataTypes=$p['data_types'];$this->decimals=$p['decimals'];$this->headerFooter=$p['header_footer'];$this->pageSetup=$p['page_setup'];$this->logoPath=$p['logo_path'];$this->signaturePath=$p['signature_path'];$this->originalLogoPath=$this->logoPath;$this->originalSignaturePath=$this->signaturePath;$this->removeLogoRequested=false;$this->removeSignatureRequested=false;$this->activeColumnKey=$this->activeColumnKey&&isset(PriceListExportProfileService::COLUMNS[$this->activeColumnKey])?$this->activeColumnKey:($this->columnOrder[0]??null);}
    public function render():View{return view('Pharma::livewire.price-list.export-configurator',['columnDefinitions'=>PriceListExportProfileService::COLUMNS,'columnGroups'=>PriceListExportProfileService::GROUPS]);}
}
