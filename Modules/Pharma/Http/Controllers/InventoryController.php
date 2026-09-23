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
use Modules\Pharma\Models\PriceList;
use Modules\Pharma\Models\PriceListItem;
use Modules\Pharma\Models\SupplierTracking;
use Modules\Pharma\Services\InventoryService;
use Modules\Partner\Models\Partner;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class InventoryController extends Controller
{
    public function index(Request $request, InventoryService $inventory): View
    {
        $warehouse=$inventory->defaultWarehouse();
        $costs=$this->activeSupplierCosts();
        $costSubquery=$this->activeSupplierCostSubquery();
        $query=InventoryBalance::query()
            ->with('medicine')
            ->leftJoinSub($costSubquery,'supplier_costs',fn($join)=>$join->on('supplier_costs.medicine_id','=','pharma_inventory_balances.medicine_id'))
            ->select('pharma_inventory_balances.*')
            ->selectRaw('supplier_costs.average_cost_price as query_average_cost_price')
            ->selectRaw('(pharma_inventory_balances.quantity_on_hand * supplier_costs.average_cost_price) as inventory_value')
            ->where('pharma_inventory_balances.warehouse_id',$warehouse->id)
            ->when($request->filled('q'),fn($q)=>$q->whereHas('medicine',fn($m)=>$m->where('medicine_code','like','%'.$request->q.'%')->orWhere('name','like','%'.$request->q.'%')))
            ->when($request->boolean('in_stock'),fn($q)=>$q->where('pharma_inventory_balances.quantity_on_hand','>',0));
        $this->applyExpiryFilter($query,(string)$request->input('expiry_warning',''));
        $this->applyCostFilter($query,(string)$request->input('cost_status',''));
        $sort=(string)$request->input('value_sort','');
        $sort === 'value_desc' ? $query->orderByDesc('inventory_value') : ($sort === 'value_asc' ? $query->orderByRaw('inventory_value IS NULL, inventory_value ASC') : $query->orderBy('pharma_inventory_balances.expiry_date'));
        $perPage=in_array((int)$request->input('per_page',25),[25,50,100],true) ? (int)$request->input('per_page',25) : 25;
        $balances=$query->paginate($perPage)->withQueryString();
        $balances->getCollection()->each(function(InventoryBalance $row)use($costs){
            $cost=$costs->get($row->medicine_id);
            $row->setAttribute('average_cost_price',$cost?->average_cost_price !== null ? (float)$cost->average_cost_price : null);
            $row->setAttribute('supplier_cost_count',(int)($cost?->supplier_cost_count ?? 0));
        });
        $allBalances=InventoryBalance::query()->where('warehouse_id',$warehouse->id)->where('quantity_on_hand','>',0)->get(['medicine_id','quantity_on_hand','expiry_date']);
        $totalInventoryValue=$allBalances->sum(function(InventoryBalance $row)use($costs){
            $cost=$costs->get($row->medicine_id);
            return $cost?->average_cost_price === null ? 0 : (float)$row->quantity_on_hand*(float)$cost->average_cost_price;
        });
        $unpricedBalanceCount=$allBalances->filter(fn(InventoryBalance $row)=>!$costs->has($row->medicine_id))->count();
        $expiredInventoryValue=$allBalances->filter(fn(InventoryBalance $row)=>$row->expiry_date->lt(now()->startOfDay()))->sum(function(InventoryBalance $row)use($costs){
            $cost=$costs->get($row->medicine_id);
            return $cost?->average_cost_price === null ? 0 : (float)$row->quantity_on_hand*(float)$cost->average_cost_price;
        });
        $receipts=InventoryReceipt::query()->withCount('items')->withSum('items','quantity')->latest()->limit(5)->get();
        $issues=InventoryIssue::query()->withCount('items')->withSum('items','quantity')->latest()->limit(5)->get();
        return view('Pharma::pages.inventory.index',compact('warehouse','balances','receipts','issues','totalInventoryValue','unpricedBalanceCount','expiredInventoryValue'));
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
        $balances=InventoryBalance::query()->with('medicine')->where('warehouse_id',$warehouse->id)->orderBy('expiry_date')->get();
        $rows=$this->exportRows($balances,$costs);
        return (new FastExcel($rows))->download('pharma-ton-kho-'.now()->format('Ymd-His').'.xlsx');
    }

    public function exportSelected(Request $request, InventoryService $inventory): StreamedResponse
    {
        $data=$request->validate(['ids'=>'required|array|min:1|max:100','ids.*'=>'integer|distinct|exists:pharma_inventory_balances,id']);
        $warehouse=$inventory->defaultWarehouse();
        $costs=$this->activeSupplierCosts();
        $rows=InventoryBalance::query()->with('medicine')
            ->where('warehouse_id',$warehouse->id)->whereIn('id',$data['ids'])->orderBy('expiry_date')->get();
        return (new FastExcel($this->exportRows($rows,$costs)))->download('pharma-ton-kho-da-chon-'.now()->format('Ymd-His').'.xlsx');
    }

    public function updateBalance(Request $request, InventoryBalance $balance, InventoryService $inventory): RedirectResponse
    {
        $warehouse=$inventory->defaultWarehouse();
        abort_unless((int)$balance->warehouse_id===(int)$warehouse->id,404);
        $data=$request->validate(['batch_number'=>'required|string|max:100','expiry_date'=>'required|date']);
        $duplicate=InventoryBalance::query()->where('warehouse_id',$balance->warehouse_id)->where('medicine_id',$balance->medicine_id)
            ->where('batch_number',$data['batch_number'])->whereDate('expiry_date',$data['expiry_date'])->whereKeyNot($balance->id)->exists();
        if($duplicate) throw ValidationException::withMessages(['batch_number'=>'Số lô và hạn dùng này đã tồn tại cho thuốc.']);

        DB::transaction(function()use($balance,$data){
            $oldBatch=$balance->batch_number;
            $oldExpiry=$balance->expiry_date->toDateString();
            DB::table('pharma_inventory_transactions')->where('warehouse_id',$balance->warehouse_id)->where('medicine_id',$balance->medicine_id)
                ->where('batch_number',$oldBatch)->whereDate('expiry_date',$oldExpiry)
                ->update(['batch_number'=>$data['batch_number'],'expiry_date'=>$data['expiry_date'],'updated_at'=>now()]);
            DB::table('pharma_inventory_receipt_items')->whereIn('receipt_id',DB::table('pharma_inventory_receipts')->select('id')->where('warehouse_id',$balance->warehouse_id))
                ->where('medicine_id',$balance->medicine_id)->where('batch_number',$oldBatch)->whereDate('expiry_date',$oldExpiry)
                ->update(['batch_number'=>$data['batch_number'],'expiry_date'=>$data['expiry_date'],'updated_at'=>now()]);
            DB::table('pharma_inventory_issue_items')->whereIn('issue_id',DB::table('pharma_inventory_issues')->select('id')->where('warehouse_id',$balance->warehouse_id))
                ->where('medicine_id',$balance->medicine_id)->where('batch_number',$oldBatch)->whereDate('expiry_date',$oldExpiry)
                ->update(['batch_number'=>$data['batch_number'],'expiry_date'=>$data['expiry_date'],'updated_at'=>now()]);
            $balance->update(['batch_number'=>$data['batch_number'],'expiry_date'=>$data['expiry_date']]);
        });
        return back()->with('success','Đã cập nhật số lô và hạn dùng, đồng bộ lịch sử kho liên quan.');
    }

    public function destroyBalance(InventoryBalance $balance, InventoryService $inventory): RedirectResponse
    {
        $warehouse=$inventory->defaultWarehouse();
        abort_unless((int)$balance->warehouse_id===(int)$warehouse->id,404);
        $hasLedger=DB::table('pharma_inventory_transactions')->where('warehouse_id',$balance->warehouse_id)->where('medicine_id',$balance->medicine_id)
            ->where('batch_number',$balance->batch_number)->whereDate('expiry_date',$balance->expiry_date->toDateString())->exists();
        if($hasLedger) throw ValidationException::withMessages(['inventory'=>'Không thể xóa lô đã có lịch sử giao dịch kho.']);
        $balance->delete();
        return back()->with('success','Đã xóa lô tồn kho chưa phát sinh giao dịch.');
    }

    private function exportRows($balances,$costs)
    {
        return $balances->map(function(InventoryBalance $row)use($costs){
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
    public function createReceipt(InventoryService $inventory): View
    {
        $partners=Partner::query()->withPartnerType('supplier')->where('status','active')->orderBy('name')->get(['id','name','tax_code']);
        return view('Pharma::pages.inventory.receipt-form',[
            'warehouse'=>$inventory->defaultWarehouse(),
            'medicines'=>$this->medicines(),
            'partners'=>$partners,
        ]);
    }
    public function storeReceipt(Request $request, InventoryService $inventory): RedirectResponse
    {
        $data=$request->validate(['receipt_date'=>'required|date','supplier_name'=>'required|string|max:255','invoice_number'=>'nullable|string|max:100','invoice_date'=>'nullable|date','notes'=>'nullable|string','items'=>'required|array|min:1','items.*.medicine_id'=>'required|exists:pharma_medicines,id','items.*.batch_number'=>'required|string|max:100','items.*.expiry_date'=>'required|date','items.*.quantity'=>'required|numeric|gt:0','items.*.unit_price_ex_vat'=>'required|numeric|min:0','items.*.vat_rate'=>'nullable|numeric|min:0|max:100']);
        $receipt=DB::transaction(function()use($data,$inventory){
            $warehouse=$inventory->defaultWarehouse();
            DB::table('pharma_inventory_warehouses')->where('id',$warehouse->id)->lockForUpdate()->first();
            $r=InventoryReceipt::create(['warehouse_id'=>$warehouse->id,'number'=>$this->nextDocumentNumber(InventoryReceipt::class,'PN'),'receipt_date'=>$data['receipt_date'],'supplier_name'=>$data['supplier_name']??null,'invoice_number'=>$data['invoice_number']??null,'invoice_date'=>$data['invoice_date']??null,'notes'=>$data['notes']??null,'created_by'=>auth('admin')->id()]);
            $r->items()->createMany($data['items']);
            return $r;
        });
        return redirect()->route('admin.pharma.inventory.receipts.index')->with('success',"Đã tạo phiếu nhập {$receipt->number} ở trạng thái nháp.");
    }
    public function showReceipt(InventoryReceipt $receipt, InventoryService $inventory): View
    {
        $this->guardReceiptWarehouse($receipt,$inventory);
        $receipt->load('items.medicine');
        return view('Pharma::pages.inventory.receipt-show',compact('receipt'));
    }

    public function editReceipt(InventoryReceipt $receipt, InventoryService $inventory): View
    {
        $this->guardReceiptWarehouse($receipt,$inventory);
        $receipt->load('items');
        $partners=Partner::query()->withPartnerType('supplier')->where('status','active')->orderBy('name')->get(['id','name','tax_code']);
        return view('Pharma::pages.inventory.receipt-edit',compact('receipt','partners'));
    }

    public function updateReceipt(Request $request, InventoryReceipt $receipt, InventoryService $inventory): RedirectResponse
    {
        $this->guardReceiptWarehouse($receipt,$inventory);
        $metadata=$request->validate([
            'receipt_date'=>'required|date','supplier_name'=>'required|string|max:255',
            'invoice_number'=>'nullable|string|max:100','invoice_date'=>'nullable|date','notes'=>'nullable|string',
        ]);
        if($receipt->status===InventoryReceipt::POSTED){
            $receipt->update($metadata);
            return redirect()->route('admin.pharma.inventory.receipts.index')->with('success',"Đã cập nhật thông tin {$receipt->number}. Dữ liệu hàng hóa đã ghi sổ được giữ nguyên.");
        }

        $data=$request->validate([
            'items'=>'required|array|min:1','items.*.medicine_id'=>'required|exists:pharma_medicines,id',
            'items.*.batch_number'=>'required|string|max:100','items.*.expiry_date'=>'required|date',
            'items.*.quantity'=>'required|numeric|gt:0','items.*.unit_price_ex_vat'=>'required|numeric|min:0',
            'items.*.vat_rate'=>'nullable|numeric|min:0|max:100',
        ]);
        DB::transaction(function()use($receipt,$metadata,$data){
            $locked=InventoryReceipt::query()->whereKey($receipt->id)->lockForUpdate()->firstOrFail();
            if($locked->status!==InventoryReceipt::DRAFT) throw ValidationException::withMessages(['receipt'=>'Phiếu không còn ở trạng thái nháp.']);
            $locked->update($metadata);
            $locked->items()->delete();
            $locked->items()->createMany($data['items']);
        });
        return redirect()->route('admin.pharma.inventory.receipts.index')->with('success',"Đã cập nhật phiếu nháp {$receipt->number}.");
    }

    public function destroyReceipt(InventoryReceipt $receipt, InventoryService $inventory): RedirectResponse
    {
        $this->guardReceiptWarehouse($receipt,$inventory);
        DB::transaction(function()use($receipt){
            $locked=InventoryReceipt::query()->whereKey($receipt->id)->lockForUpdate()->firstOrFail();
            if($locked->status!==InventoryReceipt::DRAFT) throw ValidationException::withMessages(['receipt'=>'Chỉ phiếu nhập nháp mới được xóa.']);
            $locked->items()->delete();
            $locked->delete();
        });
        return redirect()->route('admin.pharma.inventory.receipts.index')->with('success','Đã xóa phiếu nhập nháp.');
    }

    public function postReceipt(InventoryReceipt $receipt, InventoryService $inventory): RedirectResponse { $inventory->postReceipt($receipt,auth('admin')->id()); return back()->with('success',"Đã ghi sổ {$receipt->number}."); }

    public function revertReceipt(InventoryReceipt $receipt, InventoryService $inventory): RedirectResponse
    {
        $this->guardReceiptWarehouse($receipt,$inventory);
        $inventory->revertReceipt($receipt,auth('admin')->id());
        return redirect()->route('admin.pharma.inventory.receipts.index')->with('success',"Đã hoàn tác ghi sổ {$receipt->number}; tồn kho đã được cập nhật và phiếu trở về nháp.");
    }
    public function createIssue(InventoryService $inventory): View
    {
        $warehouse=$inventory->defaultWarehouse();
        $availableBalances=InventoryBalance::query()->with('medicine')
            ->where('warehouse_id',$warehouse->id)->where('quantity_on_hand','>',0)
            ->whereDate('expiry_date','>=',now()->toDateString())
            ->orderBy('expiry_date')->orderBy('medicine_id')->get();
        $partners=Partner::query()->withPartnerType('customer')->where('status','active')->orderBy('name')->get(['id','name','tax_code']);
        $customerPriceLists=PriceList::query()->with('manager:id,name')
            ->where('type',PriceList::TYPE_CUSTOMER)->where('status',PriceList::STATUS_ACTIVE)
            ->whereHas('items',fn($q)=>$q->where('status','active')->whereNotNull('medicine_id'))
            ->orderBy('manager_user_id')->orderByDesc('priority')->orderBy('name')->get([
                'id','code','name','manager_user_id','partner_id','effective_from','effective_to','priority',
            ]);
        $issueSalePrices=$this->issueSalePriceCandidates();
        return view('Pharma::pages.inventory.issue-form',compact('warehouse','availableBalances','partners','customerPriceLists','issueSalePrices'));
    }

    public function receipts(Request $request, InventoryService $inventory): View
    {
        $warehouse=$inventory->defaultWarehouse();
        $query=InventoryReceipt::query()->withCount('items')
            ->withSum(['items as total_value'=>fn($q)=>$q->select(DB::raw('COALESCE(SUM(quantity * unit_price_ex_vat),0)'))],'unit_price_ex_vat')
            ->where('warehouse_id',$warehouse->id)
            ->when($request->filled('q'),fn($q)=>$q->where(fn($x)=>$x->where('number','like','%'.$request->q.'%')->orWhere('supplier_name','like','%'.$request->q.'%')))
            ->when(in_array($request->status,['draft','posted'],true),fn($q)=>$q->where('status',$request->status))
            ->latest('receipt_date')->latest('id');
        return view('Pharma::pages.inventory.documents',[
            'type'=>'receipt','title'=>'Phiếu nhập kho','documents'=>$query->paginate($this->documentPerPage($request))->withQueryString(),
        ]);
    }

    public function issues(Request $request, InventoryService $inventory): View
    {
        $warehouse=$inventory->defaultWarehouse();
        $query=InventoryIssue::query()->withCount('items')
            ->withSum(['items as total_value'=>fn($q)=>$q->select(DB::raw('COALESCE(SUM(quantity * unit_price),0)'))],'unit_price')
            ->where('warehouse_id',$warehouse->id)
            ->when($request->filled('q'),fn($q)=>$q->where(fn($x)=>$x->where('number','like','%'.$request->q.'%')->orWhere('recipient_name','like','%'.$request->q.'%')))
            ->when(in_array($request->status,['draft','posted'],true),fn($q)=>$q->where('status',$request->status))
            ->latest('issue_date')->latest('id');
        return view('Pharma::pages.inventory.documents',[
            'type'=>'issue','title'=>'Phiếu xuất kho','documents'=>$query->paginate($this->documentPerPage($request))->withQueryString(),
        ]);
    }
    public function storeIssue(Request $request, InventoryService $inventory): RedirectResponse
    {
        $data=$request->validate([
            'issue_date'=>'required|date','recipient_name'=>'nullable|string|max:255','recipient_partner_id'=>'nullable|integer|exists:partners,id',
            'price_list_id'=>'required|integer|exists:pharma_price_lists,id','notes'=>'nullable|string','items'=>'required|array|min:1',
            'items.*.balance_id'=>'required|exists:pharma_inventory_balances,id','items.*.quantity'=>'required|numeric|gt:0',
            'items.*.unit_price'=>'required|numeric|min:0',
        ]);
        $priceList=PriceList::query()->whereKey($data['price_list_id'])
            ->where('type',PriceList::TYPE_CUSTOMER)->activeAt($data['issue_date'])->firstOrFail();
        if($priceList->partner_id !== null && (int)$priceList->partner_id !== (int)($data['recipient_partner_id'] ?? 0)){
            throw ValidationException::withMessages(['price_list_id'=>'Bảng giá này chỉ áp dụng cho khách hàng đã liên kết.']);
        }
        $allowedMedicineIds=PriceListItem::query()->where('price_list_id',$priceList->id)->where('status','active')
            ->where(fn($q)=>$q->whereNull('effective_from')->orWhereDate('effective_from','<=',$data['issue_date']))
            ->where(fn($q)=>$q->whereNull('effective_to')->orWhereDate('effective_to','>=',$data['issue_date']))
            ->pluck('medicine_id')->filter()->map(fn($id)=>(int)$id)->unique();
        $warehouse=$inventory->defaultWarehouse();
        if(!empty($data['recipient_partner_id'])){
            $partner=Partner::query()->whereKey($data['recipient_partner_id'])->where('status','active')->firstOrFail();
            $data['recipient_name']=$partner->name;
        }
        $items=collect($data['items'])->map(function(array $item) use ($warehouse,$allowedMedicineIds): array {
            $balance=InventoryBalance::query()->where('warehouse_id',$warehouse->id)->whereKey($item['balance_id'])->where('quantity_on_hand','>',0)->firstOrFail();
            if(! $allowedMedicineIds->contains((int)$balance->medicine_id)){
                throw ValidationException::withMessages(['items'=>'Thuốc đã chọn không thuộc bảng giá CUSTOMER hoặc giá không còn hiệu lực.']);
            }
            return ['medicine_id'=>$balance->medicine_id,'batch_number'=>$balance->batch_number,'expiry_date'=>$balance->expiry_date->toDateString(),'quantity'=>$item['quantity'],'unit_price'=>(float)$item['unit_price']];
        })->all();
        $issue=DB::transaction(function()use($data,$inventory,$items){
            $warehouse=$inventory->defaultWarehouse();
            DB::table('pharma_inventory_warehouses')->where('id',$warehouse->id)->lockForUpdate()->first();
            $i=InventoryIssue::create(['warehouse_id'=>$warehouse->id,'number'=>$this->nextDocumentNumber(InventoryIssue::class,'PX'),'issue_date'=>$data['issue_date'],'recipient_name'=>$data['recipient_name']??null,'price_list_id'=>$data['price_list_id'],'notes'=>$data['notes']??null,'created_by'=>auth('admin')->id()]);
            $i->items()->createMany($items);
            return $i;
        });
        return redirect()->route('admin.pharma.inventory.issues.index')->with('success',"Đã tạo phiếu xuất {$issue->number} ở trạng thái nháp.");
    }
    public function showIssue(InventoryIssue $issue, InventoryService $inventory): View
    {
        $this->guardIssueWarehouse($issue,$inventory);
        $issue->load('items.medicine');
        return view('Pharma::pages.inventory.issue-show',compact('issue'));
    }

    public function editIssue(InventoryIssue $issue, InventoryService $inventory): View
    {
        $this->guardIssueWarehouse($issue,$inventory);
        $issue->load('items');
        $partners=Partner::query()->withPartnerType('customer')->where('status','active')->orderBy('name')->get(['id','name','tax_code']);
        return view('Pharma::pages.inventory.issue-edit',compact('issue','partners'));
    }

    public function updateIssue(Request $request, InventoryIssue $issue, InventoryService $inventory): RedirectResponse
    {
        $this->guardIssueWarehouse($issue,$inventory);
        $metadata=$request->validate(['issue_date'=>'required|date','recipient_name'=>'nullable|string|max:255','notes'=>'nullable|string']);
        if($issue->status===InventoryIssue::POSTED){
            $issue->update($metadata);
            return redirect()->route('admin.pharma.inventory.issues.index')->with('success',"Đã cập nhật thông tin {$issue->number}. Dữ liệu hàng hóa đã ghi sổ được giữ nguyên.");
        }
        return redirect()->route('admin.pharma.inventory.issues.edit',$issue)->withErrors(['items'=>'Phiếu nháp cần chỉnh hàng hóa tại màn hình lập phiếu; chức năng sửa chi tiết sẽ giữ nguyên kiểm soát lô tồn khả dụng.']);
    }

    public function destroyIssue(InventoryIssue $issue, InventoryService $inventory): RedirectResponse
    {
        $this->guardIssueWarehouse($issue,$inventory);
        DB::transaction(function()use($issue){
            $locked=InventoryIssue::query()->whereKey($issue->id)->lockForUpdate()->firstOrFail();
            if($locked->status!==InventoryIssue::DRAFT) throw ValidationException::withMessages(['issue'=>'Chỉ phiếu xuất nháp mới được xóa.']);
            $locked->items()->delete(); $locked->delete();
        });
        return redirect()->route('admin.pharma.inventory.issues.index')->with('success','Đã xóa phiếu xuất nháp.');
    }

    public function exportIssues(Request $request, InventoryService $inventory): StreamedResponse
    {
        $warehouse=$inventory->defaultWarehouse();
        $issues=InventoryIssue::query()->with('items.medicine')->where('warehouse_id',$warehouse->id)
            ->when($request->filled('q'),fn($q)=>$q->where(fn($x)=>$x->where('number','like','%'.$request->q.'%')->orWhere('recipient_name','like','%'.$request->q.'%')))
            ->when(in_array($request->status,['draft','posted'],true),fn($q)=>$q->where('status',$request->status))
            ->latest('issue_date')->latest('id')->get();
        $rows=$issues->flatMap(fn(InventoryIssue $issue)=>$issue->items->map(fn($item)=>[
            'Ma phieu'=>$issue->number,'Ngay xuat'=>$issue->issue_date->format('d/m/Y'),'Khach hang / noi nhan'=>$issue->recipient_name,
            'Trang thai'=>$issue->status,'Ma thuoc'=>$item->medicine->medicine_code,'Ten thuoc'=>$item->medicine->name,
            'So lo'=>$item->batch_number,'Han dung'=>$item->expiry_date->format('d/m/Y'),'So luong'=>(float)$item->quantity,
            'Don gia xuat'=>(float)$item->unit_price,'Thanh tien'=>(float)$item->quantity*(float)$item->unit_price,
        ]));
        return (new FastExcel($rows))->download('pharma-phieu-xuat-'.now()->format('Ymd-His').'.xlsx');
    }

    public function revertIssue(InventoryIssue $issue, InventoryService $inventory): RedirectResponse
    {
        $this->guardIssueWarehouse($issue,$inventory);
        $inventory->revertIssue($issue,auth('admin')->id());
        return redirect()->route('admin.pharma.inventory.issues.index')->with('success',"Đã hoàn tác ghi sổ {$issue->number}; hàng đã được cộng trả tồn kho và phiếu trở về nháp.");
    }

    public function postIssue(InventoryIssue $issue, InventoryService $inventory): RedirectResponse { $inventory->postIssue($issue,auth('admin')->id()); return back()->with('success',"Đã ghi sổ {$issue->number}."); }

    private function issueSalePriceCandidates()
    {
        return PriceListItem::query()
            ->join('pharma_price_lists','pharma_price_lists.id','=','pharma_price_list_items.price_list_id')
            ->where('pharma_price_lists.status',PriceList::STATUS_ACTIVE)
            ->where('pharma_price_list_items.status','active')
            ->where('pharma_price_lists.type',PriceList::TYPE_CUSTOMER)
            ->whereNotNull('pharma_price_list_items.medicine_id')
            ->orderByDesc('pharma_price_lists.priority')
            ->orderByDesc('pharma_price_lists.effective_from')
            ->orderByDesc('pharma_price_lists.id')
            ->get([
                'pharma_price_list_items.medicine_id','pharma_price_list_items.company_sale_price',
                'pharma_price_list_items.effective_from as item_effective_from','pharma_price_list_items.effective_to as item_effective_to',
                'pharma_price_lists.id as price_list_id','pharma_price_lists.code as price_list_code','pharma_price_lists.name as price_list_name',
                'pharma_price_lists.type as price_list_type','pharma_price_lists.partner_id','pharma_price_lists.manager_user_id','pharma_price_lists.priority',
                'pharma_price_lists.effective_from as list_effective_from','pharma_price_lists.effective_to as list_effective_to',
            ]);
    }

    private function guardIssueWarehouse(InventoryIssue $issue, InventoryService $inventory): void
    {
        abort_unless((int)$issue->warehouse_id===(int)$inventory->defaultWarehouse()->id,404);
    }

    private function guardReceiptWarehouse(InventoryReceipt $receipt, InventoryService $inventory): void
    {
        abort_unless((int)$receipt->warehouse_id===(int)$inventory->defaultWarehouse()->id,404);
    }

    private function activeSupplierCostSubquery()
    {
        $today=now()->toDateString();
        return SupplierTracking::query()
            ->select('medicine_id',DB::raw('AVG(cost_price) as average_cost_price'))
            ->where('status','active')->whereNotNull('cost_price')
            ->where(fn($q)=>$q->whereNull('start_date')->orWhereDate('start_date','<=',$today))
            ->where(fn($q)=>$q->whereNull('end_date')->orWhereDate('end_date','>=',$today))
            ->groupBy('medicine_id');
    }

    private function applyCostFilter($query,string $status): void
    {
        match($status){
            'priced'=>$query->whereNotNull('supplier_costs.average_cost_price'),
            'unpriced'=>$query->whereNull('supplier_costs.average_cost_price'),
            default=>null,
        };
    }

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
    private function documentPerPage(Request $request): int
    {
        $value=(int)$request->input('per_page',25);
        return in_array($value,[25,50,100],true) ? $value : 25;
    }

    private function nextDocumentNumber(string $modelClass,string $prefix): string
    {
        $date=now()->format('ymd');
        $pattern=$prefix.'-'.$date.'-';
        $latest=$modelClass::query()->where('number','like',$pattern.'%')->orderByDesc('number')->value('number');
        $sequence=$latest ? ((int)substr($latest,-3))+1 : 1;
        if($sequence>999) throw ValidationException::withMessages(['number'=>'Đã vượt quá 999 chứng từ trong ngày.']);
        return $pattern.str_pad((string)$sequence,3,'0',STR_PAD_LEFT);
    }
}
