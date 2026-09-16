<?php

namespace Modules\Pharma\Livewire\PriceList;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Modules\Pharma\Services\PriceListExportJsonLibrary;
use Modules\Pharma\Services\PriceListExportProfileService;

class ExportConfigurator extends Component
{
    use WithFileUploads;

    public bool $open=false; public string $activeSection='brand'; public ?string $activeColumnKey=null;
    public array $profiles=[]; public ?int $profileId=null; public string $profileName='Mặc định'; public bool $isDefault=false;
    public array $columnOrder=[],$columnGroupsMap=[],$selectedColumns=[],$headers=[],$alignments=[],$widths=[],$dataTypes=[],$decimals=[],$headerFooter=[],$pageSetup=[];
    public array $columnDraft=[];
    public ?string $logoPath=null,$signaturePath=null,$originalLogoPath=null,$originalSignaturePath=null; public bool $removeLogoRequested=false,$removeSignatureRequested=false;
    public $logoUpload=null,$signatureUpload=null,$profileJsonUpload=null;
    public bool $jsonLibraryOpen=false,$jsonSaveOpen=false,$noticeOpen=false,$confirmOpen=false;
    public array $jsonFiles=[]; public string $jsonFileName=''; public ?string $selectedJsonFile=null; public string $noticeTitle='',$noticeMessage='',$pendingConfirmAction='',$pendingConfirmValue='';

    public function mount():void{$this->refreshProfiles();$this->loadProfile();}
    public function openConfig():void{$this->refreshProfiles();$this->loadProfile();$this->activeSection='brand';$this->open=true;}
    public function closeConfig():void{$this->open=false;$this->loadProfile();$this->reset(['logoUpload','signatureUpload','profileJsonUpload']);}
    public function setSection(string $section):void{if(in_array($section,['brand','columns','page'],true))$this->activeSection=$section;}
    public function editColumn(string $key):void{if(!isset(PriceListExportProfileService::COLUMNS[$key]))return;$this->commitColumnDraft();$this->activeColumnKey=$key;$this->loadColumnDraft($key);$this->activeSection='columns';}
    public function updatedProfileId():void{$this->loadProfile();} public function updatedSelectedColumns():void{$this->compactSelectedColumns();}
    public function newProfile():void{$this->apply(app(PriceListExportProfileService::class)->defaults());$this->profileName='Cấu hình mới';$this->notify('Cấu hình mới','Đã khởi tạo cấu hình mới. Hãy điều chỉnh và nhấn Lưu cấu hình.');}
    public function selectAll():void{foreach($this->columnOrder as$key)$this->selectedColumns[$key]=true;$this->compactSelectedColumns();} public function clearAll():void{foreach($this->columnOrder as$key)$this->selectedColumns[$key]=false;}
    public function selectGroup(string $group,bool $selected=true):void{foreach($this->columnOrder as$key)if((PriceListExportProfileService::COLUMNS[$key]['group']??null)===$group)$this->selectedColumns[$key]=$selected;$this->compactSelectedColumns();}
    public function addColumn(string $key):void{if(!isset(PriceListExportProfileService::COLUMNS[$key]))return;$this->selectedColumns[$key]=true;$this->compactSelectedColumns();$this->editColumn($key);}
    public function removeColumn(string $key):void{if(!isset(PriceListExportProfileService::COLUMNS[$key]))return;$this->commitColumnDraft();$this->selectedColumns[$key]=false;$this->compactSelectedColumns();if($this->activeColumnKey===$key){$this->activeColumnKey=$this->selectedOrder()[0]??null;$this->loadColumnDraft($this->activeColumnKey);}}
    public function moveColumn(string $key,int $offset):void{$selected=$this->selectedOrder();$index=array_search($key,$selected,true);if($index===false)return;$target=max(0,min(count($selected)-1,$index+$offset));if($target===$index)return;array_splice($selected,$index,1);array_splice($selected,$target,0,[$key]);$this->replaceSelectedOrder($selected);$this->activeColumnKey=$key;$this->loadColumnDraft($key);}
    public function setColumnPosition(string $key,int $position):void{if(!isset(PriceListExportProfileService::COLUMNS[$key])||!($this->selectedColumns[$key]??false))return;$selected=array_values(array_filter($this->selectedOrder(),fn($columnKey)=>$columnKey!==$key));$position=max(1,min(count($selected)+1,$position));array_splice($selected,$position-1,0,[$key]);$this->replaceSelectedOrder($selected);$this->activeColumnKey=$key;$this->loadColumnDraft($key);}
    public function resetSelectedOrder():void{$canonical=array_keys(PriceListExportProfileService::COLUMNS);$this->replaceSelectedOrder(array_values(array_filter($canonical,fn($key)=>$this->selectedColumns[$key]??false)));}
    public function resetColumn(string $key):void{if(!isset(PriceListExportProfileService::COLUMNS[$key]))return;$d=PriceListExportProfileService::COLUMNS[$key];$this->columnGroupsMap[$key]=$d['group'];$this->headers[$key]=$d['label'];$this->alignments[$key]=$d['align'];$this->widths[$key]=$d['width'];$this->dataTypes[$key]=$d['type'];$this->decimals[$key]=0;$this->selectedColumns[$key]=in_array($key,PriceListExportProfileService::DEFAULT_SELECTED,true);$this->compactSelectedColumns();if($this->activeColumnKey===$key)$this->loadColumnDraft($key);}
    public function resetColumnGroup(string $key):void{$this->resetColumn($key);}
    public function applyColumnDraft():void{$this->commitColumnDraft();}
    public function setColumnWidthPreset(string $preset):void{if(!$this->activeColumnKey)return;$width=match($preset){'xs'=>70,'s'=>95,'m'=>125,'l'=>170,'xl'=>220,default=>PriceListExportProfileService::COLUMNS[$this->activeColumnKey]['width']??125};$this->columnDraft['width']=$width;$this->commitColumnDraft();}
    public function duplicate():void{if(!$this->profileId)return;$this->apply(app(PriceListExportProfileService::class)->duplicate((int)auth('admin')->id(),$this->profileId));$this->refreshProfiles();$this->notify('Đã nhân đôi','Profile đã được nhân đôi thành cấu hình mới.');}
    public function requestDeleteProfile():void{if($this->profileId)$this->openConfirmation('Xóa profile?','delete-profile',(string)$this->profileId);}
    public function delete():void{$this->requestDeleteProfile();}

