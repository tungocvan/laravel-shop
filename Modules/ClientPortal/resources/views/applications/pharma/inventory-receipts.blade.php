@extends('ClientPortal::layouts.application')
@section('title','Phiếu nhập kho')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle','Workspace Pharma dành cho User')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
@php
    $labels=['draft'=>'Nháp','pending_approval'=>'Chờ duyệt','approved'=>'Đã duyệt','posted'=>'Đã ghi sổ','cancelled'=>'Đã hủy'];
    $railStatuses=[''=>'Tất cả','draft'=>'Nháp','approved'=>'Đã duyệt','posted'=>'Đã ghi sổ','cancelled'=>'Đã hủy'];
    $activeStatus=(string)($filters['status'] ?? '');
    $hasFilters=filled($filters['q']) || $activeStatus!=='';
@endphp

<div class="min-w-0 max-w-full overflow-x-hidden bg-slate-50 pb-24 lg:pb-8" data-receipt-workspace>
    <header class="sticky top-0 z-30 -mx-4 border-b border-slate-200 bg-white/95 px-4 py-4 backdrop-blur sm:-mx-6 sm:px-6 lg:static lg:mx-0 lg:rounded-3xl lg:border lg:px-6">
        <div class="relative flex items-center justify-center">
            <a href="{{ route('client.pharma.inventory') }}" class="absolute left-0 inline-flex h-11 w-11 items-center justify-center rounded-full text-2xl text-slate-900 transition active:scale-95 motion-reduce:transform-none" aria-label="Quay lại tồn kho">←</a>
            <div class="px-12 text-center">
                <p class="text-[10px] font-black uppercase tracking-[0.16em] text-slate-400">Inventory · Receipts</p>
                <h1 class="mt-0.5 text-xl font-black tracking-tight text-slate-950 sm:text-2xl">Phiếu nhập kho</h1>
            </div>
        </div>
    </header>

    <section class="mt-4">
        <div class="flex items-center gap-2">
            <form id="receipt-search-form" method="GET" action="{{ route('client.pharma.inventory.receipts') }}" class="flex min-w-0 flex-1 gap-2 lg:max-w-[620px]">
                @if($activeStatus!=='')<input type="hidden" name="status" value="{{ $activeStatus }}">@endif
                <label class="relative min-w-0 flex-1">
                    <span class="sr-only">Tìm kiếm phiếu nhập</span>
                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-xl text-slate-500">⌕</span>
                    <input id="receipt-search-input" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Số phiếu / nhà cung cấp / hóa đơn" data-pwa-debounced-search="600" data-pwa-search-region="#receipt-search-region" data-pwa-search-clear="#receipt-search-clear" class="h-14 w-full rounded-2xl border border-slate-300 bg-white pl-12 pr-11 text-[15px] font-medium text-slate-900 placeholder:text-[13px] placeholder:font-normal outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
                    <button id="receipt-search-clear" type="button" data-pwa-search-clear-button="#receipt-search-input" class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full px-2 py-1 text-xl text-slate-500 {{ $filters['q'] ? '' : 'hidden' }}" aria-label="Xóa từ khóa tìm kiếm">×</button>
                </label>
            </form>
            @if(session('receipt_share_url'))
                <div class="fixed inset-x-4 top-4 z-[100] mx-auto max-w-xl rounded-2xl border border-emerald-200 bg-white p-4 pr-12 shadow-2xl transition duration-200" data-share-flash>
                    <button type="button" data-dismiss-share-flash class="absolute right-3 top-3 inline-flex h-8 w-8 items-center justify-center rounded-lg text-lg font-bold text-emerald-700 hover:bg-emerald-50" aria-label="Đóng thông báo link chia sẻ">×</button>
                    <p class="text-xs font-black uppercase tracking-wide text-emerald-700">Link chia sẻ PDF hóa đơn</p>
                    <div class="mt-2 flex gap-2"><input readonly value="{{ session('receipt_share_url') }}" class="min-w-0 flex-1 rounded-xl border border-slate-300 px-3 text-xs"><button type="button" data-copy-receipt-share="{{ session('receipt_share_url') }}" data-dismiss-share-after-copy class="rounded-xl bg-slate-950 px-3 text-xs font-black text-white">Sao chép</button></div>
                </div>
            @endif

    @can('client.pharma.inventory.receipts.create')
                <a href="{{ route('client.pharma.inventory.receipts.create') }}" class="ml-auto hidden h-14 shrink-0 items-center justify-center gap-2 rounded-2xl bg-slate-950 px-5 text-sm font-black text-white shadow-sm transition active:scale-[0.985] lg:inline-flex motion-reduce:transform-none">
                    <span class="text-base font-light leading-none">+</span><span>Thêm phiếu nhập</span>
                </a>
            @endcan
        </div>
    </section>

    <div id="receipt-search-region">
    <nav class="mt-3 flex w-full max-w-full snap-x snap-proximity items-center gap-1.5 overflow-x-auto overscroll-x-contain pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" data-receipt-status-bar aria-label="Trạng thái phiếu nhập">
        @foreach($railStatuses as $value=>$label)
            @php($statusCount=$value==='' ? ($statusCounts['all'] ?? 0) : ($statusCounts[$value] ?? 0))
            @if($statusCount>0 || $activeStatus===$value)
                <a href="{{ route('client.pharma.inventory.receipts', array_filter(['q'=>$filters['q'],'status'=>$value], fn($v)=>$v!=='' && $v!==null)) }}" class="inline-flex h-7 w-fit shrink-0 snap-start items-center whitespace-nowrap rounded-full border px-2.5 text-[11px] font-bold transition active:scale-[0.985] motion-reduce:transform-none {{ $activeStatus===$value ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-600' }}">
                    {{ $label }} <span class="ml-1 opacity-70">{{ number_format($statusCount) }}</span>
                </a>
            @else
                <span aria-disabled="true" data-disabled-status class="inline-flex h-7 w-fit shrink-0 cursor-not-allowed snap-start items-center whitespace-nowrap rounded-full border border-slate-100 bg-slate-50 px-2.5 text-[11px] font-bold text-slate-300">
                    {{ $label }} <span class="ml-1">{{ number_format($statusCount) }}</span>
                </span>
            @endif
        @endforeach
        @if($hasFilters)
            <a href="{{ route('client.pharma.inventory.receipts') }}" class="ml-1 inline-flex h-7 shrink-0 items-center whitespace-nowrap rounded-full border border-slate-200 bg-white px-2.5 text-[11px] font-black text-rose-600 transition active:scale-[0.985] motion-reduce:transform-none">Xóa bộ lọc</a>
        @endif
    </nav>

        <section id="receipt-mobile-list" class="mt-4 grid min-w-0 max-w-full grid-cols-1 gap-3 md:grid-cols-2 xl:hidden">
            @forelse($receipts as $receipt)
                <article data-receipt-card class="min-w-0 max-w-full overflow-hidden rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <a href="{{ route('client.pharma.inventory.receipts.show',$receipt) }}" class="break-all text-xs font-black uppercase tracking-wide text-slate-500">{{ $receipt->number }}</a>
                            <h2 class="mt-1 line-clamp-2 break-words text-base font-black leading-5 text-slate-950">{{ $receipt->supplier_name ?: 'Chưa xác định nhà cung cấp' }}</h2>
                        </div>
                        <span class="h-fit shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-black text-slate-700">{{ $labels[$receipt->status] ?? $receipt->status }}</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-3 border-t border-slate-100 pt-3 text-sm">
                        <div><p class="text-xs text-slate-500">Ngày nhập</p><p class="mt-0.5 font-bold text-slate-800">{{ $receipt->receipt_date?->format('d/m/Y') ?: '—' }}</p></div>
                        <div class="text-right"><p class="text-xs text-slate-500">Số lượng</p><p class="mt-0.5 font-black tabular-nums text-slate-950">{{ number_format((float)$receipt->total_quantity,0,',','.') }} · {{ number_format($receipt->items_count) }} dòng</p></div>
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-3 rounded-2xl bg-slate-50 px-3 py-2.5">
                        <div class="min-w-0"><p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Hóa đơn</p><p class="mt-0.5 truncate text-sm font-bold text-slate-700">{{ $receipt->invoice_number ?: 'Chưa có số HĐ' }}@if($receipt->invoice_symbol) · {{ $receipt->invoice_symbol }}@endif</p></div>
                        <span class="shrink-0 text-xl font-black text-slate-300">›</span>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3">
                        @if(in_array($receipt->status,[\Modules\Pharma\Models\InventoryReceipt::DRAFT,\Modules\Pharma\Models\InventoryReceipt::PENDING_APPROVAL],true) && $canApproveReceipt)
                            <form method="POST" action="{{ route('client.pharma.inventory.receipts.approve',$receipt) }}">@csrf<button class="inline-flex min-h-10 items-center rounded-xl bg-slate-950 px-3 text-xs font-black text-white transition active:scale-[0.985] motion-reduce:transform-none">Phê duyệt</button></form>
                        @endif
                        @if($receipt->status === \Modules\Pharma\Models\InventoryReceipt::APPROVED && $canApproveReceipt)
                            <form method="POST" action="{{ route('client.pharma.inventory.receipts.undo-approval',$receipt) }}">@csrf<button class="inline-flex min-h-10 items-center rounded-xl border border-slate-300 bg-white px-3 text-xs font-black text-slate-700 transition active:scale-[0.985] motion-reduce:transform-none">Hoàn tác duyệt</button></form>
                        @endif
                        @if($receipt->status === \Modules\Pharma\Models\InventoryReceipt::APPROVED && $canPostReceipt)
                            <form method="POST" action="{{ route('client.pharma.inventory.receipts.post',$receipt) }}">@csrf<button class="inline-flex min-h-10 items-center rounded-xl bg-emerald-600 px-3 text-xs font-black text-white transition active:scale-[0.985] motion-reduce:transform-none">Ghi sổ</button></form>
                        @endif
                        @if($receipt->status === \Modules\Pharma\Models\InventoryReceipt::POSTED && $canPostReceipt)
                            <form method="POST" action="{{ route('client.pharma.inventory.receipts.revert',$receipt) }}">@csrf<button class="inline-flex min-h-10 items-center rounded-xl border border-amber-300 bg-amber-50 px-3 text-xs font-black text-amber-700 transition active:scale-[0.985] motion-reduce:transform-none">Hoàn tác ghi sổ</button></form>
                        @endif
                        @if(! ($receiptPdfActions[$receipt->id]['can_use_pdf'] ?? false))
                            <span class="text-xs font-bold text-slate-400">Ghi sổ để xuất PDF</span>
                        @elseif($receiptPdfActions[$receipt->id]['invoice_ready'] ?? false)
                            <a href="{{ route('client.pharma.inventory.receipts.pdf',$receipt) }}" data-receipt-pdf-download class="inline-flex min-h-10 items-center rounded-xl border border-slate-300 bg-white px-3 text-xs font-black text-slate-700 transition active:scale-[0.985] motion-reduce:transform-none">↓ PDF</a>
                            <a href="{{ route('client.pharma.inventory.receipts.print',$receipt) }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center rounded-xl border border-slate-300 bg-white px-3 text-xs font-black text-slate-700 transition active:scale-[0.985] motion-reduce:transform-none">In</a>
                            @if($receiptPdfActions[$receipt->id]['share'] ?? null)
                                <button type="button" data-copy-receipt-share="{{ $receiptPdfActions[$receipt->id]['share']['url'] }}" class="inline-flex min-h-10 items-center rounded-xl border border-slate-300 bg-white px-3 text-xs font-black text-slate-700 transition active:scale-[0.985] motion-reduce:transform-none">Sao chép link</button>
                                <form method="POST" action="{{ route('client.pharma.inventory.receipts.share.revoke',[$receipt,$receiptPdfActions[$receipt->id]['share']['id']]) }}">@csrf @method('DELETE')<button class="inline-flex min-h-10 items-center rounded-xl border border-rose-200 bg-rose-50 px-3 text-xs font-black text-rose-700 transition active:scale-[0.985] motion-reduce:transform-none">Thu hồi</button></form>
                            @else
                                <form method="POST" action="{{ route('client.pharma.inventory.receipts.share',$receipt) }}">@csrf<button class="inline-flex min-h-10 items-center rounded-xl border border-slate-300 bg-white px-3 text-xs font-black text-slate-700 transition active:scale-[0.985] motion-reduce:transform-none">Chia sẻ</button></form>
                            @endif
                        @else
                            <form method="POST" action="{{ route('client.pharma.inventory.receipts.pdf.export',$receipt) }}">@csrf<button class="inline-flex min-h-10 items-center rounded-xl bg-slate-950 px-3 text-xs font-black text-white transition active:scale-[0.985] motion-reduce:transform-none">Xuất PDF</button></form>
                        @endif
                        <a href="{{ route('client.pharma.inventory.receipts.show',$receipt) }}" class="ml-auto inline-flex min-h-10 items-center px-2 text-xs font-black text-slate-500">Chi tiết ›</a>
                    </div>
                </article>
            @empty
                <div class="col-span-full flex min-h-[48vh] flex-col items-center justify-center rounded-3xl border border-dashed border-slate-300 bg-white px-6 text-center">
                    <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-slate-100 text-4xl text-slate-400">≡</div>
                    <h2 class="mt-5 text-xl font-black text-slate-900">Không có phiếu nhập phù hợp</h2>
                    <p class="mt-2 text-sm text-slate-500">Thử thay đổi từ khóa hoặc trạng thái đang chọn.</p>
                    @if($hasFilters)<a href="{{ route('client.pharma.inventory.receipts') }}" class="mt-4 inline-flex min-h-11 items-center rounded-2xl border border-slate-300 bg-white px-5 text-sm font-black text-slate-700">Xóa bộ lọc</a>@endif
                </div>
            @endforelse
        </section>

        <section class="mt-4 hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm xl:block">
            <table class="w-full table-fixed text-left text-sm">
                <thead class="bg-slate-50 text-xs font-black uppercase tracking-wide text-slate-500">
                    <tr><th class="w-[15%] px-5 py-4">Số phiếu</th><th class="w-[12%] px-5 py-4">Ngày nhập</th><th class="w-[22%] px-5 py-4">Nhà cung cấp</th><th class="w-[17%] px-5 py-4">Hóa đơn</th><th class="w-[10%] px-5 py-4 text-right">Số lượng</th><th class="w-[11%] px-5 py-4">Trạng thái</th><th class="w-[7%] px-5 py-4 text-right"><span class="sr-only">Thao tác</span></th></tr>
                </thead>
                <tbody id="receipt-desktop-body" class="divide-y divide-slate-100">
                    @foreach($receipts as $receipt)
                        <tr data-receipt-row class="transition hover:bg-slate-50">
                            <td class="px-5 py-4"><a class="font-black text-slate-950" href="{{ route('client.pharma.inventory.receipts.show',$receipt) }}">{{ $receipt->number }}</a></td>
                            <td class="px-5 py-4 font-semibold text-slate-700">{{ $receipt->receipt_date?->format('d/m/Y') ?: '—' }}</td>
                            <td class="px-5 py-4"><a class="block truncate font-bold text-slate-800" href="{{ route('client.pharma.inventory.receipts.show',$receipt) }}">{{ $receipt->supplier_name ?: '—' }}</a></td>
                            <td class="px-5 py-4"><span class="font-semibold text-slate-700">{{ $receipt->invoice_number ?: '—' }}</span>@if($receipt->invoice_symbol)<span class="mt-0.5 block truncate text-xs text-slate-500">{{ $receipt->invoice_symbol }}</span>@endif</td>
                            <td class="px-5 py-4 text-right"><p class="font-black tabular-nums text-slate-950">{{ number_format((float)$receipt->total_quantity,0,',','.') }}</p><p class="mt-0.5 text-xs text-slate-400">{{ number_format($receipt->items_count) }} dòng</p></td>
                            <td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-700">{{ $labels[$receipt->status] ?? $receipt->status }}</span></td>
                            <td class="w-[4.5rem] px-5 py-4 text-right">
                                <button type="button" data-receipt-action-trigger="receipt-actions-{{ $receipt->id }}" class="inline-flex min-h-9 min-w-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 text-base font-bold leading-none text-slate-600 hover:bg-slate-50" aria-haspopup="menu" aria-expanded="false" aria-label="Thao tác phiếu {{ $receipt->number }}">⋯</button>
                                <template id="receipt-actions-{{ $receipt->id }}">
                                    <div class="w-56 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-left shadow-2xl" role="menu">
                                        <a href="{{ route('client.pharma.inventory.receipts.show',$receipt) }}" class="block px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Xem chi tiết</a>
                                        @if($receiptPdfActions[$receipt->id]['can_use_pdf'] ?? false)
                                            <div class="my-1 border-t border-slate-100"></div>
                                            @if($receiptPdfActions[$receipt->id]['invoice_ready'] ?? false)
                                                <a href="{{ route('client.pharma.inventory.receipts.pdf',$receipt) }}" data-receipt-pdf-download class="block px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Tải PDF hóa đơn</a>
                                                <a href="{{ route('client.pharma.inventory.receipts.print',$receipt) }}" target="_blank" rel="noopener" class="block px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">In PDF hóa đơn</a>
                                                @if($receiptPdfActions[$receipt->id]['share'] ?? null)
                                                    <button type="button" data-copy-receipt-share="{{ $receiptPdfActions[$receipt->id]['share']['url'] }}" class="block w-full px-4 py-2.5 text-left text-xs font-semibold text-slate-700 hover:bg-slate-50">Sao chép link</button>
                                                @else
                                                    <form method="POST" action="{{ route('client.pharma.inventory.receipts.share',$receipt) }}">@csrf<button class="block w-full px-4 py-2.5 text-left text-xs font-semibold text-slate-700 hover:bg-slate-50">Chia sẻ</button></form>
                                                @endif
                                            @else
                                                <form method="POST" action="{{ route('client.pharma.inventory.receipts.pdf.export',$receipt) }}">@csrf<button class="block w-full px-4 py-2.5 text-left text-xs font-semibold text-slate-700 hover:bg-slate-50">Xuất PDF hóa đơn</button></form>
                                            @endif
                                        @endif
                                        @if(in_array($receipt->status,[\Modules\Pharma\Models\InventoryReceipt::DRAFT,\Modules\Pharma\Models\InventoryReceipt::PENDING_APPROVAL],true) && $canApproveReceipt)
                                            <div class="my-1 border-t border-slate-100"></div>
                                            <form method="POST" action="{{ route('client.pharma.inventory.receipts.approve',$receipt) }}">@csrf<button class="block w-full px-4 py-2.5 text-left text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Phê duyệt</button></form>
                                        @elseif($receipt->status === \Modules\Pharma\Models\InventoryReceipt::APPROVED)
                                            @if($canApproveReceipt || $canPostReceipt)<div class="my-1 border-t border-slate-100"></div>@endif
                                            @if($canApproveReceipt)
                                                <form method="POST" action="{{ route('client.pharma.inventory.receipts.undo-approval',$receipt) }}">@csrf<button class="block w-full px-4 py-2.5 text-left text-xs font-semibold text-amber-700 hover:bg-amber-50">Hoàn tác phê duyệt</button></form>
                                            @endif
                                            @if($canPostReceipt)
                                                <button type="button" data-receipt-confirm-open="post-receipt-{{ $receipt->id }}" class="block w-full px-4 py-2.5 text-left text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Ghi sổ</button>
                                            @endif
                                        @elseif($receipt->status === \Modules\Pharma\Models\InventoryReceipt::POSTED && $canPostReceipt)
                                            <div class="my-1 border-t border-slate-100"></div>
                                            <button type="button" data-receipt-confirm-open="revert-receipt-{{ $receipt->id }}" class="block w-full px-4 py-2.5 text-left text-xs font-semibold text-amber-700 hover:bg-amber-50">Hoàn tác ghi sổ</button>
                                        @endif
                                    </div>
                                </template>
                                @if($receipt->status === \Modules\Pharma\Models\InventoryReceipt::APPROVED && $canPostReceipt)
                                    <dialog id="post-receipt-{{ $receipt->id }}" class="m-auto w-[calc(100%-2rem)] max-w-lg overflow-hidden rounded-2xl border-0 bg-white p-0 shadow-2xl ring-1 ring-slate-200 backdrop:bg-slate-950/65 backdrop:backdrop-blur-[3px]">
                                        <form method="POST" action="{{ route('client.pharma.inventory.receipts.post',$receipt) }}" class="overflow-hidden rounded-2xl bg-white">@csrf
                                            <div class="p-6"><h3 class="text-lg font-black text-slate-950">Ghi sổ phiếu nhập?</h3><p class="mt-2 text-sm text-slate-600">Phiếu <b>{{ $receipt->number }}</b> sẽ cộng tồn kho theo đúng lô và số lượng đã nhập.</p></div>
                                            <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4"><button type="button" data-receipt-confirm-close class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Hủy</button><button class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white">Ghi sổ</button></div>
                                        </form>
                                    </dialog>
                                @endif
                                @if($receipt->status === \Modules\Pharma\Models\InventoryReceipt::POSTED && $canPostReceipt)
                                    <dialog id="revert-receipt-{{ $receipt->id }}" class="m-auto w-[calc(100%-2rem)] max-w-lg overflow-hidden rounded-2xl border-0 bg-white p-0 shadow-2xl ring-1 ring-slate-200 backdrop:bg-slate-950/65 backdrop:backdrop-blur-[3px]">
                                        <form method="POST" action="{{ route('client.pharma.inventory.receipts.revert',$receipt) }}" class="overflow-hidden rounded-2xl bg-white">@csrf
                                            <div class="p-6"><h3 class="text-lg font-black text-slate-950">Hoàn tác ghi sổ?</h3><p class="mt-2 text-sm text-slate-600">Tồn kho của phiếu <b>{{ $receipt->number }}</b> sẽ được trừ lại và phiếu quay về trạng thái trước khi ghi sổ.</p></div>
                                            <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4"><button type="button" data-receipt-confirm-close class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Hủy</button><button class="rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-bold text-white">Hoàn tác ghi sổ</button></div>
                                        </form>
                                    </dialog>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        @if($receipts->hasMorePages())
            <div id="receipt-load-more-wrap" class="mt-5 text-center xl:hidden">
                <a id="receipt-load-more" href="{{ $receipts->nextPageUrl() }}" data-pwa-load-more data-pwa-load-more-target="#receipt-mobile-list" data-pwa-load-more-items="#receipt-mobile-list [data-receipt-card]" data-pwa-load-more-wrap="#receipt-load-more-wrap" data-pwa-pending-label="Đang tải…" class="inline-flex min-h-12 w-full items-center justify-center rounded-2xl border border-slate-300 bg-white px-6 text-sm font-black text-slate-800 shadow-sm transition active:scale-[0.985] sm:w-auto motion-reduce:transform-none">Xem thêm</a>
                <p class="mt-2 text-xs font-semibold text-slate-400">Đã hiển thị {{ $receipts->count() }} / {{ $receipts->total() }}</p>
            </div>
            <div class="mt-5 hidden text-center xl:block">
                <a href="{{ $receipts->nextPageUrl() }}" class="inline-flex min-h-12 items-center justify-center rounded-2xl border border-slate-300 bg-white px-6 text-sm font-black text-slate-800 shadow-sm transition active:scale-[0.985] motion-reduce:transform-none">Xem thêm</a>
                <p class="mt-2 text-xs font-semibold text-slate-400">Trang {{ $receipts->currentPage() }} · {{ number_format($receipts->total()) }} phiếu</p>
            </div>
        @endif
    </div>

    @can('client.pharma.inventory.receipts.create')
        <a href="{{ route('client.pharma.inventory.receipts.create') }}" class="fixed z-40 inline-flex h-11 w-11 items-center justify-center rounded-full bg-slate-950 text-xl font-light text-white shadow-lg transition active:scale-[0.985] lg:hidden motion-reduce:transform-none" style="right:calc(18px + env(safe-area-inset-right,0px));bottom:calc(18px + env(safe-area-inset-bottom,0px))" aria-label="Thêm phiếu nhập">+</a>
    @endcan
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const shareFlash=document.querySelector('[data-share-flash]');
    const dismissShareFlash=()=>{
        if(!shareFlash)return;
        shareFlash.classList.add('opacity-0','-translate-y-1','pointer-events-none');
        window.setTimeout(()=>shareFlash.remove(),200);
    };
    document.addEventListener('click',(event)=>{
        if(event.target.closest('[data-dismiss-share-flash]')){dismissShareFlash();return;}
        if(shareFlash&&!shareFlash.contains(event.target))dismissShareFlash();
    });
    document.addEventListener('keydown',(event)=>{if(event.key==='Escape')dismissShareFlash();});

    const receiptActionPopover=document.createElement('div');
    receiptActionPopover.dataset.receiptActionPopover='';
    receiptActionPopover.className='fixed z-[100] hidden';
    document.body.appendChild(receiptActionPopover);
    let activeReceiptTrigger=null;
    const closeReceiptActions=()=>{
        receiptActionPopover.classList.add('hidden');
        receiptActionPopover.replaceChildren();
        if(activeReceiptTrigger)activeReceiptTrigger.setAttribute('aria-expanded','false');
        activeReceiptTrigger=null;
    };
    const positionReceiptActions=()=>{
        if(!activeReceiptTrigger||receiptActionPopover.classList.contains('hidden'))return;
        const rect=activeReceiptTrigger.getBoundingClientRect();
        const menu=receiptActionPopover.firstElementChild;
        if(!menu)return;
        const gap=8,pad=12,width=menu.offsetWidth,height=menu.offsetHeight;
        let left=Math.min(rect.right-width,window.innerWidth-width-pad);
        left=Math.max(pad,left);
        const spaceBelow=window.innerHeight-rect.bottom-pad;
        const top=spaceBelow>=height+gap ? rect.bottom+gap : Math.max(pad,rect.top-height-gap);
        receiptActionPopover.style.left=left+'px';
        receiptActionPopover.style.top=top+'px';
    };
    document.addEventListener('click',(event)=>{
        const trigger=event.target.closest('[data-receipt-action-trigger]');
        if(trigger){
            event.preventDefault();
            if(activeReceiptTrigger===trigger){closeReceiptActions();return;}
            closeReceiptActions();
            const template=document.getElementById(trigger.dataset.receiptActionTrigger);
            if(!template)return;
            receiptActionPopover.appendChild(template.content.cloneNode(true));
            receiptActionPopover.classList.remove('hidden');
            activeReceiptTrigger=trigger;
            trigger.setAttribute('aria-expanded','true');
            positionReceiptActions();
            return;
        }
        const openConfirm=event.target.closest('[data-receipt-confirm-open]');
        if(openConfirm){
            const dialog=document.getElementById(openConfirm.dataset.receiptConfirmOpen);
            closeReceiptActions();
            dialog?.showModal();
            return;
        }
        const closeConfirm=event.target.closest('[data-receipt-confirm-close]');
        if(closeConfirm){closeConfirm.closest('dialog')?.close();return;}
        if(!receiptActionPopover.contains(event.target))closeReceiptActions();
    });
    window.addEventListener('resize',positionReceiptActions);
    window.addEventListener('scroll',closeReceiptActions,true);

    const prepared=new Map();
    const standalone=window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone===true;
    document.addEventListener('click',async(event)=>{
        const copy=event.target.closest('[data-copy-receipt-share]');
        if(copy){
            try{await navigator.clipboard.writeText(copy.dataset.copyReceiptShare);const old=copy.textContent;copy.textContent='Đã sao chép';if(copy.hasAttribute('data-dismiss-share-after-copy'))window.setTimeout(dismissShareFlash,650);else setTimeout(()=>copy.textContent=old,1600);}catch(e){window.prompt('Sao chép link chia sẻ:',copy.dataset.copyReceiptShare);}
            return;
        }
        const link=event.target.closest('[data-receipt-pdf-download]');
        if(!link || !standalone)return;
        event.preventDefault();
        const cached=prepared.get(link.href);
        if(cached){
            if(navigator.canShare?.({files:[cached]}))await navigator.share({files:[cached],title:'Phiếu nhập kho'});
            return;
        }
        const original=link.textContent;link.textContent='Đang chuẩn bị…';link.setAttribute('aria-disabled','true');
        try{
            const response=await fetch(link.href,{credentials:'same-origin',cache:'no-store'});
            if(!response.ok)throw new Error('download');
            const blob=await response.blob();
            const disposition=response.headers.get('content-disposition')||'';
            const match=disposition.match(/filename="?([^";]+)"?/i);
            const file=new File([blob],match?.[1]||'phieu-nhap-kho.pdf',{type:'application/pdf'});
            prepared.set(link.href,file);
            link.textContent=navigator.canShare?.({files:[file]})?'Mở / lưu PDF':'PDF đã sẵn sàng';
        }catch(e){link.textContent='Không thể chuẩn bị PDF';setTimeout(()=>link.textContent=original,1800);}
        finally{link.removeAttribute('aria-disabled');}
    });
});
</script>
@endsection
