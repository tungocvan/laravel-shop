@extends('ClientPortal::layouts.application')
@section('title', 'Phân bổ số lượng')
@section('content')
<div class="mx-auto w-full max-w-6xl space-y-4 px-3 py-4 sm:px-5 lg:px-6">
    <a href="{{ route('client.pharma.bid-awards.show', $scope) }}" class="inline-flex min-h-11 items-center text-sm font-bold text-slate-600">← Quay lại kết quả trúng thầu</a>
    <section class="rounded-[28px] bg-slate-950 p-5 text-white sm:p-6">
        <p class="text-[11px] font-black uppercase tracking-[.18em] text-slate-300">Allocation Wizard</p>
        <h1 class="mt-1 text-2xl font-black">Phân bổ số lượng</h1>
        <p class="mt-2 text-sm text-slate-300">{{ $award->investor_name }} · {{ $award->bidding_notice_code ?: $award->decision_number }}</p>
    </section>
    @if(session('success'))<div class="rounded-2xl bg-emerald-50 p-4 text-sm font-bold text-emerald-700">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rounded-2xl bg-rose-50 p-4 text-sm text-rose-700">{{ $errors->first() }}</div>@endif

    <div class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-2xl border p-4 {{ count($selectedHospitalIds) ? 'border-emerald-200 bg-emerald-50' : 'border-slate-300 bg-white' }}"><strong>Bước 1 · Chọn bệnh viện</strong><p class="mt-1 text-xs text-slate-500">{{ count($selectedHospitalIds) }} bệnh viện đã lưu</p></div>
        <div class="rounded-2xl border p-4 {{ count($selectedHospitalIds) ? 'border-slate-300 bg-white' : 'border-slate-200 bg-slate-50 opacity-60' }}"><strong>Bước 2 · Phân bổ sản phẩm</strong><p class="mt-1 text-xs text-slate-500">Chỉ mở sau khi hoàn tất Bước 1</p></div>
    </div>

    <form method="POST" action="{{ route('client.pharma.bid-awards.allocation.hospitals', $scope) }}" class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        @csrf
        <div class="flex items-center justify-between gap-3"><div><h2 class="font-black">Bước 1 · Bệnh viện được phân bổ</h2><p class="text-xs text-slate-500">Chọn trong phạm vi bệnh viện đã được Admin thiết lập.</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold">{{ count($selectedHospitalIds) }} đã chọn</span></div>
        <input type="search" data-hospital-search placeholder="Tìm bệnh viện..." class="mt-4 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm">
        <div class="mt-3 grid max-h-[46vh] gap-2 overflow-y-auto md:grid-cols-2">
            @forelse($hospitals as $hospital)
                <label data-hospital-card data-name="{{ IlluminateSupportStr::lower($hospital->name) }}" class="flex min-h-14 cursor-pointer items-center gap-3 rounded-2xl border border-slate-200 p-3 active:scale-[.985] motion-reduce:transform-none">
                    <input type="checkbox" name="hospital_ids[]" value="{{ $hospital->id }}" @checked(in_array((int)$hospital->id,$selectedHospitalIds,true)) class="h-5 w-5 rounded">
                    <span class="min-w-0"><strong class="block truncate text-sm">{{ $hospital->name }}</strong><span class="text-xs text-slate-500">{{ $hospital->province_code }}</span></span>
                </label>
            @empty <p class="text-sm text-slate-500">Chưa có bệnh viện trong phạm vi phân bổ Admin.</p>@endforelse
        </div>
        <div class="sticky bottom-3 mt-4 flex justify-end"><button class="min-h-12 rounded-2xl bg-slate-950 px-5 text-sm font-black text-white shadow-lg">Lưu Bước 1</button></div>
    </form>

    @if(count($selectedHospitalIds))
    <form method="POST" action="{{ route('client.pharma.bid-awards.allocation.store', $scope) }}" class="rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        @csrf
        <h2 class="font-black">Bước 2 · Phân bổ sản phẩm</h2><p class="text-xs text-slate-500">Tablet/mobile dùng card theo sản phẩm; nhập số lượng cho từng BV đã chọn.</p>
        <div class="mt-4 space-y-3">
        @foreach($products as $product)
            <article class="rounded-2xl border border-slate-200 p-4">
                <div class="flex items-start justify-between gap-3"><div><h3 class="font-black">{{ $product->medicine_name }}</h3><p class="text-xs text-slate-500">{{ $product->active_ingredient }} {{ $product->concentration }}</p></div><span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold">SL trúng {{ number_format((float)$product->quantity,0,',','.') }}</span></div>
                <div class="mt-3 grid gap-2 md:grid-cols-2">
                @foreach($hospitals->whereIn('id',$selectedHospitalIds) as $hospital)
                    @php($existing=$existingAllocations->get($product->id.':'.$hospital->id))
                    <label class="rounded-xl bg-slate-50 p-3"><span class="block truncate text-xs font-bold text-slate-600">{{ $hospital->name }}</span><input inputmode="decimal" name="allocations[{{ $product->id }}][{{ $hospital->id }}]" value="{{ old('allocations.'.$product->id.'.'.$hospital->id, $existing?->allocated_quantity) }}" placeholder="Số lượng" class="mt-2 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm"></label>
                @endforeach
                </div>
            </article>
        @endforeach
        </div>
        <div class="sticky bottom-3 mt-4 flex justify-end"><button class="min-h-12 rounded-2xl bg-slate-950 px-5 text-sm font-black text-white shadow-lg">Lưu phân bổ</button></div>
    </form>
    @endif
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{const q=document.querySelector('[data-hospital-search]');q?.addEventListener('input',()=>{const v=q.value.toLocaleLowerCase('vi');document.querySelectorAll('[data-hospital-card]').forEach(el=>el.classList.toggle('hidden',!el.dataset.name.includes(v)));});});
</script>
@endsection