    public function save():void
    {
        $this->commitColumnDraft();
        $this->validate(['profileName'=>'required|string|max:120','headerFooter.email'=>'nullable|email|max:255','headerFooter.logo_width_cm'=>'nullable|numeric|min:1|max:12','headerFooter.logo_height_cm'=>'nullable|numeric|min:1|max:8','headerFooter.signature_width_cm'=>'nullable|numeric|min:1|max:12','headerFooter.signature_height_cm'=>'nullable|numeric|min:1|max:8','pageSetup.paper_size'=>'required|in:A4,A3,LETTER,LEGAL','pageSetup.orientation'=>'required|in:landscape,portrait','pageSetup.product_font_size'=>'nullable|integer|in:10,11,12,13','pageSetup.header_background'=>['nullable','regex:/^#?[0-9A-Fa-f]{6}$/'],'pageSetup.header_text_color'=>['nullable','regex:/^#?[0-9A-Fa-f]{6}$/'],'pageSetup.table_border'=>'nullable|in:thin,none','logoUpload'=>'nullable|image|max:4096','signatureUpload'=>'nullable|image|max:4096']);
        $selected=collect($this->selectedColumns)->filter(fn($v)=>(bool)$v)->keys()->all();if($selected===[]){$this->addError('columns','Chọn ít nhất một cột để xuất.');$this->activeSection='columns';return;}$this->compactSelectedColumns();$selected=$this->selectedOrder();
        $newLogo=$this->logoUpload?$this->storeMedia($this->logoUpload,'logo'):($this->removeLogoRequested?null:$this->logoPath);$newSignature=$this->signatureUpload?$this->storeMedia($this->signatureUpload,'signature'):($this->removeSignatureRequested?null:$this->signaturePath);
        $groups=[];foreach(array_keys(PriceListExportProfileService::COLUMNS)as$key)$groups[$key]=PriceListExportProfileService::COLUMNS[$key]['group'];
        $saved=app(PriceListExportProfileService::class)->save((int)auth('admin')->id(),['name'=>$this->profileName,'is_default'=>$this->isDefault,'column_order'=>$this->columnOrder,'column_groups'=>$groups,'selected_columns'=>$selected,'headers'=>$this->headers,'alignments'=>$this->alignments,'widths'=>$this->widths,'data_types'=>$this->dataTypes,'decimals'=>$this->decimals,'header_footer'=>$this->headerFooter,'page_setup'=>$this->pageSetup,'logo_path'=>$newLogo,'signature_path'=>$newSignature],$this->profileId);
        if($this->originalLogoPath&&$this->originalLogoPath!==$saved['logo_path'])$this->deleteMedia($this->originalLogoPath);if($this->originalSignaturePath&&$this->originalSignaturePath!==$saved['signature_path'])$this->deleteMedia($this->originalSignaturePath);
        $this->apply($saved);$this->refreshProfiles();$this->reset(['logoUpload','signatureUpload']);$this->dispatch('pharma-price-list-export-profile-saved',profileId:$this->profileId);$this->notify('Đã lưu cấu hình','Cấu hình “'.$this->profileName.'” đã được cập nhật thành công.');
    }

