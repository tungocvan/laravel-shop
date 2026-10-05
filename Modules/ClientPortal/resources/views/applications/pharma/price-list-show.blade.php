@extends('ClientPortal::layouts.application')

@section('title', 'Chi tiết bảng giá')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Bảng giá do bạn phụ trách')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
<div class="mx-auto max-w-6xl space-y-5">
    @if(session('success'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-bold text-red-700"><p class="font-black">Không thể hoàn tất thao tác</p><p class="mt-1">{{ $errors->first() }}</p></div>@endif
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
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="document.getElementById('pending-price-list-edit-dialog').showModal()" class="rounded-2xl border border-blue-300 bg-white px-5 py-3 text-sm font-black text-blue-800">Sửa thông tin</button>
                    <form method="POST" action="{{ route('client.pharma.price-list-approvals.approve',$priceList->id) }}" onsubmit="return confirm('Phê duyệt và kích hoạt bảng giá này?')">@csrf<button class="rounded-2xl bg-emerald-700 px-5 py-3 text-sm font-black text-white">Phê duyệt & kích hoạt</button></form>
                </div>
                <dialog id="pending-price-list-edit-dialog" class="m-auto w-[calc(100%-24px)] max-w-[520px] rounded-[28px] border-0 p-0 shadow-2xl backdrop:bg-slate-950/55">
                    <form method="POST" action="{{ route('client.pharma.price-list-approvals.header.update',$priceList->id) }}" class="flex max-h-[92dvh] flex-col">@csrf @method('PUT')
                        <div class="space-y-4 overflow-y-auto p-5 sm:p-6">
                            <div class="flex items-start justify-between gap-4"><div><p class="font-black text-slate-950">Sửa thông tin trước khi kích hoạt</p><p class="mt-1 text-xs leading-5 text-slate-500">Chỉ điều chỉnh tên và thời gian hiệu lực. Sản phẩm, giá và trạng thái chờ duyệt được giữ nguyên.</p></div><button type="button" onclick="this.closest('dialog').close()" class="min-h-11 rounded-xl px-3 text-sm font-black text-slate-500">✕</button></div>
                            <label class="block"><span class="mb-1.5 block text-xs font-bold text-slate-500">Tên bảng giá *</span><input name="name" required maxlength="255" value="{{ old('name',$priceList->name) }}" class="h-12 w-full rounded-2xl border border-slate-300 px-4 text-sm font-semibold outline-none focus:border-slate-950"></label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="block"><span class="mb-1.5 block text-xs font-bold text-slate-500">Hiệu lực từ *</span><input type="date" name="effective_from" required value="{{ old('effective_from',optional($priceList->effective_from)->format('Y-m-d')) }}" class="h-12 w-full rounded-2xl border border-slate-300 px-3 text-sm font-semibold outline-none focus:border-slate-950"></label>
                                <label class="block"><span class="mb-1.5 block text-xs font-bold text-slate-500">Đến *</span><input type="date" name="effective_to" required value="{{ old('effective_to',optional($priceList->effective_to)->format('Y-m-d')) }}" class="h-12 w-full rounded-2xl border border-slate-300 px-3 text-sm font-semibold outline-none focus:border-slate-950"></label>
                            </div>
                        </div>
                        <div class="flex gap-2 border-t border-slate-100 bg-white p-4"><button type="button" onclick="this.closest('dialog').close()" class="min-h-11 flex-1 rounded-2xl border border-slate-300 px-4 text-sm font-black text-slate-700">Hủy</button><button class="min-h-11 flex-1 rounded-2xl bg-slate-950 px-4 text-sm font-black text-white">Lưu thay đổi</button></div>
                    </form>
                </dialog>
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

    @if($priceList->status === \Modules\Pharma\Models\PriceList::STATUS_PENDING_DEACTIVATION)
        <section class="rounded-3xl border border-violet-200 bg-violet-50 p-5"><p class="font-black text-violet-950">Đang chờ duyệt ngừng kích hoạt</p><p class="mt-1 text-sm text-violet-800">{{ $priceList->deactivation_reason }}</p><p class="mt-2 text-xs font-bold text-violet-600">{{ $priceList->deactivation_requested_at ? 'Yêu cầu lúc '.$priceList->deactivation_requested_at->format('H:i d/m/Y') : '' }}</p>@if($canApprove)<form method="POST" action="{{ route('client.pharma.price-lists.deactivation.approve',$priceList->id) }}" class="mt-4" onsubmit="return confirm('Chấp nhận ngừng kích hoạt bảng giá này?')">@csrf<button class="rounded-2xl bg-violet-700 px-5 py-3 text-sm font-black text-white">Chấp nhận ngừng kích hoạt</button></form>@endif</section>
    @elseif($priceList->status === \Modules\Pharma\Models\PriceList::STATUS_INACTIVE && $priceList->deactivation_reason)
        <section class="rounded-3xl border border-slate-200 bg-slate-50 p-5"><p class="font-black text-slate-900">Bảng giá đã ngừng kích hoạt</p><p class="mt-1 text-sm text-slate-600">{{ $priceList->deactivation_reason }}</p><p class="mt-2 text-xs font-bold text-slate-500">{{ $priceList->deactivated_at ? 'Ngừng lúc '.$priceList->deactivated_at->format('H:i d/m/Y') : '' }}</p></section>
    @endif

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid grid-cols-2 gap-x-4 gap-y-5 lg:grid-cols-4">
            @foreach([['Người phụ trách',$priceList->manager?->name],['Số sản phẩm',$priceList->items_count],['Hiệu lực từ',$priceList->effective_from?->format('d/m/Y')],['Hiệu lực đến',$priceList->effective_to?->format('d/m/Y')]] as [$label,$value])
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
