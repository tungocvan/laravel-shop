<?php

namespace Modules\Invoices\Livewire;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Invoices\Jobs\ProcessGdtInvoicesJob;
use Modules\Invoices\Services\GdtApiService;
use Modules\Invoices\Services\GdtInvoiceService;
use Modules\Invoices\Services\InvoiceImportService;
use RuntimeException;

class SearchHoadon extends Component
{
    use WithFileUploads;

    protected GdtInvoiceService $invoiceService;
    protected InvoiceImportService $importService;
    protected GdtApiService $apiService;

    public $start_date;
    public $end_date;
    public $vatIn = false;
    public $useQueue = false;
    public array $logs = [];
    public ?string $syncId = null;
    public string $syncState = 'idle';
    public ?string $syncMessage = null;
    public ?string $syncFile = null;
    public array $availableFiles = [];
    public ?string $selectedFile = null;
    public $uploadFile;
    public string $googleDriveUrl = '';

    public function boot(GdtInvoiceService $invoiceService, InvoiceImportService $importService, GdtApiService $apiService): void
    {
        $this->invoiceService = $invoiceService;
        $this->importService = $importService;
        $this->apiService = $apiService;
    }

    public function mount(): void
    {
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');
        $this->refreshAvailableFiles();
    }

    private function log(string $msg): void
    {
        $this->logs[] = '['.now()->format('H:i:s').'] '.$msg;
        $this->dispatch('scroll-bottom');
    }

    public function run(): void
    {
        $this->authorizePermission('invoices-create');
        $this->validate(['start_date'=>['required','date'],'end_date'=>['required','date','after_or_equal:start_date'],'vatIn'=>['boolean'],'useQueue'=>['boolean']]);
        $this->logs=[];$this->syncMessage=null;$this->syncFile=null;$this->log('Bắt đầu xử lý…');

        if ($this->useQueue) {
            $this->syncId=(string)Str::uuid();$this->syncState='queued';
            Cache::put($this->statusKey(), ['state'=>'queued','message'=>'Đã đưa tác vụ vào hàng đợi.','logs'=>['['.now()->format('H:i:s').'] Đã đưa tác vụ vào hàng đợi.'],'started_at'=>now()->toIso8601String(),'file'=>null,'direction'=>(bool)$this->vatIn?'vat_in':'vat_out'], now()->addHours(24));
            ProcessGdtInvoicesJob::dispatch($this->start_date,$this->end_date,(bool)$this->vatIn,$this->syncId);
            $this->pollStatus();return;
        }

        $this->syncState='processing';
        try {
            $file=$this->invoiceService->processRange($this->start_date,$this->end_date,fn($msg)=>$this->log($msg),(bool)$this->vatIn);
            if(!is_string($file)||!is_file($file)||!is_readable($file)) throw new RuntimeException('Đồng bộ kết thúc nhưng không tạo được file Excel trên server.');
            $this->syncState='completed';$this->syncMessage='Đồng bộ hoàn tất và file Excel đã được tạo.';$this->syncFile=basename($file);$this->log('Hoàn tất xử lý!');$this->refreshAvailableFiles();
        } catch (\Throwable $exception) { $this->syncState='failed';$this->syncMessage=$exception->getMessage();$this->log('❌ '.$exception->getMessage()); }
        if(!$this->apiService->hasToken()){session()->flash('status','Token đã hết hạn.');$this->redirectRoute('admin.invoices.create-token');}
    }

    public function pollStatus(): void
    {
        if(!$this->syncId)return;$status=Cache::get($this->statusKey());if(!is_array($status))return;
        $this->syncState=(string)($status['state']??'queued');$this->syncMessage=$status['message']??null;$this->syncFile=$status['file']??null;$this->logs=array_values($status['logs']??[]);
        if(in_array($this->syncState,['completed','failed'],true))$this->refreshAvailableFiles();
    }

    public function refreshAvailableFiles(): void
    {
        $files=[];
        foreach(['vat_out'=>'Bán ra','vat_in'=>'Mua vào'] as $direction=>$label){$folder=$this->syncFolder($direction);if(!is_dir($folder))continue;foreach(glob($folder.'/*.{xlsx,csv}',GLOB_BRACE)?:[] as $path){$filename=basename($path);$detected=$this->directionFromFilename($filename);if($detected!==$direction)continue;$files[]=['token'=>$direction.'|'.$filename,'name'=>$filename,'direction'=>$direction,'type_label'=>$label,'size'=>filesize($path)?:0,'modified_at'=>date('Y-m-d H:i:s',filemtime($path)?:time()),'mtime'=>filemtime($path)?:0];}}
        usort($files,fn(array $a,array $b)=>$b['mtime']<=>$a['mtime']);$this->availableFiles=array_map(function(array $file){unset($file['mtime']);return $file;},array_slice($files,0,50));
        if($this->selectedFile&&!collect($this->availableFiles)->contains('token',$this->selectedFile))$this->selectedFile=null;
    }

