@extends('Admin::layouts.master')
@section('title','Trung tâm hoa hồng')
@section('admin_container','full')
@section('content')
<div class="mx-auto w-full max-w-[1580px] space-y-5">
 <header><a href="{{ route('admin.pharma.dashboard') }}" class="text-sm font-semibold text-indigo-700 hover:text-indigo-900">← Trung tâm điều hành Pharma</a><h1 class="mt-2 text-2xl font-bold text-slate-950">Trung tâm hoa hồng</h1><p class="mt-1 text-sm text-slate-500">Tổng hợp hoa hồng từ mọi phiếu xuất đã ghi sổ. Dữ liệu chính sách được snapshot tại thời điểm ghi sổ.</p></header>
 <form method="GET" id="commission-filter-form" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
  <div><p class="text-xs font-bold uppercase tracking-wide text-indigo-600">1 · Nguồn tính hoa hồng</p><div class="mt-2 flex flex-wrap gap-2">
   @foreach(['all'=>'Tất cả','price_list'=>'Theo bảng giá','bid'=>'Hàng thầu'] as $key=>$label)<label class="cursor-pointer"><input type="radio" name="source" value="{{ $key }}" class="peer sr-only" @checked($source===$key) onchange="this.form.submit()"><span class="inline-flex min-h-10 items-center rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 peer-checked:text-indigo-700">{{ $label }}</span></label>@endforeach
  </div></div>
  <div class="border-t border-slate-100 pt-4"><p class="text-xs font-bold uppercase tracking-wide text-indigo-600">2 · Phạm vi phát sinh</p>
   <div class="mt-2 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
    <label class="text-sm font-semibold text-slate-700">Người phụ trách<select name="user_id" id="commission-user-filter" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3"><option value="">Tất cả User</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((int)$userId===$user->id)>{{ $user->name }}</option>@endforeach</select></label>
    <label class="text-sm font-semibold text-slate-700">Khách hàng / Bệnh viện<select name="partner_id" id="commission-partner-filter" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3"><option value="">Tất cả khách hàng</option>@foreach($partners as $partner)<option value="{{ $partner->id }}" @selected((int)$partnerId===$partner->id)>{{ $partner->name }}</option>@endforeach</select></label>
    <label class="text-sm font-semibold text-slate-700">Sản phẩm<select name="medicine_id" id="commission-medicine-filter" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3"><option value="">Tất cả sản phẩm</option>@foreach($medicines as $medicine)<option value="{{ $medicine->id }}" @selected((int)$medicineId===$medicine->id)>{{ $medicine->name }} · {{ $medicine->medicine_code }}</option>@endforeach</select></label>
    <label class="text-sm font-semibold text-slate-700">Từ ngày<input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
    <label class="text-sm font-semibold text-slate-700">Đến ngày<input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
   </div>
   <div class="mt-3 flex justify-end gap-2"><a href="{{ route('admin.pharma.inventory.commissions.index') }}" class="inline-flex min-h-10 items-center rounded-xl border border-slate-300 px-4 text-sm font-semibold">Xóa bộ lọc</a><button class="min-h-10 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white">Áp dụng</button></div>
  </div>
 </form>
 <div class="grid gap-3 md:grid-cols-4">
  <div class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs font-bold uppercase text-slate-500">Giá trị bán</p><p class="mt-2 text-2xl font-bold">{{ number_format((float)$totals->revenue,0,',','.') }} đ</p></div>
  <div class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs font-bold uppercase text-slate-500">Giá trị thu · bảng giá</p><p class="mt-2 text-2xl font-bold">{{ number_format((float)$receivableTotal,0,',','.') }} đ</p></div>
  <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-5"><p class="text-xs font-bold uppercase text-emerald-700">Hoa hồng phát sinh</p><p class="mt-2 text-2xl font-bold text-emerald-800">{{ number_format((float)$totals->commission,0,',','.') }} đ</p></div>
  <div class="rounded-2xl border {{ $unresolved ? 'border-amber-300 bg-amber-50' : 'border-slate-200 bg-white' }} p-5"><p class="text-xs font-bold uppercase text-slate-500">Chưa đủ dữ liệu</p><p class="mt-2 text-2xl font-bold">{{ $unresolved }}</p></div>
 </div>
 <div class="flex justify-end"><a href="{{ route('admin.pharma.inventory.commissions.export',request()->only(['source','from','to','user_id','partner_id','medicine_id'])) }}" class="inline-flex min-h-10 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold">Xuất Excel theo bộ lọc</a></div>
 <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
  <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold">Chi tiết phát sinh</h2><p class="mt-1 text-xs text-slate-500">Bảng giá: HH = SL × (Giá bán CT − Giá thu). Hàng thầu: giữ nguyên chính sách % đã snapshot.</p></div>
  <div class="overflow-x-auto"><table class="w-full min-w-[1450px] text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="p-3 text-left">Ngày ghi sổ</th><th class="p-3 text-left">Phiếu</th><th class="p-3 text-left">Nguồn</th><th class="p-3 text-left">Khách hàng</th><th class="p-3 text-left">Sản phẩm</th><th class="p-3 text-left">User</th><th class="p-3 text-right">SL</th><th class="p-3 text-right">Giá bán / trúng thầu</th><th class="p-3 text-right">CK</th><th class="p-3 text-right">Giá thu</th><th class="p-3 text-right">Hoa hồng</th><th class="p-3 text-left">Trạng thái</th></tr></thead>
  <tbody class="divide-y divide-slate-100">@forelse($rows as $row)<tr class="hover:bg-slate-50/60">
   <td class="p-3">{{ $row->calculated_at->format('d/m/Y H:i') }}</td><td class="p-3"><a class="font-mono font-semibold text-indigo-700" href="{{ route('admin.pharma.inventory.issues.show',$row->issue_id) }}">{{ $row->issue?->number }}</a></td>
   <td class="p-3"><span class="rounded-lg px-2 py-1 text-xs font-bold {{ $row->source_type==='bid' ? 'bg-violet-50 text-violet-700' : 'bg-sky-50 text-sky-700' }}">{{ $row->source_type==='bid' ? 'HÀNG THẦU' : 'BẢNG GIÁ' }}</span></td>
   <td class="p-3">{{ $row->partner?->name ?: '—' }}</td><td class="p-3"><p class="font-semibold">{{ $row->medicine?->name }}</p><p class="text-xs text-slate-500">{{ $row->medicine?->medicine_code }}</p></td><td class="p-3">{{ $row->user?->name ?: 'Chưa phân công' }}</td>
   <td class="p-3 text-right">{{ number_format((float)$row->quantity,0,',','.') }}</td><td class="p-3 text-right">{{ number_format((float)$row->unit_price,0,',','.') }} đ</td>
   <td class="p-3 text-right">{{ $row->commission_percentage !== null ? rtrim(rtrim(number_format((float)$row->commission_percentage,4,'.',''),'0'),'.').'%' : '—' }}</td>
   <td class="p-3 text-right">{{ $row->receivable_price_snapshot !== null ? number_format((float)$row->receivable_price_snapshot,0,',','.').' đ' : '—' }}</td>
   <td class="p-3 text-right font-bold">{{ number_format((float)$row->commission_amount,0,',','.') }} đ</td><td class="p-3">@if($row->status==='unresolved')<span class="font-semibold text-amber-700">Chưa đủ dữ liệu</span><p class="text-xs text-slate-500">{{ $row->resolution_note }}</p>@else<span class="font-semibold text-emerald-700">Đã tính</span>@endif</td>
  </tr>@empty<tr><td colspan="12" class="p-10 text-center text-slate-500">Chưa có phát sinh hoa hồng trong phạm vi đã chọn.</td></tr>@endforelse</tbody></table></div>
  @if($rows->hasPages())<div class="border-t border-slate-100 p-4">{{ $rows->links() }}</div>@endif
 </section>
</div>
<script>document.addEventListener('DOMContentLoaded',()=>{const f=document.getElementById('commission-filter-form'),u=document.getElementById('commission-user-filter'),p=document.getElementById('commission-partner-filter');u?.addEventListener('change',()=>{if(p)p.value='';f?.submit()});p?.addEventListener('change',()=>f?.submit())})</script>
@endsection
