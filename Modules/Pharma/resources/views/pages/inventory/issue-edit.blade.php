@extends('Admin::layouts.master')
@section('title','Sửa phiếu xuất nháp')
@section('admin_container','full')
@section('content')
@php
    $priceListManagers=$customerPriceLists->pluck('manager')->filter()->unique('id')->sortBy('name')->values();
    $selectedPriceList=$issue->priceList;
    $selectedManagerId=$selectedPriceList?->manager_user_id;
    $initialItems=$issue->items->map(function ($item) {
        return [
            'medicine_id'=>$item->medicine_id,'batch_number'=>$item->batch_number,'expiry_date'=>$item->expiry_date->format('Y-m-d'),
            'quantity'=>(float)$item->quantity,'unit_price'=>(float)$item->unit_price,
        ];
    })->values();
    $balanceOptions=$availableBalances->map(function ($balance) {
        return ['id'=>$balance->id,'medicine_id'=>$balance->medicine_id,'batch'=>$balance->batch_number,'expiry'=>$balance->expiry_date->format('Y-m-d'),'quantity'=>(float)$balance->quantity_on_hand];
    })->values();
    $medicineOptions=$availableBalances->pluck('medicine')->unique('id')->values()->map(function ($medicine) {
        return ['id'=>$medicine->id,'text'=>$medicine->medicine_code.' — '.$medicine->name];
    })->values();
@endphp
<div class="w-full space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div><a href="{{ route('admin.pharma.inventory.issues.index') }}" class="text-sm font-semibold text-indigo-700">← Danh sách phiếu xuất</a><h1 class="mt-1 text-2xl font-bold text-slate-950">Sửa phiếu xuất nháp · {{ $issue->number }}</h1><p class="text-sm text-slate-500">Có thể sửa toàn bộ chứng từ trước khi ghi sổ. Đơn giá trên dòng là snapshot sẽ được lưu cùng phiếu.</p></div>
        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold uppercase text-amber-800">Nháp</span>
    </div>
    <form method="POST" action="{{ route('admin.pharma.inventory.issues.update',$issue) }}" class="space-y-5">@csrf @method('PUT')
        <section class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 md:grid-cols-2 xl:grid-cols-4">
            <label class="text-sm font-medium">Ngày xuất<input id="issue-date" type="date" name="issue_date" value="{{ old('issue_date',$issue->issue_date->format('Y-m-d')) }}" required class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
            <label class="text-sm font-medium">Khách hàng / nơi nhận<x-select-search id="issue-edit-recipient" name="recipient_partner_id" placeholder="Tìm khách hàng / nơi nhận..."><option value="">Chọn khách hàng</option>@foreach($partners as $partner)<option value="{{ $partner->id }}" @selected($issue->recipient_name===$partner->name)>{{ $partner->name }}{{ $partner->tax_code ? ' · MST '.$partner->tax_code : '' }}</option>@endforeach</x-select-search><input type="hidden" name="recipient_name" value="{{ $issue->recipient_name }}"></label>
            <label class="text-sm font-medium">Người phụ trách<select id="manager" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"><option value="">Chọn người phụ trách</option>@foreach($priceListManagers as $manager)<option value="{{ $manager->id }}" @selected((string)$selectedManagerId===(string)$manager->id)>{{ $manager->name }}</option>@endforeach</select></label>
            <label class="text-sm font-medium">Bảng giá xuất <span class="text-rose-600">*</span><select id="price-list" name="price_list_id" required class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3">@foreach($customerPriceLists as $list)<option value="{{ $list->id }}" data-manager="{{ $list->manager_user_id }}" @selected($issue->price_list_id===$list->id)>{{ $list->code }} — {{ $list->name }}</option>@endforeach</select><span class="mt-1 block text-xs text-slate-500">Chỉ bảng giá CUSTOMER đang hoạt động.</span></label>
        </section>
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex items-center justify-between border-b p-4"><div><h2 class="font-bold">Chi tiết hàng xuất</h2><p class="text-xs text-slate-500">Sửa thuốc, lô/HSD, số lượng và đơn giá trước khi ghi sổ.</p></div><button type="button" id="add-row" class="rounded-xl border border-indigo-200 px-3 py-2 text-sm font-semibold text-indigo-700">+ Thêm dòng</button></div>
            <div class="overflow-x-auto"><table class="min-w-[1100px] w-full text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-3 text-left">Thuốc</th><th class="p-3 text-left">Lô · HSD</th><th class="p-3 text-right">Tồn</th><th class="p-3 text-right">SL xuất</th><th class="p-3 text-right">Đơn giá xuất</th><th class="p-3 text-right">Thành tiền</th><th class="p-3"></th></tr></thead><tbody id="rows"></tbody></table></div>
        </section>
        <label class="block rounded-2xl border border-slate-200 bg-white p-5 text-sm font-medium">Ghi chú<textarea name="notes" rows="3" class="mt-2 w-full rounded-xl border border-slate-300 p-3">{{ old('notes',$issue->notes) }}</textarea></label>
        <div class="flex justify-end gap-3"><a href="{{ route('admin.pharma.inventory.issues.show',$issue) }}" class="rounded-xl border px-4 py-2.5 text-sm font-semibold">Hủy</a><button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white">Lưu toàn bộ phiếu nháp</button></div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
