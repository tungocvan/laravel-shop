@extends('ClientPortal::layouts.application')

@section('title', $hospital->name)
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Commercial Workspace · bệnh viện')
@section('app-dashboard-route', route('client.pharma.dashboard'))

@section('content')
<div class="min-w-0 space-y-4 overflow-x-hidden">
    <a href="{{ route('client.pharma.commercial') }}" class="inline-flex h-11 items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm">← Công việc bệnh viện</a>

    <section class="rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-300">Bệnh viện được phân công</p>
        <h1 class="mt-2 text-2xl font-black tracking-tight sm:text-3xl">{{ $hospital->name }}</h1>
        @if($hospital->address)
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">{{ $hospital->address }}</p>
        @endif
        <div class="mt-4 inline-flex rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold text-slate-200">
            {{ number_format((int) $hospital->assigned_products_count, 0, ',', '.') }} sản phẩm phụ trách
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <form method="GET" action="{{ route('client.pharma.commercial.hospitals.show', $hospital->id) }}" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto_auto]">
            <label class="min-w-0">
                <span class="sr-only">Tìm sản phẩm</span>
                <input name="q" value="{{ $search }}" type="search" placeholder="Tên thuốc, hoạt chất, số đăng ký..." class="h-11 w-full min-w-0 rounded-2xl border border-slate-200 px-4 text-sm outline-none focus:border-slate-400">
            </label>
            <select name="per_page" onchange="this.form.submit()" class="h-11 rounded-2xl border border-slate-200 bg-white px-3 text-sm font-bold text-slate-700">
                @foreach([25, 50, 100] as $size)
                    <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} / trang</option>
                @endforeach
            </select>
            @if($search !== '')
                <a href="{{ route('client.pharma.commercial.hospitals.show', $hospital->id) }}" class="inline-flex h-11 items-center justify-center rounded-2xl border border-slate-200 px-4 text-sm font-bold text-slate-600">Xóa bộ lọc</a>
            @endif
        </form>
    </section>

    <section class="space-y-3">
        <div class="flex items-end justify-between gap-3 px-1">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">Danh sách sản phẩm</p>
                <h2 class="mt-1 text-lg font-black text-slate-900">{{ number_format($products->total(), 0, ',', '.') }} sản phẩm phù hợp</h2>
            </div>
        </div>

        @forelse($products as $product)
            @php
                $winningPrice = $product->winning_price ?? $product->unit_price;
                $policy = $product->effective_policy_percentage;
            @endphp
            <article class="min-w-0 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
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

                <div class="mt-4 grid grid-cols-2 gap-2 lg:grid-cols-4">
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
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
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
                    <p class="mt-3 text-xs font-semibold text-slate-400">
                        Hiệu lực
                        {{ $product->effective_from ? \Illuminate\Support\Carbon::parse($product->effective_from)->format('d/m/Y') : 'không giới hạn' }}
                        →
                        {{ $product->effective_until ? \Illuminate\Support\Carbon::parse($product->effective_until)->format('d/m/Y') : 'không giới hạn' }}
                    </p>
                @endif
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center">
                <p class="font-black text-slate-800">Không có sản phẩm phù hợp</p>
                <p class="mt-1 text-sm text-slate-500">Thử thay đổi từ khóa tìm kiếm hoặc xóa bộ lọc.</p>
            </div>
        @endforelse

        @if($products->hasPages())
            <div class="rounded-3xl border border-slate-200 bg-white p-3 shadow-sm">
                {{ $products->links() }}
            </div>
        @endif
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.querySelector('input[name="q"]');
    if (!input) return;
    let timer;
    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => input.form.submit(), 350);
    });
});
</script>
@endsection
