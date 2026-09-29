@extends('ClientPortal::layouts.application')

@section('title', $featurePresentation['page_title'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Chi tiết kết quả trúng thầu')
@section('app-dashboard-route', route('client.pharma.dashboard'))

@section('content')
<div class="min-w-0 space-y-5 overflow-x-hidden">
    <a href="{{ route('client.pharma.bid-awards') }}" class="inline-flex min-h-11 items-center text-sm font-bold text-slate-600">← Quay lại Trúng thầu</a>
    <section class="rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-300">{{ $featurePresentation['eyebrow'] }}</p>
        <h1 class="mt-2 text-2xl font-black sm:text-3xl">{{ $result->investor_name ?: $featurePresentation['page_title'] }}</h1>
        <div class="mt-4 flex flex-wrap gap-2 text-xs font-bold">
            @if($result->bidding_notice_code)<span class="rounded-full bg-white/10 px-3 py-2">TBMT {{ $result->bidding_notice_code }}</span>@endif
            @if($result->decision_number)<span class="rounded-full bg-white/10 px-3 py-2">QĐ {{ $result->decision_number }}</span>@endif
            <span class="rounded-full bg-white/10 px-3 py-2">{{ $result->products_count }} sản phẩm trúng thầu</span>
        </div>
    </section>

    <form method="GET" class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm" id="bid-product-search-form">
        <div class="relative">
            <input name="q" value="{{ $search }}" placeholder="Tìm tên thuốc, hoạt chất, số đăng ký..." autocomplete="off"
                class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 pr-12 text-sm outline-none focus:border-slate-400">
            @if($search !== '')<a href="{{ route('client.pharma.bid-awards.show', $result->scope_key) }}" class="absolute right-2 top-2 flex h-8 w-8 items-center justify-center rounded-full text-slate-500" aria-label="Xóa tìm kiếm">×</a>@endif
        </div>
    </form>

    <section id="bid-products" class="space-y-3">
        @forelse($products as $product)
            <article data-bid-product class="min-w-0 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0"><h2 class="font-black text-slate-950">{{ $product->medicine_name }}</h2><p class="mt-1 text-sm text-slate-500">{{ $product->active_ingredient ?: '—' }} @if($product->concentration) · {{ $product->concentration }} @endif</p></div>
                    @if((int) $product->my_hospitals_count > 0)
                        <span class="shrink-0 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">{{ $product->my_hospitals_count }} BV của tôi</span>
                    @else
                        <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-500">KQLCNT</span>
                    @endif
                </div>
                <p class="mt-3 text-sm text-slate-500">{{ $product->packaging_specification ?: 'Chưa có quy cách' }}</p>
                <div class="mt-4 grid grid-cols-3 gap-2">
                    <div class="rounded-2xl bg-slate-50 p-3"><span class="block text-[11px] font-bold uppercase text-slate-400">Giá trúng thầu</span><strong class="mt-1 block text-sm text-slate-950">{{ number_format((float) ($product->winning_price ?? $product->unit_price ?? 0), 0, ',', '.') }}</strong></div>
                    <div class="rounded-2xl bg-slate-50 p-3"><span class="block text-[11px] font-bold uppercase text-slate-400">SL trúng thầu</span><strong class="mt-1 block text-sm text-slate-950">{{ number_format((float) $product->winning_quantity, 0, ',', '.') }}</strong></div>
                    <div class="rounded-2xl bg-slate-50 p-3"><span class="block text-[11px] font-bold uppercase text-slate-400">SL của tôi</span><strong class="mt-1 block text-sm text-slate-950">{{ number_format((float) $product->my_allocated_quantity, 0, ',', '.') }}</strong></div>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">Không tìm thấy sản phẩm phù hợp.</div>
        @endforelse
    </section>
    @if($products->hasMorePages())<div class="text-center"><a id="bid-product-load-more" href="{{ $products->nextPageUrl() }}" class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 text-sm font-bold text-slate-700 shadow-sm">Xem thêm sản phẩm</a></div>@endif
</div>
<script>
(() => {
 const form=document.getElementById('bid-product-search-form'), input=form?.querySelector('input[name="q"]'); let timer;
 input?.addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(()=>form.requestSubmit(),350);});
 const more=document.getElementById('bid-product-load-more');
 more?.addEventListener('click',async(e)=>{e.preventDefault();more.classList.add('pointer-events-none','opacity-60');try{const r=await fetch(more.href,{headers:{'X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});const d=new DOMParser().parseFromString(await r.text(),'text/html');d.querySelectorAll('[data-bid-product]').forEach(x=>document.getElementById('bid-products').append(x));const n=d.getElementById('bid-product-load-more');if(n)more.href=n.href;else more.remove();}catch(error){window.location.href=more.href;}finally{more?.classList.remove('pointer-events-none','opacity-60');}});
})();
</script>
@endsection
