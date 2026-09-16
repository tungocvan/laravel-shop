<?php

namespace Modules\Pharma\Livewire\PriceList;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
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
    public array $columnOrder = [];
    public array $columnGroupsMap = [];
    public array $selectedColumns = [];
    public array $headers = [];
    public array $alignments = [];
    public array $widths = [];
    public array $dataTypes = [];
    public array $decimals = [];
    public array $headerFooter = [];
    public array $pageSetup = [];
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
    public function updatedSelectedColumns(): void { $this->compactSelectedColumns(); }
    public function newProfile(): void { $this->apply(app(PriceListExportProfileService::class)->defaults()); $this->profileName = 'Cấu hình mới'; }
    public function selectAll(): void { foreach ($this->columnOrder as $key) $this->selectedColumns[$key] = true; $this->compactSelectedColumns(); }
    public function clearAll(): void { foreach ($this->columnOrder as $key) $this->selectedColumns[$key] = false; }

    public function selectGroup(string $group, bool $selected = true): void
    {
        foreach ($this->columnOrder as $key) if ((PriceListExportProfileService::COLUMNS[$key]['group'] ?? null) === $group) $this->selectedColumns[$key] = $selected;
        $this->compactSelectedColumns();
    }

    public function addColumn(string $key): void
    {
        if (! isset(PriceListExportProfileService::COLUMNS[$key])) return;
        $this->selectedColumns[$key] = true;
        $this->compactSelectedColumns();
        $this->activeColumnKey = $key;
    }

    public function removeColumn(string $key): void
    {
        if (! isset(PriceListExportProfileService::COLUMNS[$key])) return;
        $this->selectedColumns[$key] = false;
        $this->compactSelectedColumns();
        if ($this->activeColumnKey === $key) $this->activeColumnKey = $this->selectedOrder()[0] ?? null;
    }

    public function moveColumn(string $key, int $offset): void
    {
        $selected = $this->selectedOrder();
        $index = array_search($key, $selected, true);
        if ($index === false) return;
        $target = max(0, min(count($selected) - 1, $index + $offset));
        if ($target === $index) return;
        array_splice($selected, $index, 1);
        array_splice($selected, $target, 0, [$key]);
        $this->replaceSelectedOrder($selected);
        $this->activeColumnKey = $key;
    }

    public function setColumnPosition(string $key, int $position): void
    {
        if (! isset(PriceListExportProfileService::COLUMNS[$key]) || ! ($this->selectedColumns[$key] ?? false)) return;
        $selected = array_values(array_filter($this->selectedOrder(), fn ($columnKey) => $columnKey !== $key));
        $position = max(1, min(count($selected) + 1, $position));
        array_splice($selected, $position - 1, 0, [$key]);
        $this->replaceSelectedOrder($selected);
        $this->activeColumnKey = $key;
    }

    public function resetSelectedOrder(): void
    {
        $canonical = array_keys(PriceListExportProfileService::COLUMNS);
        $selected = array_values(array_filter($canonical, fn ($key) => $this->selectedColumns[$key] ?? false));
        $this->replaceSelectedOrder($selected);
    }

    public function resetColumn(string $key): void
    {
        if (! isset(PriceListExportProfileService::COLUMNS[$key])) return;
        $definition = PriceListExportProfileService::COLUMNS[$key];
        $this->columnGroupsMap[$key] = $definition['group'];
        $this->headers[$key] = $definition['label'];
        $this->alignments[$key] = $definition['align'];
        $this->widths[$key] = $definition['width'];
        $this->dataTypes[$key] = $definition['type'];
        $this->decimals[$key] = 0;
        $this->selectedColumns[$key] = in_array($key, PriceListExportProfileService::DEFAULT_SELECTED, true);
        $this->compactSelectedColumns();
        if ($this->selectedColumns[$key]) {
            $canonicalSelected = array_values(array_filter(array_keys(PriceListExportProfileService::COLUMNS), fn ($columnKey) => $this->selectedColumns[$columnKey] ?? false));
            $canonicalPosition = array_search($key, $canonicalSelected, true);
            if ($canonicalPosition !== false) $this->setColumnPosition($key, $canonicalPosition + 1);
        }
    }

    /** @deprecated Kept for old Livewire/browser payloads during the profile transition. */
    public function resetColumnGroup(string $key): void { $this->resetColumn($key); }
    public function duplicate(): void { if (! $this->profileId) return; $this->apply(app(PriceListExportProfileService::class)->duplicate((int) auth('admin')->id(), $this->profileId)); $this->refreshProfiles(); }
    public function delete(): void { if (! $this->profileId) return; app(PriceListExportProfileService::class)->delete((int) auth('admin')->id(), $this->profileId); $this->profileId = null; $this->refreshProfiles(); $this->loadProfile(); }

    public function save(): void
    {
        $this->validate(['profileName'=>'required|string|max:120','headerFooter.email'=>'nullable|email|max:255','pageSetup.paper_size'=>'required|in:A4,A3,LETTER,LEGAL','pageSetup.orientation'=>'required|in:landscape,portrait','logoUpload'=>'nullable|image|max:4096','signatureUpload'=>'nullable|image|max:4096']);
        $selected=collect($this->selectedColumns)->filter(fn($v)=>(bool)$v)->keys()->all(); if($selected===[]){$this->addError('columns','Chọn ít nhất một cột để xuất.');$this->activeSection='columns';return;}
        $this->compactSelectedColumns();$selected=$this->selectedOrder();
        $newLogo=$this->logoUpload?$this->storeMedia($this->logoUpload,'logo'):($this->removeLogoRequested?null:$this->logoPath);
        $newSignature=$this->signatureUpload?$this->storeMedia($this->signatureUpload,'signature'):($this->removeSignatureRequested?null:$this->signaturePath);
        $canonicalGroups=[];foreach(array_keys(PriceListExportProfileService::COLUMNS) as$key)$canonicalGroups[$key]=PriceListExportProfileService::COLUMNS[$key]['group'];
        $saved=app(PriceListExportProfileService::class)->save((int)auth('admin')->id(),['name'=>$this->profileName,'is_default'=>$this->isDefault,'column_order'=>$this->columnOrder,'column_groups'=>$canonicalGroups,'selected_columns'=>$selected,'headers'=>$this->headers,'alignments'=>$this->alignments,'widths'=>$this->widths,'data_types'=>$this->dataTypes,'decimals'=>$this->decimals,'header_footer'=>$this->headerFooter,'page_setup'=>$this->pageSetup,'logo_path'=>$newLogo,'signature_path'=>$newSignature],$this->profileId);
        if($this->originalLogoPath&&$this->originalLogoPath!==$saved['logo_path'])$this->deleteMedia($this->originalLogoPath);
        if($this->originalSignaturePath&&$this->originalSignaturePath!==$saved['signature_path'])$this->deleteMedia($this->originalSignaturePath);
        $this->apply($saved);$this->refreshProfiles();$this->reset(['logoUpload','signatureUpload']);$this->dispatch('pharma-price-list-export-profile-saved',profileId:$this->profileId);session()->flash('export-config-success','Đã lưu cấu hình xuất Excel.');
    }

    public function removeLogo():void{$this->logoUpload=null;$this->logoPath=null;$this->removeLogoRequested=true;}
    public function removeSignature():void{$this->signatureUpload=null;$this->signaturePath=null;$this->removeSignatureRequested=true;}
    public function exportJson(){ $payload=app(PriceListExportProfileService::class)->exportPayload((int)auth('admin')->id(),$this->profileId);$json=json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);return response()->streamDownload(fn()=>print($json),'pharma-price-list-layout-'.now()->format('Ymd-His').'.json',['Content-Type'=>'application/json; charset=UTF-8']); }
    public function importJson():void{$this->validate(['profileJsonUpload'=>'required|file|max:1024']);$payload=json_decode((string)file_get_contents($this->profileJsonUpload->getRealPath()),true);if(!is_array($payload)){$this->addError('profileJsonUpload','File JSON không hợp lệ.');return;}try{$saved=app(PriceListExportProfileService::class)->importPayload((int)auth('admin')->id(),$payload);}catch(InvalidArgumentException $e){$this->addError('profileJsonUpload',$e->getMessage());return;}$this->apply($saved);$this->refreshProfiles();$this->reset('profileJsonUpload');session()->flash('export-config-success','Đã import cấu hình JSON thành profile mới.');}

    private function selectedOrder(): array { return array_values(array_filter($this->columnOrder,fn($key)=>$this->selectedColumns[$key]??false)); }
    private function replaceSelectedOrder(array $selected): void { $unselected=array_values(array_filter($this->columnOrder,fn($key)=>!($this->selectedColumns[$key]??false)));$this->columnOrder=[...$selected,...$unselected]; }
    private function compactSelectedColumns(): void { $this->replaceSelectedOrder($this->selectedOrder()); }
    private function storeMedia($upload,string $kind):string{return$upload->store('pharma/price-list-export/'.auth('admin')->id().'/'.$kind,'public');}
    private function deleteMedia(?string $path):void{if($path)Storage::disk('public')->delete($path);}
    private function refreshProfiles():void{$this->profiles=app(PriceListExportProfileService::class)->profilesForUser((int)auth('admin')->id());}
    private function loadProfile():void{$this->apply(app(PriceListExportProfileService::class)->forUser((int)auth('admin')->id(),$this->profileId));}
    private function apply(array $p):void{$this->profileId=$p['profile_id'];$this->profileName=$p['profile_name'];$this->isDefault=$p['is_default'];$this->columnOrder=$p['column_order'];$this->columnGroupsMap=[];foreach(array_keys(PriceListExportProfileService::COLUMNS)as$k)$this->columnGroupsMap[$k]=PriceListExportProfileService::COLUMNS[$k]['group'];$lookup=array_fill_keys($p['selected_columns'],true);$this->selectedColumns=[];foreach($this->columnOrder as$k)$this->selectedColumns[$k]=isset($lookup[$k]);$this->compactSelectedColumns();$this->headers=$p['headers'];$this->alignments=$p['alignments'];$this->widths=$p['widths'];$this->dataTypes=$p['data_types'];$this->decimals=$p['decimals'];$this->headerFooter=$p['header_footer'];$this->pageSetup=$p['page_setup'];$this->logoPath=$p['logo_path'];$this->signaturePath=$p['signature_path'];$this->originalLogoPath=$this->logoPath;$this->originalSignaturePath=$this->signaturePath;$this->removeLogoRequested=false;$this->removeSignatureRequested=false;$this->activeColumnKey=$this->activeColumnKey&&isset(PriceListExportProfileService::COLUMNS[$this->activeColumnKey])?$this->activeColumnKey:($this->selectedOrder()[0]??$this->columnOrder[0]??null);}
    public function render():View{return view('Pharma::livewire.price-list.export-configurator',['columnDefinitions'=>PriceListExportProfileService::COLUMNS,'columnGroups'=>PriceListExportProfileService::GROUPS]);}
}
