@extends('ClientPortal::layouts.application')

@section('title', $featurePresentation['page_title'])
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Chi tiết kết quả trúng thầu')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
<div class="min-w-0 space-y-5 overflow-x-hidden">
    <header class="flex min-h-16 items-center gap-3 border-b border-slate-200 bg-white px-1 pb-4">
        <a href="{{ route('client.pharma.bid-awards') }}" aria-label="Quay lại Trúng thầu" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-lg font-black text-slate-700 shadow-sm">←</a>
        <div class="min-w-0">
            <h1 class="truncate text-lg font-black text-slate-950">Chi tiết kết quả trúng thầu</h1>
            <p class="truncate text-xs text-slate-500">{{ $featurePresentation['page_title'] }}</p>
        </div>
    </header>
    <section class="rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-300">{{ $featurePresentation['eyebrow'] }}</p>
        <h1 class="mt-2 text-2xl font-black sm:text-3xl">{{ $result->investor_name ?: $featurePresentation['page_title'] }}</h1>
        <div class="mt-4 flex flex-wrap gap-2 text-xs font-bold">
            @if($result->bidding_notice_code)<span class="rounded-full bg-white/10 px-3 py-2">TBMT {{ $result->bidding_notice_code }}</span>@endif
            @if($result->decision_number)<span class="rounded-full bg-white/10 px-3 py-2">QĐ {{ $result->decision_number }}</span>@endif
            <span class="rounded-full bg-white/10 px-3 py-2">{{ $result->products_count }} sản phẩm trúng thầu</span>
        </div>
    </section>

    @if($canAllocate || $canManageCommercialPolicy)
    <section class="grid gap-3 sm:grid-cols-2">
        @if($canAllocate)
        <a href="{{ route('client.pharma.bid-awards.allocation',$result->scope_key) }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm active:scale-[.985] motion-reduce:transform-none"><span class="text-xs font-black uppercase text-slate-400">Bước 1–2</span><strong class="mt-1 block text-slate-950">Phân bổ số lượng</strong><span class="mt-1 block text-xs text-slate-500">Chọn bệnh viện trước, sau đó phân bổ sản phẩm.</span></a>
        @endif
        @if($canManageCommercialPolicy)
            @if($hasActiveAllocation)
            <a href="{{ route('client.pharma.bid-awards.commercial-policy',$result->scope_key) }}" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm active:scale-[.985] motion-reduce:transform-none"><span class="text-xs font-black uppercase text-emerald-600">Đã mở</span><strong class="mt-1 block text-emerald-950">Thiết lập chính sách kinh doanh</strong><span class="mt-1 block text-xs text-emerald-700">KQLCNT đã có phân bổ số lượng.</span></a>
            @else
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 opacity-70" aria-disabled="true"><span class="text-xs font-black uppercase text-slate-400">Đang khóa</span><strong class="mt-1 block text-slate-700">Thiết lập chính sách kinh doanh</strong><span class="mt-1 block text-xs text-slate-500">Cần hoàn tất phân bổ số lượng trước.</span></div>
            @endif
        @endif
    </section>
    @endif

    <form method="GET" class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm" id="bid-product-search-form">
        <div class="relative">
            <input name="q" value="{{ $search }}" placeholder="Tìm tên thuốc, hoạt chất, số đăng ký..." autocomplete="off" data-pwa-debounced-search="750" data-pwa-search-region="#bid-product-region"
                class="h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 pr-12 text-sm outline-none focus:border-slate-400">
            @if($search !== '')<a href="{{ route('client.pharma.bid-awards.show', $result->scope_key) }}" class="absolute right-2 top-2 flex h-8 w-8 items-center justify-center rounded-full text-slate-500" aria-label="Xóa tìm kiếm">×</a>@endif
        </div>
    </form>

    <div id="bid-product-region"><section id="bid-products" class="space-y-3">
        @forelse($products as $product)
            <article data-bid-product class="min-w-0 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0"><h2 class="font-black text-slate-950">{{ $product->medicine_name }}</h2><p class="mt-1 text-sm text-slate-500">{{ $product->active_ingredient ?: '—' }} @if($product->concentration) · {{ $product->concentration }} @endif</p></div>
<span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-500">KQLCNT</span>
                </div>
                <p class="mt-3 text-sm text-slate-500">{{ $product->packaging_specification ?: 'Chưa có quy cách' }}</p>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    <div class="rounded-2xl bg-slate-50 p-3"><span class="block text-[11px] font-bold uppercase text-slate-400">Giá trúng thầu</span><strong class="mt-1 block text-sm text-slate-950">{{ number_format((float) ($product->winning_price ?? $product->unit_price ?? 0), 0, ',', '.') }}</strong></div>
                    <div class="rounded-2xl bg-slate-50 p-3"><span class="block text-[11px] font-bold uppercase text-slate-400">SL trúng thầu</span><strong class="mt-1 block text-sm text-slate-950">{{ number_format((float) $product->winning_quantity, 0, ',', '.') }}</strong></div>
                    
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">Không tìm thấy sản phẩm phù hợp.</div>
        @endforelse
    </section>
    @if($products->hasMorePages())<div id="bid-product-load-more-wrap" class="text-center"><a id="bid-product-load-more" data-pwa-load-more data-pwa-load-more-target="#bid-products" data-pwa-load-more-items="[data-bid-product]" data-pwa-load-more-wrap="#bid-product-load-more-wrap" href="{{ $products->nextPageUrl() }}" class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 text-sm font-bold text-slate-700 shadow-sm">Xem thêm sản phẩm</a></div>@endif</div>
</div>

@endsection
