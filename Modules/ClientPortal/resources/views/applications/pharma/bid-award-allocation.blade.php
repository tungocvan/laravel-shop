@extends('ClientPortal::layouts.application')
@section('title','Phân bổ số lượng')
@section('content')
@php($savedScope=$setup['scope'])
<div class="mx-auto w-full max-w-6xl space-y-4 px-3 py-4 sm:px-5 lg:px-6">
<a href="{{ route('client.pharma.bid-awards.show',$scope) }}" class="inline-flex min-h-11 items-center text-sm font-bold text-slate-600">← Quay lại kết quả trúng thầu</a>
<section class="rounded-[28px] bg-slate-950 p-5 text-white sm:p-6"><p class="text-[11px] font-black uppercase tracking-[.18em] text-slate-300">Allocation Wizard</p><h1 class="mt-1 text-2xl font-black">Phân bổ số lượng</h1><p class="mt-2 text-sm text-slate-300">{{ $award->investor_name }} · {{ $award->bidding_notice_code ?: $award->decision_number }}</p></section>
@if(session('success'))<div class="rounded-2xl bg-emerald-50 p-4 text-sm font-bold text-emerald-700">{{ session('success') }}</div>@endif
@if($errors->any())<div class="rounded-2xl bg-rose-50 p-4 text-sm text-rose-700">{{ $errors->first() }}</div>@endif

<section class="rounded-[24px] border border-indigo-200 bg-indigo-50/30 p-4 shadow-sm sm:p-5">
<div class="flex flex-wrap items-start justify-between gap-3"><div><p class="text-[11px] font-black uppercase tracking-[.14em] text-indigo-600">Thiết lập chung</p><h2 class="mt-1 text-lg font-black">Phạm vi & hiệu lực phân bổ</h2><p class="mt-1 text-xs text-slate-500">Thiết lập một lần cho toàn bộ sản phẩm thuộc TBMT. Lưu thiết lập này trước khi phân bổ sản phẩm.</p></div>@if($savedScope)<span class="rounded-full bg-white px-3 py-1.5 text-xs font-bold text-emerald-700">{{ count($setup['facility_ids']) }} cơ sở đã lưu</span>@endif</div>

<form method="POST" action="{{ route('client.pharma.bid-awards.allocation.setup',$scope) }}" class="mt-4 space-y-4" id="distribution-setup-form">@csrf
<div class="grid gap-3 lg:grid-cols-3">
    <div class="rounded-2xl border border-slate-200 bg-white p-4">
        <span class="text-[11px] font-black uppercase text-indigo-600">Bước 1 · Phạm vi</span><h3 class="mt-1 font-black">Tỉnh/Thành trúng thầu</h3>
        <input type="search" data-province-search placeholder="Tìm Tỉnh/Thành..." class="mt-3 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm">
        <div class="mt-2 max-h-56 space-y-1 overflow-y-auto">
        @foreach($provinceOptions as $province)<label data-province-card data-name="{{ str($province)->lower() }}" class="flex min-h-11 items-center gap-2 rounded-xl px-2 hover:bg-slate-50"><input type="checkbox" name="province_names[]" value="{{ $province }}" @checked(in_array($province,$draftProvinces,true)) class="h-5 w-5 rounded"><span class="text-sm">{{ $province }}</span></label>@endforeach
        </div>
        <div class="mt-3 grid grid-cols-2 gap-2"><label class="text-xs font-bold text-slate-600">Từ ngày<input type="date" name="effective_from" value="{{ old('effective_from',$savedScope?->effective_from?->format('Y-m-d') ?: $award->decision_date?->format('Y-m-d')) }}" class="mt-1 h-11 w-full rounded-xl border border-slate-200 px-2"></label><label class="text-xs font-bold text-slate-600">Đến ngày<input type="date" name="effective_until" value="{{ old('effective_until',$savedScope?->effective_until?->format('Y-m-d')) }}" class="mt-1 h-11 w-full rounded-xl border border-slate-200 px-2"></label></div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4">
        <span class="text-[11px] font-black uppercase text-indigo-600">Bước 2 · Cơ sở nhận phân bổ</span><h3 class="mt-1 font-black">Chọn cơ sở KCB</h3>
        <p class="mt-1 text-xs text-slate-500">Đổi Tỉnh/Thành rồi bấm cập nhật để tải đúng danh sách cơ sở.</p>
        <button type="submit" formmethod="GET" formaction="{{ route('client.pharma.bid-awards.allocation',$scope) }}" class="mt-2 min-h-10 rounded-xl border border-slate-200 px-3 text-xs font-bold">Cập nhật phạm vi chọn</button>
        <input type="search" data-facility-search placeholder="Tìm tên hoặc mã cơ sở..." class="mt-3 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm">
        <div class="mt-2 max-h-64 space-y-1 overflow-y-auto">
        @foreach($facilityOptions as $facility)<label data-facility-card data-name="{{ str($facility->facility_name.' '.$facility->external_id)->lower() }}" class="flex min-h-12 items-start gap-2 rounded-xl px-2 py-2 hover:bg-slate-50"><input type="checkbox" name="facility_ids[]" value="{{ $facility->id }}" @checked(in_array((int)$facility->id,$draftFacilityIds,true)) class="mt-0.5 h-5 w-5 rounded"><span class="min-w-0"><strong class="block truncate text-sm">{{ $facility->facility_name }}</strong><span class="text-xs text-slate-500">{{ $facility->external_id }} · {{ $facility->province_name }}</span></span></label>@endforeach
        </div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4">
        <span class="text-[11px] font-black uppercase text-indigo-600">Bước 3 · Kiểm tra trước khi lưu</span><div class="flex items-center justify-between"><h3 class="mt-1 font-black">Cơ sở KCB đã chọn</h3><span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700" data-review-count>{{ count($draftFacilityIds) }} cơ sở</span></div>
        <p class="mt-1 text-xs text-slate-500">Các cơ sở đã chọn được checkbox sẵn. Bỏ checkbox tại đây nếu muốn loại trước khi lưu.</p>
        <div class="mt-3 max-h-72 space-y-2 overflow-y-auto" data-review-list>
        @foreach($facilityOptions->whereIn('id',$draftFacilityIds) as $facility)
            <label data-review-item data-facility-id="{{ $facility->id }}" class="flex min-h-12 items-start gap-2 rounded-xl border border-slate-200 bg-slate-50 p-2.5"><input type="checkbox" checked data-review-checkbox value="{{ $facility->id }}" class="mt-0.5 h-5 w-5 rounded"><span class="min-w-0"><strong class="block truncate text-sm">{{ $facility->facility_name }}</strong><span class="text-xs text-slate-500">{{ $facility->external_id }} · {{ $facility->province_name }}</span></span></label>
        @endforeach
        </div>
        <button type="submit" class="mt-4 min-h-12 w-full rounded-2xl bg-indigo-600 px-5 text-sm font-black text-white">Lưu thiết lập phân bổ</button>
    </div>
