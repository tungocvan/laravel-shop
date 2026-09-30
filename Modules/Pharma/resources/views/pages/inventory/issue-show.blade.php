@extends('Admin::layouts.master')
@section('title','Phiếu xuất kho '.$issue->number)
@section('admin_container','full')
@section('content')
@php
$totalValue=$issue->items->sum(fn($i)=>(float)$i->quantity*(float)$i->unit_price);
$signatures=collect([
 ['show'=>$settings->show_issuer_signature,'label'=>$settings->issuer_label,'show_date'=>false],
 ['show'=>$settings->show_deliverer_signature,'label'=>$settings->deliverer_label,'show_date'=>false],
 ['show'=>$settings->show_receiver_signature,'label'=>$settings->receiver_label,'show_date'=>false],
 ['show'=>$settings->show_keeper_signature,'label'=>$settings->keeper_label,'show_date'=>true],
])->where('show',true)->values();
@endphp
<div class="mx-auto w-full max-w-[1580px] space-y-6">
 <div class="flex flex-wrap items-end justify-between gap-4">
  <div><a href="{{ route('admin.pharma.inventory.issues.index') }}" class="text-sm font-semibold text-indigo-700">← Danh sách phiếu xuất</a><div class="mt-2 flex items-center gap-3"><h1 class="text-2xl font-bold text-slate-950">Phiếu xuất kho</h1><span class="rounded-full px-3 py-1 text-xs font-bold uppercase {{ $issue->status==='posted'?'bg-emerald-100 text-emerald-700':'bg-amber-100 text-amber-800' }}">{{ ['draft'=>'Nháp','pending_approval'=>'Chờ duyệt','approved'=>'Đã duyệt','rejected'=>'Từ chối','posted'=>'Đã ghi sổ','cancelled'=>'Đã hủy'][$issue->status] ?? $issue->status }}</span></div><p class="mt-1 text-sm text-slate-500">Chứng từ xuất hàng · {{ $issue->number }}</p></div>
  <div class="flex flex-wrap gap-2">
   @if($issue->status==='approved')
    @can('approve_pharma_inventory_issue')
     <a href="{{ ($issue->issue_source ?? 'normal')==='bid' ? route('admin.pharma.inventory.issues.bid-sales.batches',$issue) : route('admin.pharma.inventory.issues.edit',$issue) }}" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-emerald-700">{{ ($issue->issue_source ?? 'normal')==='bid' ? 'Xử lý kho · Chọn lô' : 'Xử lý kho' }}</a>
    @endcan
   @endif
   @if(in_array($issue->status,['draft','approved'],true))<a href="{{ ($issue->issue_source ?? 'normal')==='bid' ? route('admin.pharma.inventory.issues.bid-sales.edit',$issue) : route('admin.pharma.inventory.issues.edit',$issue) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">{{ ($issue->issue_source ?? 'normal')==='bid' ? 'Sửa đơn hàng thầu' : 'Sửa phiếu' }}</a>@endif
   <a href="{{ route('admin.pharma.inventory.issues.pdf',$issue) }}" class="rounded-xl border border-indigo-200 bg-white px-4 py-2.5 text-sm font-semibold text-indigo-700">↓ Tải PDF</a><a href="{{ route('admin.pharma.inventory.issues.print',$issue) }}" target="_blank" rel="noopener" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm">▣ In trực tiếp</a>
  </div>
 </div>
 @if($issue->status === 'pending_approval')
 @can('approve_pharma_inventory_issue')
 <section class="rounded-2xl border border-amber-200 bg-amber-50/50 p-5 shadow-sm">
  <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
   <div><div class="flex items-center gap-2"><span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">CHỜ DUYỆT</span><h2 class="font-bold text-slate-950">Xử lý đơn hàng</h2></div><p class="mt-2 text-sm text-slate-600">Phê duyệt chỉ xác nhận đơn được phép chuyển sang xử lý kho. Bước này chưa chọn lô, chưa trừ tồn và chưa ghi nhận hoa hồng.</p></div>
   <div class="flex flex-wrap gap-2">
    <button type="button" onclick="document.getElementById('reject-order-dialog').showModal()" class="rounded-xl border border-rose-300 bg-white px-4 py-2.5 text-sm font-bold text-rose-700 hover:bg-rose-50">Từ chối</button>
    <form method="POST" action="{{ route('admin.pharma.inventory.issues.approve-order',$issue) }}">@csrf<button class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-700">Phê duyệt đơn</button></form>
   </div>
  </div>
 </section>
 <dialog id="reject-order-dialog" class="m-auto w-[calc(100%-24px)] max-w-[520px] rounded-[28px] border-0 bg-white p-0 shadow-2xl backdrop:bg-slate-950/60">
  <form method="POST" action="{{ route('admin.pharma.inventory.issues.reject-order',$issue) }}" class="p-6">@csrf
   <div class="flex items-start justify-between gap-4"><div><h3 class="text-lg font-bold text-slate-950">Từ chối đơn hàng</h3><p class="mt-1 text-sm text-slate-500">{{ $issue->number }}</p></div><button type="button" onclick="this.closest('dialog').close()" class="rounded-lg p-2 text-xl text-slate-400 hover:bg-slate-100">×</button></div>
   <label class="mt-5 block text-sm font-semibold text-slate-700">Lý do từ chối *<textarea name="rejection_reason" rows="4" required maxlength="1000" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="Nhập lý do để User biết và điều chỉnh đơn..."></textarea></label>
   <div class="mt-5 flex justify-end gap-2"><button type="button" onclick="this.closest('dialog').close()" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold">Hủy</button><button class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-bold text-white">Xác nhận từ chối</button></div>
  </form>
 </dialog>
 @endcan
 @endif
 <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
  <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold text-slate-900">Thông tin chứng từ</h2><p class="mt-0.5 text-xs text-slate-500">Thông tin giao hàng và chính sách giá tại thời điểm lập phiếu.</p></div>
  <div class="grid gap-px bg-slate-100 md:grid-cols-4">
   <div class="bg-white p-5"><p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Số phiếu</p><p class="mt-2 font-mono font-bold">{{ $issue->number }}</p></div>
   <div class="bg-white p-5"><p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Ngày lên đơn</p><p class="mt-2 font-bold">{{ $issue->issue_date->format('d/m/Y') }}</p></div>
   <div class="bg-white p-5"><p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Trạng thái</p><p class="mt-2 font-bold">{{ ['draft'=>'Nháp','pending_approval'=>'Chờ duyệt','approved'=>'Đã duyệt','rejected'=>'Từ chối','posted'=>'Đã ghi sổ','cancelled'=>'Đã hủy'][$issue->status] ?? $issue->status }}</p></div>
   <div class="bg-white p-5"><p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ $issue->posted_at ? 'Ngày ghi sổ' : 'Kho xuất' }}</p><p class="mt-2 font-bold">{{ $issue->posted_at ? $issue->posted_at->format('d/m/Y H:i') : $settings->warehouse_name }}</p>@if($issue->posted_at)<p class="mt-1 text-xs text-slate-500">{{ $settings->warehouse_name }}</p>@endif</div>
  </div>
  <div class="grid gap-4 border-t border-slate-100 bg-slate-50/70 p-5 md:grid-cols-4">
   <div><p class="text-[11px] font-bold uppercase text-slate-400">Khách hàng / nơi nhận</p><p class="mt-1 font-semibold">{{ $issue->recipient_name ?: 'Chưa xác định' }}</p></div>
   <div><p class="text-[11px] font-bold uppercase text-slate-400">Người phụ trách</p><p class="mt-1 font-semibold">{{ ($issue->issue_source ?? 'normal')==='bid' ? ($bidManagerNames ?: 'Chưa phân công') : ($issue->manager?->name ?: '—') }}</p></div>
   @if(($issue->issue_source ?? 'normal')==='bid')
   <div><p class="text-[11px] font-bold uppercase text-slate-400">Chủ đầu tư / Gói thầu</p><p class="mt-1 font-semibold">{{ $issue->bid_investor_name ?: '—' }}</p>@if($issue->bid_investor_code)<p class="text-xs text-slate-500">{{ $issue->bid_investor_code }}</p>@endif</div>
   @elseif($settings->show_price_list)<div><p class="text-[11px] font-bold uppercase text-slate-400">Bảng giá áp dụng</p><p class="mt-1 font-semibold">{{ $issue->priceList?->code ?: '—' }}</p><p class="text-xs text-slate-500">{{ $issue->priceList?->name }}</p></div>@endif
   @if($settings->show_notes && filled($issue->notes))<div><p class="text-[11px] font-bold uppercase text-slate-400">Ghi chú</p><p class="mt-1 text-sm">{{ $issue->notes }}</p></div>@endif
  </div>
 </section>
 <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
  <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold text-slate-900">Chi tiết hàng xuất</h2><p class="mt-0.5 text-xs text-slate-500">{{ $issue->items->count() }} mặt hàng trong chứng từ.</p></div>
  <div class="overflow-x-auto"><table class="min-w-[1100px] w-full text-sm"><thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500"><tr><th class="p-3 text-center">STT</th><th class="p-3 text-left">Mã thuốc</th><th class="p-3 text-left">Tên thuốc / Quy cách</th><th class="p-3 text-center">ĐVT</th><th class="p-3 text-left">Số lô</th><th class="p-3 text-center">Hạn dùng</th><th class="p-3 text-right">Số lượng</th>@if($settings->show_unit_price)<th class="p-3 text-right">Đơn giá xuất</th>@endif @if($settings->show_total_value)<th class="p-3 text-right">Thành tiền</th>@endif</tr></thead><tbody class="divide-y divide-slate-100">@foreach($issue->items as $item)<tr class="hover:bg-slate-50/60"><td class="p-3 text-center">{{ $loop->iteration }}</td><td class="p-3 font-mono text-xs font-semibold">{{ $item->medicine->medicine_code }}</td><td class="p-3"><b>{{ $item->medicine->name }}</b>@if($item->medicine->packaging_specification)<div class="mt-0.5 text-xs text-slate-500">{{ $item->medicine->packaging_specification }}</div>@endif</td><td class="p-3 text-center">{{ $item->medicine->unit ?: '—' }}</td><td class="p-3">{{ $item->batch_number ?: 'Chưa chọn lô' }}</td><td class="p-3 text-center">{{ $item->expiry_date?->format('d/m/Y') ?: '—' }}</td><td class="p-3 text-right font-semibold">{{ number_format((float)$item->quantity,0,',','.') }}</td>@if($settings->show_unit_price)<td class="p-3 text-right">{{ number_format((float)$item->unit_price,0,',','.') }} đ</td>@endif @if($settings->show_total_value)<td class="p-3 text-right font-bold">{{ number_format((float)$item->quantity*(float)$item->unit_price,0,',','.') }} đ</td>@endif</tr>@endforeach</tbody><tfoot class="border-t-2 border-slate-200 bg-slate-50"><tr><td colspan="7" class="p-4 text-right font-bold">Tổng cộng</td>@if($settings->show_unit_price)<td></td>@endif @if($settings->show_total_value)<td class="p-4 text-right text-base font-bold">{{ number_format($totalValue,0,',','.') }} đ</td>@endif</tr></tfoot></table></div>
 </section>
 @if(($issue->deferredSupplies ?? collect())->isNotEmpty())
 <section class="overflow-hidden rounded-2xl border border-amber-200 bg-amber-50/40 shadow-sm">
  <div class="border-b border-amber-100 px-5 py-4"><div class="flex flex-wrap items-center gap-2"><h2 class="font-bold text-amber-950">Nhật ký chờ cung cấp</h2><span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">{{ $issue->deferredSupplies->count() }} mặt hàng</span></div><p class="mt-1 text-xs text-amber-800">Các số lượng này chưa xuất kho và không tạo bút toán trừ tồn.</p></div>
  <div class="divide-y divide-amber-100">@foreach($issue->deferredSupplies as $deferred)<div class="grid gap-3 px-5 py-4 md:grid-cols-[minmax(0,1fr)_150px_180px]"><div><p class="font-semibold text-slate-900">{{ $deferred->medicine?->medicine_code }} · {{ $deferred->medicine?->name }}</p><p class="mt-1 text-sm text-slate-600">{{ $deferred->note }}</p></div><div><p class="text-[11px] font-bold uppercase text-slate-400">SL chờ cấp</p><p class="mt-1 font-bold text-amber-800">{{ number_format((float)$deferred->quantity,0,',','.') }} {{ $deferred->medicine?->unit }}</p></div><div><p class="text-[11px] font-bold uppercase text-slate-400">Dự kiến cung cấp</p><p class="mt-1 font-semibold">{{ $deferred->expected_supply_date?->format('d/m/Y') ?: 'Chưa xác định' }}</p></div></div>@endforeach</div>
 </section>
 @endif
 <div class="grid gap-5 lg:grid-cols-[360px_minmax(0,1fr)]">
  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-bold">Tóm tắt phiếu</h2><dl class="mt-4 space-y-3 text-sm"><div class="flex justify-between"><dt class="text-slate-500">Số mặt hàng</dt><dd class="font-bold">{{ $issue->items->count() }}</dd></div>@if($settings->show_total_value)<div class="border-t pt-3"><dt class="text-slate-500">Tổng giá trị</dt><dd class="mt-1 text-2xl font-bold text-indigo-700">{{ number_format($totalValue,0,',','.') }} đ</dd></div>@endif</dl></section>
  <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-bold">Người liên quan</h2>
   @if($signatures->isNotEmpty())
   <div class="mt-5 grid gap-6 text-center" style="grid-template-columns: repeat({{ $signatures->count() }}, minmax(0, 1fr));">
    @foreach($signatures as $signature)
    <div><p class="font-semibold">{{ $signature['label'] }}</p>@if($signature['show_date'])<p class="mt-1 text-xs text-slate-500">Ngày ..... tháng ..... năm .....</p>@endif<p class="mt-1 text-xs text-slate-400">Ký, ghi rõ họ tên</p><div class="h-16"></div></div>
    @endforeach
   </div>
   @else
   <p class="mt-4 text-sm text-slate-500">Không hiển thị khu vực chữ ký theo cấu hình hiện tại.</p>
   @endif
  </section>
 </div>
</div>
@endsection