    public function removeLogo():void{$this->logoUpload=null;$this->logoPath=null;$this->removeLogoRequested=true;} public function removeSignature():void{$this->signatureUpload=null;$this->signaturePath=null;$this->removeSignatureRequested=true;}
    public function openJsonSave():void{$this->jsonFileName=app(PriceListExportJsonLibrary::class)->suggestedName($this->profileName);$this->jsonSaveOpen=true;}
    public function saveJsonToServer():void{$this->validate(['jsonFileName'=>'required|string|max:120']);$payload=app(PriceListExportProfileService::class)->exportPayload((int)auth('admin')->id(),$this->profileId);$name=app(PriceListExportJsonLibrary::class)->save((int)auth('admin')->id(),$this->jsonFileName,$payload);$this->jsonSaveOpen=false;$this->refreshJsonFiles();$this->notify('Đã lưu JSON','Đã lưu “'.$name.'” vào thư viện cấu hình trên server.');}
    public function openJsonLibrary():void{$this->refreshJsonFiles();$this->selectedJsonFile=null;$this->jsonLibraryOpen=true;}
    public function selectJsonFile(string $name):void{$this->selectedJsonFile=$name;}
    public function importSelectedJson():void{if(!$this->selectedJsonFile){$this->addError('jsonLibrary','Hãy chọn một file JSON.');return;}$payload=app(PriceListExportJsonLibrary::class)->read((int)auth('admin')->id(),$this->selectedJsonFile);$saved=app(PriceListExportProfileService::class)->importPayload((int)auth('admin')->id(),$payload);$this->apply($saved);$this->refreshProfiles();$this->jsonLibraryOpen=false;$this->notify('Import thành công','Đã tạo profile mới từ “'.$this->selectedJsonFile.'”.');}
    public function requestDeleteJson(string $name):void{$this->openConfirmation('Xóa file JSON?','delete-json',$name);}
    public function exportJson():void{$this->openJsonSave();}
    public function importJson():void{$this->validate(['profileJsonUpload'=>'required|file|max:1024']);$payload=json_decode((string)file_get_contents($this->profileJsonUpload->getRealPath()),true);if(!is_array($payload)){$this->addError('profileJsonUpload','File JSON không hợp lệ.');return;}try{$saved=app(PriceListExportProfileService::class)->importPayload((int)auth('admin')->id(),$payload);}catch(InvalidArgumentException $e){$this->addError('profileJsonUpload',$e->getMessage());return;}$this->apply($saved);$this->refreshProfiles();$this->reset('profileJsonUpload');$this->notify('Import thành công','Đã tạo profile mới từ file JSON trên máy.');}
    public function executeConfirmedAction():void
    {
        $action=$this->pendingConfirmAction;$value=$this->pendingConfirmValue;$this->confirmOpen=false;$this->pendingConfirmAction='';$this->pendingConfirmValue='';
        if($action==='delete-profile'&&$value!==''){$profileId=(int)$value;app(PriceListExportProfileService::class)->delete((int)auth('admin')->id(),$profileId);$this->profileId=null;$this->refreshProfiles();$this->loadProfile();$this->notify('Đã xóa profile','Profile đã được xóa thành công.');return;}
        if($action==='delete-json'&&$value!==''){$jsonName=$value;app(PriceListExportJsonLibrary::class)->delete((int)auth('admin')->id(),$jsonName);$this->refreshJsonFiles();$this->selectedJsonFile=null;$this->notify('Đã xóa JSON','File cấu hình JSON đã được xóa khỏi server.');}
    }
    public function closeNotice():void{$this->noticeOpen=false;}