    public function updatedVatIn(): void { $this->refreshAvailableFiles(); }

    public function importSelectedFile(): void
    {
        $this->authorizePermission('invoices-create');[$direction,,$path]=$this->resolveSelectedFile();abort_unless(is_readable($path),404);$this->runImport($path,$direction==='vat_in'?'purchase':'sold');
    }

    public function downloadSelectedFile()
    {
        $this->authorizePermission('invoices-create');[,$filename,$path]=$this->resolveSelectedFile();abort_unless(is_readable($path),404);return response()->download($path,$filename);
    }

    public function deleteSelectedFile(): void
    {
        $this->authorizePermission('invoices-create');[,$filename,$path]=$this->resolveSelectedFile();if(!@unlink($path)){$this->log('❌ Không thể xóa file: '.$filename);return;}if($this->syncFile===$filename)$this->syncFile=null;$this->selectedFile=null;$this->refreshAvailableFiles();$this->log('🗑️ Đã xóa file Excel: '.$filename);
    }

    public function stageUploadedFile(): void
    {
        $this->authorizePermission('invoices-create');$this->validate(['uploadFile'=>['required','file','mimes:xlsx,csv','max:20480']]);
        $originalName=$this->uploadFile->getClientOriginalName();$direction=$this->directionFromFilename($originalName);
        if($direction===null){$this->addError('uploadFile',$this->invalidFilenameMessage($originalName));return;}
        $folder=$this->ensureSyncFolder($direction);$filename=$this->uniqueFilename($folder,$this->sanitizeFilename($originalName));$target=$folder.DIRECTORY_SEPARATOR.$filename;
        if(!@copy($this->uploadFile->getRealPath(),$target))throw new RuntimeException('Không thể lưu file upload vào kho file đồng bộ. Kiểm tra quyền ghi thư mục storage.');
        $this->reset('uploadFile');$this->resetValidation('uploadFile');$this->refreshAvailableFiles();$this->selectedFile=$direction.'|'.$filename;$this->log('📥 Đã nhận dạng '.($direction==='vat_in'?'Mua vào':'Bán ra').' và đưa file vào File đã đồng bộ: '.$filename);
    }

    public function stageGoogleDriveFile(): void
    {
        $this->authorizePermission('invoices-create');$this->validate(['googleDriveUrl'=>['required','url','max:2048']]);
        $fileId=$this->extractGoogleDriveFileId($this->googleDriveUrl);if($fileId===null){$this->addError('googleDriveUrl','Link Google Drive không hợp lệ hoặc không phải link chia sẻ file.');return;}
        $response=Http::connectTimeout(10)->timeout(60)->withOptions(['allow_redirects'=>true])->get('https://drive.usercontent.google.com/download',['id'=>$fileId,'export'=>'download','confirm'=>'t']);
        if(!$response->successful())throw new RuntimeException('Không thể tải file từ Google Drive. Hãy kiểm tra quyền chia sẻ công khai của file.');
        $body=$response->body();if($body===''||strlen($body)>20*1024*1024)throw new RuntimeException('File Google Drive rỗng hoặc vượt quá giới hạn 20 MB.');
        $filename=$this->filenameFromGoogleResponse($response->header('Content-Disposition'),$response->header('Content-Type'),$fileId);$direction=$this->directionFromFilename($filename);
        if($direction===null){$this->addError('googleDriveUrl',$this->invalidFilenameMessage($filename).' Hãy đổi tên file trên Google Drive rồi thử lại.');return;}
        $folder=$this->ensureSyncFolder($direction);$filename=$this->uniqueFilename($folder,$filename);$target=$folder.DIRECTORY_SEPARATOR.$filename;$temp=$target.'.part-'.Str::random(8);
        if(file_put_contents($temp,$body,LOCK_EX)===false||!@rename($temp,$target)){@unlink($temp);throw new RuntimeException('Không thể lưu file Google Drive vào kho file đồng bộ. Kiểm tra quyền ghi thư mục storage.');}
        $this->googleDriveUrl='';$this->resetValidation('googleDriveUrl');$this->refreshAvailableFiles();$this->selectedFile=$direction.'|'.$filename;$this->log('☁️ Đã nhận dạng '.($direction==='vat_in'?'Mua vào':'Bán ra').' và đưa file Google Drive vào File đã đồng bộ: '.$filename);
    }

    private function runImport(string $path,string $invoiceType): void
    {
        $this->logs=[];$this->log('Bắt đầu import: '.basename($path));try{$count=$this->importService->import($path,$invoiceType,fn($message)=>$this->log($message));$this->log("🎯 Import hoàn tất: {$count} hóa đơn mới.");}catch(\Throwable $exception){$this->log('❌ '.$exception->getMessage());}
    }