</div>
</form>
</section>

@if($savedScope && $hospitals->isNotEmpty())
<section class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
<div><p class="text-[11px] font-black uppercase tracking-[.14em] text-slate-400">Phân bổ sản phẩm</p><h2 class="mt-1 text-lg font-black">Phân bổ số lượng cho bệnh viện</h2><p class="mt-1 text-xs text-slate-500">{{ $hospitals->count() }} bệnh viện từ Thiết lập chung · {{ $products->count() }} sản phẩm KQLCNT.</p></div>
<form method="POST" action="{{ route('client.pharma.bid-awards.allocation.store',$scope) }}" class="mt-4 space-y-3">@csrf
@foreach($products as $product)
<article class="rounded-2xl border border-slate-200 p-4"><div class="flex items-start justify-between gap-3"><div><h3 class="font-black">{{ $product->medicine_name }}</h3><p class="text-xs text-slate-500">{{ $product->active_ingredient }} {{ $product->concentration }}</p></div><span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold">SL trúng {{ number_format((float)$product->quantity,0,',','.') }}</span></div>
<div class="mt-3 grid gap-2 md:grid-cols-2">@foreach($hospitals as $hospital)@php($existing=$existingAllocations->get($product->id.':'.$hospital->id))<label class="rounded-xl bg-slate-50 p-3"><span class="block truncate text-xs font-bold text-slate-600">{{ $hospital->name }}</span><input inputmode="decimal" name="allocations[{{ $product->id }}][{{ $hospital->id }}]" value="{{ old('allocations.'.$product->id.'.'.$hospital->id,$existing?->allocated_quantity) }}" placeholder="Số lượng" class="mt-2 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm"></label>@endforeach</div></article>
@endforeach
<div class="sticky bottom-3 flex justify-end"><button class="min-h-12 rounded-2xl bg-slate-950 px-5 text-sm font-black text-white shadow-lg">Lưu phân bổ sản phẩm</button></div>
</form></section>
@else
<div class="rounded-2xl border border-dashed border-slate-300 bg-white p-5 text-sm text-slate-500">Hãy hoàn tất và lưu <strong>Thiết lập chung 3 bước</strong> trước khi phân bổ sản phẩm.</div>
@endif
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const filter=(input,selector)=>document.querySelector(input)?.addEventListener('input',e=>{const v=e.target.value.toLocaleLowerCase('vi');document.querySelectorAll(selector).forEach(el=>el.classList.toggle('hidden',!el.dataset.name.includes(v)));});filter('[data-province-search]','[data-province-card]');filter('[data-facility-search]','[data-facility-card]');
 const form=document.getElementById('distribution-setup-form'), review=document.querySelector('[data-review-list]'), count=document.querySelector('[data-review-count]');
 const sync=()=>{const selected=[...form.querySelectorAll('input[name="facility_ids[]"]:checked')];review.innerHTML='';selected.forEach(cb=>{const card=cb.closest('[data-facility-card]'), clone=card.cloneNode(true);clone.removeAttribute('data-facility-card');clone.removeAttribute('data-name');clone.setAttribute('data-review-item','');clone.querySelector('input').removeAttribute('name');clone.querySelector('input').setAttribute('data-review-checkbox','');clone.querySelector('input').checked=true;clone.className='flex min-h-12 items-start gap-2 rounded-xl border border-slate-200 bg-slate-50 p-2.5';review.append(clone);});count.textContent=selected.length+' cơ sở';};
 form.querySelectorAll('input[name="facility_ids[]"]').forEach(cb=>cb.addEventListener('change',sync));
 review.addEventListener('change',e=>{if(!e.target.matches('[data-review-checkbox]'))return;const source=form.querySelector('input[name="facility_ids[]"][value="'+e.target.value+'"]');if(source){source.checked=e.target.checked;sync();}});
});
</script>
@endsection
