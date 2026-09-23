<?php
namespace Modules\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\Pharma\Models\InventoryBalance;
use Modules\Pharma\Models\InventoryIssue;
use Modules\Pharma\Models\InventoryReceipt;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\SupplierTracking;
use Modules\Pharma\Services\InventoryService;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class InventoryController extends Controller
{
    public function index(Request $request, InventoryService $inventory): View
    {
        $warehouse=$inventory->defaultWarehouse();
        $costs=$this->activeSupplierCosts();
        $query=InventoryBalance::query()->with('medicine')->where('warehouse_id',$warehouse->id)
            ->when($request->filled('q'),fn($q)=>$q->whereHas('medicine',fn($m)=>$m->where('medicine_code','like','%'.$request->q.'%')->orWhere('name','like','%'.$request->q.'%')))
            ->when($request->boolean('in_stock'),fn($q)=>$q->where('quantity_on_hand','>',0));
        $this->applyExpiryFilter($query,(string)$request->input('expiry_warning',''));
        $balances=$query->orderBy('expiry_date')->paginate(25)->withQueryString();
        $balances->getCollection()->each(function(InventoryBalance $row)use($costs){
            $cost=$costs->get($row->medicine_id);
            $row->setAttribute('average_cost_price',$cost?->average_cost_price !== null ? (float)$cost->average_cost_price : null);
            $row->setAttribute('supplier_cost_count',(int)($cost?->supplier_cost_count ?? 0));
        });
        $allBalances=InventoryBalance::query()->where('warehouse_id',$warehouse->id)->where('quantity_on_hand','>',0)->get(['medicine_id','quantity_on_hand']);
        $totalInventoryValue=$allBalances->sum(function(InventoryBalance $row)use($costs){
            $cost=$costs->get($row->medicine_id);
            return $cost?->average_cost_price === null ? 0 : (float)$row->quantity_on_hand*(float)$cost->average_cost_price;
        });
        $unpricedBalanceCount=$allBalances->filter(fn(InventoryBalance $row)=>!$costs->has($row->medicine_id))->count();
        $receipts=InventoryReceipt::query()->withCount('items')->latest()->limit(10)->get();
        $issues=InventoryIssue::query()->withCount('items')->latest()->limit(10)->get();
        return view('Pharma::pages.inventory.index',compact('warehouse','balances','receipts','issues','totalInventoryValue','unpricedBalanceCount'));
    }

    public function template(): StreamedResponse
    {
        $rows=collect([
            ['Ma thuoc'=>'MED-000001','So lo'=>'LO-001','Han dung'=>'31/12/2027','Ton dau ky'=>100],
        ]);
        return (new FastExcel($rows))->download('pharma-ton-dau-ky-mau.xlsx');
    }

    public function export(InventoryService $inventory): StreamedResponse
    {
        $warehouse=$inventory->defaultWarehouse();
        $costs=$this->activeSupplierCosts();
        $rows=InventoryBalance::query()->with('medicine')->where('warehouse_id',$warehouse->id)->orderBy('expiry_date')->get()
            ->map(function(InventoryBalance $row)use($costs){
                $cost=$costs->get($row->medicine_id);
                $average=$cost?->average_cost_price !== null ? (float)$cost->average_cost_price : null;
                return [
                    'Ma thuoc'=>$row->medicine->medicine_code,
                    'Ten thuoc'=>$row->medicine->name,
                    'Don vi'=>$row->medicine->unit,
                    'So lo'=>$row->batch_number,
                    'Han dung'=>$row->expiry_date->format('d/m/Y'),
                    'Ton dau ky'=>(float)$row->opening_quantity,
                    'Ton hien tai'=>(float)$row->quantity_on_hand,
                    'Gia von NCC trung binh'=>$average,
                    'So nguon gia von'=>(int)($cost?->supplier_cost_count ?? 0),
                    'Gia tri ton'=>$average === null ? null : (float)$row->quantity_on_hand*$average,
                ];
            });
        return (new FastExcel($rows))->download('pharma-ton-kho-'.now()->format('Ymd-His').'.xlsx');
    }

    public function importOpening(Request $request, InventoryService $inventory): RedirectResponse
    {
        $request->validate(['file'=>'required|file|mimes:xlsx,xls,csv|max:10240']);
        $rows=(new FastExcel)->import($request->file('file')->getRealPath());
        if($rows->isEmpty()) throw ValidationException::withMessages(['file'=>'File import không có dữ liệu.']);

        $normalized=$rows->values()->map(function(array $row,int $index): array {
            $line=$index+2;
            $code=trim((string)($row['Ma thuoc']??''));
            $batch=trim((string)($row['So lo']??''));
            $expiry=$this->excelDate($row['Han dung']??null,$line);
            $quantity=$row['Ton dau ky']??null;
            if($code===''||$batch===''||!is_numeric($quantity)||(float)$quantity<=0){
                throw ValidationException::withMessages(['file'=>"Dòng {$line}: Mã thuốc, Số lô và Tồn đầu kỳ (> 0) là bắt buộc."]);
            }
            return ['line'=>$line,'medicine_code'=>$code,'batch_number'=>$batch,'expiry_date'=>$expiry,'quantity'=>(float)$quantity];
        });

        $duplicates=$normalized->groupBy(fn($r)=>$r['medicine_code'].'|'.$r['batch_number'].'|'.$r['expiry_date'])->filter(fn($g)=>$g->count()>1);
        if($duplicates->isNotEmpty()) throw ValidationException::withMessages(['file'=>'File có dòng trùng Mã thuốc + Số lô + Hạn dùng.']);

        $medicines=Medicine::query()->whereIn('medicine_code',$normalized->pluck('medicine_code')->unique())->get()->keyBy('medicine_code');
        $missing=$normalized->pluck('medicine_code')->unique()->reject(fn($code)=>$medicines->has($code))->values();
        if($missing->isNotEmpty()) throw ValidationException::withMessages(['file'=>'Không tìm thấy mã thuốc trong Medicine Master: '.$missing->join(', ')]);

        $warehouse=$inventory->defaultWarehouse();
        DB::transaction(function()use($normalized,$medicines,$warehouse,$inventory){
            foreach($normalized as $row){
                $inventory->setOpeningBalance($warehouse->id,$medicines[$row['medicine_code']]->id,$row['batch_number'],$row['expiry_date'],$row['quantity'],auth('admin')->id());
            }
        });
        return redirect()->route('admin.pharma.inventory.index')->with('success',"Đã import {$normalized->count()} dòng tồn đầu kỳ.");
    }

    public function createOpening(InventoryService $inventory): View { return view('Pharma::pages.inventory.opening-form',['warehouse'=>$inventory->defaultWarehouse(),'medicines'=>$this->medicines()]); }
    public function storeOpening(Request $request, InventoryService $inventory): RedirectResponse
    {
        $data=$request->validate(['medicine_id'=>'required|exists:pharma_medicines,id','batch_number'=>'required|string|max:100','expiry_date'=>'required|date','quantity'=>'required|numeric|gt:0']);
        $inventory->setOpeningBalance($inventory->defaultWarehouse()->id,(int)$data['medicine_id'],$data['batch_number'],$data['expiry_date'],(float)$data['quantity'],auth('admin')->id());
        return redirect()->route('admin.pharma.inventory.index')->with('success','Đã ghi nhận tồn đầu kỳ.');
    }
    public function createReceipt(InventoryService $inventory): View { return view('Pharma::pages.inventory.receipt-form',['warehouse'=>$inventory->defaultWarehouse(),'medicines'=>$this->medicines()]); }
    public function storeReceipt(Request $request, InventoryService $inventory): RedirectResponse
    {
        $data=$request->validate(['receipt_date'=>'required|date','supplier_name'=>'nullable|string|max:255','invoice_number'=>'nullable|string|max:100','invoice_date'=>'nullable|date','notes'=>'nullable|string','items'=>'required|array|min:1','items.*.medicine_id'=>'required|exists:pharma_medicines,id','items.*.batch_number'=>'required|string|max:100','items.*.expiry_date'=>'required|date','items.*.quantity'=>'required|numeric|gt:0','items.*.unit_price_ex_vat'=>'required|numeric|min:0','items.*.vat_rate'=>'nullable|numeric|min:0|max:100']);
        $receipt=DB::transaction(function()use($data,$inventory){$r=InventoryReceipt::create(['warehouse_id'=>$inventory->defaultWarehouse()->id,'number'=>$this->number('PN'),'receipt_date'=>$data['receipt_date'],'supplier_name'=>$data['supplier_name']??null,'invoice_number'=>$data['invoice_number']??null,'invoice_date'=>$data['invoice_date']??null,'notes'=>$data['notes']??null,'created_by'=>auth('admin')->id()]);$r->items()->createMany($data['items']);return $r;});
        return redirect()->route('admin.pharma.inventory.index')->with('success',"Đã tạo phiếu nhập {$receipt->number} ở trạng thái nháp.");
    }
    public function postReceipt(InventoryReceipt $receipt, InventoryService $inventory): RedirectResponse { $inventory->postReceipt($receipt,auth('admin')->id()); return back()->with('success',"Đã ghi sổ {$receipt->number}."); }
    public function createIssue(InventoryService $inventory): View {
        $warehouse=$inventory->defaultWarehouse();
        $availableBalances=InventoryBalance::query()->with('medicine')->where('warehouse_id',$warehouse->id)->where('quantity_on_hand','>',0)->orderBy('expiry_date')->get();
        return view('Pharma::pages.inventory.issue-form',compact('warehouse','availableBalances'));
    }
    public function storeIssue(Request $request, InventoryService $inventory): RedirectResponse
    {
        $data=$request->validate(['issue_date'=>'required|date','recipient_name'=>'nullable|string|max:255','notes'=>'nullable|string','items'=>'required|array|min:1','items.*.balance_id'=>'required|exists:pharma_inventory_balances,id','items.*.quantity'=>'required|numeric|gt:0']);
        $warehouse=$inventory->defaultWarehouse();
        $items=collect($data['items'])->map(function(array $item) use ($warehouse): array {
            $balance=InventoryBalance::query()->where('warehouse_id',$warehouse->id)->whereKey($item['balance_id'])->where('quantity_on_hand','>',0)->firstOrFail();
            return ['medicine_id'=>$balance->medicine_id,'batch_number'=>$balance->batch_number,'expiry_date'=>$balance->expiry_date->toDateString(),'quantity'=>$item['quantity'],'unit_price'=>0];
        })->all();
        $issue=DB::transaction(function()use($data,$inventory,$items){$i=InventoryIssue::create(['warehouse_id'=>$inventory->defaultWarehouse()->id,'number'=>$this->number('PX'),'issue_date'=>$data['issue_date'],'recipient_name'=>$data['recipient_name']??null,'notes'=>$data['notes']??null,'created_by'=>auth('admin')->id()]);$i->items()->createMany($items);return $i;});
        return redirect()->route('admin.pharma.inventory.index')->with('success',"Đã tạo phiếu xuất {$issue->number} ở trạng thái nháp.");
    }
    public function postIssue(InventoryIssue $issue, InventoryService $inventory): RedirectResponse { $inventory->postIssue($issue,auth('admin')->id()); return back()->with('success',"Đã ghi sổ {$issue->number}."); }

    private function activeSupplierCosts()
    {
        $today=now()->toDateString();
        return SupplierTracking::query()
            ->select('medicine_id',DB::raw('AVG(cost_price) as average_cost_price'),DB::raw('COUNT(cost_price) as supplier_cost_count'))
            ->where('status','active')->whereNotNull('cost_price')
            ->where(fn($q)=>$q->whereNull('start_date')->orWhereDate('start_date','<=',$today))
            ->where(fn($q)=>$q->whereNull('end_date')->orWhereDate('end_date','>=',$today))
            ->groupBy('medicine_id')->get()->keyBy('medicine_id');
    }

    private function applyExpiryFilter($query,string $warning): void
    {
        $today=now()->startOfDay();
        match($warning){
            'expired'=>$query->whereDate('expiry_date','<',$today),
            'lt1'=>$query->whereDate('expiry_date','>=',$today)->whereDate('expiry_date','<',$today->copy()->addMonth()),
            'lt3'=>$query->whereDate('expiry_date','>=',$today)->whereDate('expiry_date','<',$today->copy()->addMonths(3)),
            'lt6'=>$query->whereDate('expiry_date','>=',$today)->whereDate('expiry_date','<',$today->copy()->addMonths(6)),
            'safe'=>$query->whereDate('expiry_date','>=',$today->copy()->addMonths(6)),
            default=>null,
        };
    }

    private function excelDate(mixed $value,int $line): string
    {
        try {
            if($value instanceof \DateTimeInterface) return Carbon::instance($value)->toDateString();
            $text=trim((string)$value);
            if($text==='') throw new \RuntimeException();
            foreach(['d/m/Y','Y-m-d'] as $format){
                try { return Carbon::createFromFormat($format,$text)->startOfDay()->toDateString(); } catch(\Throwable) {}
            }
        } catch(\Throwable) {}
        throw ValidationException::withMessages(['file'=>"Dòng {$line}: Hạn dùng không hợp lệ, dùng định dạng dd/mm/yyyy."]);
    }
    private function medicines(){ return Medicine::query()->orderBy('name')->limit(500)->get(['id','medicine_code','name','unit']); }
    private function number(string $prefix): string { return $prefix.'-'.now()->format('Ymd-His').'-'.str_pad((string)random_int(1,999),3,'0',STR_PAD_LEFT); }
}
