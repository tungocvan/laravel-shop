@extends('ClientPortal::layouts.application')

@section('title', 'Danh mục thuốc')
@section('app-name', $applicationPresentation['name'] ?? $application['name'])
@section('app-subtitle', 'Danh mục thuốc · chỉ đọc')
@section('app-dashboard-route', route('client.pharma.dashboard'))

@section('content')
<div class="min-w-0 space-y-4 overflow-x-hidden">
    <section class="rounded-[2rem] bg-slate-950 px-5 py-6 text-white shadow-sm sm:px-7">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-300">Medicine Catalog</p>
        <h1 class="mt-2 text-2xl font-black tracking-tight sm:text-3xl">Danh mục thuốc</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">Tra cứu danh mục chuẩn theo tên thuốc, mã thuốc, SKU, hoạt chất hoặc giấy phép lưu hành. PWA chỉ đọc và không thay đổi Medicine Master.</p>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('client.pharma.products') }}" class="flex flex-col gap-3 lg:flex-row lg:items-end">
            <label class="min-w-0 flex-1">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Tìm thuốc</span>
                <input type="search" name="q" value="{{ $search }}" placeholder="Tên thuốc, mã thuốc, SKU, hoạt chất, GPLH..." class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-950 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200">
            </label>
            <label class="w-full lg:w-36">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Hiển thị</span>
                <select name="per_page" class="w-full rounded-2xl border border-slate-300 px-3 py-3 text-sm text-slate-950" onchange="this.form.submit()">
                    @foreach([25, 50, 100] as $size)
                        <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }} / trang</option>
                    @endforeach
                </select>
            </label>
            <div class="flex gap-2">
                <button type="submit" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-bold text-white">Tìm kiếm</button>
                @if($search !== '')
                    <a href="{{ route('client.pharma.products', ['per_page' => $perPage]) }}" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700">Xóa bộ lọc</a>
                @endif
            </div>
        </form>
    </section>

    <section class="space-y-3 xl:hidden">
        @forelse($products as $product)
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-slate-400">{{ $product->sku }}</p>
                        <h2 class="mt-1 font-black text-slate-950">{{ $product->brandName }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $product->activeIngredients ?: '—' }}@if($product->strength) · {{ $product->strength }}@endif</p>
                    </div>
                    <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">{{ $product->unit ?: '—' }}</span>
                </div>
                <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                    <div><dt class="text-xs font-bold text-slate-400">GPLH</dt><dd class="mt-1 text-slate-700">{{ $product->registrationNumber ?: '—' }}</dd></div>
                    <div><dt class="text-xs font-bold text-slate-400">Dạng bào chế</dt><dd class="mt-1 text-slate-700">{{ $product->dosageForm ?: '—' }}</dd></div>
                    <div class="col-span-2"><dt class="text-xs font-bold text-slate-400">Quy cách</dt><dd class="mt-1 text-slate-700">{{ $product->packaging ?: '—' }}</dd></div>
                    <div class="col-span-2"><dt class="text-xs font-bold text-slate-400">Nhà sản xuất</dt><dd class="mt-1 text-slate-700">{{ $product->manufacturer ?: '—' }}</dd></div>
                </dl>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">Không tìm thấy thuốc phù hợp.</div>
        @endforelse
    </section>

    <section class="hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm xl:block">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-3">SKU</th><th class="px-4 py-3">Tên thuốc</th><th class="px-4 py-3">Hoạt chất / Hàm lượng</th><th class="px-4 py-3">GPLH</th><th class="px-4 py-3">ĐVT</th><th class="px-4 py-3">Quy cách</th><th class="px-4 py-3">Nhà sản xuất</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $product)
                        <tr class="align-top">
                            <td class="whitespace-nowrap px-4 py-4 font-bold text-slate-600">{{ $product->sku }}</td>
                            <td class="px-4 py-4 font-bold text-slate-950">{{ $product->brandName }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ $product->activeIngredients ?: '—' }}@if($product->strength)<br><span class="text-xs text-slate-400">{{ $product->strength }}</span>@endif</td>
                            <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $product->registrationNumber ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $product->unit ?: '—' }}</td>
                            <td class="max-w-xs px-4 py-4 text-slate-600">{{ $product->packaging ?: '—' }}</td>
                            <td class="max-w-xs px-4 py-4 text-slate-600">{{ $product->manufacturer ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">Không tìm thấy thuốc phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if($products->hasPages())
        <div>{{ $products->links() }}</div>
    @endif
</div>
@endsection
