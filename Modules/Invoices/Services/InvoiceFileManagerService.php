<?php

namespace Modules\Invoices\Services;

use Illuminate\Support\Str;
use Modules\Invoices\Models\InvoiceFile;
use Modules\Invoices\Models\Invoices;
use RuntimeException;
use ZipArchive;

class InvoiceFileManagerService
{
    public function __construct(private readonly InvoiceService $invoiceService, private readonly InvoiceFileService $fileService) {}

    public function recordAvailable(Invoices $invoice, string $path, string $provider): InvoiceFile
    {
        return InvoiceFile::query()->updateOrCreate(['invoice_id'=>$invoice->getKey()],['provider'=>$provider,'status'=>'available','path'=>$this->relativeStoragePath($path),'size'=>is_file($path)?filesize($path):null,'last_error'=>null,'downloaded_at'=>now()]);
    }

    public function recordFailure(Invoices $invoice, string $provider, string $error): InvoiceFile
    {
        return InvoiceFile::query()->updateOrCreate(['invoice_id'=>$invoice->getKey()],['provider'=>$provider,'status'=>'error','path'=>null,'size'=>null,'last_error'=>Str::limit($error,2000,''),'downloaded_at'=>null]);
    }

    public function summary(array $filters): array
    {
        $base = $filters; $base['pdf_status']='all';
        $invoiceIds = $this->invoiceService->filteredBuilder($base)->select('id');
        $total=(clone $invoiceIds)->count();
        $available=InvoiceFile::query()->whereIn('invoice_id',clone $invoiceIds)->where('status','available')->count();
        $errors=InvoiceFile::query()->whereIn('invoice_id',clone $invoiceIds)->where('status','error')->count();
        $size=(int)InvoiceFile::query()->whereIn('invoice_id',clone $invoiceIds)->where('status','available')->sum('size');
        return ['total'=>$total,'available'=>$available,'error'=>$errors,'missing'=>max(0,$total-$available-$errors),'size'=>$size];
    }

    public function errorDetails(array $filters, int $limit=10): array
    {
        $base=$filters; $base['pdf_status']='all';
        $ids=$this->invoiceService->filteredBuilder($base)->select('id');
        return InvoiceFile::query()->with('invoice:id,invoice_number,symbol,tax_code,name,invoice_type,issued_date')->whereIn('invoice_id',$ids)->where('status','error')->latest('updated_at')->limit(max(1,min($limit,50)))->get()->map(fn($file)=>['invoice_id'=>$file->invoice_id,'invoice_number'=>$file->invoice?->invoice_number,'partner'=>$file->invoice?->name,'provider'=>$file->provider,'error'=>$file->last_error,'updated_at'=>$file->updated_at?->format('d/m/Y H:i')])->all();
    }

    public function reconcile(array $filters, int $limit=1000): array
    {
        $base=$filters; $base['pdf_status']='all';
        $invoices=$this->invoiceService->filteredBuilder($base)->orderByDesc('issued_date')->limit(max(1,min($limit,5000)))->get();
        $available=0;$missing=0;
        foreach($invoices as $invoice){
            $file=InvoiceFile::query()->where('invoice_id',$invoice->getKey())->first();
            if($this->fileService->existsForInvoice($invoice)){
                $path=$this->fileService->pdfPathForInvoice($invoice);$detected=str_contains(str_replace('\\','/',$path),'/hoadon_temp/')?'legacy':'local';
                $this->recordAvailable($invoice,$path,$file?->provider?:$detected);$available++;continue;
            }
            if($file?->status==='available')$file->update(['status'=>'missing','path'=>null,'size'=>null,'downloaded_at'=>null]);
            $missing++;
        }
        return ['scanned'=>$invoices->count(),'available'=>$available,'missing'=>$missing];
    }

    public function missingInvoiceIds(array $filters,int $limit=25):array
    {
        $base=$filters;$base['pdf_status']='all';
        return $this->invoiceService->filteredBuilder($base)->whereDoesntHave('file',fn($q)=>$q->where('status','available'))->orderByDesc('issued_date')->limit(max(1,min($limit,100)))->pluck('id')->map(fn($id)=>(int)$id)->all();
    }

