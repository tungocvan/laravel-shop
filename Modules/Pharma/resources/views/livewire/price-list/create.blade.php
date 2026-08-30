<div class="space-y-5">
    @if (session()->has('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-gray-100 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-semibold text-gray-900">Workbook nguồn: BANG_GIA_TONG_HOP.xlsx</p>
                @if ($workbookReady)
                    <p class="mt-1 text-sm text-gray-500">
                        Sheet {{ $analysisSummary['sheet_name'] }} · {{ $analysisSummary['product_count'] }} sản phẩm · tiêu đề dòng {{ $analysisSummary['header_row'] }} · A:{{ $analysisSummary['last_header_column'] }}
                    </p>
                @else
                    <p class="mt-1 text-sm font-medium text-rose-600">Workbook chưa sẵn sàng. Vui lòng kiểm tra file nguồn.</p>
                @endif
            </div>
            <button type="button" wire:click="loadWorkbook" wire:loading.attr="disabled" wire:target="loadWorkbook"
                class="inline-flex h-10 items-center justify-center rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                <span wire:loading.remove wire:target="loadWorkbook">Phân tích lại workbook</span>
                <span wire:loading wire:target="loadWorkbook">Đang phân tích...</span>
            </button>
        </div>

        <div class="grid gap-5 p-5 lg:grid-cols-2">
            <section class="space-y-4">
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Thông tin bảng giá</h2>
                    <p class="mt-1 text-sm text-gray-500">Nội dung đầu trang và chữ ký của file xuất.</p>
                </div>
                <div>
                    <label for="recipient" class="block text-sm font-medium text-gray-700">Kính gửi</label>
                    <input id="recipient" type="text" wire:model="recipient"
                        class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    @error('recipient') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="signature-date" class="block text-sm font-medium text-gray-700">Ngày tháng chữ ký</label>
                        <input id="signature-date" type="text" wire:model="signatureDate"
                            class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                        @error('signatureDate') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="signature-title" class="block text-sm font-medium text-gray-700">Chức danh người ký</label>
                        <input id="signature-title" type="text" wire:model="signatureTitle"
                            class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                        @error('signatureTitle') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <section class="space-y-4 border-t border-gray-100 pt-5 lg:border-l lg:border-t-0 lg:pl-5 lg:pt-0">
                <div>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Cột xuất</h2>
                    <p class="mt-1 text-sm text-gray-500">Có thể chọn liên tục hoặc rời nhau, ví dụ A,B,E:V.</p>
                </div>
                <div>
                    <label for="columns" class="block text-sm font-medium text-gray-700">Danh sách cột</label>
                    <input id="columns" type="text" wire:model="columns" placeholder="A:X"
                        class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 font-mono text-sm uppercase focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    @error('columns') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="useColumns('A:X')" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">A:X</button>
                    <button type="button" wire:click="useColumns('A:V')" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">A:V</button>
                    <button type="button" wire:click="useColumns('A,B,E:V')" class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">A,B,E:V</button>
                </div>
                <div class="max-h-28 overflow-y-auto rounded-xl bg-gray-50 p-3">
                    <div class="flex flex-wrap gap-2">
                        @foreach ($columnsMetadata as $column)
                            <span class="inline-flex max-w-full items-center gap-1 rounded-lg bg-white px-2.5 py-1.5 text-xs text-gray-600 shadow-sm" title="{{ $column['header'] }}">
                                <strong>{{ $column['letter'] }}</strong>
                                <span class="max-w-36 truncate">{{ $column['header'] }}</span>
                            </span>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-gray-200 px-5 py-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-base font-semibold text-gray-900">Chọn sản phẩm</h2>
                <p class="mt-1 text-sm text-gray-500">Chỉ chọn theo trang hiện tại; không tự động chọn toàn workbook.</p>
            </div>
            <div class="grid w-full gap-3 sm:grid-cols-[1fr_auto] lg:w-auto lg:min-w-[520px]">
                <input id="product-search" type="search" wire:model.live.debounce.300ms="search"
                    placeholder="STT, tên thuốc, hoạt chất, số đăng ký..."
                    class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                <select wire:model.live="perPage" class="rounded-xl border border-gray-300 px-3 py-2.5 text-sm">
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}">{{ $option }} dòng</option>
                    @endforeach
                </select>
            </div>
        </div>

        @error('selectedRows') <p class="px-5 pt-4 text-sm text-rose-600">{{ $message }}</p> @enderror

        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 bg-gray-50/70 px-5 py-3">
            <div class="text-sm text-gray-500">
                <strong class="font-semibold text-gray-700">{{ $products->total() }}</strong> kết quả · Trang {{ $products->currentPage() }}/{{ max(1, $products->lastPage()) }}
                @if (count($selectedRows) > 0)
                    · <span class="font-semibold text-indigo-700">Đã chọn {{ count($selectedRows) }}</span>
                @endif
            </div>
            <button type="button" wire:click="clearProducts" class="rounded-lg px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-white">Bỏ chọn tất cả</button>
        </div>

        <div wire:loading.class="opacity-60" class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="sticky top-0 z-10 border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="w-14 px-4 py-3 text-center"><input type="checkbox" wire:model.live="selectPage" class="rounded border-gray-300 text-indigo-600"></th>
                        <th class="w-20 px-4 py-3 text-center">STT</th>
                        <th class="px-4 py-3">Tên biệt dược</th>
                        <th class="px-4 py-3">Hoạt chất</th>
                        <th class="px-4 py-3">Số đăng ký</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse ($products as $product)
                        <tr wire:key="price-product-{{ $product['row'] }}" class="hover:bg-gray-50/80">
                            <td class="px-4 py-3 text-center"><input type="checkbox" wire:model.live="selectedRows" value="{{ $product['row'] }}" class="rounded border-gray-300 text-indigo-600"></td>
                            <td class="px-4 py-3 text-center font-semibold text-gray-900">{{ $product['stt'] }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $product['name'] }}</td>
                            <td class="max-w-sm px-4 py-3">{{ $product['active_ingredient'] ?: '-' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $product['registration_number'] ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">Không tìm thấy sản phẩm phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-4 border-t border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <button type="button" wire:click="gotoPage({{ max(1, $products->currentPage() - 1) }})" @disabled($products->onFirstPage()) class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 disabled:opacity-40">Trước</button>
                <span class="px-2 text-sm font-semibold text-gray-700">{{ $products->currentPage() }} / {{ max(1, $products->lastPage()) }}</span>
                <button type="button" wire:click="gotoPage({{ min(max(1, $products->lastPage()), $products->currentPage() + 1) }})" @disabled(!$products->hasMorePages()) class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 disabled:opacity-40">Sau</button>
            </div>

            <button type="button" wire:click="generate" wire:loading.attr="disabled" wire:target="generate" @disabled(!$workbookReady)
                class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                <span wire:loading.remove wire:target="generate">Tạo và tải bảng giá ({{ count($selectedRows) }})</span>
                <span wire:loading wire:target="generate">Đang tạo file...</span>
            </button>
        </div>
    </div>
</div>
