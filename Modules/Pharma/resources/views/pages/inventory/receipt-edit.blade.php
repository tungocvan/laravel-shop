@extends('Admin::layouts.master')
@section('title','Cập nhật phiếu nhập')
@section('content')
<div class="mx-auto max-w-7xl space-y-5">
 <header><a href="{{ route('admin.pharma.inventory.receipts.index') }}" class="text-sm font-semibold text-indigo-700">← Danh sách phiếu nhập</a><h1 class="mt-2 text-2xl font-bold">{{ $receipt->status === 'draft' ? 'Sửa phiếu nhập nháp' : 'Cập nhật thông tin phiếu nhập' }}</h1>
 @if($receipt->status === 'posted')<p class="mt-1 text-sm text-amber-700">Phiếu đã ghi sổ: chỉ cập nhật thông tin chứng từ, không thay đổi thuốc/lô/HSD/số lượng/giá nhập.</p>@endif</header>
 @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">{{ $errors->first() }}</div>@endif
 <form method="POST" action="{{ route('admin.pharma.inventory.receipts.update',$receipt) }}" class="space-y-5">@csrf @method('PUT')
  <section class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 md:grid-cols-2 lg:grid-cols-4">
   <label class="text-sm font-medium">Ngày nhập<input type="date" name="receipt_date" required value="{{ old('receipt_date',$receipt->receipt_date->toDateString()) }}" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
   <label class="text-sm font-medium">Nhà cung cấp *
    <x-select-search id="receipt-edit-supplier" name="supplier_name" placeholder="Tìm nhà cung cấp..."><option value="">Chọn nhà cung cấp</option>@foreach($partners as $partner)<option value="{{ $partner->name }}" @selected(old('supplier_name',$receipt->supplier_name)===$partner->name)>{{ $partner->name }}{{ $partner->tax_code ? ' — MST '.$partner->tax_code : '' }}</option>@endforeach</x-select-search>
   </label>
   <label class="text-sm font-medium">Số hóa đơn<input name="invoice_number" value="{{ old('invoice_number',$receipt->invoice_number) }}" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
   <label class="text-sm font-medium">Ngày hóa đơn<input type="date" name="invoice_date" value="{{ old('invoice_date',$receipt->invoice_date?->toDateString()) }}" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
  </section>
  @if($receipt->status === 'draft')
   @php $draftItems=collect(old('items',$receipt->items->map(fn($item)=>['medicine_id'=>$item->medicine_id,'batch_number'=>$item->batch_number,'expiry_date'=>$item->expiry_date->toDateString(),'quantity'=>$item->quantity,'unit_price_ex_vat'=>$item->unit_price_ex_vat,'vat_rate'=>$item->vat_rate])->all())); @endphp
   <section class="rounded-2xl border border-slate-200 bg-white p-5">
    <div class="flex items-start justify-between gap-3"><div><h2 class="font-semibold">Chi tiết thuốc / lô</h2><p class="mt-1 text-xs text-slate-500">Phiếu nháp được đổi Medicine, thêm/xóa dòng và chỉnh thông tin lô trước khi ghi sổ.</p></div><button type="button" id="receipt-add-item" class="rounded-xl bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700">+ Thêm sản phẩm</button></div>
    <div class="mt-4 overflow-x-auto"><table class="min-w-[1100px] w-full text-left text-sm">
     <thead class="bg-slate-50 text-xs uppercase text-slate-600"><tr><th class="px-3 py-2 min-w-[300px]">Thuốc</th><th class="px-3 py-2">Số lô</th><th class="px-3 py-2">HSD</th><th class="px-3 py-2">SL</th><th class="px-3 py-2">Giá nhập chưa VAT</th><th class="px-3 py-2 text-right">Thao tác</th></tr></thead>
     <tbody id="receipt-items">
      @foreach($draftItems as $i=>$item)
       <tr data-receipt-row>
        <td class="px-3 py-2"><select data-medicine-select name="items[{{ $i }}][medicine_id]" required class="w-full"><option value="">Chọn Medicine</option>@foreach($medicines as $medicine)<option value="{{ $medicine->id }}" @selected((int)$item['medicine_id']===$medicine->id)>{{ $medicine->medicine_code }} · {{ $medicine->name }}</option>@endforeach</select></td>
        <td class="px-3 py-2"><input name="items[{{ $i }}][batch_number]" value="{{ $item['batch_number'] }}" required class="min-h-10 w-full rounded-lg border border-slate-300 px-2"></td>
        <td class="px-3 py-2"><input type="date" name="items[{{ $i }}][expiry_date]" value="{{ $item['expiry_date'] }}" required class="min-h-10 w-full rounded-lg border border-slate-300 px-2"></td>
        <td class="px-3 py-2"><input type="number" step="0.001" min="0.001" name="items[{{ $i }}][quantity]" value="{{ $item['quantity'] }}" required class="min-h-10 w-full rounded-lg border border-slate-300 px-2"></td>
        <td class="px-3 py-2"><input type="number" step="0.0001" min="0" name="items[{{ $i }}][unit_price_ex_vat]" value="{{ $item['unit_price_ex_vat'] }}" required class="min-h-10 w-full rounded-lg border border-slate-300 px-2"><input type="hidden" name="items[{{ $i }}][vat_rate]" value="{{ $item['vat_rate'] ?? 0 }}"></td>
        <td class="px-3 py-2 text-right"><button type="button" data-remove-receipt-row class="text-sm font-semibold text-rose-700">Xóa</button></td>
       </tr>
      @endforeach
     </tbody>
    </table></div>
   </section>
   <template id="receipt-item-template"><tr data-receipt-row>
    <td class="px-3 py-2"><select data-medicine-select data-name="medicine_id" required class="w-full"><option value="">Chọn Medicine</option>@foreach($medicines as $medicine)<option value="{{ $medicine->id }}">{{ $medicine->medicine_code }} · {{ $medicine->name }}</option>@endforeach</select></td>
    <td class="px-3 py-2"><input data-name="batch_number" required class="min-h-10 w-full rounded-lg border border-slate-300 px-2"></td>
    <td class="px-3 py-2"><input type="date" data-name="expiry_date" required class="min-h-10 w-full rounded-lg border border-slate-300 px-2"></td>
    <td class="px-3 py-2"><input type="number" step="0.001" min="0.001" data-name="quantity" required class="min-h-10 w-full rounded-lg border border-slate-300 px-2"></td>
    <td class="px-3 py-2"><input type="number" step="0.0001" min="0" data-name="unit_price_ex_vat" required class="min-h-10 w-full rounded-lg border border-slate-300 px-2"><input type="hidden" data-name="vat_rate" value="0"></td>
    <td class="px-3 py-2 text-right"><button type="button" data-remove-receipt-row class="text-sm font-semibold text-rose-700">Xóa</button></td>
   </tr></template>
  @endif
  <div class="flex justify-end"><button class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white">Lưu thay đổi</button></div>
 </form>
 @if($receipt->status === 'draft')
 <script>
 document.addEventListener('DOMContentLoaded',()=>{
  const body=document.getElementById('receipt-items'), template=document.getElementById('receipt-item-template');
  const initSelect=(select)=>{ if(!select || select.tomselect || !window.TomSelect) return; new TomSelect(select,{plugins:['dropdown_input'],placeholder:'Tìm mã / tên thuốc...',create:false,allowEmptyOption:true,dropdownParent:'body'}); };
  const renumber=()=>{ [...body.querySelectorAll('[data-receipt-row]')].forEach((row,index)=>{ row.querySelectorAll('[name],[data-name]').forEach(input=>{ const field=input.dataset.name || (input.name.match(/\]\[([^\]]+)\]$/)||[])[1]; if(field) input.name=`items[${index}][${field}]`; }); }); };
  body.querySelectorAll('[data-medicine-select]').forEach(initSelect);
  document.getElementById('receipt-add-item')?.addEventListener('click',()=>{ const row=template.content.firstElementChild.cloneNode(true); body.appendChild(row); renumber(); initSelect(row.querySelector('[data-medicine-select]')); });
  body.addEventListener('click',(event)=>{ const button=event.target.closest('[data-remove-receipt-row]'); if(!button) return; if(body.querySelectorAll('[data-receipt-row]').length<=1){ alert('Phiếu nhập phải có ít nhất một sản phẩm.'); return; } const row=button.closest('[data-receipt-row]'); row.querySelector('[data-medicine-select]')?.tomselect?.destroy(); row.remove(); renumber(); });
 });
 </script>
 @endif
</div>
@endsection
