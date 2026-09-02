@extends('Admin::layouts.master')

@section('title', 'Wishlist thuốc cần theo dõi')

@section('content')
    @php($pageIds = $items->pluck('id')->map(fn ($id) => (string) $id)->values()->all())

    <div class="space-y-6" x-data="{
        selected: [],
        pageIds: @js($pageIds),
        toggleAll() {
            this.selected = this.selected.length === this.pageIds.length ? [] : [...this.pageIds];
        }
    }">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-rose-600">Mua sắm công</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Wishlist thuốc cần theo dõi</h1>
                <p class="mt-1 text-sm text-gray-500">Danh sách riêng của tài khoản hiện tại, lưu các thuốc cần theo dõi để tra cứu lại nhanh.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @include('Muasamcong::partials.dashboard-return-link')
                <a href="{{ route('muasamcong.synced') }}" class="inline-flex items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">Danh sách đã đồng bộ</a>
                <a href="{{ route('muasamcong.index') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">← Về tra cứu thuốc</a>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('success') }}</div>
        @endif

        <form method="GET" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto_auto_auto] lg:items-end">
                <div>
                    <label for="wishlist-search" class="mb-1 block text-xs font-semibold text-gray-600">Tìm trong Wishlist</label>
                    <input id="wishlist-search" type="search" name="q" value="{{ $keyword }}" placeholder="Tên thuốc, hoạt chất, nhóm hoặc mã TBMT..." class="min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                </div>
                <div>
                    <label for="wishlist-per-page" class="mb-1 block text-xs font-semibold text-gray-600">Số dòng / trang</label>
                    <select id="wishlist-per-page" name="per_page" class="min-h-11 rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                        @foreach ([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="min-h-11 rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-rose-700">Áp dụng</button>
                @if ($keyword !== '' || $perPage !== 25)
                    <a href="{{ route('muasamcong.wishlist') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50">Xóa bộ lọc</a>
                @endif
            </div>
        </form>

        <form method="POST" action="{{ route('muasamcong.wishlist.export-selected') }}" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            @csrf
            <input type="hidden" name="q" value="{{ $keyword }}">

            <div class="flex flex-col gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm text-gray-600">
                    <span class="font-semibold text-gray-900">{{ $items->total() }}</span> thuốc đang theo dõi
                    <span class="ml-2 text-rose-700" x-show="selected.length > 0">· Đã chọn <strong x-text="selected.length"></strong></span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="toggleAll()" @disabled(empty($pageIds)) class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 disabled:cursor-not-allowed disabled:opacity-40">
                        <span x-text="selected.length === pageIds.length && pageIds.length > 0 ? 'Bỏ chọn trang này' : 'Chọn trang hiện tại'"></span>
                    </button>
                    <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700">
                        <span x-show="selected.length === 0">Xuất Excel — tất cả phù hợp</span>
                        <span x-show="selected.length > 0">Xuất Excel (<span x-text="selected.length"></span> đã chọn)</span>
                    </button>
                    <button type="submit"
                            name="_method" value="DELETE"
                            formaction="{{ route('muasamcong.wishlist.destroy-selected') }}"
                            :disabled="selected.length === 0"
                            onclick="return confirm('Xóa các thuốc Wishlist đã chọn?');"
                            class="rounded-xl bg-red-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-40">
                        Xóa đã chọn (<span x-text="selected.length"></span>)
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[1320px] w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                        <tr>
                            <th class="w-14 px-4 py-3 text-center">
                                <input type="checkbox"
                                       @click="toggleAll()"
                                       :checked="pageIds.length > 0 && selected.length === pageIds.length"
                                       @disabled(empty($pageIds))
                                       class="h-4 w-4 rounded border-gray-300 text-rose-600 focus:ring-rose-500"
                                       title="Chọn trang hiện tại">
                            </th>
                            <th class="px-4 py-3">STT</th>
                            <th class="px-4 py-3">Thuốc</th>
                            <th class="px-4 py-3">Hoạt chất</th>
                            <th class="px-4 py-3">Nồng độ</th>
                            <th class="px-4 py-3">Nhóm</th>
                            <th class="px-4 py-3">Đơn vị trúng thầu</th>
                            <th class="px-4 py-3">Chủ đầu tư</th>
                            <th class="px-4 py-3">Mã TBMT</th>
                            <th class="px-4 py-3">Từ khóa lúc lưu</th>
                            <th class="px-4 py-3">Theo dõi từ</th>
                            <th class="px-4 py-3 text-center">Tra cứu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        @forelse ($items as $item)
                            @php($snapshot = is_array($item->snapshot) ? $item->snapshot : [])
                            @php($winningNames = array_values(array_filter(array_map(fn ($value) => is_scalar($value) ? trim((string) $value) : '', (array) ($snapshot['winningName'] ?? [])))))
                            <tr class="align-top hover:bg-rose-50/30">
                                <td class="px-4 py-4 text-center">
                                    <input type="checkbox" name="selected_ids[]" value="{{ $item->id }}" x-model="selected" class="h-4 w-4 rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                                </td>
                                <td class="px-4 py-4 text-gray-500">{{ $items->firstItem() + $loop->index }}</td>
                                <td class="px-4 py-4 font-semibold text-gray-950">{{ $item->medicine_name ?: '-' }}</td>
                                <td class="px-4 py-4">{{ $item->active_ingredient ?: '-' }}</td>
                                <td class="px-4 py-4">{{ $item->strength ?: '-' }}</td>
                                <td class="px-4 py-4">{{ $item->medicine_group ?: '-' }}</td>
                                <td class="min-w-64 px-4 py-4">
                                    @forelse ($winningNames as $winningName)
                                        <div class="font-semibold text-emerald-700">{{ $winningName }}</div>
                                    @empty
                                        <span class="text-gray-400">-</span>
                                    @endforelse
                                </td>
                                <td class="min-w-56 px-4 py-4">{{ $snapshot['tenCdtBmt'] ?? '-' }}</td>
                                <td class="px-4 py-4 font-mono text-xs">{{ $item->ma_tbmt ?: '-' }}</td>
                                <td class="px-4 py-4">{{ $item->search_keyword }}</td>
                                <td class="px-4 py-4 whitespace-nowrap">{{ $item->created_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                <td class="px-4 py-4 text-center"><a href="{{ route('muasamcong.index', ['q' => $item->medicine_name ?: $item->search_keyword]) }}" class="inline-flex rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700">Mở tra cứu</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="12" class="px-5 py-12 text-center text-gray-500">Wishlist chưa có thuốc nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($items->hasPages())
                <div class="border-t border-gray-200 px-4 py-4">{{ $items->links('Muasamcong::vendor.pagination.admin-muasamcong') }}</div>
            @endif
        </form>
    </div>
@endsection
