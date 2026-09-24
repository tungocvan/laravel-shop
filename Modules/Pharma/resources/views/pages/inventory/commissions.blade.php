@extends('Admin::layouts.master')
@section('title','Hoa hồng kinh doanh')
@section('admin_container','full')
@section('content')
<div class="mx-auto w-full max-w-[1580px] space-y-6">
 <div>
  <a href="{{ route('admin.pharma.inventory.index') }}" class="text-sm font-semibold text-indigo-700">← Quản lý kho</a>
  <h1 class="mt-2 text-2xl font-bold text-slate-950">Hoa hồng kinh doanh</h1>
  <p class="mt-1 text-sm text-slate-500">Tổng hợp snapshot hoa hồng đã phát sinh khi phiếu bán hàng thầu được ghi sổ. Chính sách lịch sử không bị tính lại khi cấu hình hiện tại thay đổi.</p>
 </div>

 <div class="space-y-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
  <form method="GET" id="commission-filter-form" class="space-y-3">
   <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[180px_180px_auto_1fr] lg:items-end">
    <label class="text-sm font-semibold text-slate-700">Từ ngày<input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
    <label class="text-sm font-semibold text-slate-700">Đến ngày<input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
    <button class="min-h-11 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white">Lọc dữ liệu</button>
   </div>
   <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-[minmax(200px,1fr)_minmax(240px,1.2fr)_minmax(240px,1.2fr)_auto] xl:items-end">
    <label class="text-sm font-semibold text-slate-700">User phụ trách
     <select name="user_id" id="commission-user-filter" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3">
      <option value="">Tất cả User</option>
      @foreach($users as $user)<option value="{{ $user->id }}" @selected((int)$userId===$user->id)>{{ $user->name }}</option>@endforeach
     </select>
    </label>
    <label class="text-sm font-semibold text-slate-700">Bệnh viện
     <select name="partner_id" id="commission-partner-filter" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3">
      <option value="">Tất cả bệnh viện</option>
      @foreach($partners as $partner)<option value="{{ $partner->id }}" @selected((int)$partnerId===$partner->id)>{{ $partner->name }}</option>@endforeach
     </select>
    </label>
    <label class="text-sm font-semibold text-slate-700">Sản phẩm
     <select name="medicine_id" id="commission-medicine-filter" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3">
      <option value="">Tất cả sản phẩm</option>
      @foreach($medicines as $medicine)<option value="{{ $medicine->id }}" @selected((int)$medicineId===$medicine->id)>{{ $medicine->name }} · {{ $medicine->medicine_code }}</option>@endforeach
     </select>
    </label>
    @if($userId || $partnerId || $medicineId)
     <a href="{{ route('admin.pharma.inventory.commissions.index',['from'=>$from->format('Y-m-d'),'to'=>$to->format('Y-m-d')]) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Xóa bộ lọc</a>
    @endif
   </div>
  </form>
  <div class="flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-3">
   <a href="{{ route('admin.pharma.inventory.commissions.export',request()->only(['from','to','user_id','partner_id','medicine_id'])) }}" class="inline-flex min-h-10 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Xuất Excel theo bộ lọc</a>
   <button type="submit" form="commission-selected-export" id="commission-export-selected" disabled class="min-h-10 rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40">Xuất Excel đã chọn (<span id="commission-selected-count">0</span>)</button>
  </div>
 </div>
 <script>
 document.addEventListener('DOMContentLoaded',()=>{
   const form=document.getElementById('commission-filter-form'), user=document.getElementById('commission-user-filter');
   const partner=document.getElementById('commission-partner-filter'), medicine=document.getElementById('commission-medicine-filter');
   const all=document.getElementById('commission-check-all'), rows=[...document.querySelectorAll('[data-commission-check]')];
   const button=document.getElementById('commission-export-selected'), count=document.getElementById('commission-selected-count');
   const refresh=()=>{ const n=rows.filter(row=>row.checked).length; if(count) count.textContent=n; if(button) button.disabled=n===0; if(all) all.checked=rows.length>0&&n===rows.length; };
   user?.addEventListener('change',()=>{ if(partner) partner.value=''; if(medicine) medicine.value=''; form?.submit(); });
   partner?.addEventListener('change',()=>{ if(medicine) medicine.value=''; form?.submit(); });
   medicine?.addEventListener('change',()=>form?.submit());
   all?.addEventListener('change',()=>{ rows.forEach(row=>row.checked=all.checked); refresh(); });
   rows.forEach(row=>row.addEventListener('change',refresh)); refresh();
 });
 </script>

 <div class="grid gap-4 md:grid-cols-3">
  <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase text-slate-500">Doanh thu tính hoa hồng</p><p class="mt-2 text-2xl font-bold text-slate-950">{{ number_format((float)$totals->revenue,0,',','.') }} đ</p></div>
  <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-5 shadow-sm"><p class="text-xs font-bold uppercase text-emerald-700">Hoa hồng phát sinh</p><p class="mt-2 text-2xl font-bold text-emerald-800">{{ number_format((float)$totals->commission,0,',','.') }} đ</p></div>
  <div class="rounded-2xl border {{ $unresolved ? 'border-amber-300 bg-amber-50' : 'border-slate-200 bg-white' }} p-5 shadow-sm"><p class="text-xs font-bold uppercase text-slate-500">Chưa đủ chính sách/phân công</p><p class="mt-2 text-2xl font-bold {{ $unresolved ? 'text-amber-800' : 'text-slate-950' }}">{{ $unresolved }}</p></div>
 </div>

 <form method="GET" action="{{ route('admin.pharma.inventory.commissions.export') }}" id="commission-selected-export">
  <input type="hidden" name="from" value="{{ $from->format('Y-m-d') }}">
  <input type="hidden" name="to" value="{{ $to->format('Y-m-d') }}">
  @if($userId)<input type="hidden" name="user_id" value="{{ $userId }}">@endif
  @if($partnerId)<input type="hidden" name="partner_id" value="{{ $partnerId }}">@endif
  @if($medicineId)<input type="hidden" name="medicine_id" value="{{ $medicineId }}">@endif
 </form>
 <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
  <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold text-slate-900">Chi tiết phát sinh</h2><p class="mt-1 text-xs text-slate-500">Hoa hồng = Số lượng thực xuất × Đơn giá trúng thầu × Chính sách % tại thời điểm ghi sổ.</p></div>
  <div class="overflow-x-auto">
   <table class="w-full min-w-[1280px] text-sm">
    <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="w-10 p-3 text-center"><input id="commission-check-all" type="checkbox" class="h-4 w-4 rounded border-slate-300"></th><th class="p-3 text-left">Ngày ghi sổ</th><th class="p-3 text-left">Phiếu</th><th class="p-3 text-left">Bệnh viện</th><th class="p-3 text-left">Sản phẩm</th><th class="p-3 text-left">User</th><th class="p-3 text-right">SL</th><th class="p-3 text-right">Đơn giá</th><th class="p-3 text-right">Doanh thu</th><th class="p-3 text-right">%</th><th class="p-3 text-right">Hoa hồng</th><th class="p-3 text-left">Trạng thái</th></tr></thead>
    <tbody class="divide-y divide-slate-100">
    @forelse($rows as $row)
     <tr class="hover:bg-slate-50/60">
      <td class="p-3 text-center"><input data-commission-check form="commission-selected-export" type="checkbox" name="ids[]" value="{{ $row->id }}" class="h-4 w-4 rounded border-slate-300"></td>
      <td class="p-3">{{ $row->calculated_at->format('d/m/Y H:i') }}</td>
      <td class="p-3"><a class="font-mono font-semibold text-indigo-700" href="{{ route('admin.pharma.inventory.issues.show',$row->issue_id) }}">{{ $row->issue?->number }}</a>@if($row->entry_type==='reversal')<div class="text-xs text-rose-600">Hoàn tác</div>@endif</td>
      <td class="p-3">{{ $row->partner?->name ?: '—' }}</td>
      <td class="p-3"><p class="font-semibold">{{ $row->medicine?->name }}</p><p class="text-xs text-slate-500">{{ $row->medicine?->medicine_code }}</p></td>
      <td class="p-3">{{ $row->user?->name ?: 'Chưa phân công' }}</td>
      <td class="p-3 text-right">{{ number_format((float)$row->quantity,0,',','.') }}</td>
      <td class="p-3 text-right">{{ number_format((float)$row->unit_price,0,',','.') }} đ</td>
      <td class="p-3 text-right font-semibold">{{ number_format((float)$row->revenue_amount,0,',','.') }} đ</td>
      <td class="p-3 text-right">{{ $row->commission_percentage !== null ? rtrim(rtrim(number_format((float)$row->commission_percentage,4,'.',''),'0'),'.').'%' : '—' }}</td>
      <td class="p-3 text-right font-bold">{{ number_format((float)$row->commission_amount,0,',','.') }} đ</td>
      <td class="p-3">@if($row->status==='unresolved')<span class="font-semibold text-amber-700">Chưa đủ dữ liệu</span><p class="mt-1 max-w-xs text-xs text-slate-500">{{ $row->resolution_note }}</p>@elseif($row->entry_type==='reversal')<span class="font-semibold text-rose-700">Đã đảo</span>@else<span class="font-semibold text-emerald-700">Đã tính</span>@endif</td>
     </tr>
    @empty
     <tr><td colspan="12" class="p-10 text-center text-slate-500">Chưa có phát sinh hoa hồng trong khoảng thời gian đã chọn.</td></tr>
    @endforelse
    </tbody>
   </table>
  </div>
  @if($rows->hasPages())<div class="border-t border-slate-100 p-4">{{ $rows->links() }}</div>@endif
 </section>
</div>
@endsection