    public function errorInvoiceIds(array $filters,int $limit=25):array
    {
        $base=$filters;$base['pdf_status']='all';
        return $this->invoiceService->filteredBuilder($base)->whereHas('file',fn($q)=>$q->where('status','error'))->orderByDesc('issued_date')->limit(max(1,min($limit,100)))->pluck('id')->map(fn($id)=>(int)$id)->all();
    }

    public function storageBreakdown(array $filters): array
    {
        $base=$filters;$base['pdf_status']='all';
        $ids=$this->invoiceService->filteredBuilder($base)->select('id');
        return InvoiceFile::query()->join('invoices','invoices.id','=','invoice_files.invoice_id')->whereIn('invoice_files.invoice_id',$ids)->where('invoice_files.status','available')->selectRaw('YEAR(invoices.issued_date) as year, MONTH(invoices.issued_date) as month, invoices.invoice_type, COUNT(*) as files, COALESCE(SUM(invoice_files.size),0) as bytes')->groupByRaw('YEAR(invoices.issued_date), MONTH(invoices.issued_date), invoices.invoice_type')->orderByDesc('year')->orderByDesc('month')->get()->toArray();
    }

    public function deleteFiles(array $filters): array
    {
        $base=$filters;$base['pdf_status']='all';
        $invoices=$this->invoiceService->filteredBuilder($base)->with('file')->get();$deleted=0;$failed=0;
        foreach($invoices as $invoice){
            if(!$this->fileService->existsForInvoice($invoice)) continue;
            $path=$this->fileService->pdfPathForInvoice($invoice);
            if(is_file($path) && @unlink($path)){
                $invoice->file?->update(['status'=>'missing','path'=>null,'size'=>null,'last_error'=>null,'downloaded_at'=>null]);$deleted++;
            } else $failed++;
        }
        return ['deleted'=>$deleted,'failed'=>$failed];
    }

    public function createZip(array $filters):array
    {
        if(!class_exists(ZipArchive::class))throw new RuntimeException('PHP chưa cài extension zip (ZipArchive).');
        $base=$filters;$base['pdf_status']='all';$invoices=$this->invoiceService->filter($base);$dir=storage_path('app/invoices/archives');
        if(!is_dir($dir)&&!mkdir($dir,0775,true)&&!is_dir($dir))throw new RuntimeException('Không thể tạo thư mục lưu ZIP hóa đơn. Kiểm tra quyền ghi storage/app/invoices.');
        $filename=$this->archiveFilename($base);$path=$dir.'/'.$filename;$zip=new ZipArchive();
        if($zip->open($path,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true)throw new RuntimeException('Không thể tạo file ZIP hóa đơn.');
        $added=0;foreach($invoices as $invoice){if(!$this->fileService->existsForInvoice($invoice))continue;$pdf=$this->fileService->pdfPathForInvoice($invoice);$zip->addFile($pdf,$this->fileService->filenameForInvoice($invoice));$added++;}$zip->close();
        if($added===0){@unlink($path);throw new RuntimeException('Bộ lọc hiện tại chưa có PDF nào để đóng gói ZIP.');}
        return ['path'=>$path,'filename'=>$filename,'count'=>$added];
    }

    private function archiveFilename(array $filters):string
    {
        $type=match($filters['invoice_type']??null){'sold'=>'ban-ra','purchase'=>'mua-vao',default=>'tat-ca'};$from=preg_replace('/[^0-9-]/','',(string)($filters['issued_date_from']??''))?:'all';$to=preg_replace('/[^0-9-]/','',(string)($filters['issued_date_to']??''))?:'all';return "hoa-don_{$type}_{$from}_{$to}.zip";
    }
    private function relativeStoragePath(string $path):string{$root=rtrim(str_replace('\\','/',storage_path('app')),'/').'/';$normalized=str_replace('\\','/',$path);return str_starts_with($normalized,$root)?substr($normalized,strlen($root)):basename($normalized);}
}
