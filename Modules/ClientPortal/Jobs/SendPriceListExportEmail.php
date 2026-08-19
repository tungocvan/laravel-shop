<?php
namespace Modules\ClientPortal\Jobs;
use Illuminate\Bus\Queueable; use Illuminate\Contracts\Queue\ShouldQueue; use Illuminate\Foundation\Bus\Dispatchable; use Illuminate\Queue\InteractsWithQueue; use Illuminate\Queue\SerializesModels; use Illuminate\Support\Facades\Mail; use Illuminate\Support\Facades\Storage; use Modules\ClientPortal\Models\PriceListExport;
class SendPriceListExportEmail implements ShouldQueue
{
 use Dispatchable,InteractsWithQueue,Queueable,SerializesModels;
 public function __construct(public string $exportId,public string $email){}
 public function handle(): void { $e=PriceListExport::findOrFail($this->exportId); if($e->status!=='completed'||!$e->file_path||!Storage::disk('local')->exists($e->file_path))return; $path=Storage::disk('local')->path($e->file_path); Mail::raw('Bảng Giá được xuất từ ứng dụng Mua sắm công. File Excel được đính kèm trong email này.',function($m)use($path,$e){$m->to($this->email)->subject('Bảng Giá Mua sắm công')->attach($path,['as'=>$e->file_name]);}); }
}
