@extends('ClientPortal::layouts.application')
@section('title','Chi tiết phiếu nhập')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle','Workspace Pharma dành cho User')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)
@section('content')
@php $money=fn($v)=>$v === null ? '—' : number_format((float)$v,0,',','.').' đ'; $costTotal=$receipt->items->sum(fn($item)=>(float)$item->quantity*(float)$item->unit_price_ex_vat); $invoiceTotal=$receipt->items->sum(fn($item)=>(float)$item->quantity*(float)($item->invoice_unit_price_ex_vat ?? 0)); $labels=['draft'=>'Nháp','pending_approval'=>'Chờ duyệt','approved'=>'Đã duyệt','posted'=>'Đã ghi sổ','cancelled'=>'Đã hủy']; @endphp
<div class="min-w-0 space-y-4 pb-8">
<section class="rounded-[1.75rem] bg-slate-950 px-5 py-4 text-white shadow-sm sm:px-7">
<div class="flex items-center justify-between gap-3"><a href="{{ route('client.pharma.inventory.receipts') }}" class="inline-flex min-h-10 items-center text-sm font-bold text-slate-300 hover:text-white">← Phiếu nhập kho</a><span class="shrink-0 rounded-full bg-white/10 px-3 py-1.5 text-xs font-black">{{ $labels[$receipt->status] ?? $receipt->status }}</span></div>
<h1 class="mt-1 text-xl font-black sm:text-2xl">{{ $receipt->number }}</h1>
<div class="mt-3 flex flex-wrap items-center gap-2 border-t border-white/10 pt-3">
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
<p class="mt-2 text-[11px] leading-4 text-slate-400">Nháp / Chờ duyệt / Đã duyệt chưa làm thay đổi tồn. Chỉ Ghi sổ mới cộng tồn.</p>
</section>
<section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
<div class="grid gap-3 sm:grid-cols-[9rem_minmax(0,1fr)] sm:items-start">
<div><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Ngày nhập</p><p class="mt-1 text-sm font-black text-slate-950">{{ $receipt->receipt_date?->format('d/m/Y') }}</p></div>
<div><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Nhà cung cấp</p><p class="mt-1 text-sm font-black leading-snug text-slate-950">{{ $receipt->supplier_name }}</p></div>
</div>
<div class="mt-3 border-t border-slate-100 pt-3">
<div class="flex items-center justify-between gap-3"><p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Hóa đơn</p><span class="text-[10px] font-semibold text-slate-400">Tham khảo chứng từ</span></div>
<div class="mt-2 grid grid-cols-3 gap-2">
<div class="min-w-0"><p class="text-[10px] font-bold text-slate-400">Số HĐ</p><p class="mt-0.5 truncate text-sm font-bold text-slate-700">{{ $receipt->invoice_number ?: '—' }}</p></div>
<div class="min-w-0"><p class="text-[10px] font-bold text-slate-400">Ký hiệu</p><p class="mt-0.5 truncate text-sm font-bold text-slate-700">{{ $receipt->invoice_symbol ?: '—' }}</p></div>
<div class="min-w-0"><p class="text-[10px] font-bold text-slate-400">Ngày HĐ</p><p class="mt-0.5 whitespace-nowrap text-sm font-bold text-slate-700">{{ $receipt->invoice_date?->format('d/m/Y') ?: '—' }}</p></div>
</div>
</div>
</section>
<section class="space-y-3 xl:hidden">@foreach($receipt->items as $item)<article class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"><div class="flex items-start justify-between gap-3"><h2 class="font-black">{{ $item->medicine?->name }}</h2>@if($canViewInventory && isset($inventoryBalanceLinks[$item->id]))<a href="{{ route('client.pharma.inventory.balances.show', $inventoryBalanceLinks[$item->id]) }}" class="inline-flex min-h-11 shrink-0 items-center rounded-2xl border border-slate-300 bg-white px-3 text-xs font-black text-slate-700">Xem tồn lô ›</a>@endif</div><p class="mt-1 text-xs text-slate-500">Lô {{ $item->batch_number }} · HSD {{ $item->expiry_date?->format('d/m/Y') }}</p><div class="mt-3 grid grid-cols-3 gap-2 text-sm"><span>SL<br><b>{{ number_format((float)$item->quantity,0,',','.') }}</b></span><span>Giá vốn<br><b>{{ $money($item->unit_price_ex_vat) }}</b></span><span>Giá HĐ<br><b>{{ $money($item->invoice_unit_price_ex_vat) }}</b></span></div><p class="mt-2 text-xs text-slate-500">VAT {{ number_format((float)$item->vat_rate,2,',','.') }}%</p></article>@endforeach</section>
<section class="hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm xl:block"><table class="w-full text-sm"><thead class="bg-slate-50 text-left text-xs font-black uppercase text-slate-500"><tr><th class="px-4 py-3">Thuốc</th><th class="px-4 py-3">Số lô</th><th class="px-4 py-3">Hạn dùng</th><th class="px-4 py-3 text-right">Số lượng</th><th class="px-4 py-3 text-right">Giá nhập / Giá vốn</th><th class="px-4 py-3 text-right">Giá xuất HĐ chưa VAT</th><th class="px-4 py-3 text-right">VAT %</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach($receipt->items as $item)<tr><td class="px-4 py-3 font-bold">{{ $item->medicine?->name }}@if($canViewInventory && isset($inventoryBalanceLinks[$item->id]))<a href="{{ route('client.pharma.inventory.balances.show', $inventoryBalanceLinks[$item->id]) }}" class="mt-1 block text-xs font-black text-slate-500 hover:text-slate-950 hover:underline">Xem tồn lô ›</a>@endif</td><td class="px-4 py-3">{{ $item->batch_number }}</td><td class="px-4 py-3">{{ $item->expiry_date?->format('d/m/Y') }}</td><td class="px-4 py-3 text-right">{{ number_format((float)$item->quantity,0,',','.') }}</td><td class="px-4 py-3 text-right font-bold">{{ $money($item->unit_price_ex_vat) }}</td><td class="px-4 py-3 text-right">{{ $money($item->invoice_unit_price_ex_vat) }}</td><td class="px-4 py-3 text-right">{{ number_format((float)$item->vat_rate,2,',','.') }}%</td></tr>@endforeach</tbody></table></section>
<section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"><div class="flex items-center justify-between gap-3"><p class="text-xs font-black uppercase text-slate-500">Tổng giá trị</p><span class="text-[10px] font-semibold text-slate-400">Đối chiếu chứng từ</span></div><div class="mt-3 grid grid-cols-2 gap-4"><div><p class="text-[10px] font-bold text-slate-400">Giá vốn</p><p class="mt-1 text-lg font-black text-slate-950">{{ $money($costTotal) }}</p></div><div><p class="text-[10px] font-bold text-slate-400">Hóa đơn chưa VAT</p><p class="mt-1 text-lg font-black text-slate-950">{{ $money($invoiceTotal) }}</p></div></div><p class="mt-2 text-[11px] text-slate-500">Giá hóa đơn chỉ dùng đối chiếu chứng từ, không thay đổi giá vốn.</p></section>
@if($receipt->notes)<section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-black uppercase text-slate-500">Ghi chú</p><p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ $receipt->notes }}</p></section>@endif
</div>
@endsection
