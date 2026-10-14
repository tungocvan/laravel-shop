@extends('Website::layouts.frontend')

@section('title', 'Đặt hàng nhanh Pharma')

@section('content')
<main class="min-h-screen bg-slate-50 px-4 py-6 text-slate-900 sm:px-6" aria-labelledby="quick-order-title">
    <div class="mx-auto max-w-6xl">
        <nav class="mb-4 text-sm text-slate-500"><a href="{{ route('home') }}" class="hover:text-emerald-700">Trang chủ</a> / Đặt hàng nhanh</nav>
        <h1 id="quick-order-title" class="text-2xl font-bold sm:text-3xl">Đặt hàng nhanh Pharma</h1>
        <p class="mt-2 max-w-2xl text-sm text-slate-600">Tra cứu thuốc từ bảng giá toàn cục đã được phê duyệt. Việc gửi yêu cầu và xét duyệt đơn sẽ được mở sau khi hoàn thành bước xác minh người mua.</p>

        <form method="GET" action="{{ route('website.pharma.quick-order') }}" class="mt-6 flex flex-col gap-2 sm:flex-row" role="search">
            <label for="quick-order-search" class="sr-only">Tìm tên thuốc</label>
            <input id="quick-order-search" name="search" type="search" value="{{ $search }}" maxlength="120" placeholder="Tìm tên thuốc..." class="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm focus:border-emerald-600 focus:outline-none">
            <div class="flex gap-2">
                <button type="submit" class="flex-1 rounded-lg bg-emerald-700 px-5 py-3 text-sm font-semibold text-white active:scale-[0.985]">Tìm kiếm</button>
                @if($search !== '')
                    <a href="{{ route('website.pharma.quick-order') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">Xóa lọc</a>
                @endif
            </div>
        </form>

        <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($items as $item)
                <article class="flex min-w-0 gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-100">
                        @if($item->image)
                            <img src="{{ str_starts_with($item->image, 'http') ? $item->image : asset('storage/'.ltrim(preg_replace('#^storage/#', '', $item->image), '/')) }}" alt="{{ $item->title }}" loading="lazy" class="h-full w-full object-contain">
                        @else
                            <span class="text-xs text-slate-400">Chưa có ảnh</span>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="line-clamp-2 text-sm font-semibold">{{ $item->title }}</h2>
                        <p class="mt-2 text-xs text-slate-500">Giá và điều kiện mua được xác nhận khi xét duyệt.</p>
                        <a href="{{ route('product.detail', $item->slug) }}" class="mt-2 inline-block text-sm font-semibold text-emerald-700">Xem thông tin →</a>
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-600 sm:col-span-2 lg:col-span-3">Không tìm thấy thuốc thuộc bảng giá toàn cục được phép hiển thị.</div>
            @endforelse
        </div>
        @if($items->hasPages())
            <div class="mt-6">{{ $items->links() }}</div>
        @endif
        <p class="mt-6 text-xs text-slate-500">Danh mục tra cứu chưa phải đơn đặt hàng. Chưa có yêu cầu nào được gửi đến kho.</p>
    </div>
</main>
@endsection
