@extends('ClientPortal::layouts.application')
@section('title','Chi tiết phiếu nhập')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle','Workspace Pharma dành cho User')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
@php $money=fn($v)=>number_format((float)$v,0,',','.').' đ'; $labels=['draft'=>'Nháp','pending_approval'=>'Chờ duyệt','approved'=>'Đã duyệt','posted'=>'Đã ghi sổ','cancelled'=>'Đã hủy']; @endphp
<div class="min-w-0 space-y-4 pb-8">
<section class="rounded-[1.75rem] bg-slate-950 px-5 py-5 text-white shadow-sm sm:px-7"><a href="{{ route('client.pharma.inventory.receipts') }}" class="text-sm font-bold text-slate-300">← Phiếu nhập kho</a><div class="mt-4 flex flex-wrap items-center justify-between gap-3"><div><p class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-400">Receipt detail · Workflow</p><h1 class="mt-1 text-2xl font-black">{{ $receipt->number }}</h1></div><span class="rounded-full bg-white/10 px-3 py-1.5 text-xs font-black">{{ $labels[$receipt->status] ?? $receipt->status }}</span></div></section>
<section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
<div class="flex flex-wrap justify-end gap-2">
@if($receipt->status === \Modules\Pharma\Models\InventoryReceipt::DRAFT && $canEditReceipt)
<a href="{{ route('client.pharma.inventory.receipts.edit',$receipt) }}" class="inline-flex min-h-11 items-center rounded-2xl border border-slate-300 px-4 text-sm font-black">Sửa</a>
<form method="POST" action="{{ route('client.pharma.inventory.receipts.delete',$receipt) }}">@csrf @method('DELETE')<button class="min-h-11 rounded-2xl border border-rose-200 px-4 text-sm font-black text-rose-700">Xóa</button></form>
@endif
@if($receipt->status === \Modules\Pharma\Models\InventoryReceipt::DRAFT && $canSubmitReceipt)
<form method="POST" action="{{ route('client.pharma.inventory.receipts.submit',$receipt) }}">@csrf<button class="min-h-11 rounded-2xl bg-slate-950 px-4 text-sm font-black text-white">Gửi duyệt</button></form>
@endif
@if($receipt->status === \Modules\Pharma\Models\InventoryReceipt::PENDING_APPROVAL && $canSubmitReceipt)
<form method="POST" action="{{ route('client.pharma.inventory.receipts.undo-submit',$receipt) }}">@csrf<button class="min-h-11 rounded-2xl border border-slate-300 px-4 text-sm font-black">Hoàn tác gửi duyệt</button></form>
@endif
@if($receipt->status === \Modules\Pharma\Models\InventoryReceipt::PENDING_APPROVAL && $canApproveReceipt)
<form method="POST" action="{{ route('client.pharma.inventory.receipts.approve',$receipt) }}">@csrf<button class="min-h-11 rounded-2xl bg-slate-950 px-4 text-sm font-black text-white">Duyệt</button></form>
@endif
@if($receipt->status === \Modules\Pharma\Models\InventoryReceipt::APPROVED && $canApproveReceipt)
<form method="POST" action="{{ route('client.pharma.inventory.receipts.undo-approval',$receipt) }}">@csrf<button class="min-h-11 rounded-2xl border border-slate-300 px-4 text-sm font-black">Hoàn tác duyệt</button></form>
@endif
@if($receipt->status === \Modules\Pharma\Models\InventoryReceipt::APPROVED && $canPostReceipt)
<form method="POST" action="{{ route('client.pharma.inventory.receipts.post',$receipt) }}">@csrf<button class="min-h-11 rounded-2xl bg-emerald-700 px-4 text-sm font-black text-white">Ghi sổ</button></form>
@endif
@if($receipt->status === \Modules\Pharma\Models\InventoryReceipt::POSTED && $canPostReceipt)
<form method="POST" action="{{ route('client.pharma.inventory.receipts.revert',$receipt) }}">@csrf<button class="min-h-11 rounded-2xl border border-amber-300 px-4 text-sm font-black text-amber-800">Hoàn tác ghi sổ</button></form>
@endif
</div>
<p class="mt-3 text-right text-xs text-slate-500">Nháp / Chờ duyệt / Đã duyệt không làm thay đổi tồn kho. Chỉ Ghi sổ mới cộng tồn.</p>
</section>
<section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">@foreach([['Ngày nhập',$receipt->receipt_date?->format('d/m/Y')],['Nhà cung cấp',$receipt->supplier_name],['Số hóa đơn',$receipt->invoice_number ?: '—'],['Ngày hóa đơn',$receipt->invoice_date?->format('d/m/Y') ?: '—']] as [$label,$value])<div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-bold text-slate-500">{{ $label }}</p><p class="mt-2 font-black text-slate-950">{{ $value }}</p></div>@endforeach</section>
<section class="space-y-3 xl:hidden">@foreach($receipt->items as $item)<article class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"><h2 class="font-black">{{ $item->medicine?->name }}</h2><p class="mt-1 text-xs text-slate-500">Lô {{ $item->batch_number }} · HSD {{ $item->expiry_date?->format('d/m/Y') }}</p><div class="mt-3 flex justify-between text-sm"><span>SL <b>{{ number_format((float)$item->quantity,0,',','.') }}</b></span><span class="font-black">{{ $money($item->unit_price_ex_vat) }}</span></div></article>@endforeach</section>
<section class="hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm xl:block"><table class="w-full text-sm"><thead class="bg-slate-50 text-left text-xs font-black uppercase text-slate-500"><tr><th class="px-4 py-3">Thuốc</th><th class="px-4 py-3">Số lô</th><th class="px-4 py-3">Hạn dùng</th><th class="px-4 py-3 text-right">Số lượng</th><th class="px-4 py-3 text-right">Giá nhập</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach($receipt->items as $item)<tr><td class="px-4 py-3 font-bold">{{ $item->medicine?->name }}</td><td class="px-4 py-3">{{ $item->batch_number }}</td><td class="px-4 py-3">{{ $item->expiry_date?->format('d/m/Y') }}</td><td class="px-4 py-3 text-right">{{ number_format((float)$item->quantity,0,',','.') }}</td><td class="px-4 py-3 text-right font-bold">{{ $money($item->unit_price_ex_vat) }}</td></tr>@endforeach</tbody></table></section>
@if($receipt->notes)<section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-black uppercase text-slate-500">Ghi chú</p><p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $receipt->notes }}</p></section>@endif
</div>
@endsection
