<?php

namespace Modules\ClientPortal\Applications\Muasamcong\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\ClientPortal\Jobs\GeneratePriceListExport;
use Modules\ClientPortal\Jobs\GeneratePriceListPdf;
use Modules\ClientPortal\Jobs\SendPriceListExportEmail;
use Modules\ClientPortal\Models\PriceListExport;
use Modules\Muasamcong\Models\PricingResult;
use Modules\Muasamcong\Models\PricingWishlist;
use Modules\Muasamcong\Models\SyncedExportProfile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MuasamcongPriceListController extends Controller
{
    public function index(Request $request): View
    {
        $user=$request->user('web'); abort_if(!$user,401);
        $source=in_array($request->query('source'),['synced','wishlist'],true)?$request->query('source'):'synced';
        $itemSearch=trim((string)$request->query('item_q','')); $exportSearch=trim((string)$request->query('q',''));
        $exportStatus=in_array($request->query('status'),['queued','processing','completed','failed'],true)?$request->query('status'):null;
        $profiles=SyncedExportProfile::query()->orderByDesc('is_default')->orderBy('name')->get();
        $items=$source==='wishlist'?PricingWishlist::query()->where('user_id',$user->getKey())->when($itemSearch!=='',fn($q)=>$q->where(function($n)use($itemSearch){$v='%'.$itemSearch.'%';$n->where('medicine_name','like',$v)->orWhere('active_ingredient','like',$v)->orWhere('ma_tbmt','like',$v);}))->latest()->paginate(20,['*'],'items_page')->withQueryString():PricingResult::query()->when($itemSearch!=='',fn($q)=>$q->where(function($n)use($itemSearch){$v='%'.$itemSearch.'%';$n->where('ten_thuoc','like',$v)->orWhere('ten_hoat_chat','like',$v)->orWhere('ma_tbmt','like',$v);}))->latest('synced_at')->paginate(20,['*'],'items_page')->withQueryString();
        $exports=PriceListExport::query()->where('user_id',$user->getKey())->when($exportStatus,fn($q)=>$q->where('status',$exportStatus))->when($exportSearch!=='',fn($q)=>$q->where(function($n)use($exportSearch){$v='%'.$exportSearch.'%';$n->where('file_name','like',$v)->orWhere('source','like',$v);}))->latest()->paginate(12,['*'],'exports_page')->withQueryString();
        $editing=null;$selectedIds=[];$selectedProfileId=$profiles->firstWhere('is_default',true)?->id??$profiles->first()?->id;
        if($request->filled('edit')){$editing=$this->exportRecord((string)$request->query('edit'));$this->owner($request,$editing);$source=$editing->source;$selectedIds=array_map('strval',(array)$editing->selected_ids);$selectedProfileId=$editing->profile_id;}
        $canExport=$user->can('client.muasamcong.price-list.export');
        return view('ClientPortal::applications.muasamcong.price-list',compact('source','profiles','items','exports','canExport','editing','selectedIds','selectedProfileId','itemSearch','exportSearch','exportStatus'));
    }

    public function store(Request $request): RedirectResponse
    { $user=$this->exportUser($request);[$data,$profile,$ids]=$this->validatedExportRequest($request,$user->getKey());$export=PriceListExport::create(['user_id'=>$user->getKey(),'profile_id'=>$profile->id,'source'=>$data['source'],'selected_ids'=>$ids,'status'=>'queued']);GeneratePriceListExport::dispatch($export->id);return redirect()->route('client.muasamcong.price-list')->with('queue_export_id',$export->id)->with('status','Đã đưa Bảng Giá vào hàng đợi. Bạn có thể tiếp tục sử dụng ứng dụng.'); }
    public function edit(Request $request,string $exportId): RedirectResponse { $r=$this->exportRecord($exportId);$this->owner($request,$r);return redirect()->route('client.muasamcong.price-list',['edit'=>$r->id,'source'=>$r->source]); }
    public function recreate(Request $request,string $exportId): RedirectResponse { $user=$this->exportUser($request);$r=$this->exportRecord($exportId);$this->owner($request,$r);abort_unless(SyncedExportProfile::whereKey($r->profile_id)->exists(),422,'Cấu hình Admin của Bảng Giá này không còn tồn tại.');$copy=PriceListExport::create(['user_id'=>$user->getKey(),'profile_id'=>$r->profile_id,'source'=>$r->source,'selected_ids'=>$r->selected_ids,'status'=>'queued']);GeneratePriceListExport::dispatch($copy->id);return back()->with('queue_export_id',$copy->id)->with('status','Đã tạo lại Bảng Giá bằng cấu hình Admin hiện tại.'); }
    public function destroy(Request $request,string $exportId): RedirectResponse { $r=$this->exportRecord($exportId);$this->owner($request,$r);foreach([$r->file_path,$r->pdf_path] as $path)if($path)Storage::disk('local')->delete($path);$r->delete();return redirect()->route('client.muasamcong.price-list')->with('status','Đã xóa Bảng Giá cùng file Excel/PDF tương ứng.'); }

    public function status(Request $request,string $exportId): JsonResponse
    { $r=$this->exportRecord($exportId);$this->owner($request,$r);return response()->json(['status'=>$r->status,'status_label'=>$this->statusLabel($r->status),'items_count'=>$r->items_count,'error'=>$r->error_message,'download_url'=>$this->fileAvailable($r)?route('client.muasamcong.price-list.download',['exportId'=>$r->id]):null,'file_available'=>$this->fileAvailable($r),'pdf_status'=>$r->pdf_status,'pdf_error'=>$r->pdf_error_message,'pdf_available'=>$this->pdfAvailable($r),'pdf_download_url'=>$this->pdfAvailable($r)?route('client.muasamcong.price-list.pdf-download',['exportId'=>$r->id]):null]); }
    public function download(Request $request,string $exportId): StreamedResponse { $r=$this->exportRecord($exportId);$this->owner($request,$r);abort_unless($this->fileAvailable($r),404,'File Excel không còn tồn tại trên storage. Vui lòng tạo lại.');return Storage::disk('local')->download($r->file_path,$r->file_name?:basename($r->file_path)); }
    public function queuePdf(Request $request,string $exportId): RedirectResponse { $this->exportUser($request);$r=$this->exportRecord($exportId);$this->owner($request,$r);abort_unless($this->fileAvailable($r),409,'File Excel chưa sẵn sàng.');if(!$this->pdfAvailable($r)&&$r->pdf_status!=='processing'){$r->update(['pdf_status'=>'queued','pdf_error_message'=>null]);GeneratePriceListPdf::dispatch($r->id);}return back()->with('status',$this->pdfAvailable($r)?'PDF đã sẵn sàng.':'Đã đưa yêu cầu chuyển PDF vào Queue.'); }
    public function downloadPdf(Request $request,string $exportId): StreamedResponse { $r=$this->exportRecord($exportId);$this->owner($request,$r);abort_unless($this->pdfAvailable($r),404,'File PDF chưa sẵn sàng.');return Storage::disk('local')->download($r->pdf_path,$r->pdf_name?:basename($r->pdf_path)); }

    public function share(Request $request,string $exportId): JsonResponse
    { $this->exportUser($request);$r=$this->exportRecord($exportId);$this->owner($request,$r);abort_unless($this->fileAvailable($r),409,'File Excel chưa sẵn sàng hoặc không còn tồn tại.');$data=$request->validate(['recipient'=>'nullable|string|max:200']);if(!$r->share_token)$r->update(['share_token'=>Str::random(64)]);$history=(array)$r->fresh()->delivery_history;$history[]=['channel'=>'share','recipient'=>trim((string)($data['recipient']??''))?:'Link chia sẻ','formats'=>['excel'],'sent_at'=>now()->toIso8601String()];$r->update(['delivery_history'=>array_slice($history,-20)]);return response()->json(['url'=>route('public.muasamcong.price-list',$r->share_token)]); }
    public function publicDownload(string $token): StreamedResponse { $r=PriceListExport::where('share_token',$token)->where('status','completed')->firstOrFail();abort_unless($this->fileAvailable($r),404,'File Excel không còn tồn tại.');return Storage::disk('local')->download($r->file_path,$r->file_name?:basename($r->file_path)); }

    public function email(Request $request,string $exportId): RedirectResponse
    { $this->exportUser($request);$r=$this->exportRecord($exportId);$this->owner($request,$r);abort_unless($this->fileAvailable($r),409,'File Excel chưa sẵn sàng.');$data=$request->validate(['email'=>'required|email|max:200','content'=>'required|string|max:5000','attach_excel'=>'nullable|boolean','attach_pdf'=>'nullable|boolean']);$excel=$request->boolean('attach_excel');$pdf=$request->boolean('attach_pdf');abort_if(!$excel&&!$pdf,422,'Vui lòng chọn ít nhất Excel hoặc PDF.');abort_if($pdf&&!$this->pdfAvailable($r),422,'PDF chưa sẵn sàng. Hãy chuyển PDF trước khi gửi.');SendPriceListExportEmail::dispatch($r->id,$data['email'],$data['content'],$excel,$pdf);return back()->with('status','Đã đưa yêu cầu Gửi bảng giá vào hàng đợi.'); }

    private function validatedExportRequest(Request $request,int|string $userId): array { $data=$request->validate(['source'=>'required|in:synced,wishlist','profile_id'=>'required|integer|exists:muasamcong_synced_export_profiles,id','selected_ids'=>'required|array|min:1|max:200','selected_ids.*'=>'required|string|max:64']);$profile=SyncedExportProfile::findOrFail($data['profile_id']);$ids=array_values(array_unique(array_map('strval',$data['selected_ids'])));$allowed=$data['source']==='wishlist'?PricingWishlist::where('user_id',$userId)->whereIn('id',$ids)->pluck('id')->map(fn($v)=>(string)$v)->all():PricingResult::whereIn('source_id',$ids)->pluck('source_id')->map(fn($v)=>(string)$v)->all();abort_if(count($allowed)!==count($ids),403);return[$data,$profile,$ids]; }
    private function exportRecord(string $id): PriceListExport { return PriceListExport::query()->whereKey($id)->firstOrFail(); }
    private function fileAvailable(PriceListExport $e): bool { return $e->status==='completed'&&is_string($e->file_path)&&trim($e->file_path)!==''&&Storage::disk('local')->exists($e->file_path); }
    private function pdfAvailable(PriceListExport $e): bool { return $e->pdf_status==='completed'&&is_string($e->pdf_path)&&trim($e->pdf_path)!==''&&Storage::disk('local')->exists($e->pdf_path); }
    private function owner(Request $request,PriceListExport $e): void { $u=$request->user('web');abort_if(!$u||(int)$e->user_id!==(int)$u->getKey(),403); }
    private function exportUser(Request $request) { $u=$request->user('web');abort_if(!$u,401);abort_unless($u->can('client.muasamcong.price-list.export'),403);return $u; }
    private function statusLabel(string $status): string { return match($status){'queued'=>'Đang chờ','processing'=>'Đang tạo','completed'=>'Hoàn thành','failed'=>'Không thành công',default=>$status}; }
}
