@extends('Admin::layouts.master')

@section('title', 'Wishlist thuốc cần theo dõi')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-rose-600">Mua sắm công</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Wishlist thuốc cần theo dõi</h1>
                <p class="mt-1 text-sm text-gray-500">Danh sách riêng của tài khoản hiện tại, lưu các thuốc cần theo dõi để tra cứu lại nhanh.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('muasamcong.synced') }}" class="inline-flex items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">Danh sách đã đồng bộ</a>
                <a href="{{ route('muasamcong.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">← Về tra cứu thuốc</a>
            </div>
        </div>

        <form method="GET" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row">
                <input type="search" name="q" value="{{ $keyword }}" placeholder="Tên thuốc, hoạt chất, nhóm hoặc mã TBMT..." class="min-h-11 flex-1 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-100">
                <button class="rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-rose-700">Tìm trong Wishlist</button>
                @if ($keyword !== '')<a href="{{ route('muasamcong.wishlist') }}" class="rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-center text-sm font-semibold text-gray-700">Xóa lọc</a>@endif
            </div>
        </form>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                <span class="font-semibold text-gray-900">{{ $items->total() }}</span> thuốc đang theo dõi
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-[1100px] w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                        <tr><th class="px-4 py-3">STT</th><th class="px-4 py-3">Thuốc</th><th class="px-4 py-3">Hoạt chất</th><th class="px-4 py-3">Nồng độ</th><th class="px-4 py-3">Nhóm</th><th class="px-4 py-3">Mã TBMT</th><th class="px-4 py-3">Từ khóa lúc lưu</th><th class="px-4 py-3">Theo dõi từ</th><th class="px-4 py-3 text-center">Tra cứu</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        @forelse ($items as $item)
                            <tr class="align-top hover:bg-rose-50/30">
                                <td class="px-4 py-4 text-gray-500">{{ $items->firstItem() + $loop->index }}</td>
                                <td class="px-4 py-4 font-semibold text-gray-950">{{ $item->medicine_name ?: '-' }}</td>
                                <td class="px-4 py-4">{{ $item->active_ingredient ?: '-' }}</td>
                                <td class="px-4 py-4">{{ $item->strength ?: '-' }}</td>
                                <td class="px-4 py-4">{{ $item->medicine_group ?: '-' }}</td>
                                <td class="px-4 py-4 font-mono text-xs">{{ $item->ma_tbmt ?: '-' }}</td>
                                <td class="px-4 py-4">{{ $item->search_keyword }}</td>
                                <td class="px-4 py-4 whitespace-nowrap">{{ $item->created_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                <td class="px-4 py-4 text-center"><a href="{{ route('muasamcong.index', ['q' => $item->medicine_name ?: $item->search_keyword]) }}" class="inline-flex rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700">Mở tra cứu</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-5 py-12 text-center text-gray-500">Wishlist chưa có thuốc nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($items->hasPages())<div class="border-t border-gray-200 px-4 py-4">{{ $items->links() }}</div>@endif
        </div>
    </div>
@endsection
