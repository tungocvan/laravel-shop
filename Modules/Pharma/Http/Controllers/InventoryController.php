<?php
namespace Modules\Pharma\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Pharma\Models\InventoryBalance;
use Modules\Pharma\Models\InventoryIssue;
use Modules\Pharma\Models\InventoryReceipt;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Services\InventoryService;

final class InventoryController extends Controller
{
    public function index(Request $request, InventoryService $inventory): View
    {
        $warehouse=$inventory->defaultWarehouse();
        $balances=InventoryBalance::query()->with('medicine')->where('warehouse_id',$warehouse->id)
            ->when($request->filled('q'),fn($q)=>$q->whereHas('medicine',fn($m)=>$m->where('medicine_code','like','%'.$request->q.'%')->orWhere('name','like','%'.$request->q.'%')))
            ->when($request->boolean('in_stock'),fn($q)=>$q->where('quantity_on_hand','>',0))
            ->orderBy('expiry_date')->paginate(25)->withQueryString();
        $receipts=InventoryReceipt::query()->withCount('items')->latest()->limit(10)->get();
        $issues=InventoryIssue::query()->withCount('items')->latest()->limit(10)->get();
        return view('Pharma::pages.inventory.index',compact('warehouse','balances','receipts','issues'));
    }
    public function createReceipt(InventoryService $inventory): View { return view('Pharma::pages.inventory.receipt-form',['warehouse'=>$inventory->defaultWarehouse(),'medicines'=>$this->medicines()]); }
    public function storeReceipt(Request $request, InventoryService $inventory): RedirectResponse
    {
        $data=$request->validate(['receipt_date'=>'required|date','supplier_name'=>'nullable|string|max:255','invoice_number'=>'nullable|string|max:100','invoice_date'=>'nullable|date','notes'=>'nullable|string','items'=>'required|array|min:1','items.*.medicine_id'=>'required|exists:pharma_medicines,id','items.*.batch_number'=>'required|string|max:100','items.*.expiry_date'=>'required|date','items.*.quantity'=>'required|numeric|gt:0','items.*.unit_price_ex_vat'=>'required|numeric|min:0','items.*.vat_rate'=>'nullable|numeric|min:0|max:100']);
        $receipt=DB::transaction(function()use($data,$inventory){$r=InventoryReceipt::create(['warehouse_id'=>$inventory->defaultWarehouse()->id,'number'=>$this->number('PN'),'receipt_date'=>$data['receipt_date'],'supplier_name'=>$data['supplier_name']??null,'invoice_number'=>$data['invoice_number']??null,'invoice_date'=>$data['invoice_date']??null,'notes'=>$data['notes']??null,'created_by'=>auth('admin')->id()]);$r->items()->createMany($data['items']);return $r;});
        return redirect()->route('admin.pharma.inventory.index')->with('success',"Đã tạo phiếu nhập {$receipt->number} ở trạng thái nháp.");
    }
    public function postReceipt(InventoryReceipt $receipt, InventoryService $inventory): RedirectResponse { $inventory->postReceipt($receipt,auth('admin')->id()); return back()->with('success',"Đã ghi sổ {$receipt->number}."); }
    public function createIssue(InventoryService $inventory): View { return view('Pharma::pages.inventory.issue-form',['warehouse'=>$inventory->defaultWarehouse(),'medicines'=>$this->medicines()]); }
    public function storeIssue(Request $request, InventoryService $inventory): RedirectResponse
    {
        $data=$request->validate(['issue_date'=>'required|date','recipient_name'=>'nullable|string|max:255','notes'=>'nullable|string','items'=>'required|array|min:1','items.*.medicine_id'=>'required|exists:pharma_medicines,id','items.*.batch_number'=>'required|string|max:100','items.*.expiry_date'=>'required|date','items.*.quantity'=>'required|numeric|gt:0','items.*.unit_price'=>'nullable|numeric|min:0']);
        $issue=DB::transaction(function()use($data,$inventory){$i=InventoryIssue::create(['warehouse_id'=>$inventory->defaultWarehouse()->id,'number'=>$this->number('PX'),'issue_date'=>$data['issue_date'],'recipient_name'=>$data['recipient_name']??null,'notes'=>$data['notes']??null,'created_by'=>auth('admin')->id()]);$i->items()->createMany($data['items']);return $i;});
        return redirect()->route('admin.pharma.inventory.index')->with('success',"Đã tạo phiếu xuất {$issue->number} ở trạng thái nháp.");
    }
    public function postIssue(InventoryIssue $issue, InventoryService $inventory): RedirectResponse { $inventory->postIssue($issue,auth('admin')->id()); return back()->with('success',"Đã ghi sổ {$issue->number}."); }
    private function medicines(){ return Medicine::query()->orderBy('name')->limit(500)->get(['id','medicine_code','name','unit']); }
    private function number(string $prefix): string { return $prefix.'-'.now()->format('Ymd-His').'-'.str_pad((string)random_int(1,999),3,'0',STR_PAD_LEFT); }
}