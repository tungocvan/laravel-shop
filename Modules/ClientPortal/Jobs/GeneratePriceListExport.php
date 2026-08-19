<?php
namespace Modules\ClientPortal\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Modules\ClientPortal\Models\PriceListExport;
use Modules\Muasamcong\Models\PriceListProfile;
use Modules\Muasamcong\Models\PricingResult;
use Modules\Muasamcong\Models\PricingWishlist;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;
class GeneratePriceListExport implements ShouldQueue
{
    use Dispatchable,InteractsWithQueue,Queueable,SerializesModels;
    public function __construct(public string $exportId) {}
    public function handle(): void
    {
        $export=PriceListExport::findOrFail($this->exportId); $profile=PriceListProfile::findOrFail($export->profile_id);
        $export->update(['status'=>'processing','started_at'=>now()]);
        try {
            $rows=$export->source==='wishlist' ? $this->wishlistRows($export) : $this->syncedRows($export);
            $sheet=(new Spreadsheet())->getActiveSheet(); $sheet->setTitle(substr($profile->sheet_name,0,31)); $columns=$profile->columns;
            $labels=PriceListProfile::availableColumns(); $r=1;
            if($profile->title){$sheet->setCellValue('A'.$r,$profile->title);$sheet->mergeCells('A'.$r.':'.\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($columns)).$r);$r+=2;}
            foreach($columns as $i=>$key)$sheet->setCellValue([$i+1,$r],$labels[$key]??$key); $r++;
            foreach($rows as $row){foreach($columns as $i=>$key){$value=$row[$key]??null;if(is_array($value))$value=implode('; ',array_map('strval',$value));$sheet->setCellValue([$i+1,$r],$value);} $r++;}
            foreach(range(1,count($columns)) as $i)$sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
            $name=$profile->file_prefix.'-'.now()->format('Ymd-His').'.xlsx'; $path='client-portal/price-lists/'.$export->user_id.'/'.$name; $tmp=tempnam(sys_get_temp_dir(),'price-list-').'.xlsx'; (new Xlsx($sheet->getParent()))->save($tmp); Storage::disk('local')->put($path,file_get_contents($tmp)); @unlink($tmp);
            $export->update(['status'=>'completed','items_count'=>count($rows),'file_path'=>$path,'file_name'=>$name,'completed_at'=>now()]);
        } catch(Throwable $e){$export->update(['status'=>'failed','error_message'=>$e->getMessage(),'completed_at'=>now()]);throw $e;}
    }
    private function syncedRows(PriceListExport $e): array { return PricingResult::whereIn('source_id',$e->selected_ids)->get()->map(fn($m)=>$m->toArray())->all(); }
    private function wishlistRows(PriceListExport $e): array { return PricingWishlist::where('user_id',$e->user_id)->whereIn('id',$e->selected_ids)->get()->map(function($m){$s=$m->snapshot??[];return array_merge($s,['ten_thuoc'=>$s['ten_thuoc']??$s['tenThuoc']??$m->medicine_name,'ten_hoat_chat'=>$s['ten_hoat_chat']??$s['tenHoatChat']??$m->active_ingredient,'nong_do'=>$s['nong_do']??$s['nongDo']??$m->strength,'ma_tbmt'=>$s['ma_tbmt']??$s['maTbmt']??$m->ma_tbmt,'don_gia'=>$s['don_gia']??$s['donGia']??null,'winning_name'=>$s['winning_name']??$s['winningName']??[]]);})->all(); }
}