    private function resolveSelectedFile(): array
    {
        $this->validate(['selectedFile'=>['required','string','max:320']]);[$direction,$filename]=array_pad(explode('|',(string)$this->selectedFile,2),2,null);abort_unless(in_array($direction,['vat_in','vat_out'],true),422);abort_unless(is_string($filename)&&basename($filename)===$filename,422);abort_unless(in_array(strtolower(pathinfo($filename,PATHINFO_EXTENSION)),['xlsx','csv'],true),422);abort_unless($this->directionFromFilename($filename)===$direction,422,'Tên file không khớp loại hóa đơn.');$path=$this->syncFolder($direction).DIRECTORY_SEPARATOR.$filename;abort_unless(is_file($path),404);return[$direction,$filename,$path];
    }

    private function directionFromFilename(string $filename): ?string
    {
        $name=strtolower(basename(trim($filename)));
        if(str_starts_with($name,'vat_in_'))return 'vat_in';
        if(str_starts_with($name,'vat_out_'))return 'vat_out';
        return null;
    }

    private function invalidFilenameMessage(string $filename): string
    {
        return 'Không xác định được loại hóa đơn từ tên file "'.basename($filename).'". Vui lòng đổi tên bắt đầu bằng vat_in_ (Mua vào) hoặc vat_out_ (Bán ra), ví dụ vat_in_2026-08.xlsx.';
    }

    private function ensureSyncFolder(string $direction): string
    {
        $folder=$this->syncFolder($direction);if(!is_dir($folder)&&!@mkdir($folder,0775,true)&&!is_dir($folder))throw new RuntimeException('Không thể tạo thư mục lưu file đồng bộ: '.$folder);if(!is_writable($folder))throw new RuntimeException('Thư mục lưu file đồng bộ không có quyền ghi: '.$folder);return $folder;
    }

    private function sanitizeFilename(string $filename): string
    {
        $filename=basename(trim($filename));$extension=strtolower(pathinfo($filename,PATHINFO_EXTENSION));if(!in_array($extension,['xlsx','csv'],true))throw new RuntimeException('Chỉ hỗ trợ file XLSX hoặc CSV.');$name=pathinfo($filename,PATHINFO_FILENAME);$name=preg_replace('/[^A-Za-z0-9._-]+/u','-',$name)?:'invoice-file';$name=trim($name,'-._')?:'invoice-file';return Str::limit($name,120,'').'.'.$extension;
    }

    private function uniqueFilename(string $folder,string $filename): string
    {
        if(!is_file($folder.DIRECTORY_SEPARATOR.$filename))return $filename;$extension=pathinfo($filename,PATHINFO_EXTENSION);$name=pathinfo($filename,PATHINFO_FILENAME);return $name.'_'.now()->format('Ymd_His').'_'.Str::lower(Str::random(4)).'.'.$extension;
    }

    private function extractGoogleDriveFileId(string $url): ?string
    {
        $parts=parse_url(trim($url));$host=strtolower((string)($parts['host']??''));if(!in_array($host,['drive.google.com','docs.google.com'],true))return null;$path=(string)($parts['path']??'');if(preg_match('~/file/d/([A-Za-z0-9_-]+)~',$path,$matches))return $matches[1];parse_str((string)($parts['query']??''),$query);$id=$query['id']??null;return is_string($id)&&preg_match('/^[A-Za-z0-9_-]+$/',$id)?$id:null;
    }

    private function filenameFromGoogleResponse(?string $contentDisposition,?string $contentType,string $fileId): string
    {
        $filename=null;if(is_string($contentDisposition)&&preg_match('/filename\*?=(?:UTF-8\'\')?"?([^";]+)"?/i',$contentDisposition,$matches))$filename=rawurldecode(trim($matches[1]));
        if(!is_string($filename)||$filename===''){$type=strtolower((string)$contentType);$extension=str_contains($type,'csv')?'csv':(str_contains($type,'spreadsheetml')||str_contains($type,'excel')?'xlsx':null);if($extension===null)throw new RuntimeException('Google Drive không trả về tên hoặc định dạng XLSX/CSV hợp lệ.');throw new RuntimeException('Google Drive không trả về tên file gốc nên không thể xác định vat_in_ hoặc vat_out_. Hãy bảo đảm file có tên đúng quy ước và được chia sẻ công khai.');}
        return $this->sanitizeFilename($filename);
    }

    private function syncFolder(string $direction): string { $base=trim((string)config('invoices.storage.export_directory','gdt'),'/');return storage_path("app/{$base}/{$direction}"); }
    private function statusKey(): string { return 'invoices:gdt-sync:'.$this->syncId; }
    private function authorizePermission(string $permission): void { abort_unless(auth('admin')->check()&&auth('admin')->user()->can($permission),403); }
    public function render(){return view('Invoices::livewire.search-hoadon');}
}
