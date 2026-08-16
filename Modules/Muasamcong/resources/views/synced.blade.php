@extends('Admin::layouts.master')

@section('title', 'Danh sách đã đồng bộ')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Mua sắm công</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Danh sách đã đồng bộ</h1>
                <p class="mt-1 text-sm text-gray-500">Các thuốc đã được chọn và lưu vào database từ kết quả tra cứu.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('muasamcong.wishlist') }}" class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100">♥ Wishlist</a>
                <a href="{{ route('muasamcong.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">← Về tra cứu thuốc</a>
            </div>
        </div>

        <form method="GET" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row">
                <input type="search" name="q" value="{{ $keyword }}" placeholder="Tên thuốc, hoạt chất, nhóm, mã TBMT, chủ đầu tư..." class="min-h-11 flex-1 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                <button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Tìm trong danh sách</button>
                @if ($keyword !== '')<a href="{{ route('muasamcong.synced') }}" class="rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-center text-sm font-semibold text-gray-700">Xóa lọc</a>@endif
            </div>
        </form>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-[1300px] w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                        <tr><th class="px-4 py-3">STT</th><th class="px-4 py-3">Thuốc</th><th class="px-4 py-3">Nhóm</th><th class="px-4 py-3">Hoạt chất</th><th class="px-4 py-3">Nồng độ</th><th class="px-4 py-3 text-right">Giá</th><th class="px-4 py-3">Đơn vị trúng thầu</th><th class="px-4 py-3">Mã TBMT</th><th class="px-4 py-3">Đồng bộ lúc</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        @forelse ($items as $item)
                            <tr class="align-top hover:bg-indigo-50/30">
                                <td class="px-4 py-4 text-gray-500">{{ $items->firstItem() + $loop->index }}</td>
                                <td class="px-4 py-4 font-semibold text-gray-950">{{ $item->ten_thuoc ?: '-' }}</td>
                                <td class="px-4 py-4">{{ $item->nhom_thuoc ?: '-' }}</td>
                                <td class="px-4 py-4">{{ $item->ten_hoat_chat ?: '-' }}</td>
                                <td class="px-4 py-4">{{ $item->nong_do ?: '-' }}</td>
                                <td class="px-4 py-4 text-right font-semibold">{{ is_numeric($item->don_gia) ? number_format((float) $item->don_gia, 0, ',', '.') : '-' }}</td>
                                <td class="px-4 py-4">@forelse ((array) $item->winning_name as $name)<div class="font-medium text-emerald-700">{{ $name }}</div>@empty<span class="text-gray-400">-</span>@endforelse</td>
                                <td class="px-4 py-4 font-mono text-xs">{{ $item->ma_tbmt ?: '-' }}</td>
                                <td class="px-4 py-4 whitespace-nowrap">{{ $item->synced_at?->format('d/m/Y H:i') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-5 py-12 text-center text-gray-500">Chưa có dữ liệu đã đồng bộ.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($items->hasPages())<div class="border-t border-gray-200 px-4 py-4">{{ $items->links() }}</div>@endif
        </div>
    </div>
@endsection
