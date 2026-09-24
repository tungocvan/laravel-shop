<?php
namespace Modules\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\DrugBidAwardAllocation;
use Modules\Pharma\Models\DrugBidAwardManagementAssignment;
use Modules\Pharma\Models\InventoryBalance;
use Modules\Pharma\Models\InventoryIssue;
use Modules\Pharma\Models\InventoryIssueDocumentSetting;
use Modules\Pharma\Models\InventoryIssueCommission;
use Modules\Pharma\Models\InventoryIssueDeferredSupply;
use Modules\Pharma\Models\InventoryReceipt;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\PriceList;
use Modules\Pharma\Models\PriceListItem;
use Modules\Pharma\Models\SupplierTracking;
use Modules\Pharma\Services\InventoryService;
use Modules\Pharma\Services\InventoryMovementSummaryService;
use Modules\Pharma\Services\DrugBidCommissionService;
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
            ->selectRaw('COALESCE(pharma_inventory_balances.manual_cost_price, supplier_costs.average_cost_price) as query_average_cost_price')
            ->selectRaw('(pharma_inventory_balances.quantity_on_hand * COALESCE(pharma_inventory_balances.manual_cost_price, supplier_costs.average_cost_price)) as inventory_value')
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
            $supplierAverage=$cost?->average_cost_price !== null ? (float)$cost->average_cost_price : null;
            $manual=$row->manual_cost_price !== null ? (float)$row->manual_cost_price : null;
            $row->setAttribute('average_cost_price',$manual ?? $supplierAverage);
            $row->setAttribute('cost_source',$manual !== null ? 'manual' : ($supplierAverage !== null ? 'supplier' : 'unpriced'));
            $row->setAttribute('supplier_cost_count',(int)($cost?->supplier_cost_count ?? 0));
        });
        $allBalances=InventoryBalance::query()->where('warehouse_id',$warehouse->id)->where('quantity_on_hand','>',0)->get(['medicine_id','quantity_on_hand','expiry_date','manual_cost_price']);
        $totalInventoryValue=$allBalances->sum(function(InventoryBalance $row)use($costs){
            $cost=$costs->get($row->medicine_id);
            $effective=$row->manual_cost_price !== null ? (float)$row->manual_cost_price : ($cost?->average_cost_price !== null ? (float)$cost->average_cost_price : null);
            return $effective === null ? 0 : (float)$row->quantity_on_hand*$effective;
        });
        $unpricedBalanceCount=$allBalances->filter(function(InventoryBalance $row)use($costs){
            $supplier=$costs->get($row->medicine_id)?->average_cost_price;
            $effective=$row->manual_cost_price !== null ? (float)$row->manual_cost_price : ($supplier !== null ? (float)$supplier : null);
            return $effective === null || $effective <= 0;
        })->count();
        $expiredBalances=$allBalances->filter(fn(InventoryBalance $row)=>$row->expiry_date->lt(now()->startOfDay()));
        $expiredBalanceCount=$expiredBalances->count();
        $expiredInventoryValue=$expiredBalances->sum(function(InventoryBalance $row)use($costs){
            $cost=$costs->get($row->medicine_id);
            $effective=$row->manual_cost_price !== null ? (float)$row->manual_cost_price : ($cost?->average_cost_price !== null ? (float)$cost->average_cost_price : null);
            return $effective === null ? 0 : (float)$row->quantity_on_hand*$effective;
        });
        return view('Pharma::pages.inventory.index',compact('warehouse','balances','totalInventoryValue','unpricedBalanceCount','expiredInventoryValue','expiredBalanceCount'));
    }

    public function movements(Request $request, InventoryService $inventory, InventoryMovementSummaryService $movementSummary): View
    {
        $warehouse=$inventory->defaultWarehouse();
        $from=$request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->startOfMonth();
        $to=$request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfMonth();
        if($from->gt($to)) throw ValidationException::withMessages(['from'=>'Từ ngày không được sau Đến ngày.']);
        $movementMedicineId=$request->filled('movement_medicine_id') ? $request->integer('movement_medicine_id') : null;
        if($movementMedicineId && !Medicine::query()->whereKey($movementMedicineId)->exists()) throw ValidationException::withMessages(['movement_medicine_id'=>'Thuốc được chọn không tồn tại trong Medicine Master.']);
        $movement=$movementSummary->summarize($warehouse,$from,$to,$movementMedicineId);
        $movementMedicineIds=InventoryBalance::query()->where('warehouse_id',$warehouse->id)->distinct()->pluck('medicine_id');
        $movementMedicines=Medicine::query()->whereIn('id',$movementMedicineIds)->orderBy('name')->get(['id','medicine_code','name']);
        return view('Pharma::pages.inventory.movements',compact('warehouse','from','to','movement','movementMedicineId','movementMedicines'));
    }

    public function template(): StreamedResponse
    {
        $rows=collect([
            ['Ma thuoc'=>'MED-000001','So lo'=>'LO-001','Han dung'=>'31/12/2027','Ton dau ky'=>100],
        ]);
        return (new FastExcel($rows))->download('pharma-ton-dau-ky-mau.xlsx');
    }

    public function exportMovements(Request $request, InventoryService $inventory, InventoryMovementSummaryService $movementSummary): StreamedResponse
    {
        $data=$request->validate([
            'from'=>'required|date','to'=>'required|date|after_or_equal:from',
            'movement_medicine_id'=>'nullable|integer|exists:pharma_medicines,id',
            'ids'=>'nullable|array|max:500','ids.*'=>'integer|distinct|exists:pharma_inventory_balances,id',
        ]);
        $warehouse=$inventory->defaultWarehouse();
        $from=Carbon::parse($data['from'])->startOfDay();
        $to=Carbon::parse($data['to'])->endOfDay();
        $summary=$movementSummary->summarize($warehouse,$from,$to,!empty($data['movement_medicine_id'])?(int)$data['movement_medicine_id']:null,$data['ids']??[]);
        $rows=$summary['rows']->map(fn($row)=>[
            'Ma thuoc'=>$row->medicine_code,'Ten thuoc'=>$row->name,'So lo'=>$row->batch_number,
            'Han dung'=>Carbon::parse($row->expiry_date)->format('d/m/Y'),
            'Ton dau ky'=>(float)$row->period_opening,'Nhap trong ky'=>(float)$row->period_in,
            'Xuat trong ky'=>(float)$row->period_out,'Ton cuoi ky'=>(float)$row->period_closing,
        ]);
        return (new FastExcel($rows))->download('pharma-xuat-nhap-ton-'.$from->format('Ymd').'-'.$to->format('Ymd').'.xlsx');
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
        $data=$request->validate([
            'batch_number'=>'required|string|max:100',
            'expiry_date'=>'required|date',
            'cost_mode'=>'required|in:supplier,manual',
            'manual_cost_price'=>'nullable|required_if:cost_mode,manual|numeric|min:0|max:9999999999999999.99',
            'cost_adjustment_reason'=>'nullable|string|max:500',
        ]);
        $duplicate=InventoryBalance::query()->where('warehouse_id',$balance->warehouse_id)->where('medicine_id',$balance->medicine_id)
            ->where('batch_number',$data['batch_number'])->whereDate('expiry_date',$data['expiry_date'])->whereKeyNot($balance->id)->exists();
        if($duplicate) throw ValidationException::withMessages(['batch_number'=>'Số lô và hạn dùng này đã tồn tại cho thuốc.']);
        $requestedManual=$data['cost_mode']==='manual' ? (float)$data['manual_cost_price'] : null;
        $currentManual=$balance->manual_cost_price !== null ? (float)$balance->manual_cost_price : null;
        if($requestedManual !== $currentManual && $requestedManual !== null && blank($data['cost_adjustment_reason'] ?? null)){
            throw ValidationException::withMessages(['cost_adjustment_reason'=>'Vui lòng nhập lý do khi thay đổi giá vốn thủ công.']);
        }

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
            $oldManual=$balance->manual_cost_price !== null ? (float)$balance->manual_cost_price : null;
            $newManual=$data['cost_mode']==='manual' ? (float)$data['manual_cost_price'] : null;
            $costChanged=$oldManual !== $newManual;
            $balance->update([
                'batch_number'=>$data['batch_number'],
                'expiry_date'=>$data['expiry_date'],
                'manual_cost_price'=>$newManual,
            ]);
            if($costChanged){
                DB::table('pharma_inventory_cost_adjustments')->insert([
                    'inventory_balance_id'=>$balance->id,
                    'old_manual_cost_price'=>$oldManual,
                    'new_manual_cost_price'=>$newManual,
                    'reason'=>trim((string)($data['cost_adjustment_reason'] ?? ($newManual === null ? 'Chuyển về giá vốn NCC tự động.' : 'Điều chỉnh giá vốn thủ công.'))),
                    'adjusted_by'=>auth('admin')->id(),
                    'created_at'=>now(),
                    'updated_at'=>now(),
                ]);
            }
        });
        return back()->with('success','Đã cập nhật thông tin lô và giá vốn. Số lượng tồn không thay đổi.');
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
            $supplierAverage=$cost?->average_cost_price !== null ? (float)$cost->average_cost_price : null;
            $average=$row->manual_cost_price !== null ? (float)$row->manual_cost_price : $supplierAverage;
            $costSource=$row->manual_cost_price !== null ? 'Điều chỉnh thủ công' : ($supplierAverage !== null ? 'Supplier Tracking' : 'Chưa có giá vốn');
            return [
                'Ma thuoc'=>$row->medicine->medicine_code,
                'Ten thuoc'=>$row->medicine->name,
                'Don vi'=>$row->medicine->unit,
                'So lo'=>$row->batch_number,
                'Han dung'=>$row->expiry_date->format('d/m/Y'),
                'Ton dau ky'=>(float)$row->opening_quantity,
                'Ton hien tai'=>(float)$row->quantity_on_hand,
                'Gia von'=>$average,
                'Nguon gia von'=>$costSource,
                'So nguon gia von NCC'=>(int)($cost?->supplier_cost_count ?? 0),
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
        $receipt->load('items.medicine');
        $partners=Partner::query()->withPartnerType('supplier')->where('status','active')->orderBy('name')->get(['id','name','tax_code']);
        $medicines=$this->medicines();
        return view('Pharma::pages.inventory.receipt-edit',compact('receipt','partners','medicines'));
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
        $duplicateKeys=collect($data['items'])->map(fn($item)=>(int)$item['medicine_id'].'|'.mb_strtolower(trim($item['batch_number'])).'|'.Carbon::parse($item['expiry_date'])->toDateString());
        if($duplicateKeys->duplicates()->isNotEmpty()) throw ValidationException::withMessages(['items'=>'Không được trùng Thuốc + Số lô + Hạn dùng trong cùng phiếu nhập.']);

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
            ->with(['items:id,issue_id,medicine_id,batch_number,expiry_date,quantity'])
            ->withSum(['items as total_value'=>fn($q)=>$q->select(DB::raw('COALESCE(SUM(quantity * unit_price),0)'))],'unit_price')
            ->where('warehouse_id',$warehouse->id)
            ->when($request->filled('q'),fn($q)=>$q->where(fn($x)=>$x->where('number','like','%'.$request->q.'%')->orWhere('recipient_name','like','%'.$request->q.'%')))
            ->when(in_array($request->status,['draft','posted'],true),fn($q)=>$q->where('status',$request->status))
            ->latest('issue_date')->latest('id');
        $documents=$query->paginate($this->documentPerPage($request))->withQueryString();
        $balanceKeys=InventoryBalance::query()->where('warehouse_id',$warehouse->id)->get()
            ->keyBy(fn($balance)=>$balance->medicine_id.'|'.$balance->batch_number.'|'.$balance->expiry_date->format('Y-m-d'));
        $documents->getCollection()->each(function($issue)use($balanceKeys){
            $issue->can_post_stock=($issue->issue_source ?? 'normal')!=='bid' && $issue->status===InventoryIssue::DRAFT
                && $issue->items->isNotEmpty() && $issue->items->every(function($item)use($balanceKeys){
                    if(blank($item->batch_number) || !$item->expiry_date) return false;
                    $key=$item->medicine_id.'|'.$item->batch_number.'|'.$item->expiry_date->format('Y-m-d');
                    return (float)($balanceKeys[$key]?->quantity_on_hand ?? 0) >= (float)$item->quantity;
                });
        });
        return view('Pharma::pages.inventory.documents',[
            'type'=>'issue','title'=>'Phiếu xuất kho','documents'=>$documents,
        ]);
    }
    public function issueDocumentSettings(): View
    {
        $settings=InventoryIssueDocumentSetting::current();
        return view('Pharma::pages.inventory.issue-settings',compact('settings'));
    }

    public function updateIssueDocumentSettings(Request $request): RedirectResponse
    {
        $data=$request->validate([
            'organization_name'=>'nullable|string|max:255','organization_address'=>'nullable|string|max:500',
            'tax_code'=>'nullable|string|max:50','phone'=>'nullable|string|max:50',
            'document_title'=>'required|string|max:120','document_subtitle'=>'nullable|string|max:255',
            'warehouse_name'=>'required|string|max:120','issuer_label'=>'required|string|max:120',
            'deliverer_label'=>'required|string|max:120','receiver_label'=>'required|string|max:120',
            'keeper_label'=>'required|string|max:120','footer_note'=>'nullable|string|max:1000',
        ]);
        foreach(['show_price_list','show_unit_price','show_total_value','show_notes','show_issuer_signature','show_deliverer_signature','show_receiver_signature','show_keeper_signature'] as $field){
            $data[$field]=$request->boolean($field);
        }
        InventoryIssueDocumentSetting::current()->update($data);
        return back()->with('success','Đã lưu cấu hình phiếu xuất kho.');
    }

    public function createBidSaleIssue(InventoryService $inventory): View
    {
        $warehouse=$inventory->defaultWarehouse();
        $investors=DrugBidAwardAllocation::query()
            ->join('pharma_drug_bid_awards as awards','awards.id','=','pharma_drug_bid_award_allocations.drug_bid_award_id')
            ->where('pharma_drug_bid_award_allocations.status',DrugBidAwardAllocation::STATUS_ACTIVE)
            ->whereNotNull('awards.investor_name')
            ->select('awards.investor_code','awards.investor_name')->distinct()->orderBy('awards.investor_name')->get();
        return view('Pharma::pages.inventory.bid-sale-create',compact('warehouse','investors'));
    }

    public function bidSaleAllocations(Request $request, InventoryService $inventory)
    {
        $data=$request->validate(['investor'=>'required|string|max:255','partner_id'=>'nullable|integer']);
        $warehouse=$inventory->defaultWarehouse();
        $query=DrugBidAwardAllocation::query()
            ->with(['partner','award.medicine'])
            ->where('status',DrugBidAwardAllocation::STATUS_ACTIVE)
            ->whereHas('award',fn($q)=>$q->where(fn($x)=>$x->where('investor_code',$data['investor'])->orWhere('investor_name',$data['investor'])))
            ->when(!empty($data['partner_id']),fn($q)=>$q->where('partner_id',$data['partner_id']))
            ->where(fn($q)=>$q->whereNull('effective_from')->orWhereDate('effective_from','<=',now()))
            ->where(fn($q)=>$q->whereNull('effective_until')->orWhereDate('effective_until','>=',now()));
        $allocations=$query->get();
        $posted=DB::table('pharma_inventory_issue_items as ii')
            ->join('pharma_inventory_issues as i','i.id','=','ii.issue_id')
            ->where('i.issue_source','bid')->where('i.status',InventoryIssue::POSTED)
            ->whereIn('ii.drug_bid_award_allocation_id',$allocations->pluck('id'))
            ->groupBy('ii.drug_bid_award_allocation_id')->selectRaw('ii.drug_bid_award_allocation_id, SUM(ii.quantity) as qty')
            ->pluck('qty','ii.drug_bid_award_allocation_id');
        $rows=$allocations->map(function($allocation)use($posted,$warehouse){
            $award=$allocation->award;
            $issued=(float)($posted[$allocation->id]??0);
            $remaining=max(0,(float)$allocation->allocated_quantity-$issued);
             return [
                'allocation_id'=>$allocation->id,'partner_id'=>$allocation->partner_id,'partner_name'=>$allocation->partner?->name,
                'award_id'=>$award->id,'medicine_id'=>$award->medicine_id,'medicine_code'=>$award->medicine?->medicine_code ?? $award->medicine_code,
                'medicine_name'=>$award->medicine?->name ?? $award->medicine_name,'unit'=>$award->medicine?->unit ?? $award->unit,
                'allocated_quantity'=>(float)$allocation->allocated_quantity,'issued_quantity'=>$issued,'remaining_quantity'=>$remaining,
                'winning_price'=>(float)($award->winning_price ?? $award->unit_price ?? 0),
                'active_ingredient'=>$award->effectiveMedicineAttribute('active_ingredient')['value'],
                'effective_from'=>$allocation->effective_from?->format('Y-m-d'),
                'effective_until'=>$allocation->effective_until?->format('Y-m-d'),
                'days_remaining'=>$allocation->effective_until ? now()->startOfDay()->diffInDays($allocation->effective_until->copy()->startOfDay(),false) : null,
                'investor_code'=>$award->investor_code,'investor_name'=>$award->investor_name,
            ];
        })->filter(fn($row)=>$row['medicine_id'] && $row['remaining_quantity']>0)->values();
        return response()->json(['data'=>$rows]);
    }

    public function storeBidSaleIssue(Request $request, InventoryService $inventory): RedirectResponse
    {
        $data=$request->validate([
            'issue_date'=>'required|date','quantities'=>'required|array','quantities.*'=>'nullable|numeric|min:0','notes'=>'nullable|string',
        ],[
            'quantities.required'=>'Vui lòng nhập số lượng xuất cho ít nhất một sản phẩm.',
            'quantities.*.numeric'=>'Số lượng xuất phải là số.',
            'quantities.*.min'=>'Số lượng xuất không được âm.',
        ]);
        $allocationIds=collect($data['quantities'])->filter(fn($quantity)=>(float)$quantity>0)->keys()->map(fn($id)=>(int)$id)->values()->all();
        if(empty($allocationIds)) throw ValidationException::withMessages(['quantities'=>'Vui lòng nhập số lượng xuất cho ít nhất một sản phẩm.']);
        $allocations=DrugBidAwardAllocation::query()->with(['partner','award'])->whereIn('id',$allocationIds)
            ->where('status',DrugBidAwardAllocation::STATUS_ACTIVE)->get()->keyBy('id');
        if($allocations->count()!==count($allocationIds)) throw ValidationException::withMessages(['allocation_ids'=>'Phân bổ hàng thầu không còn hợp lệ.']);
        $partnerIds=$allocations->pluck('partner_id')->unique();
        if($partnerIds->count()!==1) throw ValidationException::withMessages(['allocation_ids'=>'Một phiếu chỉ được xuất cho một khách hàng/bệnh viện.']);
        $investorKeys=$allocations->map(fn($a)=>$a->award->investor_code ?: $a->award->investor_name)->unique();
        if($investorKeys->count()!==1) throw ValidationException::withMessages(['allocation_ids'=>'Một phiếu chỉ được thuộc một chủ đầu tư.']);

        $warehouse=$inventory->defaultWarehouse();
        $posted=DB::table('pharma_inventory_issue_items as ii')->join('pharma_inventory_issues as i','i.id','=','ii.issue_id')
            ->where('i.issue_source','bid')->where('i.status',InventoryIssue::POSTED)->whereIn('ii.drug_bid_award_allocation_id',$allocationIds)
            ->groupBy('ii.drug_bid_award_allocation_id')->selectRaw('ii.drug_bid_award_allocation_id, SUM(ii.quantity) as qty')
            ->pluck('qty','ii.drug_bid_award_allocation_id');
        $items=[];
        foreach($allocationIds as $allocationId){
            $allocation=$allocations[$allocationId]; $award=$allocation->award;
            $quantity=(float)($data['quantities'][$allocationId]??0);
            if($quantity<=0) throw ValidationException::withMessages(['quantities'=>"Số lượng xuất phải lớn hơn 0."]);
            $remaining=(float)$allocation->allocated_quantity-(float)($posted[$allocationId]??0);
            if($quantity>$remaining+0.00005) throw ValidationException::withMessages(['quantities'=>"Số lượng xuất vượt quá phân bổ còn lại của {$award->medicine_name}."]);
            $items[]=['medicine_id'=>$award->medicine_id,'drug_bid_award_id'=>$award->id,'drug_bid_award_allocation_id'=>$allocation->id,
                'batch_number'=>null,'expiry_date'=>null,'quantity'=>$quantity,
                'unit_price'=>(float)($award->winning_price ?? $award->unit_price ?? 0)];
        }
        $first=$allocations->first(); $partner=$first->partner()->firstOrFail(); $award=$first->award;
        $issue=DB::transaction(function()use($warehouse,$data,$items,$partner,$award){
            $issue=InventoryIssue::create(['warehouse_id'=>$warehouse->id,'number'=>'PX-'.now()->format('Ymd-His').'-'.random_int(100,999),
                'issue_date'=>$data['issue_date'],'recipient_name'=>$partner->name,
                'issue_source'=>'bid','bid_partner_id'=>$partner->id,'bid_investor_code'=>$award->investor_code,'bid_investor_name'=>$award->investor_name,
                'status'=>InventoryIssue::DRAFT,'notes'=>$data['notes']??null,'created_by'=>auth('admin')->id()]);
            $issue->items()->createMany($items); return $issue;
        });
        return redirect()->route('admin.pharma.inventory.issues.show',$issue)->with('success',"Đã tạo phiếu xuất bán hàng thầu {$issue->number}.");
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
        $issue->load(['items.medicine','priceList.manager','deferredSupplies.medicine']);
        $settings=InventoryIssueDocumentSetting::current();
        return view('Pharma::pages.inventory.issue-show',compact('issue','settings'));
    }

    public function issuePdf(InventoryIssue $issue, InventoryService $inventory): Response
    {
        $this->guardIssueWarehouse($issue,$inventory);
        $issue->load(['items.medicine','priceList.manager']);
        $settings=InventoryIssueDocumentSetting::current();
        $pdf=Pdf::loadView('Pharma::pages.inventory.issue-pdf',compact('issue','settings'))->setPaper('a4','portrait');
        return $pdf->download("phieu-xuat-kho-{$issue->number}.pdf");
    }

    public function issuePrint(InventoryIssue $issue, InventoryService $inventory): View
    {
        $this->guardIssueWarehouse($issue,$inventory);
        $issue->load(['items.medicine','priceList.manager']);
        $settings=InventoryIssueDocumentSetting::current();
        return view('Pharma::pages.inventory.issue-print',compact('issue','settings'));
    }

    public function editIssue(InventoryIssue $issue, InventoryService $inventory): View
    {
        $this->guardIssueWarehouse($issue,$inventory);
        $issue->load(['items.medicine','priceList.manager']);
        $warehouse=$inventory->defaultWarehouse();
        $availableBalances=InventoryBalance::query()->with('medicine')->where('warehouse_id',$warehouse->id)
            ->whereDate('expiry_date','>=',now()->toDateString())->orderBy('expiry_date')->orderBy('medicine_id')->get();
        $partners=Partner::query()->withPartnerType('customer')->where('status','active')->orderBy('name')->get(['id','name','tax_code']);
        $customerPriceLists=PriceList::query()->with('manager:id,name')->where('type',PriceList::TYPE_CUSTOMER)
            ->where('status',PriceList::STATUS_ACTIVE)->orderBy('manager_user_id')->orderByDesc('priority')->orderBy('name')->get([
                'id','code','name','manager_user_id','partner_id','effective_from','effective_to','priority',
            ]);
        $issueSalePrices=$this->issueSalePriceCandidates();
        return view('Pharma::pages.inventory.issue-edit',compact('issue','warehouse','availableBalances','partners','customerPriceLists','issueSalePrices'));
    }

    public function updateIssue(Request $request, InventoryIssue $issue, InventoryService $inventory): RedirectResponse
    {
        $this->guardIssueWarehouse($issue,$inventory);
        $metadata=$request->validate(['issue_date'=>'required|date','recipient_name'=>'nullable|string|max:255','notes'=>'nullable|string']);
        if($issue->status===InventoryIssue::POSTED){
            $issue->update($metadata);
            return redirect()->route('admin.pharma.inventory.issues.index')->with('success',"Đã cập nhật thông tin {$issue->number}. Dữ liệu hàng hóa đã ghi sổ được giữ nguyên.");
        }
        $data=$request->validate([
            'recipient_partner_id'=>'nullable|integer|exists:partners,id','price_list_id'=>'required|integer|exists:pharma_price_lists,id',
            'items'=>'required|array|min:1','items.*.balance_id'=>'required|exists:pharma_inventory_balances,id',
            'items.*.quantity'=>'required|numeric|gt:0','items.*.unit_price'=>'required|numeric|min:0',
        ]);
        $priceList=PriceList::query()->whereKey($data['price_list_id'])->where('type',PriceList::TYPE_CUSTOMER)->activeAt($metadata['issue_date'])->firstOrFail();
        if($priceList->partner_id !== null && (int)$priceList->partner_id !== (int)($data['recipient_partner_id'] ?? 0)){
            throw ValidationException::withMessages(['price_list_id'=>'Bảng giá này chỉ áp dụng cho khách hàng đã liên kết.']);
        }
        $allowedMedicineIds=PriceListItem::query()->where('price_list_id',$priceList->id)->where('status','active')->pluck('medicine_id')->filter()->map(fn($id)=>(int)$id)->unique();
        $warehouse=$inventory->defaultWarehouse();
        if(!empty($data['recipient_partner_id'])) $metadata['recipient_name']=Partner::query()->whereKey($data['recipient_partner_id'])->where('status','active')->firstOrFail()->name;
        $items=collect($data['items'])->map(function(array $item)use($warehouse,$allowedMedicineIds){
            $balance=InventoryBalance::query()->where('warehouse_id',$warehouse->id)->whereKey($item['balance_id'])->firstOrFail();
            if(! $allowedMedicineIds->contains((int)$balance->medicine_id)) throw ValidationException::withMessages(['items'=>'Thuốc đã chọn không thuộc bảng giá CUSTOMER.']);
            return ['medicine_id'=>$balance->medicine_id,'batch_number'=>$balance->batch_number,'expiry_date'=>$balance->expiry_date->toDateString(),'quantity'=>$item['quantity'],'unit_price'=>(float)$item['unit_price']];
        })->all();
        DB::transaction(function()use($issue,$metadata,$data,$items){
            $locked=InventoryIssue::query()->whereKey($issue->id)->lockForUpdate()->firstOrFail();
            if($locked->status!==InventoryIssue::DRAFT) throw ValidationException::withMessages(['issue'=>'Phiếu không còn ở trạng thái nháp.']);
            $locked->update(array_merge($metadata,['price_list_id'=>$data['price_list_id']]));
            $locked->items()->delete();
            $locked->items()->createMany($items);
        });
        $route=$request->input('after_save')==='view' ? 'admin.pharma.inventory.issues.show' : 'admin.pharma.inventory.issues.edit';
        return redirect()->route($route,$issue)->with('success',"Đã cập nhật đầy đủ phiếu nháp {$issue->number}.");
    }

    public function destroyIssue(InventoryIssue $issue, InventoryService $inventory): RedirectResponse
    {
        $this->guardIssueWarehouse($issue,$inventory);
        DB::transaction(function()use($issue){
            $locked=InventoryIssue::query()->whereKey($issue->id)->lockForUpdate()->firstOrFail();
            if($locked->status!==InventoryIssue::DRAFT) throw ValidationException::withMessages(['issue'=>'Chỉ phiếu xuất nháp mới được xóa.']);
            if(InventoryIssueCommission::query()->where('issue_id',$locked->id)->exists()) throw ValidationException::withMessages(['issue'=>'Phiếu đã từng ghi sổ và phát sinh nhật ký hoa hồng; chỉ được hoàn tác, không được xóa để bảo toàn lịch sử kiểm toán.']);
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

    public function revertIssue(InventoryIssue $issue, InventoryService $inventory, DrugBidCommissionService $commissions): RedirectResponse
    {
        $this->guardIssueWarehouse($issue,$inventory);
        DB::transaction(function()use($issue,$inventory,$commissions){
            $inventory->revertIssue($issue,auth('admin')->id());
            $commissions->reverseIssue($issue->fresh(),auth('admin')->id());
        });
        return redirect()->route('admin.pharma.inventory.issues.index')->with('success',"Đã hoàn tác ghi sổ {$issue->number}; hàng đã được cộng trả tồn kho và hoa hồng phát sinh đã được đảo.");
    }

    public function postIssue(InventoryIssue $issue, InventoryService $inventory): RedirectResponse
    {
        if(($issue->issue_source ?? 'normal')==='bid' && $issue->status===InventoryIssue::DRAFT){
            return redirect()->route('admin.pharma.inventory.issues.bid-sales.batches',$issue);
        }
        $inventory->postIssue($issue,auth('admin')->id());
        return back()->with('success',"Đã ghi sổ {$issue->number}.");
    }

    public function editBidSaleIssue(InventoryIssue $issue, InventoryService $inventory): View
    {
        $this->guardIssueWarehouse($issue,$inventory);
        abort_unless(($issue->issue_source ?? 'normal')==='bid' && $issue->status===InventoryIssue::DRAFT,404);
        $issue->load(['items.medicine','deferredSupplies']);
        $savedDeferred=$issue->deferredSupplies->where('status',InventoryIssueDeferredSupply::PENDING)->keyBy('drug_bid_award_allocation_id');
        $allocationIds=$issue->items->pluck('drug_bid_award_allocation_id')->filter();
        $allocations=DrugBidAwardAllocation::query()->with(['partner','award.medicine'])
            ->whereIn('id',$allocationIds)->get()->keyBy('id');
        $investorKey=$issue->bid_investor_code ?: $issue->bid_investor_name;
        $addableAllocations=DrugBidAwardAllocation::query()->with(['partner','award.medicine'])
            ->where('status',DrugBidAwardAllocation::STATUS_ACTIVE)->where('partner_id',$issue->bid_partner_id)
            ->whereNotIn('id',$allocationIds)
            ->whereHas('award',fn($q)=>$q->where(fn($x)=>$x->where('investor_code',$investorKey)->orWhere('investor_name',$investorKey)))
            ->where(fn($q)=>$q->whereNull('effective_from')->orWhereDate('effective_from','<=',now()))
            ->where(fn($q)=>$q->whereNull('effective_until')->orWhereDate('effective_until','>=',now()))->get();
        $candidateIds=$addableAllocations->pluck('id');
        $candidatePosted=DB::table('pharma_inventory_issue_items as ii')->join('pharma_inventory_issues as i','i.id','=','ii.issue_id')
            ->where('i.issue_source','bid')->where('i.status',InventoryIssue::POSTED)->whereIn('ii.drug_bid_award_allocation_id',$candidateIds)
            ->groupBy('ii.drug_bid_award_allocation_id')->selectRaw('ii.drug_bid_award_allocation_id, SUM(ii.quantity) as qty')
            ->pluck('qty','ii.drug_bid_award_allocation_id');
        $addableAllocations=$addableAllocations->filter(fn($allocation)=>(float)$allocation->allocated_quantity-(float)($candidatePosted[$allocation->id]??0)>0.00005)
            ->map(function($allocation)use($candidatePosted){
                $award=$allocation->award; $remaining=max(0,(float)$allocation->allocated_quantity-(float)($candidatePosted[$allocation->id]??0));
                return ['id'=>$allocation->id,'medicine_code'=>$award?->medicine?->medicine_code ?? $award?->medicine_code,
                    'medicine_name'=>$award?->medicine?->name ?? $award?->medicine_name,'unit'=>$award?->medicine?->unit ?? $award?->unit,
                    'remaining_quantity'=>$remaining,'winning_price'=>(float)($award?->winning_price ?? $award?->unit_price ?? 0),
                    'effective_until'=>$allocation->effective_until?->format('d/m/Y')];
            })->values();
        $assignments=DrugBidAwardManagementAssignment::query()->with('user:id,name')
            ->where('status',DrugBidAwardManagementAssignment::STATUS_ACTIVE)
            ->whereIn('drug_bid_award_id',$allocations->pluck('drug_bid_award_id')->filter()->unique())
            ->whereIn('partner_id',$allocations->pluck('partner_id')->filter()->unique())
            ->get()->groupBy(fn($assignment)=>$assignment->drug_bid_award_id.'|'.$assignment->partner_id);
        $posted=DB::table('pharma_inventory_issue_items as ii')->join('pharma_inventory_issues as i','i.id','=','ii.issue_id')
            ->where('i.issue_source','bid')->where('i.status',InventoryIssue::POSTED)->whereIn('ii.drug_bid_award_allocation_id',$allocationIds)
            ->groupBy('ii.drug_bid_award_allocation_id')->selectRaw('ii.drug_bid_award_allocation_id, SUM(ii.quantity) as qty')
            ->pluck('qty','ii.drug_bid_award_allocation_id');
        $stock=InventoryBalance::query()->where('warehouse_id',$issue->warehouse_id)->whereIn('medicine_id',$issue->items->pluck('medicine_id'))
            ->whereDate('expiry_date','>=',now()->toDateString())->groupBy('medicine_id')->selectRaw('medicine_id, SUM(quantity_on_hand) as qty')->pluck('qty','medicine_id');
        $rows=$issue->items->map(function($item)use($allocations,$assignments,$posted,$stock){
            $allocation=$allocations[$item->drug_bid_award_allocation_id]??null; $award=$allocation?->award;
            $issued=(float)($posted[$allocation?->id]??0); $remaining=max(0,(float)($allocation?->allocated_quantity??0)-$issued);
            return ['item_id'=>$item->id,'allocation_id'=>$allocation?->id,'medicine_code'=>$item->medicine?->medicine_code ?? $award?->medicine_code,
                'medicine_name'=>$item->medicine?->name ?? $award?->medicine_name,'unit'=>$item->medicine?->unit ?? $award?->unit,
                'allocated_quantity'=>(float)($allocation?->allocated_quantity??0),'issued_quantity'=>$issued,'remaining_quantity'=>$remaining,
                'available_stock'=>(float)($stock[$item->medicine_id]??0),'winning_price'=>(float)$item->unit_price,'quantity'=>(float)$item->quantity,
                'effective_until'=>$allocation?->effective_until?->format('Y-m-d'),
                'manager_names'=>$allocation ? ($assignments[$allocation->drug_bid_award_id.'|'.$allocation->partner_id] ?? collect())->pluck('user.name')->filter()->unique()->values()->implode(', ') : ''];
        });
        $balances=InventoryBalance::query()->where('warehouse_id',$issue->warehouse_id)
            ->whereIn('medicine_id',$issue->items->pluck('medicine_id'))->where('quantity_on_hand','>',0)
            ->whereDate('expiry_date','>=',now()->toDateString())->orderBy('medicine_id')->orderBy('expiry_date')->get()->groupBy('medicine_id');
        $canApprove=auth('admin')->user()?->can('approve_pharma_inventory_issue') ?? false;
        return view('Pharma::pages.inventory.bid-sale-edit',compact('issue','rows','balances','canApprove','addableAllocations','savedDeferred'));
    }

    public function updateBidSaleIssue(Request $request, InventoryIssue $issue, InventoryService $inventory): RedirectResponse
    {
        $this->guardIssueWarehouse($issue,$inventory);
        abort_unless(($issue->issue_source ?? 'normal')==='bid' && $issue->status===InventoryIssue::DRAFT,404);
        $data=$request->validate(['issue_date'=>'required|date','quantities'=>'required|array','quantities.*'=>'nullable|numeric|min:0',
            'add_allocations'=>'nullable|array','add_allocations.*'=>'integer|distinct','add_quantities'=>'nullable|array','add_quantities.*'=>'nullable|numeric|min:0','notes'=>'nullable|string',
            'deferred'=>'nullable|array','deferred.*.enabled'=>'nullable|boolean','deferred.*.expected_supply_date'=>'nullable|date','deferred.*.note'=>'nullable|string|max:2000'],[
            'quantities.required'=>'Vui lòng nhập số lượng xuất cho ít nhất một sản phẩm.','quantities.*.numeric'=>'Số lượng xuất phải là số.','quantities.*.min'=>'Số lượng xuất không được âm.',
        ]);
        DB::transaction(function()use($issue,$data,$request){
            $locked=InventoryIssue::query()->whereKey($issue->id)->lockForUpdate()->firstOrFail();
            if($locked->status!==InventoryIssue::DRAFT || ($locked->issue_source ?? 'normal')!=='bid') throw ValidationException::withMessages(['issue'=>'Phiếu hàng thầu không còn ở trạng thái nháp.']);
            $items=$locked->items()->get(); $posted=DB::table('pharma_inventory_issue_items as ii')->join('pharma_inventory_issues as i','i.id','=','ii.issue_id')
                ->where('i.issue_source','bid')->where('i.status',InventoryIssue::POSTED)->whereIn('ii.drug_bid_award_allocation_id',$items->pluck('drug_bid_award_allocation_id'))
                ->groupBy('ii.drug_bid_award_allocation_id')->selectRaw('ii.drug_bid_award_allocation_id, SUM(ii.quantity) as qty')->pluck('qty','ii.drug_bid_award_allocation_id');
            $removeIds=collect($request->input('remove_items',[]))->map(fn($id)=>(int)$id)->all();
            $kept=0;
            foreach($items as $item){
                if(in_array((int)$item->id,$removeIds,true)){$item->delete();continue;}
                $quantity=(float)($data['quantities'][$item->id]??0);
                if($quantity<=0){$item->delete();continue;}
                $allocation=DrugBidAwardAllocation::query()->whereKey($item->drug_bid_award_allocation_id)->where('status',DrugBidAwardAllocation::STATUS_ACTIVE)->firstOrFail();
                $remaining=max(0,(float)$allocation->allocated_quantity-(float)($posted[$allocation->id]??0));
                if($quantity>$remaining+0.00005) throw ValidationException::withMessages(['quantities'=>"Số lượng xuất vượt phân bổ còn lại."]);
                $item->update(['quantity'=>$quantity]); $kept++;
            }
            $addIds=collect($data['add_allocations']??[])->map(fn($id)=>(int)$id)->unique()->values();
            if($addIds->isNotEmpty()){
                $existingAllocationIds=$locked->items()->pluck('drug_bid_award_allocation_id')->filter()->map(fn($id)=>(int)$id);
                if($addIds->intersect($existingAllocationIds)->isNotEmpty()) throw ValidationException::withMessages(['add_allocations'=>'Sản phẩm trúng thầu đã có trong phiếu.']);
                $investorKey=$locked->bid_investor_code ?: $locked->bid_investor_name;
                $newAllocations=DrugBidAwardAllocation::query()->with('award')->whereIn('id',$addIds)
                    ->where('status',DrugBidAwardAllocation::STATUS_ACTIVE)->where('partner_id',$locked->bid_partner_id)
                    ->whereHas('award',fn($q)=>$q->where(fn($x)=>$x->where('investor_code',$investorKey)->orWhere('investor_name',$investorKey)))
                    ->where(fn($q)=>$q->whereNull('effective_from')->orWhereDate('effective_from','<=',now()))
                    ->where(fn($q)=>$q->whereNull('effective_until')->orWhereDate('effective_until','>=',now()))->get()->keyBy('id');
                if($newAllocations->count()!==$addIds->count()) throw ValidationException::withMessages(['add_allocations'=>'Có sản phẩm không còn thuộc phân bổ hợp lệ của Chủ đầu tư/Bệnh viện này.']);
                $newPosted=DB::table('pharma_inventory_issue_items as ii')->join('pharma_inventory_issues as i','i.id','=','ii.issue_id')
                    ->where('i.issue_source','bid')->where('i.status',InventoryIssue::POSTED)->whereIn('ii.drug_bid_award_allocation_id',$addIds)
                    ->groupBy('ii.drug_bid_award_allocation_id')->selectRaw('ii.drug_bid_award_allocation_id, SUM(ii.quantity) as qty')->pluck('qty','ii.drug_bid_award_allocation_id');
                foreach($addIds as $allocationId){
                    $allocation=$newAllocations[$allocationId]; $award=$allocation->award;
                    $quantity=(float)($data['add_quantities'][$allocationId]??0);
                    $remaining=max(0,(float)$allocation->allocated_quantity-(float)($newPosted[$allocationId]??0));
                    if($quantity<=0 || $quantity>$remaining+0.00005) throw ValidationException::withMessages(['add_quantities'=>"Số lượng thêm của {$award->medicine_name} phải lớn hơn 0 và không vượt phân bổ còn lại."]);
                    $locked->items()->create(['medicine_id'=>$award->medicine_id,'drug_bid_award_id'=>$award->id,'drug_bid_award_allocation_id'=>$allocation->id,
                        'batch_number'=>null,'expiry_date'=>null,'quantity'=>$quantity,'unit_price'=>(float)($award->winning_price ?? $award->unit_price ?? 0)]);
                    $kept++;
                }
            }
            if($kept===0) throw ValidationException::withMessages(['quantities'=>'Vui lòng giữ hoặc thêm ít nhất một sản phẩm có số lượng xuất lớn hơn 0.']);
            $activeDeferredAllocationIds=[];
            foreach($locked->items()->get() as $draftItem){
                $deferred=$data['deferred'][$draftItem->id]??[];
                if(!filter_var($deferred['enabled']??false,FILTER_VALIDATE_BOOLEAN)) continue;
                $note=trim((string)($deferred['note']??''));
                if($note==='') throw ValidationException::withMessages(['deferred'=>"Vui lòng nhập ghi chú chờ cung cấp cho {$draftItem->medicine?->name}."]);
                $activeDeferredAllocationIds[]=(int)$draftItem->drug_bid_award_allocation_id;
                InventoryIssueDeferredSupply::updateOrCreate(
                    ['issue_id'=>$locked->id,'drug_bid_award_allocation_id'=>$draftItem->drug_bid_award_allocation_id,'status'=>InventoryIssueDeferredSupply::PENDING],
                    ['medicine_id'=>$draftItem->medicine_id,'drug_bid_award_id'=>$draftItem->drug_bid_award_id,'quantity'=>(float)$draftItem->quantity,
                     'expected_supply_date'=>$deferred['expected_supply_date']??null,'note'=>$note,'status'=>InventoryIssueDeferredSupply::PENDING,
                     'created_by'=>auth('admin')->id()]
                );
            }
            $locked->deferredSupplies()->where('status',InventoryIssueDeferredSupply::PENDING)
                ->when($activeDeferredAllocationIds,fn($q)=>$q->whereNotIn('drug_bid_award_allocation_id',$activeDeferredAllocationIds))
                ->when(!$activeDeferredAllocationIds,fn($q)=>$q)->delete();
            $locked->update(['notes'=>$data['notes']??null]);
        });
        return redirect()->route('admin.pharma.inventory.issues.bid-sales.edit',$issue)->with('success',"Đã lưu phiếu nháp {$issue->number}.");
    }

    public function bidSaleBatches(InventoryIssue $issue, InventoryService $inventory): View
    {
        $this->guardIssueWarehouse($issue,$inventory);
        abort_unless(($issue->issue_source ?? 'normal')==='bid' && $issue->status===InventoryIssue::DRAFT,404);
        $issue->load('items.medicine');
        $balances=InventoryBalance::query()->where('warehouse_id',$issue->warehouse_id)
            ->whereIn('medicine_id',$issue->items->pluck('medicine_id'))->where('quantity_on_hand','>',0)
            ->whereDate('expiry_date','>=',now()->toDateString())->orderBy('medicine_id')->orderBy('expiry_date')->get()->groupBy('medicine_id');
        return view('Pharma::pages.inventory.bid-sale-batches',compact('issue','balances'));
    }

    public function postBidSaleIssue(Request $request, InventoryIssue $issue, InventoryService $inventory, DrugBidCommissionService $commissions): RedirectResponse
    {
        $this->guardIssueWarehouse($issue,$inventory);
        abort_unless(($issue->issue_source ?? 'normal')==='bid' && $issue->status===InventoryIssue::DRAFT,404);
        if($request->filled('add_allocations') || $request->filled('add_quantities')){
            throw ValidationException::withMessages(['add_allocations'=>'Có sản phẩm mới chưa được lưu. Hãy lưu phiếu nháp trước khi Duyệt & ghi sổ.']);
        }
        $data=$request->validate([
            'issue_date'=>'required|date','notes'=>'nullable|string','quantities'=>'required|array',
            'quantities.*'=>'required|numeric|min:0.001','batches'=>'nullable|array','batches.*'=>'nullable|array',
            'deferred'=>'nullable|array','deferred.*.enabled'=>'nullable|boolean',
            'deferred.*.expected_supply_date'=>'nullable|date','deferred.*.note'=>'nullable|string|max:2000',
        ]);
        DB::transaction(function()use($issue,$data,$inventory,$commissions){
            $issue=InventoryIssue::query()->lockForUpdate()->findOrFail($issue->id); $issue->load('items.medicine');
            if($issue->status!==InventoryIssue::DRAFT || ($issue->issue_source ?? 'normal')!=='bid') throw ValidationException::withMessages(['issue'=>'Phiếu hàng thầu không còn ở trạng thái nháp.']);
            $posted=DB::table('pharma_inventory_issue_items as ii')->join('pharma_inventory_issues as i','i.id','=','ii.issue_id')
                ->where('i.issue_source','bid')->where('i.status',InventoryIssue::POSTED)->whereIn('ii.drug_bid_award_allocation_id',$issue->items->pluck('drug_bid_award_allocation_id'))
                ->groupBy('ii.drug_bid_award_allocation_id')->selectRaw('ii.drug_bid_award_allocation_id, SUM(ii.quantity) as qty')->pluck('qty','ii.drug_bid_award_allocation_id');
            $newItems=[];
            foreach($issue->items as $item){
                $requested=(float)($data['quantities'][$item->id]??0);
                $allocation=DrugBidAwardAllocation::query()->whereKey($item->drug_bid_award_allocation_id)->where('status',DrugBidAwardAllocation::STATUS_ACTIVE)->firstOrFail();
                $remaining=max(0,(float)$allocation->allocated_quantity-(float)($posted[$allocation->id]??0));
                if($requested>$remaining+0.00005) throw ValidationException::withMessages(['quantities'=>"Số lượng duyệt của {$item->medicine?->name} vượt phân bổ còn lại."]);
                $parts=$data['batches'][$item->id]??[]; $lotTotal=0;
                foreach($parts as $part){
                    $quantity=(float)($part['quantity']??0); if($quantity<=0) continue;
                    $balance=InventoryBalance::query()->where('warehouse_id',$issue->warehouse_id)->where('medicine_id',$item->medicine_id)
                        ->whereKey((int)($part['balance_id']??0))->where('quantity_on_hand','>',0)->lockForUpdate()->firstOrFail();
                    if($quantity>(float)$balance->quantity_on_hand+0.00005) throw ValidationException::withMessages(['batches'=>"Lô {$balance->batch_number} không đủ tồn để xuất ".number_format($quantity,3,'.','').'.']);
                    $lotTotal+=$quantity;
                    $newItems[]=['medicine_id'=>$item->medicine_id,'drug_bid_award_id'=>$item->drug_bid_award_id,
                        'drug_bid_award_allocation_id'=>$item->drug_bid_award_allocation_id,'batch_number'=>$balance->batch_number,
                        'expiry_date'=>$balance->expiry_date,'quantity'=>$quantity,'unit_price'=>$item->unit_price];
                }
                if($lotTotal>$requested+0.00005) throw ValidationException::withMessages(['batches'=>"Tổng số lượng chia lô của {$item->medicine?->name} không được vượt số lượng duyệt ".number_format($requested,3,'.','').'.']);
                $deferredQuantity=max(0,$requested-$lotTotal);
                if($deferredQuantity>0.00005){
                    $deferred=$data['deferred'][$item->id]??[];
                    if(!filter_var($deferred['enabled']??false,FILTER_VALIDATE_BOOLEAN)) throw ValidationException::withMessages(['deferred'=>"{$item->medicine?->name} còn thiếu ".number_format($deferredQuantity,3,'.','').". Hãy ghi nhận chờ cung cấp trước khi duyệt."]);
                    $note=trim((string)($deferred['note']??''));
                    if($note==='') throw ValidationException::withMessages(['deferred'=>"Vui lòng nhập ghi chú chờ cung cấp cho {$item->medicine?->name}."]);
                    InventoryIssueDeferredSupply::updateOrCreate(
                        ['issue_id'=>$issue->id,'drug_bid_award_allocation_id'=>$item->drug_bid_award_allocation_id,'status'=>InventoryIssueDeferredSupply::PENDING],
                        ['medicine_id'=>$item->medicine_id,'drug_bid_award_id'=>$item->drug_bid_award_id,'quantity'=>$deferredQuantity,
                         'expected_supply_date'=>$deferred['expected_supply_date']??null,'note'=>$note,
                         'status'=>InventoryIssueDeferredSupply::PENDING,'created_by'=>auth('admin')->id()]
                    );
                }
            }
            if(!$newItems) throw ValidationException::withMessages(['batches'=>'Chưa có hàng thực xuất. Phiếu chỉ được ghi sổ khi có ít nhất một lô có số lượng xuất lớn hơn 0.']);
            $issue->items()->delete(); $issue->items()->createMany($newItems);
            $issue->update(['notes'=>$data['notes']??null]);
            $inventory->postIssue($issue->fresh('items'),auth('admin')->id());
            $commissions->snapshotPostedIssue($issue->fresh('items'),auth('admin')->id());
        });
        return redirect()->route('admin.pharma.inventory.issues.show',$issue)->with('success',"Đã duyệt lô và ghi sổ {$issue->number}.");
    }

    public function commissions(Request $request): View
    {
        $from=$request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->startOfMonth();
        $to=$request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfMonth();
        $userId=$request->integer('user_id');
        $partnerId=$request->integer('partner_id');
        $medicineId=$request->integer('medicine_id');

        $base=InventoryIssueCommission::query()
            ->whereBetween('calculated_at',[$from,$to])
            ->when($userId>0,fn($q)=>$q->where('user_id',$userId))
            ->when($partnerId>0,fn($q)=>$q->where('partner_id',$partnerId))
            ->when($medicineId>0,fn($q)=>$q->where('medicine_id',$medicineId));

        $totals=(clone $base)->selectRaw('COALESCE(SUM(revenue_amount),0) as revenue, COALESCE(SUM(commission_amount),0) as commission')->first();
        $unresolved=(clone $base)->where('entry_type',InventoryIssueCommission::TYPE_EARNED)
            ->where('status',InventoryIssueCommission::STATUS_UNRESOLVED)->count();
        $rows=(clone $base)->with(['issue','medicine','user','partner'])
            ->orderByDesc('calculated_at')->orderByDesc('id')->paginate(50)->withQueryString();

        $assignments=DrugBidAwardManagementAssignment::query()
            ->where('status',DrugBidAwardManagementAssignment::STATUS_ACTIVE)
            ->when($userId>0,fn($q)=>$q->where('user_id',$userId));

        $users=User::query()->whereIn('id',DrugBidAwardManagementAssignment::query()
            ->where('status',DrugBidAwardManagementAssignment::STATUS_ACTIVE)->distinct()->pluck('user_id'))
            ->orderBy('name')->get(['id','name']);

        $partners=Partner::query()->whereIn('id',(clone $assignments)->distinct()->pluck('partner_id'))
            ->orderBy('name')->get(['id','name']);

        $assignedAwardIds=(clone $assignments)
            ->when($partnerId>0,fn($q)=>$q->where('partner_id',$partnerId))
            ->distinct()->pluck('drug_bid_award_id');
        $medicineIds=DrugBidAward::query()->whereIn('id',$assignedAwardIds)
            ->whereNotNull('medicine_id')->distinct()->pluck('medicine_id');
        $medicines=Medicine::query()->whereIn('id',$medicineIds)->orderBy('name')->get(['id','medicine_code','name']);

        return view('Pharma::pages.inventory.commissions',compact(
            'rows','totals','unresolved','users','partners','medicines','from','to','userId','partnerId','medicineId'
        ));
    }

    public function exportCommissions(Request $request): StreamedResponse
    {
        $data=$request->validate([
            'from'=>'nullable|date','to'=>'nullable|date','user_id'=>'nullable|integer',
            'partner_id'=>'nullable|integer','medicine_id'=>'nullable|integer',
            'ids'=>'nullable|array|max:500','ids.*'=>'integer|distinct',
        ]);
        $from=!empty($data['from']) ? Carbon::parse($data['from'])->startOfDay() : now()->startOfMonth();
        $to=!empty($data['to']) ? Carbon::parse($data['to'])->endOfDay() : now()->endOfMonth();

        $query=InventoryIssueCommission::query()
            ->with(['issue','medicine','user','partner'])
            ->whereBetween('calculated_at',[$from,$to])
            ->when(!empty($data['user_id']),fn($q)=>$q->where('user_id',(int)$data['user_id']))
            ->when(!empty($data['partner_id']),fn($q)=>$q->where('partner_id',(int)$data['partner_id']))
            ->when(!empty($data['medicine_id']),fn($q)=>$q->where('medicine_id',(int)$data['medicine_id']))
            ->when(!empty($data['ids']),fn($q)=>$q->whereIn('id',$data['ids']))
            ->orderBy('calculated_at')->orderBy('id');

        $rows=$query->get()->map(fn(InventoryIssueCommission $row)=>[
            'Ngày ghi sổ'=>$row->calculated_at?->format('d/m/Y H:i'),
            'Số phiếu'=>$row->issue?->number,
            'Bệnh viện'=>$row->partner?->name,
            'Mã sản phẩm'=>$row->medicine?->medicine_code,
            'Sản phẩm'=>$row->medicine?->name,
            'User phụ trách'=>$row->user?->name ?: 'Chưa phân công',
            'SL thực xuất'=>(float)$row->quantity,
            'Đơn giá trúng thầu'=>(float)$row->unit_price,
            'Doanh thu'=>(float)$row->revenue_amount,
            '% hoa hồng'=>$row->commission_percentage !== null ? (float)$row->commission_percentage : null,
            'Hoa hồng'=>(float)$row->commission_amount,
            'Trạng thái'=>$row->status===InventoryIssueCommission::STATUS_UNRESOLVED ? 'Chưa đủ dữ liệu' : 'Đã tính',
        ]);

        return (new FastExcel($rows))->download('pharma-hoa-hong-'.now()->format('Ymd-His').'.xlsx');
    }

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
            'priced'=>$query->whereRaw('COALESCE(pharma_inventory_balances.manual_cost_price, supplier_costs.average_cost_price) > 0'),
            'unpriced'=>$query->where(fn($q)=>$q
                ->whereNull('pharma_inventory_balances.manual_cost_price')
                ->whereNull('supplier_costs.average_cost_price')
                ->orWhereRaw('COALESCE(pharma_inventory_balances.manual_cost_price, supplier_costs.average_cost_price) <= 0')),
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