const balances=@json($balanceOptions);
const medicines=@json($medicineOptions);
const initial=@json($initialItems); let index=0; const rows=document.getElementById('rows');
function addRow(item={}){const tr=document.createElement('tr');tr.className='border-t align-top';const meds=medicines.map(m=>`<option value="${m.id}" ${String(m.id)===String(item.medicine_id||'')?'selected':''}>${m.text}</option>`).join('');tr.innerHTML=`<td class="p-3"><select class="medicine min-h-11 w-full rounded-xl border px-3"><option value="">Chọn thuốc</option>${meds}</select></td><td class="p-3"><select class="lot min-h-11 w-full rounded-xl border px-3"></select></td><td class="stock p-3 text-right font-semibold">—</td><td class="p-3"><input name="items[${index}][quantity]" value="${item.quantity??''}" type="number" step="0.001" min="0.001" required class="qty min-h-11 w-full rounded-xl border px-3 text-right"><p class="warn mt-1 hidden text-xs font-semibold text-rose-600"></p></td><td class="p-3"><input name="items[${index}][unit_price]" value="${item.unit_price??0}" type="number" min="0" step="0.01" required class="price min-h-11 w-full rounded-xl border px-3 text-right"></td><td class="total p-3 text-right font-bold">0 đ</td><td class="p-3 text-right"><button type="button" class="remove text-sm font-semibold text-rose-600">Xóa</button></td>`;rows.appendChild(tr);index++;fillLots(tr,item);tr.querySelector('.medicine').addEventListener('change',()=>fillLots(tr,{}));tr.querySelector('.lot').addEventListener('change',()=>update(tr));tr.querySelector('.qty').addEventListener('input',()=>update(tr));tr.querySelector('.price').addEventListener('input',()=>update(tr));tr.querySelector('.remove').addEventListener('click',()=>tr.remove());}
function fillLots(tr,item){const medicine=tr.querySelector('.medicine').value;const lots=balances.filter(b=>String(b.medicine_id)===String(medicine));const select=tr.querySelector('.lot');select.innerHTML='<option value="">Chọn lô</option>'+lots.map(b=>`<option value="${b.id}" ${b.batch===item.batch_number&&b.expiry===item.expiry_date?'selected':''}>${b.batch} · HSD ${b.expiry} · tồn ${b.quantity}</option>`).join('');select.name=`items[${[...rows.children].indexOf(tr)}][balance_id]`;update(tr);}
function update(tr){const b=balances.find(x=>String(x.id)===String(tr.querySelector('.lot').value));const q=Number(tr.querySelector('.qty').value||0),p=Number(tr.querySelector('.price').value||0),stock=Number(b?.quantity||0),neg=b&&q>stock;tr.querySelector('.stock').textContent=b?new Intl.NumberFormat('vi-VN',{maximumFractionDigits:3}).format(stock):'—';tr.querySelector('.total').textContent=new Intl.NumberFormat('vi-VN').format(q*p)+' đ';const input=tr.querySelector('.qty'),warn=tr.querySelector('.warn');input.classList.toggle('border-rose-500',!!neg);warn.classList.toggle('hidden',!neg);warn.textContent=neg?`Vượt tồn ${q-stock} · tồn sau xuất ${stock-q}`:'';}
initial.forEach(addRow);if(!initial.length)addRow();document.getElementById('add-row').addEventListener('click',()=>addRow());
document.getElementById('manager').addEventListener('change',e=>{const manager=e.target.value;document.querySelectorAll('#price-list option').forEach(o=>o.hidden=!!manager&&o.dataset.manager!==manager);});
});
</script>
@endsection
