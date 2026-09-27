@extends('ClientPortal::layouts.application')

@section('title', 'Danh mục thuốc')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Danh mục thuốc · chỉ đọc')
@section('app-dashboard-route', route('client.pharma.dashboard'))

@section('content')
<div class="min-w-0 space-y-4 overflow-x-hidden">
    <section class="rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-300">Medicine Catalog</p>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-black tracking-tight sm:text-3xl">Danh mục thuốc</h1>
            <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold text-slate-200">{{ number_format($products->total(), 0, ',', '.') }} SKU</span>
        </div>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">Tra cứu danh mục chuẩn theo tên thuốc, mã thuốc, SKU, hoạt chất hoặc giấy phép lưu hành. PWA chỉ đọc và không thay đổi Medicine Master.</p>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        <form id="product-search-form" method="GET" action="{{ route('client.pharma.products') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end">
            <label class="min-w-0 flex-1">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Tìm thuốc</span>
                <input id="product-search-input" type="search" name="q" value="{{ $search }}" autocomplete="off" placeholder="Tên thuốc, mã thuốc, SKU, hoạt chất, GPLH..." class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-950 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
                <span class="mt-1.5 block text-xs text-slate-400">Kết quả tự cập nhật khi bạn nhập.</span>
            </label>
            <label class="w-full lg:w-36">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Hiển thị</span>
                <select name="per_page" class="w-full rounded-2xl border border-slate-300 px-3 py-3 text-sm text-slate-950" onchange="this.form.submit()">
                    @foreach([25, 50, 100] as $size)
                        <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} / trang</option>
                    @endforeach
                </select>
            </label>
            @if($search !== '')
                <a href="{{ route('client.pharma.products', ['per_page' => $perPage]) }}" class="rounded-2xl border border-slate-300 px-4 py-3 text-center text-sm font-bold text-slate-700">Xóa bộ lọc</a>
            @endif
        </form>
        <div class="mt-4 flex flex-wrap gap-2" aria-label="Bộ lọc danh mục thuốc">
            @php
                $filters = [
                    null => 'Tất cả',
                    'awarded' => 'Đã trúng thầu',
                    'profile' => 'Có HSSP',
                ];
                if ($canViewSupplierPricing) {
                    $filters['supplier-priced'] = 'Có giá NCC';
                }
            @endphp
            @foreach($filters as $value => $label)
                <a href="{{ route('client.pharma.products', array_filter(['q' => $search, 'per_page' => $perPage, 'filter' => $value], fn ($item) => $item !== null && $item !== '')) }}"
                   class="rounded-full border px-3.5 py-2 text-xs font-bold transition {{ $filter === $value ? 'border-slate-950 bg-slate-950 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-400 hover:text-slate-950' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </section>

    <section class="space-y-3 xl:hidden">
        @forelse($products as $product)
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="mt-1 font-black text-slate-950">{{ $product->brandName }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $product->activeIngredients ?: '—' }}@if($product->strength) · {{ $product->strength }}@endif</p>
                    </div>
                    <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">{{ $product->unit ?: '—' }}</span>
                </div>
                <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                    <div><dt class="text-xs font-bold text-slate-400">GPLH</dt><dd class="mt-1 text-slate-700">{{ $product->registrationNumber ?: '—' }}</dd></div>
                    <div><dt class="text-xs font-bold text-slate-400">Dạng bào chế</dt><dd class="mt-1 text-slate-700">{{ $product->dosageForm ?: '—' }}</dd></div>
                    <div class="col-span-2"><dt class="text-xs font-bold text-slate-400">Quy cách</dt><dd class="mt-1 text-slate-700">{{ $product->packaging ?: '—' }}</dd></div>
                    </dl>
                <div class="mt-4 flex justify-end">
                    <a href="{{ route('client.pharma.products.show', ['variant' => $product->variantId]) }}" aria-label="Xem chi tiết {{ $product->brandName }}" title="Xem chi tiết" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 text-slate-600 transition hover:bg-slate-50 hover:text-slate-950">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12s3.5-6 9.75-6 9.75 6 9.75 6-3.5 6-9.75 6S2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="2.75" stroke-width="1.8"/></svg>
                    </a>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">Không tìm thấy thuốc phù hợp.</div>
        @endforelse
    </section>

    <section class="hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm xl:block">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500">
                    <tr><th class="w-[22%] px-5 py-3">Tên thuốc</th><th class="w-[30%] px-5 py-3">Hoạt chất / Hàm lượng</th><th class="w-[15%] px-5 py-3">GPLH</th><th class="w-[10%] px-5 py-3">ĐVT</th><th class="w-[18%] px-5 py-3">Quy cách</th><th class="w-[5%] px-3 py-3 text-center">Xem</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $product)
                        <tr class="align-top">
                            <td class="px-5 py-4 font-bold text-slate-950">{{ $product->brandName }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $product->activeIngredients ?: '—' }}@if($product->strength)<br><span class="text-xs text-slate-400">{{ $product->strength }}</span>@endif</td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $product->registrationNumber ?: '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $product->unit ?: '—' }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $product->packaging ?: '—' }}</td>
                            <td class="px-3 py-4 text-center">
                                <a href="{{ route('client.pharma.products.show', ['variant' => $product->variantId]) }}" aria-label="Xem chi tiết {{ $product->brandName }}" title="Xem chi tiết" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-950">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-5 w-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12s3.5-6 9.75-6 9.75 6 9.75 6-3.5 6-9.75 6S2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="2.75" stroke-width="1.8"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Không tìm thấy thuốc phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if($products->hasPages())
        <div>{{ $products->links() }}</div>
    @endif
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('product-search-form');
    const input = document.getElementById('product-search-input');
    if (!form || !input) return;

    let timer;
    input.addEventListener('input', () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(() => form.requestSubmit(), 350);
    });
});
</script>
@endsection
