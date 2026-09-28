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
            <div class="flex flex-wrap gap-2">@if($canEdit)<a href="{{ route('client.pharma.price-lists.edit', $priceList->id) }}" class="rounded-2xl border border-amber-300 bg-white px-5 py-3 text-sm font-black text-amber-800">Sửa Nháp</a><form method="POST" action="{{ route('client.pharma.price-lists.delete', $priceList->id) }}" onsubmit="return confirm('Xóa bảng giá Nháp này?')">@csrf @method('DELETE')<button class="rounded-2xl border border-red-200 bg-white px-5 py-3 text-sm font-black text-red-600">Xóa</button></form>@endif @if($canSubmit)<form method="POST" action="{{ route('client.pharma.price-lists.submit', $priceList->id) }}">@csrf<button class="rounded-2xl border border-amber-300 bg-white px-5 py-3 text-sm font-black text-amber-800">Gửi duyệt</button></form>@endif @if($canApprove)<form method="POST" action="{{ route('client.pharma.price-lists.activate-own-draft',$priceList->id) }}" onsubmit="return confirm('Kích hoạt trực tiếp bảng giá này?')">@csrf<button class="rounded-2xl bg-emerald-700 px-5 py-3 text-sm font-black text-white">Kích hoạt ngay</button></form>@endif</div>
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
        <section class="min-w-0 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <details id="price-list-documents" class="group min-w-0 lg:open">
                <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 sm:px-5">
                    <div class="min-w-0"><p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Tài liệu bảng giá</p><p class="mt-1 truncate text-sm font-bold text-slate-700">Tài liệu đã xuất · {{ count($exportHistory) }}@if($share) · {{ !empty($share['pdf_url']) ? 'Excel + PDF' : 'Excel' }}@endif</p></div>
                    <span class="shrink-0 text-sm font-black text-slate-400 transition group-open:rotate-180">⌄</span>
                </summary>
                <div class="min-w-0 border-t border-slate-100 p-4 sm:p-5">
                    <div class="flex min-w-0 flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0"><p class="text-sm text-slate-600">Xuất nhiều mẫu độc lập; mỗi bản Excel có thể tạo hoặc tạo lại PDF riêng.</p></div>
                        <div class="flex min-w-0 flex-wrap gap-2">
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
                    <details class="group min-w-0 basis-full w-full lg:basis-auto lg:flex-1">
                        <summary class="cursor-pointer list-none rounded-2xl border border-slate-300 px-5 py-3 text-sm font-black text-slate-700">Tài liệu đã xuất · {{ count($exportHistory) }}</summary>
                        <div class="mt-3 min-w-0 space-y-3 lg:min-w-[720px]">
                            @forelse($exportHistory as $export)
                                @php
                                    $exportId = (int) $export['share_id'];
                                    $profileLabel = $export['export_profile_id'] ? ($profileNames[$export['export_profile_id']] ?? 'Mẫu #'.$export['export_profile_id']) : 'Mặc định hệ thống';
                                    $isProcessing = in_array($export['pdf_status'] ?? null, ['queued','processing'], true);
                                @endphp
                                <article class="min-w-0 overflow-hidden rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" @if($share && $exportId === (int)$share['share_id']) data-export-share data-status-url="{{ route('client.pharma.price-lists.share.status',$exportId) }}" @endif>
                                    <div class="flex flex-col gap-4">
                                        <div class="min-w-0"><p class="font-black text-slate-900">{{ $profileLabel }}</p><p class="mt-1 break-all text-xs leading-5 text-slate-500">{{ $export['created_at'] }} · {{ $export['download_name'] }}</p><div class="mt-2 flex gap-2 text-xs font-bold"><span class="rounded-full bg-emerald-100 px-2.5 py-1 text-emerald-700">Excel ✓</span>@if($export['pdf_available'])<span id="{{ $share && $exportId === (int)$share['share_id'] ? 'price-list-pdf-status' : '' }}" class="rounded-full bg-violet-100 px-2.5 py-1 text-violet-700">PDF ✓</span>@elseif($isProcessing)<span id="{{ $share && $exportId === (int)$share['share_id'] ? 'price-list-pdf-status' : '' }}" class="rounded-full bg-violet-100 px-2.5 py-1 text-violet-700">Đang tạo PDF...</span>@elseif(($export['pdf_status'] ?? null)==='failed')<span class="rounded-full bg-red-100 px-2.5 py-1 text-red-700">PDF lỗi</span>@else<span class="rounded-full bg-slate-200 px-2.5 py-1 text-slate-600">Chưa có PDF</span>@endif</div></div>
                                        <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
                                            @if(!$export['revoked'])<a href="{{ $export['url'] }}" data-pwa-file-handoff data-file-name="{{ $export['download_name'] }}" class="flex min-h-11 items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-black text-emerald-800">Tải Excel</a>@endif
                                            @if($export['pdf_available'] && !$export['revoked'])<a href="{{ $export['pdf_url'] }}" data-pwa-file-handoff data-file-name="{{ preg_replace('/\.xlsx$/i','.pdf',$export['download_name']) }}" class="flex min-h-11 items-center justify-center rounded-2xl border border-violet-200 bg-violet-50 px-4 py-2.5 text-sm font-black text-violet-800">Tải PDF</a>@endif
                                            @if(!$isProcessing && !$export['revoked'])
                                                <form method="POST" action="{{ $export['pdf_available'] ? route('client.pharma.price-lists.share.pdf.regenerate',$exportId) : route('client.pharma.price-lists.share.pdf.queue',$exportId) }}">@csrf<button class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-bold">{{ $export['pdf_available'] ? 'Tạo lại PDF' : 'Tạo PDF' }}</button></form>
                                            @endif
                                            @if(!$export['revoked'])
                                                <div class="col-span-2 flex min-w-0 items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-2 sm:col-span-full sm:w-full"><span class="shrink-0">🔗</span><span class="min-w-0 flex-1 truncate text-xs font-semibold text-slate-600">{{ $export['pdf_available'] ? $export['pdf_url'] : $export['url'] }}</span><input id="export-url-{{ $exportId }}" type="hidden" value="{{ $export['pdf_available'] ? $export['pdf_url'] : $export['url'] }}"><button type="button" onclick="copyExportUrl('export-url-{{ $exportId }}',this)" class="flex h-11 shrink-0 items-center rounded-xl border border-slate-200 bg-white px-3 text-xs font-black text-slate-700" aria-label="Sao chép URL">⧉ <span class="ml-1 hidden sm:inline" data-copy-label>Sao chép</span></button><button type="button" onclick="shareExportUrl('export-url-{{ $exportId }}')" class="h-11 shrink-0 rounded-xl border border-slate-200 bg-white px-3 text-xs font-black text-slate-700">Chia sẻ</button></div>
                                                <button type="button" onclick="document.getElementById('email-export-{{ $exportId }}').showModal()" class="col-span-2 min-h-11 rounded-2xl bg-sky-700 px-4 py-2.5 text-sm font-black text-white sm:col-span-1">✉ Gửi email</button>
                                                <dialog id="email-export-{{ $exportId }}" class="mb-0 mt-auto w-full max-w-[620px] rounded-t-[28px] border-0 p-0 shadow-2xl backdrop:bg-slate-950/55 sm:m-auto sm:w-[min(92vw,620px)] sm:rounded-[28px]">
                                                    <form method="POST" action="{{ route('client.pharma.price-lists.share.email',$exportId) }}" class="flex max-h-[92dvh] flex-col">@csrf
                                                        <div class="space-y-4 overflow-y-auto p-5 pb-3 sm:p-6 sm:pb-3">
                                                        <div class="flex items-start justify-between gap-4"><div><p class="text-lg font-black text-slate-950">Gửi bảng giá qua email</p><p class="mt-1 text-xs text-slate-500">{{ $profileLabel }} · {{ $export['download_name'] }}</p></div><button type="button" onclick="this.closest('dialog').close()" class="rounded-xl px-3 py-2 text-sm font-black text-slate-500">✕</button></div>
                                                        <label class="block"><span class="mb-1.5 block text-xs font-bold text-slate-600">Email người nhận *</span><input name="recipients" required maxlength="1000" class="h-11 w-full rounded-2xl border border-slate-300 px-4 text-sm" placeholder="email@congty.vn; email2@congty.vn"></label>
                                                        <label class="block"><span class="mb-1.5 block text-xs font-bold text-slate-600">Tiêu đề *</span><input name="subject" required maxlength="255" value="Bảng giá - {{ $priceList->name }}" class="h-11 w-full rounded-2xl border border-slate-300 px-4 text-sm"></label>
                                                        <label class="block"><span class="mb-1.5 block text-xs font-bold text-slate-600">Nội dung *</span><textarea name="message" required maxlength="10000" rows="6" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" placeholder="Nhập nội dung email...">Kính gửi Quý khách,

