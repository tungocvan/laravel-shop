<div class="space-y-6">
    @if (session()->has('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Workbook nguồn</p>
            <p class="mt-2 truncate text-sm font-semibold text-gray-900">BANG_GIA_TONG_HOP.xlsx</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Sheet</p>
            <p class="mt-2 text-sm font-semibold text-gray-900">{{ $analysis['sheet_name'] ?? '-' }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Sản phẩm hợp lệ</p>
            <p class="mt-2 text-2xl font-bold text-indigo-600">{{ count($analysis['products'] ?? []) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Vùng tiêu đề</p>
            <p class="mt-2 text-sm font-semibold text-gray-900">
                Dòng {{ $analysis['header_row'] ?? '-' }} · A:{{ $analysis['last_header_column'] ?? '-' }}
            </p>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
            <h2 class="text-base font-semibold text-gray-900">1. Thông tin bảng giá</h2>
            <p class="mt-1 text-sm text-gray-500">Nội dung này được đặt vào phần đầu và chữ ký của file xuất.</p>
        </div>
        <div class="grid gap-5 p-4 sm:grid-cols-2 sm:p-6">
            <div>
                <label for="recipient" class="block text-sm font-medium text-gray-700">Kính gửi</label>
                <input id="recipient" type="text" wire:model.live="recipient"
                       class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                @error('recipient') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="signature-date" class="block text-sm font-medium text-gray-700">Ngày tháng chữ ký</label>
                <input id="signature-date" type="text" wire:model.live="signatureDate"
                       class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                @error('signatureDate') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="signature-title" class="block text-sm font-medium text-gray-700">Chức danh người ký</label>
                <input id="signature-title" type="text" wire:model.live="signatureTitle"
                       class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                @error('signatureTitle') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
            <h2 class="text-base font-semibold text-gray-900">2. Chọn cột xuất</h2>
            <p class="mt-1 text-sm text-gray-500">Hỗ trợ danh sách không liên tục, ví dụ A,B,E:V. Cột Y luôn bị loại vì không có tiêu đề.</p>
        </div>
        <div class="space-y-4 p-4 sm:p-6">
            <div>
                <label for="columns" class="block text-sm font-medium text-gray-700">Danh sách cột</label>
                <input id="columns" type="text" wire:model.live="columns" placeholder="A:X"
                       class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 font-mono text-sm uppercase text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                @error('columns') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" wire:click="useColumns('A:X')"
                        class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                    Tất cả A:X
                </button>
                <button type="button" wire:click="useColumns('A:V')"
                        class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                    Đến cột V
                </button>
                <button type="button" wire:click="useColumns('A,B,E:V')"
                        class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                    Mẫu A,B,E:V
                </button>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach ($columnsMetadata as $column)
                    <span class="inline-flex max-w-full items-center gap-1 rounded-lg bg-gray-100 px-2.5 py-1.5 text-xs text-gray-700"
                          title="{{ $column['header'] }}">
                        <strong>{{ $column['letter'] }}</strong>
                        <span class="max-w-40 truncate">{{ $column['header'] }}</span>
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">3. Chọn sản phẩm</h2>
                    <p class="mt-1 text-sm text-gray-500">Tìm bằng STT, tên biệt dược, hoạt chất hoặc số đăng ký.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="selectAllProducts"
                            class="rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                        Chọn tất cả
                    </button>
                    <button type="button" wire:click="clearProducts"
                            class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                        Bỏ chọn
                    </button>
                </div>
            </div>
            <div class="mt-4">
                <label for="product-search" class="sr-only">Tìm sản phẩm</label>
                <input id="product-search" type="search" wire:model.live.debounce.300ms="search"
                       placeholder="Ví dụ: 13, Trosicam, Meloxicam..."
                       class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>
        </div>

        @error('selectedRows') <p class="px-4 pt-4 text-sm text-rose-600 sm:px-6">{{ $message }}</p> @enderror

        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-600">
                    <tr>
                        <th class="w-14 px-4 py-3 text-center">
                            <input type="checkbox" wire:model.live="selectAllFiltered"
                                   class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        </th>
                        <th class="w-20 px-4 py-3 text-center">STT</th>
                        <th class="px-4 py-3">Tên biệt dược</th>
                        <th class="px-4 py-3">Hoạt chất</th>
                        <th class="px-4 py-3">Số đăng ký</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse ($products as $product)
                        <tr wire:key="price-product-{{ $product['row'] }}" class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-center">
                                <input type="checkbox" wire:model.live="selectedRows" value="{{ $product['row'] }}"
                                       class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </td>
                            <td class="px-4 py-3 text-center font-semibold text-gray-900">{{ $product['stt'] }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $product['name'] }}</td>
                            <td class="max-w-sm px-4 py-3">{{ $product['active_ingredient'] ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $product['registration_number'] ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">
                                Không tìm thấy sản phẩm phù hợp.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex flex-col gap-3 border-t border-gray-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p class="text-sm text-gray-600">
                Đã chọn <strong class="text-indigo-600">{{ count($selectedRows) }}</strong> sản phẩm
                · Đang hiển thị {{ count($products) }} kết quả
            </p>
            <button type="button" wire:click="generate" wire:loading.attr="disabled" wire:target="generate"
                    class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60">
                <svg wire:loading.remove wire:target="generate" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z"/>
                </svg>
                <svg wire:loading wire:target="generate" class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                </svg>
                <span wire:loading.remove wire:target="generate">Tạo và tải bảng giá</span>
                <span wire:loading wire:target="generate">Đang tạo file...</span>
            </button>
        </div>
    </div>
</div>
