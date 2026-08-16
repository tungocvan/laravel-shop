<div class="space-y-5">
    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
        <form wire:submit="search" class="flex flex-col gap-3 lg:flex-row lg:items-end">
            <div class="flex-1">
                <label for="pricing-keyword" class="text-sm font-semibold text-gray-800">Tên thuốc, hoạt chất hoặc mã TBMT</label>
                <p class="mt-1 text-xs text-gray-500">Tra cứu dữ liệu đơn giá trúng thầu thuốc từ Hệ thống mạng đấu thầu quốc gia.</p>
                <input
                    id="pricing-keyword"
                    type="search"
                    wire:model="keyword"
                    placeholder="Ví dụ: Unafen, Ibuprofen, IB2500029154"
                    class="mt-3 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                >
                @error('keyword')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="search"
                class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="search">Tìm kiếm</span>
                <span wire:loading wire:target="search">Đang tìm...</span>
            </button>
        </form>
    </div>

    @if ($error)
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
    @endif

    <div wire:loading.flex wire:target="search" class="items-center justify-center rounded-2xl border border-gray-200 bg-white p-8 text-sm text-gray-500 shadow-sm">
        Đang tải dữ liệu từ Hệ thống mạng đấu thầu quốc gia...
    </div>

    <div wire:loading.remove wire:target="search" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        @if ($results !== [])
            <div class="flex flex-col gap-2 border-b border-gray-200 bg-gray-50/70 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-gray-900">Kết quả tra cứu</p>
                    <p class="text-xs text-gray-500">Hiển thị {{ count($results) }} bản ghi. Kéo ngang để xem đầy đủ thông tin trên màn hình nhỏ.</p>
                </div>
                <span class="inline-flex w-fit items-center rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                    {{ count($results) }} kết quả
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[1800px] w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-600">
                        <tr>
                            <th class="sticky left-0 z-20 w-14 bg-gray-50 px-4 py-3 text-center">STT</th>
                            <th class="min-w-44 px-4 py-3">Thuốc</th>
                            <th class="min-w-44 px-4 py-3">Hoạt chất</th>
                            <th class="min-w-44 px-4 py-3">Nồng độ / hàm lượng</th>
                            <th class="min-w-20 px-4 py-3">ĐVT</th>
                            <th class="min-w-32 px-4 py-3 text-right">Giá trúng thầu</th>
                            <th class="min-w-28 px-4 py-3 text-right">Số lượng</th>
                            <th class="min-w-44 px-4 py-3">Số quyết định</th>
                            <th class="min-w-40 px-4 py-3">Ngày ban hành</th>
                            <th class="min-w-72 px-4 py-3">Đơn vị trúng thầu</th>
                            <th class="min-w-72 px-4 py-3">Chủ đầu tư / Bên mời thầu</th>
                            <th class="min-w-36 px-4 py-3">Mã TBMT</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        @foreach ($results as $item)
                            @php
                                $winningNames = array_values(array_filter(
                                    is_array($item['winningName'] ?? null) ? $item['winningName'] : [],
                                    static fn ($name) => is_scalar($name) && trim((string) $name) !== ''
                                ));
                            @endphp
                            <tr class="align-top transition-colors hover:bg-indigo-50/30">
                                <td class="sticky left-0 z-10 bg-white px-4 py-4 text-center font-medium text-gray-500 group-hover:bg-indigo-50/30">{{ $loop->iteration }}</td>
                                <td class="px-4 py-4">
                                    <div class="font-semibold text-gray-950">{{ $item['tenThuoc'] ?? '-' }}</div>
                                    @if (! empty($item['nhomThuoc']))
                                        <div class="mt-1 text-xs text-gray-500">{{ $item['nhomThuoc'] }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-4">{{ $item['tenHoatChat'] ?? '-' }}</td>
                                <td class="px-4 py-4">{{ $item['nongDo'] ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-4">{{ $item['donViTinh'] ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-right font-semibold tabular-nums text-gray-950">
                                    {{ is_numeric($item['donGia'] ?? null) ? number_format((float) $item['donGia'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-right tabular-nums">
                                    {{ is_numeric($item['soLuong'] ?? null) ? number_format((float) $item['soLuong'], 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-4 py-4 font-medium text-gray-800">{{ $item['soQuyetDinh'] ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-4">
                                    @if (! empty($item['ngayBanHanhQuyetDinh']))
                                        {{ \Illuminate\Support\Carbon::parse($item['ngayBanHanhQuyetDinh'])->format('d/m/Y H:i') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    @if ($winningNames !== [])
                                        <div class="space-y-1.5">
                                            @foreach ($winningNames as $winningName)
                                                <div class="font-semibold leading-5 text-emerald-700">{{ $winningName }}</div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 leading-5">{{ $item['tenCdtBmt'] ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-4">
                                    <span class="inline-flex rounded-lg bg-gray-100 px-2.5 py-1 font-mono text-xs font-semibold text-gray-700">{{ $item['maTbmt'] ?? '-' }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-10 text-center">
                <p class="text-sm font-medium text-gray-700">Chưa có dữ liệu tra cứu</p>
                <p class="mt-1 text-sm text-gray-500">Nhập tên thuốc, hoạt chất hoặc mã TBMT để bắt đầu.</p>
            </div>
        @endif
    </div>
</div>
