@extends('Admin::layouts.master')
@section('title','Xuất bán hàng thầu')
@section('admin_container','full')
@section('content')
<div class="mx-auto w-full max-w-[1580px] space-y-5">
 <header><a href="{{ route('admin.pharma.inventory.issues.index') }}" class="text-sm font-semibold text-indigo-700">← Phiếu xuất kho</a><h1 class="mt-2 text-2xl font-bold text-slate-950">Xuất bán hàng thầu</h1><p class="mt-1 text-sm text-slate-500">Chọn chủ đầu tư và bệnh viện đã được phân bổ. Mã hàng, hạn mức và đơn giá được lấy từ Quản lý kết quả trúng thầu.</p></header>
 @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">{{ $errors->first() }}</div>@endif
 <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="grid gap-4 lg:grid-cols-3">
  <label class="text-sm font-semibold">Ngày xuất<input id="issue-date" type="date" value="{{ now()->format('Y-m-d') }}" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
  <label class="text-sm font-semibold">Chủ đầu tư<select id="investor" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3"><option value="">— Chọn chủ đầu tư —</option>@foreach($investors as $investor)@php($key=$investor->investor_code ?: $investor->investor_name)<option value="{{ $key }}">{{ $investor->investor_name }}@if($investor->investor_code) · {{ $investor->investor_code }}@endif</option>@endforeach</select></label>
  <label class="text-sm font-semibold">Khách hàng / Bệnh viện<select id="partner" disabled class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3"><option value="">— Chọn chủ đầu tư trước —</option></select></label>
 </div></section>
 <form id="bid-sale-form" method="POST" action="{{ route('admin.pharma.inventory.issues.bid-sales.store') }}" class="space-y-5">@csrf<input type="hidden" name="issue_date" id="form-date">
  <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="overflow-auto"><table class="min-w-[1250px] w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-600"><tr><th class="w-12 px-4 py-3"></th><th class="px-4 py-3">Mã hàng / Thuốc</th><th class="px-4 py-3">ĐVT</th><th class="px-4 py-3 text-right">SL phân bổ</th><th class="px-4 py-3 text-right">Đã xuất</th><th class="px-4 py-3 text-right">Còn lại</th><th class="px-4 py-3 text-right">Đơn giá trúng thầu</th><th class="w-[260px] px-4 py-3">Lô tồn FEFO</th><th class="w-[140px] px-4 py-3">SL xuất</th></tr></thead><tbody id="rows"><tr><td colspan="9" class="px-6 py-12 text-center text-slate-500">Chọn chủ đầu tư và bệnh viện để tải danh sách phân bổ.</td></tr></tbody></table></div></section>
  <section class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex-row sm:items-end"><label class="flex-1 text-sm font-semibold">Ghi chú<textarea name="notes" rows="2" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2"></textarea></label><button class="min-h-11 rounded-xl bg-indigo-600 px-5 text-sm font-bold text-white">Tạo phiếu xuất hàng thầu</button></section>
 </form>
</div>
<script>
const investor=document.getElementById('investor'),partner=document.getElementById('partner'),rows=document.getElementById('rows'),form=document.getElementById('bid-sale-form');
let allRows=[];
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
const fmt=n=>new Intl.NumberFormat('vi-VN',{maximumFractionDigits:4}).format(Number(n||0));
async function load(){
 const key=investor.value;if(!key){partner.disabled=true;partner.innerHTML='<option value="">— Chọn chủ đầu tư trước —</option>';rows.innerHTML='<tr><td colspan="9" class="px-6 py-12 text-center text-slate-500">Chọn chủ đầu tư và bệnh viện để tải danh sách phân bổ.</td></tr>';return}
 const url=new URL(@json(route('admin.pharma.inventory.issues.bid-sales.allocations')),location.origin);url.searchParams.set('investor',key);
 const res=await fetch(url,{headers:{'Accept':'application/json'}});const json=await res.json();allRows=json.data||[];
 const partners=[...new Map(allRows.map(r=>[r.partner_id,r.partner_name])).entries()];partner.disabled=false;partner.innerHTML='<option value="">— Chọn bệnh viện —</option>'+partners.map(([id,name])=>`<option value="${id}">${esc(name)}</option>`).join('');render([]);
}
function render(data){rows.innerHTML=data.length?data.map(r=>{const balances=r.balances||[];const opts=balances.map(b=>`<option value="${b.id}">${esc(b.batch_number)} · HSD ${String(b.expiry_date).slice(0,10)} · tồn ${fmt(b.quantity_on_hand)}</option>`).join('');return `<tr class="border-t border-slate-100"><td class="px-4 py-3"><input type="checkbox" name="allocation_ids[]" value="${r.allocation_id}" class="h-4 w-4"></td><td class="px-4 py-3"><div class="font-mono text-xs font-bold text-indigo-700">${esc(r.medicine_code)}</div><div class="font-semibold">${esc(r.medicine_name)}</div></td><td class="px-4 py-3">${esc(r.unit||'—')}</td><td class="px-4 py-3 text-right">${fmt(r.allocated_quantity)}</td><td class="px-4 py-3 text-right">${fmt(r.issued_quantity)}</td><td class="px-4 py-3 text-right font-bold text-emerald-700">${fmt(r.remaining_quantity)}</td><td class="px-4 py-3 text-right font-semibold">${fmt(r.winning_price)}</td><td class="px-4 py-3"><select name="balance_ids[${r.allocation_id}]" class="w-full rounded-lg border border-slate-300 px-2 py-2"><option value="">— Chọn lô —</option>${opts}</select></td><td class="px-4 py-3"><input type="number" min="0" max="${r.remaining_quantity}" step="0.001" name="quantities[${r.allocation_id}]" class="w-full rounded-lg border border-slate-300 px-2 py-2"></td></tr>`}).join(''):'<tr><td colspan="9" class="px-6 py-12 text-center text-slate-500">Không còn phân bổ hợp lệ cho lựa chọn này.</td></tr>'}
investor.addEventListener('change',load);partner.addEventListener('change',()=>render(allRows.filter(r=>String(r.partner_id)===partner.value)));
form.addEventListener('submit',()=>document.getElementById('form-date').value=document.getElementById('issue-date').value);
</script>
@endsection
