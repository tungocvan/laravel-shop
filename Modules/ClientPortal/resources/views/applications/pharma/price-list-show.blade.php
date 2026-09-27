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
        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm font-bold text-blue-800">Đã gửi duyệt{{ $priceList->submitted_at ? ' lúc '.$priceList->submitted_at->format('H:i d/m/Y') : '' }} · Đang chờ phê duyệt.</div>
    @elseif($priceList->status === \Modules\Pharma\Models\PriceList::STATUS_REJECTED)
        <section class="rounded-3xl border border-rose-200 bg-rose-50 p-5">
            <p class="font-black text-rose-950">Bảng giá đã bị từ chối</p>
            <p class="mt-1 text-sm text-rose-800">{{ $priceList->rejection_reason ?: 'Không có lý do.' }}</p>
            <p class="mt-2 text-xs font-bold text-rose-600">{{ $priceList->rejected_at ? 'Từ chối lúc '.$priceList->rejected_at->format('H:i d/m/Y') : '' }}</p>
            <div class="mt-4 flex flex-wrap gap-2">@if($canEdit)<a href="{{ route('client.pharma.price-lists.edit', $priceList->id) }}" class="rounded-2xl border border-rose-300 bg-white px-5 py-3 text-sm font-black text-rose-700">Sửa bảng giá</a>@endif @if($canSubmit)<form method="POST" action="{{ route('client.pharma.price-lists.submit', $priceList->id) }}">@csrf<button class="rounded-2xl bg-rose-700 px-5 py-3 text-sm font-black text-white">Gửi duyệt lại</button></form>@endif</div>
        </section>
    @endif

    @if($priceList->status === \Modules\Pharma\Models\PriceList::STATUS_ACTIVE)
        @php($share = session('price_list_share') ?? $currentExportShare)
        <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-center gap-2">
                <details class="group flex-1 min-w-[260px] rounded-2xl border border-emerald-200 bg-emerald-50">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-black text-emerald-950">
                        <span>Xuất & chia sẻ bảng giá Excel / PDF</span><span class="text-lg transition group-open:rotate-180">⌄</span>
                    </summary>
                    <div class="border-t border-emerald-200 p-4">
                        <p class="text-sm text-emerald-800">Dùng cấu hình mẫu bảng giá Pharma. Excel và PDF được lưu trên server; liên kết chia sẻ có hiệu lực 30 ngày.</p>
                        <form method="POST" action="{{ route('client.pharma.price-lists.export-share',$priceList->id) }}" class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-end">
                            @csrf
                            <label class="flex-1"><span class="mb-1 block text-xs font-bold text-emerald-800">Mẫu bảng giá</span><select name="export_profile_id" class="h-11 w-full rounded-2xl border border-emerald-300 bg-white px-3 text-sm"><option value="">Mặc định hệ thống</option>@foreach($exportProfiles as $profile)<option value="{{ $profile['id'] }}">{{ $profile['name'] }}{{ $profile['is_default']?' · Mặc định':'' }}</option>@endforeach</select></label>
                            <button class="h-11 rounded-2xl bg-emerald-700 px-5 text-sm font-black text-white">Xuất Excel</button>
                        </form>

                        @if($share)
                            <div class="mt-4 rounded-2xl border border-emerald-200 bg-white p-4" data-export-share data-status-url="{{ route('client.pharma.price-lists.share.status',$share['share_id']) }}">
                                <div class="flex flex-wrap items-center justify-between gap-2"><p class="text-sm font-black text-slate-900">File xuất gần nhất</p><span class="text-xs font-bold text-slate-500">Hết hạn: {{ $share['expires_at'] }}</span></div>
                                <div class="mt-3 grid gap-3 lg:grid-cols-[1fr_auto]">
                                    <div><p class="mb-1 text-xs font-bold uppercase tracking-wide text-slate-400">Excel</p><input id="price-list-share-url" readonly value="{{ $share['url'] }}" class="h-11 w-full rounded-xl border border-slate-300 px-3 text-sm"></div>
                                    <div class="flex flex-wrap items-end gap-2"><a href="{{ $share['url'] }}" class="rounded-xl border border-emerald-300 px-4 py-3 text-sm font-bold text-emerald-800">Tải Excel</a><button type="button" onclick="copyShareUrl('price-list-share-url')" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold">Sao chép link</button><button type="button" onclick="shareExportUrl('price-list-share-url')" class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-bold text-white">Chia sẻ</button></div>
                                </div>

                                <div class="mt-4 border-t border-slate-100 pt-4">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div><p class="text-xs font-bold uppercase tracking-wide text-slate-400">PDF</p><p id="price-list-pdf-status" class="mt-1 text-sm font-bold text-slate-700">@if(($share['pdf_status'] ?? null)==='completed')PDF đã sẵn sàng@elseif(in_array(($share['pdf_status'] ?? null),['queued','processing'],true))Đang chuyển PDF qua Queue Pharma...@elseif(($share['pdf_status'] ?? null)==='failed')Chuyển PDF không thành công@else Chưa tạo PDF @endif</p>@if(($share['pdf_error'] ?? null))<p class="mt-1 text-xs text-red-600">{{ $share['pdf_error'] }}</p>@endif</div>
                                        @if(($share['pdf_available'] ?? false) && ($share['pdf_url'] ?? null))
                                            <div class="flex flex-wrap gap-2"><a href="{{ $share['pdf_url'] }}" class="rounded-xl border border-violet-300 px-4 py-3 text-sm font-bold text-violet-800">Tải PDF</a><input id="price-list-pdf-url" type="hidden" value="{{ $share['pdf_url'] }}"><button type="button" onclick="copyShareUrl('price-list-pdf-url')" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold">Sao chép link PDF</button><button type="button" onclick="shareExportUrl('price-list-pdf-url')" class="rounded-xl bg-violet-700 px-4 py-3 text-sm font-bold text-white">Chia sẻ PDF</button></div>
                                        @elseif(!in_array(($share['pdf_status'] ?? null),['queued','processing'],true))
                                            <form method="POST" action="{{ route('client.pharma.price-lists.share.pdf.queue',$share['share_id']) }}">@csrf<button class="rounded-xl bg-violet-700 px-4 py-3 text-sm font-black text-white">Chuyển sang PDF</button></form>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-4 flex justify-end"><form method="POST" action="{{ route('client.pharma.price-lists.share.revoke',$share['share_id']) }}">@csrf @method('DELETE')<button class="text-xs font-bold text-red-600">Thu hồi link Excel / PDF</button></form></div>
                            </div>
                        @endif
                    </div>
                </details>

                @if(($isManager && !$canApprove) || $canApprove)
                    <details class="group min-w-[220px] rounded-2xl border border-rose-200 bg-rose-50">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-black text-rose-800"><span>Ngừng kích hoạt</span><span class="text-lg transition group-open:rotate-180">⌄</span></summary>
                        <div class="border-t border-rose-200 p-4">
                            @if($isManager && !$canApprove)
                                <p class="text-sm text-rose-700">Không còn bán hàng cho khách? Gửi yêu cầu để người phê duyệt xử lý.</p>
                                <form method="POST" action="{{ route('client.pharma.price-lists.deactivation.request',$priceList->id) }}" class="mt-3 flex flex-col gap-2">@csrf<input name="deactivation_reason" required maxlength="1000" class="h-11 min-w-72 rounded-2xl border border-rose-300 bg-white px-4 text-sm" placeholder="Lý do ngừng kích hoạt..."><button class="rounded-2xl bg-rose-700 px-5 py-3 text-sm font-black text-white">Yêu cầu ngừng kích hoạt</button></form>
                            @else
                                <p class="text-sm text-rose-700">Người phê duyệt có thể ngừng kích hoạt trực tiếp; bắt buộc ghi lý do.</p>
                                <form method="POST" action="{{ route('client.pharma.price-lists.deactivate',$priceList->id) }}" class="mt-3 flex flex-col gap-2" onsubmit="return confirm('Ngừng kích hoạt bảng giá này?')">@csrf<input name="deactivation_reason" required maxlength="1000" class="h-11 min-w-72 rounded-2xl border border-rose-300 bg-white px-4 text-sm" placeholder="Lý do ngừng kích hoạt..."><button class="rounded-2xl bg-rose-700 px-5 py-3 text-sm font-black text-white">Ngừng kích hoạt</button></form>
                            @endif
                        </div>
                    </details>
                @endif
            </div>
        </section>
        <script>
            function copyShareUrl(id){const e=document.getElementById(id);if(!e)return;navigator.clipboard?navigator.clipboard.writeText(e.value):(e.type!=='hidden'&&e.select())}
            function shareExportUrl(id){const e=document.getElementById(id);if(!e)return;if(navigator.share){navigator.share({title:'Bảng giá',url:e.value})}else{copyShareUrl(id)}}
            (()=>{const box=document.querySelector('[data-export-share]');if(!box)return;const status=document.getElementById('price-list-pdf-status');if(!status||!status.textContent.includes('Đang chuyển PDF'))return;const poll=()=>fetch(box.dataset.statusUrl,{headers:{'Accept':'application/json'}}).then(r=>r.ok?r.json():Promise.reject()).then(data=>{if(data.pdf_status==='completed'){window.location.reload();return}if(data.pdf_status==='failed'){window.location.reload();return}setTimeout(poll,2500)}).catch(()=>setTimeout(poll,5000));setTimeout(poll,1500)})()
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
