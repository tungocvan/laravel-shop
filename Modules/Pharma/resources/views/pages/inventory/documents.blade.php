@extends('Admin::layouts.master')
@section('title', $title)
@section('admin_container','full')
@section('content')
<div class="mx-auto flex min-h-[calc(100vh-7.5rem)] w-full max-w-[1580px] flex-col gap-6">
    <header class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between"><a href="{{ route('admin.pharma.dashboard') }}" class="text-sm font-semibold text-indigo-700 hover:text-indigo-900">← Trung tâm điều hành Pharma</a>
        <div>
            <a href="{{ route('admin.pharma.inventory.index') }}" class="text-sm font-semibold text-indigo-700">← Quay về Tồn kho</a>
            <h1 class="mt-2 text-2xl font-bold text-slate-950">{{ $title }}</h1>
            <p class="mt-1 text-sm text-slate-500">Tra cứu chứng từ, phê duyệt và ghi sổ theo đúng trạng thái.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($type === 'issue')
                @can('edit_pharma')
                    <a href="{{ route('admin.pharma.inventory.issues.settings') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">⚙ Cấu hình phiếu xuất</a>
                @endcan
                <a href="{{ route('admin.pharma.inventory.issues.export',request()->only(['q','status','manager_user_id','recipient_name','date_from','date_to'])) }}" class="rounded-xl border border-emerald-300 bg-white px-4 py-2.5 text-sm font-semibold text-emerald-700">Export Excel</a>
            @endif
            @if($type === 'receipt')
                @can('edit_pharma')
                    <a href="{{ route('admin.pharma.inventory.receipts.settings') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">⚙ Cấu hình phiếu nhập</a>
                @endcan
            @endif
            @if($type === 'issue')<a href="{{ route('admin.pharma.inventory.issues.bid-sales.create') }}" class="rounded-xl border border-indigo-300 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700">+ Xuất bán hàng thầu</a>@endif
            <a href="{{ route($type === 'receipt' ? 'admin.pharma.inventory.receipts.create' : 'admin.pharma.inventory.issues.create') }}" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">+ {{ $type === 'receipt' ? 'Lập phiếu nhập' : 'Lập phiếu xuất' }}</a>
        </div>
    </header>

    <form method="GET" id="document-filters" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        @if($type === 'issue')
        <div class="flex flex-wrap items-center gap-3 xl:flex-nowrap">
            <div class="flex min-w-[300px] flex-[1.2]">
                <div class="relative min-w-0 flex-1">
                    <input id="issue-keyword-filter" name="q" value="{{ request('q') }}" placeholder="Tìm mã phiếu / nơi nhận" class="min-h-11 w-full rounded-l-xl border border-r-0 border-slate-300 px-3 pr-9 text-sm">
                    <button type="button" data-clear-keyword class="{{ filled(request('q')) ? 'flex' : 'hidden' }} absolute right-2 top-1/2 h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full text-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Xóa nội dung tìm kiếm">×</button>
                </div>
                <button class="min-h-11 rounded-r-xl bg-slate-900 px-5 text-sm font-semibold text-white">Tìm</button>
            </div>
            <div class="min-w-[180px] flex-[.75]"><x-select-search id="issue-manager-filter" name="manager_user_id" data-auto-submit-filter placeholder="Tìm người phụ trách..."><option value="">Tất cả người phụ trách</option>@foreach($issueManagers as $manager)<option value="{{ $manager->id }}" @selected((string)request('manager_user_id')===(string)$manager->id)>{{ $manager->name }}</option>@endforeach</x-select-search></div>
            <div class="min-w-[250px] flex-1"><x-select-search id="issue-recipient-filter" name="recipient_name" data-auto-submit-filter placeholder="Tìm khách hàng / nơi nhận..."><option value="">Tất cả khách hàng / nơi nhận</option>@foreach($issueRecipients as $recipient)<option value="{{ $recipient }}" @selected(request('recipient_name')===$recipient)>{{ $recipient }}</option>@endforeach</x-select-search></div>
            <div class="relative min-w-[155px] flex-[.62]">
                <span class="pointer-events-none absolute left-3 top-1/2 z-10 -translate-y-1/2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Từ</span>
                <input type="date" name="date_from" value="{{ $dateFrom }}" data-auto-submit-filter aria-label="Từ ngày" title="Từ ngày" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white pl-10 pr-2 text-sm text-slate-700">
            </div>
            <div class="relative min-w-[155px] flex-[.62]">
                <span class="pointer-events-none absolute left-3 top-1/2 z-10 -translate-y-1/2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Đến</span>
                <input type="date" name="date_to" value="{{ $dateTo }}" data-auto-submit-filter aria-label="Đến ngày" title="Đến ngày" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white pl-12 pr-2 text-sm text-slate-700">
            </div>
            <select name="status" onchange="this.form.submit()" class="min-h-11 min-w-[125px] rounded-xl border border-slate-300 bg-white px-3 text-sm"><option value="">Tất cả trạng thái</option><option value="draft" @selected(request('status') === 'draft')>Nháp</option><option value="pending_approval" @selected(request('status') === 'pending_approval')>Chờ duyệt</option><option value="approved" @selected(request('status') === 'approved')>Đã duyệt</option><option value="rejected" @selected(request('status') === 'rejected')>Từ chối</option><option value="posted" @selected(request('status') === 'posted')>Đã ghi sổ</option></select>
            <select name="per_page" onchange="this.form.submit()" class="min-h-11 min-w-[105px] rounded-xl border border-slate-300 bg-white px-3 text-sm">@foreach([25,50,100] as $size)<option value="{{ $size }}" @selected((int)request('per_page',25)===$size)>{{ $size }} / trang</option>@endforeach</select>
            <a href="{{ route('admin.pharma.inventory.issues.index') }}" class="inline-flex min-h-11 shrink-0 items-center justify-center whitespace-nowrap rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900">Đặt lại</a>
        </div>
        @else
        <div class="grid gap-3 md:grid-cols-[minmax(260px,1fr)_auto_auto_auto]">
            <input name="q" value="{{ request('q') }}" placeholder="Tìm mã phiếu / nhà cung cấp" class="min-h-11 rounded-xl border border-slate-300 px-3 text-sm">
            <select name="status" onchange="this.form.submit()" class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm"><option value="">Tất cả trạng thái</option><option value="draft" @selected(request('status') === 'draft')>Nháp</option><option value="posted" @selected(request('status') === 'posted')>Đã ghi sổ</option></select>
            <select name="per_page" onchange="this.form.submit()" class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm">@foreach([25,50,100] as $size)<option value="{{ $size }}" @selected((int)request('per_page',25)===$size)>{{ $size }} / trang</option>@endforeach</select>
            <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Tìm</button>
        </div>
        @endif
    </form>

    @if($type === 'issue')
    <form id="selected-export-form" method="GET" action="{{ route('admin.pharma.inventory.issues.export') }}" class="hidden items-center justify-between gap-3 rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3" data-selection-toolbar>
        @foreach(request()->only(['q','status','manager_user_id','recipient_name','date_from','date_to']) as $key=>$value)@if(filled($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach
        <div class="text-sm text-slate-700"><strong data-selected-count>0</strong> phiếu đã chọn <button type="button" data-clear-selection class="ml-2 font-semibold text-indigo-700">Bỏ chọn tất cả</button></div>
        <button class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Xuất Excel đã chọn (<span data-selected-button-count>0</span>)</button>
    </form>
    @endif

    <section class="flex min-h-0 flex-1 flex-col rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="min-h-[420px] flex-1 overflow-auto rounded-t-2xl">
            <table class="{{ $type === 'issue' ? 'min-w-[1320px]' : 'min-w-[900px]' }} w-full table-fixed text-left text-sm">
                <thead class="sticky top-0 z-10 bg-slate-50 text-xs uppercase text-slate-600 shadow-[0_1px_0_0_rgb(226_232_240)]">
                    <tr>
                        @if($type === 'issue')<th class="w-[52px] px-4 py-3"><input type="checkbox" data-select-page aria-label="Chọn tất cả phiếu trên trang" class="h-4 w-4 rounded border-slate-300"></th>@endif
                        <th class="w-[180px] px-4 py-3">Mã phiếu</th><th class="w-[110px] px-4 py-3">Ngày</th>
                        <th class="px-4 py-3">{{ $type === 'receipt' ? 'Nhà cung cấp' : 'Khách hàng / Nơi nhận' }}</th>
                        @if($type === 'issue')<th class="w-[190px] px-4 py-3">Người phụ trách</th>@endif
                        <th class="w-[100px] px-4 py-3 text-right">Mặt hàng</th><th class="w-[160px] px-4 py-3 text-right">Tổng giá trị</th>@if($type === 'issue')<th class="w-[90px] px-4 py-3 text-center">Thiếu hàng</th>@endif
                        <th class="w-[125px] px-4 py-3">Trạng thái</th><th class="{{ $type === 'issue' ? 'w-[150px]' : 'w-[230px]' }} px-4 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($documents as $doc)
                        @php
                            $date=$type === 'receipt' ? $doc->receipt_date : $doc->issue_date;
                            $party=$type === 'receipt' ? $doc->supplier_name : $doc->recipient_name;
                            $postRoute=$type === 'receipt' ? route('admin.pharma.inventory.receipts.post',$doc) : route('admin.pharma.inventory.issues.post',$doc);
                        @endphp
                        <tr class="transition hover:bg-slate-50/70">
                            @if($type === 'issue')<td class="px-4 py-4"><input type="checkbox" name="ids[]" value="{{ $doc->id }}" form="selected-export-form" data-row-select class="h-4 w-4 rounded border-slate-300" aria-label="Chọn phiếu {{ $doc->number }}"></td>@endif
                            <td class="px-4 py-4"><div class="flex min-w-0 flex-col items-start gap-1">@if($type === 'issue')<a href="{{ route('admin.pharma.inventory.issues.show',$doc) }}" class="whitespace-nowrap font-mono font-bold text-indigo-700 hover:text-indigo-900 hover:underline">{{ $doc->number }}</a>@else<a href="{{ route('admin.pharma.inventory.receipts.show',$doc) }}" class="whitespace-nowrap font-mono font-bold text-indigo-700 hover:text-indigo-900 hover:underline">{{ $doc->number }}</a>@endif @if($type === 'issue' && ($doc->issue_source ?? 'normal') === 'bid')<span class="inline-flex rounded-full bg-violet-50 px-2 py-0.5 text-[10px] font-bold uppercase leading-4 text-violet-700">Hàng thầu</span>@endif</div></td>
                            <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $date->format('d/m/Y') }}</td>
                            <td class="px-4 py-4"><div class="truncate font-semibold text-slate-800" title="{{ $party ?: '—' }}">{{ $party ?: '—' }}</div></td>
                            @if($type === 'issue')<td class="px-4 py-4"><div class="truncate font-medium text-slate-700" title="{{ $doc->resolved_manager_names ?: 'Chưa phân công' }}">{{ $doc->resolved_manager_names ?: '—' }}</div>@if(blank($doc->resolved_manager_names))<span class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500">Chưa phân công</span>@endif</td>@endif
                            <td class="px-4 py-4 text-right">{{ $doc->items_count }}</td>
                            <td class="px-4 py-4 text-right font-semibold">{{ number_format((float)$doc->total_value,0,',','.').' đ' }}</td>@if($type === 'issue')<td class="px-4 py-4 text-center">@if(filled($doc->shortage_note))<button type="button" onclick="document.getElementById('shortage-note-{{ $doc->id }}').showModal()" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100" aria-label="Xem ghi chú thiếu hàng của phiếu {{ $doc->number }}" title="Xem ghi chú thiếu hàng">!</button>@else<span class="text-slate-300">—</span>@endif</td>@endif
                            <td class="px-4 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $doc->status === 'posted' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ ['draft'=>'Nháp','pending_approval'=>'Chờ duyệt','approved'=>'Đã duyệt','rejected'=>'Từ chối','posted'=>'Đã ghi sổ','cancelled'=>'Đã hủy'][$doc->status] ?? $doc->status }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    @if($type === 'receipt')
                                        <details class="relative" data-document-actions>
                                            <summary class="flex min-h-9 cursor-pointer list-none items-center rounded-lg border border-slate-300 bg-white px-3 text-base font-bold leading-none text-slate-600 hover:bg-slate-50" aria-label="Thao tác khác">⋯</summary>
                                            <div class="absolute right-0 z-30 mt-2 w-48 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl">
                                                <a href="{{ route('admin.pharma.inventory.receipts.pdf.invoice',$doc) }}" class="block px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Tải PDF hóa đơn</a>
                                                <a href="{{ route('admin.pharma.inventory.receipts.print.invoice',$doc) }}" target="_blank" class="block px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">In theo hóa đơn</a>
                                                @can('view_pharma_inventory_costs')
                                                    <div class="my-1 border-t border-slate-100"></div>
                                                    <a href="{{ route('admin.pharma.inventory.receipts.pdf.cost',$doc) }}" class="block px-4 py-2.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Tải PDF giá vốn</a>
                                                    <a href="{{ route('admin.pharma.inventory.receipts.print.cost',$doc) }}" target="_blank" class="block px-4 py-2.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">In theo giá vốn</a>
                                                @endcan
                                                <div class="my-1 border-t border-slate-100"></div>
                                                @can('edit_pharma')
                                                    @if($doc->status === 'draft')
                                                        <a href="{{ route('admin.pharma.inventory.receipts.edit',$doc) }}" class="block px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Sửa phiếu</a>
                                                        <form method="POST" action="{{ route('admin.pharma.inventory.receipts.approve',$doc) }}">@csrf<button class="block w-full px-4 py-2.5 text-left text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Phê duyệt</button></form>
                                                    @elseif($doc->status === 'pending_approval')
                                                        <form method="POST" action="{{ route('admin.pharma.inventory.receipts.approve',$doc) }}">@csrf<button class="block w-full px-4 py-2.5 text-left text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Phê duyệt</button></form>
                                                    @elseif($doc->status === 'approved')
                                                        <form method="POST" action="{{ route('admin.pharma.inventory.receipts.undo-approval',$doc) }}">@csrf<button class="block w-full px-4 py-2.5 text-left text-xs font-semibold text-amber-700 hover:bg-amber-50">Hoàn tác phê duyệt</button></form>
                                                        <div class="my-1 border-t border-slate-100"></div>
                                                        <button type="button" onclick="document.getElementById('post-receipt-{{ $doc->id }}').showModal()" class="block w-full px-4 py-2.5 text-left text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Ghi sổ</button>
                                                    @endif
                                                @endcan
                                                @can('delete_pharma')
                                                    @if($doc->status === 'draft')
                                                        <div class="my-1 border-t border-slate-100"></div>
                                                        <button type="button" onclick="document.getElementById('delete-receipt-{{ $doc->id }}').showModal()" class="block w-full px-4 py-2.5 text-left text-xs font-semibold text-rose-700 hover:bg-rose-50">Xóa</button>
                                                    @elseif($doc->status === 'posted')
                                                        <button type="button" onclick="document.getElementById('revert-receipt-{{ $doc->id }}').showModal()" class="block w-full px-4 py-2.5 text-left text-xs font-semibold text-amber-700 hover:bg-amber-50">Hoàn tác ghi sổ</button>
                                                    @endif
                                                @endcan
                                            </div>
                                        </details>
                                    @else
                                        <details class="relative" data-document-actions>
                                            <summary class="flex min-h-9 cursor-pointer list-none items-center rounded-lg border border-slate-300 bg-white px-3 text-base font-bold leading-none text-slate-600 hover:bg-slate-50" aria-label="Thao tác khác">⋯</summary>
                                            <div class="absolute right-0 z-30 mt-2 w-48 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl">
                                                @can('approve_pharma_inventory_issue')
                                                    @if($doc->status === 'pending_approval')
                                                        <a href="{{ route('admin.pharma.inventory.issues.show',$doc) }}" class="block px-4 py-2.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Xử lý phê duyệt</a>
                                                        <div class="my-1 border-t border-slate-100"></div>
                                                    @endif
                                                @endcan
                                                @can('edit_pharma')
                                                    @if(($doc->issue_source ?? 'normal') === 'bid')
                                                        @if(in_array($doc->status, ['draft','approved'], true))
                                                            <a href="{{ route('admin.pharma.inventory.issues.bid-sales.edit',$doc) }}" class="block px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Sửa đơn hàng thầu</a>
                                                        @endif
                                                    @else
                                                        <a href="{{ route('admin.pharma.inventory.issues.edit',$doc) }}" class="block px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">{{ $doc->status === 'draft' ? 'Sửa phiếu' : 'Cập nhật phiếu' }}</a>
                                                    @endif
                                                @endcan
                                                @can('approve_pharma_inventory_issue')
                                                    @if($doc->status === 'approved')
                                                        <form method="POST" action="{{ route('admin.pharma.inventory.issues.undo-approval',$doc) }}">@csrf<button class="block w-full px-4 py-2.5 text-left text-xs font-semibold text-amber-700 hover:bg-amber-50">Hoàn tác phê duyệt</button></form>
                                                        @if(($doc->issue_source ?? 'normal') !== 'bid')
                                                            @if($doc->can_post_stock)<button type="button" onclick="this.closest('details').removeAttribute('open'); document.getElementById('post-{{ $type }}-{{ $doc->id }}').showModal()" class="block w-full px-4 py-2.5 text-left text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Ghi sổ</button>
                                                            @else<button type="button" disabled title="Không đủ tồn kho để ghi sổ" class="block w-full cursor-not-allowed px-4 py-2.5 text-left text-xs font-semibold text-slate-400">Ghi sổ · Không đủ tồn</button>@endif
                                                        @endif
                                                        <div class="my-1 border-t border-slate-100"></div>
                                                    @endif
                                                @endcan
                                                <a href="{{ route('admin.pharma.inventory.issues.pdf',$doc) }}" class="block px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Tải PDF</a>
                                                <a href="{{ route('admin.pharma.inventory.issues.print',$doc) }}" target="_blank" rel="noopener" class="block px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">In trực tiếp</a>
                                                @can('delete_pharma')
                                                    <div class="my-1 border-t border-slate-100"></div>
                                                    @if(in_array($doc->status, ['draft','rejected'], true))<button type="button" onclick="this.closest('details').removeAttribute('open'); document.getElementById('delete-issue-{{ $doc->id }}').showModal()" class="block w-full px-4 py-2.5 text-left text-xs font-semibold text-rose-700 hover:bg-rose-50">Xóa phiếu</button>
                                                    @elseif($doc->status === 'posted')<button type="button" onclick="this.closest('details').removeAttribute('open'); document.getElementById('revert-issue-{{ $doc->id }}').showModal()" class="block w-full px-4 py-2.5 text-left text-xs font-semibold text-amber-700 hover:bg-amber-50">Hoàn tác ghi sổ</button>@endif
                                                @endcan
                                            </div>
                                        </details>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @if($type === 'issue' && filled($doc->shortage_note))
                            <dialog id="shortage-note-{{ $doc->id }}" class="m-auto w-[calc(100%-2rem)] max-w-lg rounded-2xl border-0 bg-white p-0 shadow-2xl ring-1 ring-slate-200 backdrop:bg-slate-950/50">
                                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4"><div><p class="text-xs font-bold uppercase tracking-wider text-amber-600">Thiếu hàng</p><h3 class="mt-1 font-semibold text-slate-950">{{ $doc->number }}</h3></div><button type="button" onclick="this.closest('dialog').close()" class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 text-slate-500" aria-label="Đóng">×</button></div>
                                <div class="whitespace-pre-line px-5 py-4 text-sm leading-6 text-slate-700">{{ $doc->shortage_note }}</div>
                            </dialog>
                        @endif
                        @if((($type === 'issue' && $doc->status === 'approved') || ($type === 'receipt' && $doc->status === 'approved')) && !($type === 'issue' && ($doc->issue_source ?? 'normal') === 'bid') && ($type !== 'issue' || $doc->can_post_stock))
                            <dialog id="post-{{ $type }}-{{ $doc->id }}" class="m-auto w-[calc(100%-2rem)] max-w-lg overflow-hidden rounded-2xl border-0 bg-white p-0 shadow-2xl ring-1 ring-slate-200 backdrop:bg-slate-950/65 backdrop:backdrop-blur-[3px]">
                                <form method="POST" action="{{ $postRoute }}" class="overflow-hidden rounded-2xl bg-white">
                                    @csrf
                                    <div class="flex items-start gap-4 p-6">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xl text-emerald-700">✓</div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-start justify-between gap-3"><div><h3 class="text-lg font-bold text-slate-950">Xác nhận ghi sổ?</h3><p class="mt-0.5 break-all font-mono text-xs font-semibold text-slate-500">{{ $doc->number }}</p></div><button type="button" onclick="this.closest('dialog').close()" class="rounded-lg p-1.5 text-xl leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Đóng">×</button></div>
                                            <p class="mt-4 text-sm leading-6 text-slate-600">Phiếu có <strong>{{ $doc->items_count }} mặt hàng</strong>, tổng số lượng <strong>{{ number_format((float)$doc->total_quantity,0,',','.') }}</strong>. Sau khi xác nhận, tồn kho thực tế sẽ được cập nhật.</p>
                                            @if($type === 'issue')<div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm leading-5 text-amber-800"><strong>Kiểm tra tồn kho:</strong> hệ thống sẽ kiểm tra tồn khả dụng trước khi xuất.</div>@endif
                                        </div>
                                    </div>
                                    <div class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end"><button type="button" onclick="this.closest('dialog').close()" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Hủy</button><button class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm">Xác nhận ghi sổ</button></div>
                                </form>
                            </dialog>
                        @endif
                        @if($type === 'issue' && $doc->status === 'posted')
                            @can('delete_pharma')
                            <dialog id="revert-issue-{{ $doc->id }}" class="m-auto w-[calc(100%-2rem)] max-w-lg overflow-hidden rounded-2xl border-0 bg-white p-0 shadow-2xl ring-1 ring-slate-200 backdrop:bg-slate-950/65 backdrop:backdrop-blur-[3px]"><form method="POST" action="{{ route('admin.pharma.inventory.issues.revert',$doc) }}" class="overflow-hidden rounded-2xl bg-white">@csrf<div class="p-6"><h3 class="text-lg font-bold">Hoàn tác ghi sổ phiếu xuất?</h3><p class="mt-2 text-sm text-slate-600">Hàng của phiếu sẽ được cộng trả đúng lô tồn kho và phiếu trở về Nháp.</p></div><div class="flex justify-end gap-2 border-t bg-slate-50 px-6 py-4"><button type="button" onclick="this.closest('dialog').close()" class="rounded-xl border bg-white px-4 py-2">Hủy</button><button class="rounded-xl bg-amber-600 px-4 py-2 font-semibold text-white">Hoàn tác ghi sổ</button></div></form></dialog>
                            @endcan
                        @endif
                        @if($type === 'issue' && in_array($doc->status, ['draft','rejected'], true))
                            @can('delete_pharma')
                            <dialog id="delete-issue-{{ $doc->id }}" class="m-auto w-[calc(100%-2rem)] max-w-lg overflow-hidden rounded-2xl border-0 bg-white p-0 shadow-2xl ring-1 ring-slate-200 backdrop:bg-slate-950/65 backdrop:backdrop-blur-[3px]"><form method="POST" action="{{ route('admin.pharma.inventory.issues.destroy',$doc) }}" class="overflow-hidden rounded-2xl bg-white">@csrf @method('DELETE')<div class="p-6"><h3 class="text-lg font-bold">{{ $doc->status === 'rejected' ? 'Xóa phiếu xuất đã từ chối?' : 'Xóa phiếu xuất nháp?' }}</h3><p class="mt-2 text-sm text-slate-600">Phiếu chưa ghi sổ nên xóa không ảnh hưởng tồn kho. Thao tác này không thể hoàn tác.</p></div><div class="flex justify-end gap-2 border-t bg-slate-50 px-6 py-4"><button type="button" onclick="this.closest('dialog').close()" class="rounded-xl border bg-white px-4 py-2">Hủy</button><button class="rounded-xl bg-rose-600 px-4 py-2 font-semibold text-white">Xác nhận xóa</button></div></form></dialog>
                            @endcan
                        @endif
                        @if($type === 'receipt' && $doc->status === 'posted')
                            @can('delete_pharma')
                                <dialog id="revert-receipt-{{ $doc->id }}" class="m-auto w-[calc(100%-2rem)] max-w-lg overflow-hidden rounded-2xl border-0 bg-white p-0 shadow-2xl ring-1 ring-slate-200 backdrop:bg-slate-950/65 backdrop:backdrop-blur-[3px]">
                                    <form method="POST" action="{{ route('admin.pharma.inventory.receipts.revert',$doc) }}" class="overflow-hidden rounded-2xl bg-white">@csrf
                                        <div class="flex items-start gap-4 p-6">
                                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-100 text-xl font-bold text-amber-700">!</div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-start justify-between gap-3"><div><h3 class="text-lg font-bold text-slate-950">Hoàn tác ghi sổ?</h3><p class="mt-0.5 break-all font-mono text-xs font-semibold text-slate-500">{{ $doc->number }}</p></div><button type="button" onclick="this.closest('dialog').close()" class="rounded-lg p-1.5 text-xl leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Đóng">×</button></div>
                                                <p class="mt-4 text-sm leading-6 text-slate-600">Phiếu sẽ trở về <strong>Đã duyệt</strong> và đúng số lượng đã nhập của phiếu này sẽ được rút khỏi tồn kho.</p>
                                                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm leading-5 text-amber-900"><strong>Kiểm tra tồn kho:</strong> nếu bất kỳ lô nào không đủ tồn để hoàn tác, hệ thống sẽ hủy toàn bộ thao tác và không thay đổi tồn kho.</div>
                                            </div>
                                        </div>
                                        <div class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end"><button type="button" onclick="this.closest('dialog').close()" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Hủy</button><button class="rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm">Hoàn tác ghi sổ</button></div>
                                    </form>
                                </dialog>
                            @endcan
                        @endif
                        @if($type === 'receipt' && $doc->status === 'draft')
                            <dialog id="delete-receipt-{{ $doc->id }}" class="m-auto w-[calc(100%-2rem)] max-w-lg overflow-hidden rounded-2xl border-0 bg-white p-0 shadow-2xl ring-1 ring-slate-200 backdrop:bg-slate-950/65 backdrop:backdrop-blur-[3px]">
                                <form method="POST" action="{{ route('admin.pharma.inventory.receipts.destroy',$doc) }}" class="overflow-hidden rounded-2xl bg-white">@csrf @method('DELETE')
                                    <div class="flex items-start gap-4 p-6">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-rose-100 text-xl font-bold text-rose-700">×</div>
                                        <div class="min-w-0 flex-1"><div class="flex items-start justify-between gap-3"><div><h3 class="text-lg font-bold text-slate-950">Xóa phiếu nhập nháp?</h3><p class="mt-0.5 break-all font-mono text-xs font-semibold text-slate-500">{{ $doc->number }}</p></div><button type="button" onclick="this.closest('dialog').close()" class="rounded-lg p-1.5 text-xl leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Đóng">×</button></div>
                                        <p class="mt-4 text-sm leading-6 text-slate-600">Phiếu và toàn bộ chi tiết hàng hóa nháp sẽ bị xóa. Phiếu chưa ghi sổ nên thao tác này không làm thay đổi tồn kho.</p></div>
                                    </div>
                                    <div class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end"><button type="button" onclick="this.closest('dialog').close()" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Hủy</button><button class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm">Xác nhận xóa</button></div>
                                </form>
                            </dialog>
                        @endif
                    @empty
                        <tr><td colspan="{{ $type === 'issue' ? 9 : 7 }}" class="px-6 py-12 text-center text-slate-500">Chưa có chứng từ.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 p-4">{{ $documents->links() }}</div>
    </section>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    @if($type === 'issue')
    const filterForm=document.getElementById('document-filters');
    const keyword=document.getElementById('issue-keyword-filter');
    document.querySelector('[data-clear-keyword]')?.addEventListener('click',()=>{if(keyword){keyword.value='';filterForm?.requestSubmit();}});
    document.querySelectorAll('[data-auto-submit-filter]').forEach((select)=>{
        select.addEventListener('change',()=>filterForm?.requestSubmit());
    });
    @endif
    const actionMenus = Array.from(document.querySelectorAll('details[data-document-actions]'));
    actionMenus.forEach((menu) => {
        menu.addEventListener('toggle', () => {
            if (!menu.open) return;
            actionMenus.forEach((other) => {
                if (other !== menu) other.removeAttribute('open');
            });
        });
    });
    document.addEventListener('click', (event) => {
        actionMenus.forEach((menu) => {
            if (menu.open && !menu.contains(event.target)) menu.removeAttribute('open');
        });
    });
});
@if($type === 'issue')
    const rows=[...document.querySelectorAll('[data-row-select]')];
    const all=document.querySelector('[data-select-page]');
    const toolbar=document.querySelector('[data-selection-toolbar]');
    const count=document.querySelector('[data-selected-count]');
    const buttonCount=document.querySelector('[data-selected-button-count]');
    const refreshSelection=()=>{
        const selected=rows.filter(row=>row.checked).length;
        if(count) count.textContent=selected;
        if(buttonCount) buttonCount.textContent=selected;
        if(toolbar){toolbar.classList.toggle('hidden',selected===0);toolbar.classList.toggle('flex',selected>0);}
        if(all){all.checked=rows.length>0 && selected===rows.length;all.indeterminate=selected>0 && selected<rows.length;}
    };
    all?.addEventListener('change',()=>{rows.forEach(row=>row.checked=all.checked);refreshSelection();});
    rows.forEach(row=>row.addEventListener('change',refreshSelection));
    document.querySelector('[data-clear-selection]')?.addEventListener('click',()=>{rows.forEach(row=>row.checked=false);refreshSelection();});
    refreshSelection();
@endif
</script>
@endsection
