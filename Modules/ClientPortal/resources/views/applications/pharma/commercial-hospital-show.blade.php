@extends('ClientPortal::layouts.application')

@section('title', $hospital->name)
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Commercial Workspace · bệnh viện')
@section('app-dashboard-route', route('client.pharma.dashboard'))
@section('hide-application-header', true)
@section('hide-mobile-navigation', true)

@section('content')
<div class="min-w-0 space-y-4 overflow-x-hidden">
    <a href="{{ route('client.pharma.commercial', array_filter(['manager_user_id' => $managerUserId, 'award_scope' => $awardScopeKey])) }}" aria-label="Quay lại Công việc bệnh viện" data-pwa-navigation-feedback="#commercial-navigation-feedback" class="inline-flex h-11 items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm">← Công việc bệnh viện</a>

    <section class="rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-300">Bệnh viện được phân công</p>
        <h1 class="mt-2 text-2xl font-black tracking-tight sm:text-3xl">{{ $hospital->name }}</h1>
        @if($hospital->address)
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">{{ $hospital->address }}</p>
        @endif
        <div class="mt-4 flex flex-wrap gap-2 text-xs font-bold text-slate-200">
            <span class="rounded-full bg-white/10 px-3 py-1.5">{{ str_pad((string) ((int) $hospital->assigned_products_count), 2, '0', STR_PAD_LEFT) }} SKU</span>
            <span class="rounded-full bg-white/10 px-3 py-1.5">Tổng giá trị trúng thầu {{ number_format((float) $hospital->allocated_award_value, 0, ',', '.') }} đ</span>
        </div>
    </section>

    <div id="commercial-product-search-region" class="space-y-4">
    <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5 lg:max-w-3xl">
        <form data-pwa-pending-feedback="#commercial-navigation-feedback" method="GET" action="{{ route('client.pharma.commercial.hospitals.show', $hospital->id) }}" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
            @if($managerUserId)<input type="hidden" name="manager_user_id" value="{{ $managerUserId }}">@endif
            <input type="hidden" name="award_scope" value="{{ $awardScopeKey }}">
            <label class="relative min-w-0">
                <span class="sr-only">Tìm sản phẩm</span>
                <input id="commercial-product-search-input" name="q" value="{{ $search }}" data-pwa-debounced-search="800" data-pwa-search-region="#commercial-product-search-region" data-pwa-search-clear="#commercial-product-search-clear" type="search" autocomplete="off" placeholder="Tên thuốc, hoạt chất, số đăng ký..." class="h-11 w-full min-w-0 rounded-2xl border border-slate-200 px-4 pr-11 text-sm outline-none focus:border-slate-400">
                <button id="commercial-product-search-clear" data-pwa-search-clear-button="#commercial-product-search-input" type="button" aria-label="Xóa tìm kiếm sản phẩm" class="absolute right-1.5 top-1/2 inline-flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full text-lg font-bold text-slate-400 hover:bg-slate-100 hover:text-slate-700 {{ $search === '' ? 'hidden' : '' }}">×</button>
            </label>
        </form>
    </section>

    <section class="space-y-3">
        <div class="flex items-end justify-between gap-3 px-1">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">Danh sách sản phẩm</p>
                <h2 class="mt-1 text-lg font-black text-slate-900">{{ number_format($products->total(), 0, ',', '.') }} sản phẩm phù hợp</h2>
            </div>
        </div>

        <div id="commercial-product-list" class="space-y-3">
        @forelse($products as $product)
            @php
                $winningPrice = $product->winning_price ?? $product->unit_price;
                $policy = $product->effective_policy_percentage;
            @endphp
            <article data-commercial-product class="min-w-0 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5 lg:p-6">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-bold text-slate-500">
                        @if($product->bidding_notice_code)
                            <span class="rounded-full bg-slate-100 px-2.5 py-1">TBMT {{ $product->bidding_notice_code }}</span>
                        @endif
                        @if($product->decision_number)
                            <span class="rounded-full bg-slate-100 px-2.5 py-1">QĐ {{ $product->decision_number }}</span>
                        @endif
                    </div>
                    <h3 class="mt-3 break-words text-base font-black text-slate-900 sm:text-lg">{{ $product->medicine_name }}</h3>
                    @if($product->active_ingredient)
                        <p class="mt-1 text-sm font-semibold text-slate-600">{{ $product->active_ingredient }}@if($product->concentration) · {{ $product->concentration }}@endif</p>
                    @endif
                    @if($product->packaging_specification)
                        <p class="mt-1 text-sm text-slate-500">{{ $product->packaging_specification }}</p>
                    @endif
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2 md:grid-cols-4">
                    <div class="rounded-2xl bg-slate-50 p-3">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Giá trúng thầu</p>
                        <p class="mt-1 text-sm font-black text-slate-900">{{ $winningPrice !== null ? number_format((float) $winningPrice, 0, ',', '.') . ' đ' : 'Chưa có' }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-3">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">SL phân bổ</p>
                        <p class="mt-1 text-sm font-black text-slate-900">{{ $product->allocated_quantity !== null ? number_format((float) $product->allocated_quantity, 0, ',', '.') : 'Chưa có' }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-3">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">SL trúng thầu</p>
                        <p class="mt-1 text-sm font-black text-slate-900">{{ $product->winning_quantity !== null ? number_format((float) $product->winning_quantity, 0, ',', '.') : 'Chưa có' }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-3">
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Chính sách hiệu lực</p>
                        <p class="mt-1 text-sm font-black text-slate-900">{{ $policy !== null ? rtrim(rtrim(number_format((float) $policy, 2, ',', '.'), '0'), ',') . '%' : 'Chưa thiết lập' }}</p>
                        @if($policy !== null)
                            <p class="mt-1 text-[11px] font-semibold text-slate-400">{{ $product->hospital_policy_percentage !== null ? 'Theo bệnh viện' : 'Theo sản phẩm' }}</p>
                        @endif
                    </div>
                </div>

                @if($product->sale_price || $product->supplier)
                    <div class="mt-3 grid gap-2 md:grid-cols-2">
                        @if($product->sale_price)
                            <div class="rounded-2xl border border-slate-200 p-3">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Giá bán hiện hành</p>
                                <p class="mt-1 text-base font-black text-slate-900">{{ $product->sale_price['company_sale_price'] !== null ? number_format((float) $product->sale_price['company_sale_price'], 0, ',', '.') . ' đ' : 'Chưa có' }}</p>
                                <p class="mt-1 text-xs font-semibold text-slate-500">{{ $product->sale_price['source_type'] === 'customer' ? 'Bảng giá bệnh viện' : 'Bảng giá chung' }}</p>
                            </div>
                        @endif
                        @if($product->supplier)
                            <div class="rounded-2xl border border-slate-200 p-3">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Điều kiện NCC hiện hành</p>
                                <p class="mt-1 break-words text-sm font-black text-slate-900">{{ $product->supplier['supplier_name'] ?: 'Nhà cung cấp' }}</p>
                                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs font-semibold text-slate-500">
                                    @if($product->supplier['import_price'] !== null)
                                        <span>Giá vốn NCC: <strong class="text-slate-800">{{ number_format((float) $product->supplier['import_price'], 0, ',', '.') }} đ</strong></span>
                                    @endif
                                    @if($product->supplier['cost_price'] !== null)
                                        <span>Giá vốn tính toán: <strong class="text-slate-800">{{ number_format((float) $product->supplier['cost_price'], 0, ',', '.') }} đ</strong></span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                @if($product->effective_from || $product->effective_until)
                    <div class="mt-3 border-t border-slate-100 pt-3">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Thời gian phân bổ hiệu lực</p>
                    <p class="mt-1 text-xs font-semibold text-slate-500">
                        
                        {{ $product->effective_from ? \Illuminate\Support\Carbon::parse($product->effective_from)->format('d/m/Y') : 'không giới hạn' }}
                        →
                        {{ $product->effective_until ? \Illuminate\Support\Carbon::parse($product->effective_until)->format('d/m/Y') : 'không giới hạn' }}
                    </p>
                    </div>
                @endif
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center">
                <p class="font-black text-slate-800">Không có sản phẩm phù hợp</p>
                <p class="mt-1 text-sm text-slate-500">Thử thay đổi từ khóa tìm kiếm hoặc xóa bộ lọc.</p>
            </div>
        @endforelse
        </div>

        @if($products->hasMorePages())
            <div id="commercial-product-load-more-wrap" class="pt-1 text-center">
                <a id="commercial-product-load-more" data-pwa-load-more data-pwa-load-more-target="#commercial-product-list" data-pwa-load-more-items="#commercial-product-list [data-commercial-product]" data-pwa-load-more-wrap="#commercial-product-load-more-wrap" data-pwa-pending-label="Đang tải…" href="{{ $products->nextPageUrl() }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-black text-slate-800 shadow-sm transition duration-150 active:scale-[0.985] sm:w-auto motion-reduce:transform-none">
                    Xem thêm sản phẩm
                </a>
                <p class="mt-2 text-xs font-semibold text-slate-400">Đã hiển thị {{ $products->count() }} / {{ $products->total() }}</p>
            </div>
        @endif
    </section>
</div>
</div>

<div id="commercial-navigation-feedback" class="pointer-events-none fixed inset-x-0 bottom-6 z-50 mx-auto hidden w-fit items-center gap-2 rounded-full bg-slate-950/95 px-4 py-2.5 text-sm font-bold text-white shadow-xl backdrop-blur" role="status" aria-live="polite">
    <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white motion-reduce:animate-none"></span>
    Đang mở…
</div>

@endsection
