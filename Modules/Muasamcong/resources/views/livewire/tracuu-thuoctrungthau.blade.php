<div class="space-y-5">
    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
        <form wire:submit="search" class="flex flex-col gap-3 sm:flex-row sm:items-start">
            <div class="flex-1">
                <label for="pricing-keyword" class="text-sm font-medium text-gray-700">Tên thuốc, hoạt chất hoặc mã TBMT</label>
                <input
                    id="pricing-keyword"
                    type="search"
                    wire:model.live="keyword"
                    placeholder="Ví dụ: paracetamol"
                    class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                >
                @error('keyword')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="search"
                class="mt-6 inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="search">Tìm kiếm</span>
                <span wire:loading wire:target="search">Đang tìm...</span>
            </button>
        </form>
    </div>

    @if ($error)
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
    @endif

    <div wire:loading.flex wire:target="search" class="items-center justify-center rounded-2xl border border-gray-200 bg-white p-8 text-sm text-gray-500">
        Đang tải dữ liệu...
    </div>

    <div wire:loading.remove wire:target="search" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        @if ($results !== [])
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                        <tr>
                            <th class="px-4 py-3">STT</th>
                            <th class="px-4 py-3">Tên thuốc</th>
                            <th class="px-4 py-3">Hoạt chất</th>
                            <th class="px-4 py-3">Nồng độ / hàm lượng</th>
                            <th class="px-4 py-3">ĐVT</th>
                            <th class="px-4 py-3 text-right">Giá trúng thầu</th>
                            <th class="px-4 py-3 text-right">Số lượng</th>
                            <th class="px-4 py-3">Số quyết định</th>
                            <th class="px-4 py-3">Ngày ban hành</th>
                            <th class="px-4 py-3">Đơn vị trúng thầu</th>
                            <th class="px-4 py-3">Mã TBMT</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        @foreach ($results as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-4 py-3">{{ $loop->iteration }}</td>
                                <td class="min-w-48 px-4 py-3 font-medium text-gray-900">{{ $item['tenThuoc'] ?? '-' }}</td>
                                <td class="min-w-48 px-4 py-3">{{ $item['tenHoatChat'] ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $item['nongDo'] ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $item['donViTinh'] ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">{{ is_numeric($item['donGia'] ?? null) ? number_format((float) $item['donGia'], 0, ',', '.') : '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">{{ is_numeric($item['soLuong'] ?? null) ? number_format((float) $item['soLuong'], 0, ',', '.') : '-' }}</td>
                                <td class="px-4 py-3">{{ $item['soQuyetDinh'] ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $item['ngayBanHanhQuyetDinh'] ?? '-' }}</td>
                                <td class="min-w-48 px-4 py-3">{{ $item['tenCdtBmt'] ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $item['maTbmt'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-8 text-center text-sm text-gray-500">Chưa có dữ liệu. Hãy nhập từ khóa để bắt đầu tra cứu.</div>
        @endif
    </div>
</div>
