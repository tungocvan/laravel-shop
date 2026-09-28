@extends('ClientPortal::layouts.application')

@section('title', 'Chi tiết bảng giá')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Bảng giá do bạn phụ trách')
@section('app-dashboard-route', route('client.pharma.dashboard'))

@section('content')
<div class="mx-auto max-w-6xl space-y-5">
    @if(session('success'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800">{{ session('success') }}</div>@endif
    <a href="{{ route('client.pharma.price-lists') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-slate-950">← Bảng giá của tôi</a>
    @php $customer = $priceList->partner?->name ?? $priceList->officialFacility?->facility_name ?? $priceList->officialFacility?->name ?? 'Bảng giá chung'; @endphp
    <section class="rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"><div><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-300">Price List</p><h1 class="mt-2 text-2xl font-black sm:text-3xl">{{ $priceList->name }}</h1><p class="mt-2 text-sm text-slate-300">{{ $customer }} · {{ $priceList->purpose?->name ?: 'Chưa có mục đích' }}</p></div><span class="w-fit rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold">{{ $priceList->status }}</span></div>
    </section>

    @if($priceList->status === \Modules\Pharma\Models\PriceList::STATUS_DRAFT && ($canSubmit || $canEdit))
        <section class="flex flex-col gap-3 rounded-3xl border border-amber-200 bg-amber-50 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div><p class="font-black text-amber-950">Bảng giá đang ở trạng thái Nháp</p><p class="mt-1 text-sm text-amber-800">Gửi duyệt sẽ khóa bước lập giá và chuyển sang hàng chờ người có quyền phê duyệt.</p></div>
            <div class="flex flex-wrap gap-2">@if($canEdit)<a href="{{ route('client.pharma.price-lists.edit', $priceList->id) }}" class="rounded-2xl border border-amber-300 bg-white px-5 py-3 text-sm font-black text-amber-800">Sửa Nháp</a><form method="POST" action="{{ route('client.pharma.price-lists.delete', $priceList->id) }}" onsubmit="return confirm('Xóa bảng giá Nháp này?')">@csrf @method('DELETE')<button class="rounded-2xl border border-red-200 bg-white px-5 py-3 text-sm font-black text-red-600">Xóa</button></form>@endif @if($canSubmit)<form method="POST" action="{{ route('client.pharma.price-lists.submit', $priceList->id) }}">@csrf<button class="rounded-2xl bg-amber-600 px-5 py-3 text-sm font-black text-white">Gửi duyệt</button></form>@endif</div>
        </section>
    @elseif($priceList->status === \Modules\Pharma\Models\PriceList::STATUS_PENDING_APPROVAL)
        <section class="flex flex-col gap-3 rounded-3xl border border-blue-200 bg-blue-50 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div><p class="font-black text-blue-950">Đang chờ phê duyệt</p><p class="mt-1 text-sm text-blue-700">Đã gửi{{ $priceList->submitted_at ? ' lúc '.$priceList->submitted_at->format('H:i d/m/Y') : '' }}.</p></div>
            @if($canApprove)
                <form method="POST" action="{{ route('client.pharma.price-list-approvals.approve',$priceList->id) }}" onsubmit="return confirm('Phê duyệt và kích hoạt bảng giá này?')">@csrf<button class="rounded-2xl bg-emerald-700 px-5 py-3 text-sm font-black text-white">Phê duyệt & kích hoạt</button></form>
            @endif
        </section>
    @elseif($priceList->status === \Modules\Pharma\Models\PriceList::STATUS_REJECTED)
        <section class="rounded-3xl border border-rose-200 bg-rose-50 p-5">
            <p class="font-black text-rose-950">Bảng giá đã bị từ chối</p>
            <p class="mt-1 text-sm text-rose-800">{{ $priceList->rejection_reason ?: 'Không có lý do.' }}</p>
            <p class="mt-2 text-xs font-bold text-rose-600">{{ $priceList->rejected_at ? 'Từ chối lúc '.$priceList->rejected_at->format('H:i d/m/Y') : '' }}</p>
            <div class="mt-4 flex flex-wrap gap-2">@if($canEdit)<a href="{{ route('client.pharma.price-lists.edit', $priceList->id) }}" class="rounded-2xl border border-rose-300 bg-white px-5 py-3 text-sm font-black text-rose-700">Sửa bảng giá</a>@endif @if($canSubmit)<form method="POST" action="{{ route('client.pharma.price-lists.submit', $priceList->id) }}">@csrf<button class="rounded-2xl bg-rose-700 px-5 py-3 text-sm font-black text-white">Gửi duyệt lại</button></form>@endif</div>
        </section>
    @endif

    @if($priceList->status === \Modules\Pharma\Models\PriceList::STATUS_ACTIVE)
        @php
            $share = session('price_list_share') ?? $currentExportShare;
            $profileNames = collect($exportProfiles)->mapWithKeys(fn ($profile) => [(int) $profile['id'] => $profile['name']]);
        @endphp
        <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div><p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Tài liệu bảng giá</p><p class="mt-1 text-sm text-slate-600">Xuất nhiều mẫu độc lập; mỗi bản Excel có thể tạo hoặc tạo lại PDF riêng.</p></div>
                <div class="flex flex-wrap gap-2">
                    <details class="group relative">
                        <summary class="cursor-pointer list-none rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white">+ Xuất tài liệu</summary>
                        <div class="absolute right-0 z-20 mt-2 w-[min(92vw,430px)] rounded-3xl border border-slate-200 bg-white p-5 shadow-xl">
                            <p class="font-black text-slate-950">Xuất tài liệu mới</p><p class="mt-1 text-xs text-slate-500">Bản xuất trước được giữ nguyên trong lịch sử.</p>
                            <form method="POST" action="{{ route('client.pharma.price-lists.export-share',$priceList->id) }}" class="mt-4 space-y-4">@csrf
                                <label class="block"><span class="mb-1.5 block text-xs font-bold text-slate-500">Mẫu bảng giá</span><select name="export_profile_id" class="h-11 w-full rounded-2xl border border-slate-300 bg-white px-3 text-sm"><option value="">Mặc định hệ thống</option>@foreach($exportProfiles as $profile)<option value="{{ $profile['id'] }}">{{ $profile['name'] }}{{ $profile['is_default']?' · Mặc định':'' }}</option>@endforeach</select></label>
                                <div class="flex justify-end"><button class="rounded-2xl bg-emerald-700 px-5 py-3 text-sm font-black text-white">Xuất Excel mới</button></div>
                            </form>
                        </div>
                    </details>
                    <details class="group">
                        <summary class="cursor-pointer list-none rounded-2xl border border-slate-300 px-5 py-3 text-sm font-black text-slate-700">Tài liệu đã xuất · {{ count($exportHistory) }}</summary>
                        <div class="mt-3 space-y-3 lg:min-w-[720px]">
                            @forelse($exportHistory as $export)
                                @php
                                    $exportId = (int) $export['share_id'];
                                    $profileLabel = $export['export_profile_id'] ? ($profileNames[$export['export_profile_id']] ?? 'Mẫu #'.$export['export_profile_id']) : 'Mặc định hệ thống';
                                    $isProcessing = in_array($export['pdf_status'] ?? null, ['queued','processing'], true);
                                @endphp
                                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4" @if($share && $exportId === (int)$share['share_id']) data-export-share data-status-url="{{ route('client.pharma.price-lists.share.status',$exportId) }}" @endif>
                                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                        <div><p class="font-black text-slate-900">{{ $profileLabel }}</p><p class="mt-1 text-xs text-slate-500">{{ $export['created_at'] }} · {{ $export['download_name'] }}</p><div class="mt-2 flex gap-2 text-xs font-bold"><span class="rounded-full bg-emerald-100 px-2.5 py-1 text-emerald-700">Excel ✓</span>@if($export['pdf_available'])<span id="{{ $share && $exportId === (int)$share['share_id'] ? 'price-list-pdf-status' : '' }}" class="rounded-full bg-violet-100 px-2.5 py-1 text-violet-700">PDF ✓</span>@elseif($isProcessing)<span id="{{ $share && $exportId === (int)$share['share_id'] ? 'price-list-pdf-status' : '' }}" class="rounded-full bg-violet-100 px-2.5 py-1 text-violet-700">Đang tạo PDF...</span>@elseif(($export['pdf_status'] ?? null)==='failed')<span class="rounded-full bg-red-100 px-2.5 py-1 text-red-700">PDF lỗi</span>@else<span class="rounded-full bg-slate-200 px-2.5 py-1 text-slate-600">Chưa có PDF</span>@endif</div></div>
                                        <div class="flex flex-wrap gap-2">
                                            @if(!$export['revoked'])<a href="{{ $export['url'] }}" class="rounded-xl border border-emerald-300 bg-white px-3 py-2 text-xs font-bold text-emerald-800">Tải Excel</a>@endif
                                            @if($export['pdf_available'] && !$export['revoked'])<a href="{{ $export['pdf_url'] }}" class="rounded-xl border border-violet-300 bg-white px-3 py-2 text-xs font-bold text-violet-800">Tải PDF</a>@endif
                                            @if(!$isProcessing && !$export['revoked'])
                                                <form method="POST" action="{{ $export['pdf_available'] ? route('client.pharma.price-lists.share.pdf.regenerate',$exportId) : route('client.pharma.price-lists.share.pdf.queue',$exportId) }}">@csrf<button class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold">{{ $export['pdf_available'] ? 'Tạo lại PDF' : 'Tạo PDF' }}</button></form>
                                            @endif
                                            @if(!$export['revoked'])<button type="button" onclick="shareExportUrl('export-url-{{ $exportId }}')" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold">Chia sẻ</button><input id="export-url-{{ $exportId }}" type="hidden" value="{{ $export['pdf_available'] ? $export['pdf_url'] : $export['url'] }}">@endif
                                            <form method="POST" action="{{ route('client.pharma.price-lists.share.export.delete',$exportId) }}" onsubmit="return confirm('Xóa bản xuất Excel/PDF này?')">@csrf @method('DELETE')<button @disabled($isProcessing) class="rounded-xl px-3 py-2 text-xs font-bold text-red-600 disabled:opacity-40">Xóa bản xuất</button></form>
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">Chưa có tài liệu xuất.</div>
                            @endforelse
                        </div>
                    </details>
                    @if(($isManager && !$canApprove) || $canApprove)
                        <details class="group relative">
                            <summary class="cursor-pointer list-none rounded-2xl border border-slate-300 px-4 py-3 text-sm font-black text-slate-600">⋯</summary>
                            <div class="absolute right-0 z-20 mt-2 w-80 rounded-3xl border border-slate-200 bg-white p-4 shadow-xl">
                                <p class="font-black text-slate-900">Ngừng kích hoạt</p>
                                @if($isManager && !$canApprove)
                                    <p class="mt-1 text-xs text-slate-500">Gửi yêu cầu để người phê duyệt xử lý.</p><form method="POST" action="{{ route('client.pharma.price-lists.deactivation.request',$priceList->id) }}" class="mt-3 space-y-2">@csrf<input name="deactivation_reason" required maxlength="1000" class="h-11 w-full rounded-2xl border border-slate-300 px-4 text-sm" placeholder="Lý do..."><button class="w-full rounded-2xl bg-rose-700 px-4 py-3 text-sm font-black text-white">Yêu cầu ngừng kích hoạt</button></form>
                                @else
                                    <p class="mt-1 text-xs text-slate-500">Bắt buộc ghi lý do để lưu lịch sử.</p><form method="POST" action="{{ route('client.pharma.price-lists.deactivate',$priceList->id) }}" class="mt-3 space-y-2" onsubmit="return confirm('Ngừng kích hoạt bảng giá này?')">@csrf<input name="deactivation_reason" required maxlength="1000" class="h-11 w-full rounded-2xl border border-slate-300 px-4 text-sm" placeholder="Lý do..."><button class="w-full rounded-2xl bg-rose-700 px-4 py-3 text-sm font-black text-white">Ngừng kích hoạt</button></form>
                                @endif
                            </div>
                        </details>
                    @endif
                </div>
            </div>
        </section>
        <script>
            function copyShareUrl(id){const e=document.getElementById(id);if(!e)return;navigator.clipboard?navigator.clipboard.writeText(e.value):(e.type!=='hidden'&&e.select())}
            function shareExportUrl(id){const e=document.getElementById(id);if(!e)return;if(navigator.share){navigator.share({title:'Bảng giá',url:e.value})}else{copyShareUrl(id)}}
            (()=>{const box=document.querySelector('[data-export-share]');if(!box)return;const status=document.getElementById('price-list-pdf-status');if(!status||!status.textContent.includes('Đang tạo PDF'))return;const poll=()=>fetch(box.dataset.statusUrl,{headers:{'Accept':'application/json'}}).then(r=>r.ok?r.json():Promise.reject()).then(data=>{if(data.pdf_status==='completed'||data.pdf_status==='failed'){const key='pharma-pdf-status-refreshed-'+data.share_id;if(!sessionStorage.getItem(key)){sessionStorage.setItem(key,'1');window.location.reload()}return}setTimeout(poll,2500)}).catch(()=>setTimeout(poll,5000));setTimeout(poll,1500)})()
        </script>
    @elseif($priceList->status === \Modules\Pharma\Models\PriceList::STATUS_PENDING_DEACTIVATION)
        <section class="rounded-3xl border border-violet-200 bg-violet-50 p-5"><p class="font-black text-violet-950">Đang chờ duyệt ngừng kích hoạt</p><p class="mt-1 text-sm text-violet-800">{{ $priceList->deactivation_reason }}</p><p class="mt-2 text-xs font-bold text-violet-600">{{ $priceList->deactivation_requested_at ? 'Yêu cầu lúc '.$priceList->deactivation_requested_at->format('H:i d/m/Y') : '' }}</p>@if($canApprove)<form method="POST" action="{{ route('client.pharma.price-lists.deactivation.approve',$priceList->id) }}" class="mt-4" onsubmit="return confirm('Chấp nhận ngừng kích hoạt bảng giá này?')">@csrf<button class="rounded-2xl bg-violet-700 px-5 py-3 text-sm font-black text-white">Chấp nhận ngừng kích hoạt</button></form>@endif</section>
    @elseif($priceList->status === \Modules\Pharma\Models\PriceList::STATUS_INACTIVE && $priceList->deactivation_reason)
        <section class="rounded-3xl border border-slate-200 bg-slate-50 p-5"><p class="font-black text-slate-900">Bảng giá đã ngừng kích hoạt</p><p class="mt-1 text-sm text-slate-600">{{ $priceList->deactivation_reason }}</p><p class="mt-2 text-xs font-bold text-slate-500">{{ $priceList->deactivated_at ? 'Ngừng lúc '.$priceList->deactivated_at->format('H:i d/m/Y') : '' }}</p></section>
    @endif

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([['Mã bảng giá',$priceList->code],['Số sản phẩm',$priceList->items_count],['Hiệu lực từ',$priceList->effective_from?->format('d/m/Y')],['Hiệu lực đến',$priceList->effective_to?->format('d/m/Y')]] as [$label,$value])
                <div><p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $label }}</p><p class="mt-1.5 font-bold text-slate-800">{{ $value ?: '—' }}</p></div>
            @endforeach
        </div>
        @if($priceList->notes)<p class="mt-5 rounded-2xl bg-slate-50 p-4 text-sm leading-6 text-slate-600">{{ $priceList->notes }}</p>@endif
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4"><p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">Sản phẩm</p><h2 class="mt-1 text-lg font-black text-slate-950">{{ $priceList->items_count }} sản phẩm trong bảng giá</h2></div>
        <div class="divide-y divide-slate-100">
            @forelse($priceList->items as $item)
                <article class="grid gap-4 px-5 py-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="lg:col-span-2"><p class="font-black text-slate-900">{{ $item->variant?->medicine?->name ?? $item->variant?->sku ?? 'SKU #'.$item->medicine_variant_id }}</p><p class="mt-1 text-xs text-slate-400">{{ $item->variant?->sku }}@if($item->package) · {{ $item->package->name ?? $item->package->packaging_specification ?? '' }}@endif</p></div>
                    <div><p class="text-xs font-bold text-slate-400">Giá kê khai</p><p class="mt-1 font-bold tabular-nums">{{ $item->declared_price_snapshot !== null ? number_format((float)$item->declared_price_snapshot,0,',','.') : '—' }}</p></div>
                    <div><p class="text-xs font-bold text-slate-400">Giá bán CT</p><p class="mt-1 font-black tabular-nums text-slate-950">{{ $item->company_sale_price !== null ? number_format((float)$item->company_sale_price,0,',','.') : '—' }}</p></div>
                    <div><p class="text-xs font-bold text-slate-400">Giá thu / Giá HĐ</p><p class="mt-1 text-sm tabular-nums text-slate-700">{{ $item->actual_receivable_price !== null ? number_format((float)$item->actual_receivable_price,0,',','.') : '—' }} / {{ $item->invoice_price !== null ? number_format((float)$item->invoice_price,0,',','.') : '—' }}</p></div>
                </article>
            @empty <p class="px-5 py-8 text-center text-sm text-slate-500">Bảng giá chưa có sản phẩm.</p> @endforelse
        </div>
    </section>
</div>
@endsection