    private function notify(string $title,string $message):void{$this->noticeTitle=$title;$this->noticeMessage=$message;$this->noticeOpen=true;}
    private function openConfirmation(string $title,string $action,string $value=''):void{$this->noticeTitle=$title;$this->pendingConfirmAction=$action;$this->pendingConfirmValue=$value;$this->confirmOpen=true;}
    private function refreshJsonFiles():void{$this->jsonFiles=app(PriceListExportJsonLibrary::class)->list((int)auth('admin')->id());}
    private function selectedOrder():array{return array_values(array_filter($this->columnOrder,fn($key)=>$this->selectedColumns[$key]??false));}
    private function replaceSelectedOrder(array $selected):void{$unselected=array_values(array_filter($this->columnOrder,fn($key)=>!($this->selectedColumns[$key]??false)));$this->columnOrder=[...$selected,...$unselected];}
    private function compactSelectedColumns():void{$this->replaceSelectedOrder($this->selectedOrder());}
    private function loadColumnDraft(?string $key):void{if(!$key||!isset(PriceListExportProfileService::COLUMNS[$key])){$this->columnDraft=[];return;}$d=PriceListExportProfileService::COLUMNS[$key];$this->columnDraft=['key'=>$key,'header'=>$this->headers[$key]??$d['label'],'alignment'=>$this->alignments[$key]??$d['align'],'width'=>(int)($this->widths[$key]??$d['width']),'data_type'=>$this->dataTypes[$key]??$d['type'],'decimals'=>(int)($this->decimals[$key]??0)];}
    private function commitColumnDraft():void{$key=$this->columnDraft['key']??null;if(!$key||$key!==$this->activeColumnKey||!isset(PriceListExportProfileService::COLUMNS[$key]))return;$d=PriceListExportProfileService::COLUMNS[$key];$this->headers[$key]=trim((string)($this->columnDraft['header']??$d['label']))?:$d['label'];$alignment=(string)($this->columnDraft['alignment']??$d['align']);$this->alignments[$key]=in_array($alignment,['left','center','right'],true)?$alignment:$d['align'];$this->widths[$key]=max(40,min(400,(int)($this->columnDraft['width']??$d['width'])));$type=(string)($this->columnDraft['data_type']??$d['type']);$this->dataTypes[$key]=in_array($type,['auto','string','number','date'],true)?$type:$d['type'];$this->decimals[$key]=max(0,min(6,(int)($this->columnDraft['decimals']??0)));}
    private function storeMedia($upload,string $kind):string{return $upload->store('pharma/price-list-export/'.auth('admin')->id().'/'.$kind,'public');} private function deleteMedia(?string $path):void{if($path)Storage::disk('public')->delete($path);}
    private function refreshProfiles():void{$this->profiles=app(PriceListExportProfileService::class)->profilesForUser((int)auth('admin')->id());} private function loadProfile():void{$this->apply(app(PriceListExportProfileService::class)->forUser((int)auth('admin')->id(),$this->profileId));}
    private function apply(array $p):void{$this->profileId=$p['profile_id'];$this->profileName=$p['profile_name'];$this->isDefault=$p['is_default'];$this->columnOrder=$p['column_order'];$this->columnGroupsMap=[];foreach(array_keys(PriceListExportProfileService::COLUMNS)as$k)$this->columnGroupsMap[$k]=PriceListExportProfileService::COLUMNS[$k]['group'];$lookup=array_fill_keys($p['selected_columns'],true);$this->selectedColumns=[];foreach($this->columnOrder as$k)$this->selectedColumns[$k]=isset($lookup[$k]);$this->compactSelectedColumns();$this->headers=$p['headers'];$this->alignments=$p['alignments'];$this->widths=$p['widths'];$this->dataTypes=$p['data_types'];$this->decimals=$p['decimals'];$this->headerFooter=$p['header_footer'];$this->headerFooter['logo_width_cm']=$this->headerFooter['logo_width_cm']??4.65;$this->headerFooter['logo_height_cm']=$this->headerFooter['logo_height_cm']??2.82;$this->headerFooter['signature_width_cm']=$this->headerFooter['signature_width_cm']??4.00;$this->headerFooter['signature_height_cm']=$this->headerFooter['signature_height_cm']??3.60;$this->pageSetup=$p['page_setup'];$this->pageSetup['table_border']=$this->pageSetup['table_border']??'thin';$this->pageSetup['header_background']=$this->pageSetup['header_background']??'#E8EEF9';$this->pageSetup['header_text_color']=$this->pageSetup['header_text_color']??'#111827';$this->logoPath=$p['logo_path'];$this->signaturePath=$p['signature_path'];$this->originalLogoPath=$this->logoPath;$this->originalSignaturePath=$this->signaturePath;$this->removeLogoRequested=false;$this->removeSignatureRequested=false;$this->activeColumnKey=$this->activeColumnKey&&isset(PriceListExportProfileService::COLUMNS[$this->activeColumnKey])?$this->activeColumnKey:($this->selectedOrder()[0]??$this->columnOrder[0]??null);$this->loadColumnDraft($this->activeColumnKey);}
    public function render():View{return view('Pharma::livewire.price-list.export-configurator-v32',['columnDefinitions'=>PriceListExportProfileService::COLUMNS,'columnGroups'=>PriceListExportProfileService::GROUPS]);}
}