Xin gửi kèm bảng giá {{ $priceList->name }}.

Trân trọng.</textarea></label>
                                                        <fieldset><legend class="text-xs font-bold text-slate-600">Tệp đính kèm *</legend><div class="mt-2 flex flex-wrap gap-4 text-sm"><label class="flex items-center gap-2 font-bold text-emerald-800"><input type="checkbox" name="attach_excel" value="1" checked> Excel</label><label class="flex items-center gap-2 font-bold {{ $export['pdf_available'] ? 'text-violet-800' : 'text-slate-400' }}"><input type="checkbox" name="attach_pdf" value="1" @disabled(!$export['pdf_available'])> PDF @if(!$export['pdf_available'])<span class="text-xs font-normal">(chưa sẵn sàng)</span>@endif</label></div></fieldset>
                                                        </div>
                                                        <div class="flex shrink-0 gap-2 border-t border-slate-100 bg-white p-4 sm:justify-end"><button type="button" onclick="this.closest('dialog').close()" class="min-h-11 flex-1 rounded-2xl border border-slate-300 px-5 py-3 text-sm font-black text-slate-700 sm:flex-none">Hủy</button><button class="min-h-11 flex-1 rounded-2xl bg-sky-700 px-5 py-3 text-sm font-black text-white sm:flex-none">Gửi email →</button></div>
                                                    </form>
                                                </dialog>
                                            @endif
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
                </div>
            </details>
        </section>
        <dialog id="pwa-file-handoff" class="mb-0 mt-auto w-full max-w-[560px] rounded-t-[28px] border-0 p-0 shadow-2xl backdrop:bg-slate-950/55 sm:m-auto sm:w-[min(92vw,560px)] sm:rounded-[28px]">
            <div class="p-5 sm:p-6">
                <p class="text-lg font-black text-slate-950" data-file-title>Chuẩn bị tệp</p>
                <p class="mt-2 text-sm leading-6 text-slate-600" data-file-message>Đang chuẩn bị tệp trong PWA. Màn hình hiện tại sẽ được giữ nguyên.</p>
                <div class="mt-5 flex gap-2">
                    <button type="button" data-file-cancel class="min-h-11 flex-1 rounded-2xl border border-slate-300 px-4 py-3 text-sm font-black text-slate-700">Đóng</button>
                    <button type="button" data-file-share disabled class="min-h-11 flex-1 rounded-2xl bg-slate-950 px-4 py-3 text-sm font-black text-white disabled:cursor-not-allowed disabled:opacity-40">Mở / chia sẻ tệp</button>
                </div>
            </div>
        </dialog>
        <script>
            const isInstalledPwa=()=>window.matchMedia('(display-mode: standalone)').matches||window.navigator.standalone===true;
            let preparedPwaFile=null;
            const canNativeSharePreparedFile=()=>{if(!preparedPwaFile||!navigator.share)return false;const payload={files:[preparedPwaFile]};return !navigator.canShare||navigator.canShare(payload)};
            function downloadPreparedPwaFile(){
                if(!preparedPwaFile)return;
                const objectUrl=URL.createObjectURL(preparedPwaFile),download=document.createElement('a');
                download.href=objectUrl;download.download=preparedPwaFile.name;download.hidden=true;document.body.appendChild(download);download.click();download.remove();
                setTimeout(()=>URL.revokeObjectURL(objectUrl),30000);
            }
            async function preparePwaFile(event,anchor){
                if(!isInstalledPwa())return;
                event.preventDefault();
                const dialog=document.getElementById('pwa-file-handoff'),title=dialog.querySelector('[data-file-title]'),message=dialog.querySelector('[data-file-message]'),share=dialog.querySelector('[data-file-share]');
                preparedPwaFile=null;share.disabled=true;share.textContent='Mở / chia sẻ tệp';title.textContent='Chuẩn bị tệp';message.textContent='Đang chuẩn bị tệp trong PWA. Màn hình hiện tại sẽ được giữ nguyên.';dialog.showModal();
                try{
                    const response=await fetch(anchor.href,{credentials:'same-origin',cache:'no-store',headers:{'X-PWA-File-Handoff':'1'}});
                    if(!response.ok)throw new Error('download failed');
                    const blob=await response.blob(),name=anchor.dataset.fileName||'tai-lieu';
                    preparedPwaFile=new File([blob],name,{type:blob.type||'application/octet-stream'});
                    title.textContent='Tệp đã sẵn sàng';share.disabled=false;
                    if(canNativeSharePreparedFile()){
                        message.textContent='Chọn “Mở / chia sẻ tệp” để bàn giao sang Files, Excel, PDF hoặc ứng dụng phù hợp. Nếu thiết bị không mở được bảng chia sẻ, tệp sẽ được tải xuống mà không thay thế màn hình PWA.';
                    }else{
                        share.textContent='Lưu tệp';message.textContent='Thiết bị không hỗ trợ chia sẻ tệp trực tiếp. Chọn “Lưu tệp” để tải tệp xuống mà không thay thế màn hình PWA.';
                    }
                }catch(_){title.textContent='Không thể chuẩn bị tệp';message.textContent='Không tải được tệp trong phiên hiện tại. PWA vẫn giữ nguyên màn hình để bạn có thể thử lại.'}
            }
            document.querySelectorAll('[data-pwa-file-handoff]').forEach(anchor=>anchor.addEventListener('click',event=>preparePwaFile(event,anchor)));
            document.querySelector('[data-file-cancel]')?.addEventListener('click',()=>document.getElementById('pwa-file-handoff')?.close());
            document.querySelector('[data-file-share]')?.addEventListener('click',async()=>{
                if(!preparedPwaFile)return;
                if(canNativeSharePreparedFile()){
                    try{await navigator.share({files:[preparedPwaFile],title:'Bảng giá'});document.getElementById('pwa-file-handoff')?.close();return}
                    catch(error){if(error?.name==='AbortError')return}
                }
                downloadPreparedPwaFile();
                const message=document.querySelector('[data-file-message]');if(message)message.textContent='Đã chuyển tệp sang trình tải xuống. Màn hình PWA vẫn được giữ nguyên.';
                const button=document.querySelector('[data-file-share]');if(button)button.textContent='Lưu lại tệp';
            });
            function copyShareUrl(id){const e=document.getElementById(id);if(!e)return;navigator.clipboard?navigator.clipboard.writeText(e.value):(e.type!=='hidden'&&e.select())}
            async function copyExportUrl(id,button){const e=document.getElementById(id);if(!e)return;try{if(navigator.clipboard&&window.isSecureContext){await navigator.clipboard.writeText(e.value)}else{copyShareUrl(id)}const l=button?.querySelector('[data-copy-label]');const p=l?.textContent;if(l)l.textContent='Đã sao chép';button?.classList.add('text-emerald-700');setTimeout(()=>{if(l)l.textContent=p||'Sao chép';button?.classList.remove('text-emerald-700')},1600)}catch(_){copyShareUrl(id)}}
            function shareExportUrl(id){const e=document.getElementById(id);if(!e)return;if(navigator.share){navigator.share({title:'Bảng giá',url:e.value})}else{copyShareUrl(id)}}
            (()=>{const box=document.querySelector('[data-export-share]');if(!box)return;const status=document.getElementById('price-list-pdf-status');if(!status||!status.textContent.includes('Đang tạo PDF'))return;const poll=()=>fetch(box.dataset.statusUrl,{headers:{'Accept':'application/json'}}).then(r=>r.ok?r.json():Promise.reject()).then(data=>{if(data.pdf_status==='completed'||data.pdf_status==='failed'){const key='pharma-pdf-status-refreshed-'+data.share_id;if(!sessionStorage.getItem(key)){sessionStorage.setItem(key,'1');window.location.reload()}return}setTimeout(poll,2500)}).catch(()=>setTimeout(poll,5000));setTimeout(poll,1500)})()
        </script>
    @elseif($priceList->status === \Modules\Pharma\Models\PriceList::STATUS_PENDING_DEACTIVATION)
        <section class="rounded-3xl border border-violet-200 bg-violet-50 p-5"><p class="font-black text-violet-950">Đang chờ duyệt ngừng kích hoạt</p><p class="mt-1 text-sm text-violet-800">{{ $priceList->deactivation_reason }}</p><p class="mt-2 text-xs font-bold text-violet-600">{{ $priceList->deactivation_requested_at ? 'Yêu cầu lúc '.$priceList->deactivation_requested_at->format('H:i d/m/Y') : '' }}</p>@if($canApprove)<form method="POST" action="{{ route('client.pharma.price-lists.deactivation.approve',$priceList->id) }}" class="mt-4" onsubmit="return confirm('Chấp nhận ngừng kích hoạt bảng giá này?')">@csrf<button class="rounded-2xl bg-violet-700 px-5 py-3 text-sm font-black text-white">Chấp nhận ngừng kích hoạt</button></form>@endif</section>
    @elseif($priceList->status === \Modules\Pharma\Models\PriceList::STATUS_INACTIVE && $priceList->deactivation_reason)
        <section class="rounded-3xl border border-slate-200 bg-slate-50 p-5"><p class="font-black text-slate-900">Bảng giá đã ngừng kích hoạt</p><p class="mt-1 text-sm text-slate-600">{{ $priceList->deactivation_reason }}</p><p class="mt-2 text-xs font-bold text-slate-500">{{ $priceList->deactivated_at ? 'Ngừng lúc '.$priceList->deactivated_at->format('H:i d/m/Y') : '' }}</p></section>
    @endif

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid grid-cols-2 gap-x-4 gap-y-5 lg:grid-cols-4">
            @foreach([['Mã bảng giá',$priceList->code],['Số sản phẩm',$priceList->items_count],['Hiệu lực từ',$priceList->effective_from?->format('d/m/Y')],['Hiệu lực đến',$priceList->effective_to?->format('d/m/Y')]] as [$label,$value])
                <div><p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $label }}</p><p class="mt-1.5 font-bold text-slate-800">{{ $value ?: '—' }}</p></div>
            @endforeach
        </div>
        @if($priceList->notes)<p class="mt-5 rounded-2xl bg-slate-50 p-4 text-sm leading-6 text-slate-600">{{ $priceList->notes }}</p>@endif
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-4 sm:px-5">
            <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">Sản phẩm</p>
            <div class="mt-1 flex items-baseline justify-between gap-3"><h2 class="text-lg font-black text-slate-950">{{ $priceList->items_count }} sản phẩm trong bảng giá</h2><span id="price-list-product-count" class="shrink-0 text-xs font-bold text-slate-400"></span></div>
            @if($priceList->items->isNotEmpty())
                <label class="relative mt-3 block"><span class="sr-only">Tìm sản phẩm trong bảng giá</span><span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">⌕</span><input id="price-list-product-search" type="search" autocomplete="off" class="h-12 w-full rounded-2xl border border-slate-300 bg-white pl-10 pr-4 text-sm outline-none focus:border-slate-500" placeholder="Tìm tên thuốc, mã thuốc, quy cách..."></label>
            @endif
        </div>
        <div id="price-list-products" class="divide-y divide-slate-100">
            @forelse($priceList->items as $item)
                @php $productSearchText = collect([$item->variant?->medicine?->name, $item->variant?->sku, $item->package?->name, $item->package?->packaging_specification])->filter()->implode(' '); @endphp
                <article class="px-4 py-5 sm:px-5" data-price-list-product data-search="{{ $productSearchText }}">
                    <div><p class="font-black leading-6 text-slate-900">{{ $item->variant?->medicine?->name ?? $item->variant?->sku ?? 'SKU #'.$item->medicine_variant_id }}</p><p class="mt-1 break-words text-xs leading-5 text-slate-400">{{ $item->variant?->sku }}@if($item->package) · {{ $item->package->name ?? $item->package->packaging_specification ?? '' }}@endif</p></div>
                    <div class="mt-4 grid grid-cols-2 gap-x-4 gap-y-4 lg:grid-cols-3">
                        <div><p class="text-xs font-bold text-slate-400">Giá kê khai</p><p class="mt-1 font-bold tabular-nums">{{ $item->declared_price_snapshot !== null ? number_format((float)$item->declared_price_snapshot,0,',','.') : '—' }}</p></div>
                        <div><p class="text-xs font-bold text-slate-400">Giá bán CT</p><p class="mt-1 font-black tabular-nums text-slate-950">{{ $item->company_sale_price !== null ? number_format((float)$item->company_sale_price,0,',','.') : '—' }}</p></div>
                        <div class="col-span-2 lg:col-span-1"><p class="text-xs font-bold text-slate-400">Giá thu / Giá HĐ</p><p class="mt-1 text-sm tabular-nums text-slate-700">{{ $item->actual_receivable_price !== null ? number_format((float)$item->actual_receivable_price,0,',','.') : '—' }} / {{ $item->invoice_price !== null ? number_format((float)$item->invoice_price,0,',','.') : '—' }}</p></div>
                    </div>
                </article>
            @empty <p class="px-5 py-8 text-center text-sm text-slate-500">Bảng giá chưa có sản phẩm.</p> @endforelse
        </div>
        <p id="price-list-product-empty" hidden class="px-5 py-8 text-center text-sm font-semibold text-slate-500">Không tìm thấy sản phẩm phù hợp.</p>
    </section>
    <script>
        (()=>{const input=document.getElementById('price-list-product-search'),items=[...document.querySelectorAll('[data-price-list-product]')],count=document.getElementById('price-list-product-count'),empty=document.getElementById('price-list-product-empty'),documents=document.getElementById('price-list-documents');if(documents&&window.matchMedia('(min-width: 1024px)').matches)documents.open=true;if(!input||!items.length)return;const normalize=value=>(value||'').toLocaleLowerCase('vi').normalize('NFD').replace(/[\\u0300-\\u036f]/g,'');const filter=()=>{const query=normalize(input.value.trim());let visible=0;items.forEach(item=>{const matched=!query||normalize(item.dataset.search).includes(query);item.hidden=!matched;if(matched)visible++});if(count)count.textContent=query?visible+' / '+items.length:'';if(empty)empty.hidden=visible!==0};input.addEventListener('input',filter)})();
    </script>
</div>
@endsection
