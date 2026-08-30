<div class="mx-auto max-w-7xl space-y-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Theo dõi nhà cung cấp</h1>
            <p class="mt-1 text-sm text-gray-500">Theo dõi giá, lợi nhuận, cam kết và hợp đồng theo HSSP.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @can('edit_pharma')
                <button type="button" wire:click="toggleImportExport"
                    class="inline-flex h-11 items-center justify-center rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                    {{ $showImportExport ? 'Đóng Import / Export' : 'Import / Export' }}
                </button>
            @endcan
            @can('create_pharma')
                <a href="{{ route('admin.pharma.supplier-trackings.create') }}"
                    class="inline-flex h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    + Thêm theo dõi
                </a>
            @endcan
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    @can('edit_pharma')
        @if ($showImportExport)
            <div class="rounded-2xl border border-indigo-100 bg-indigo-50/30 p-1 shadow-sm">
                @livewire('shared.import-export.panel', [
                    'serviceClass' => \Modules\Pharma\Services\ImportExport::class,
                    'title' => 'Import / Export theo dõi nhà cung cấp',
                    'description' => 'File Excel chuẩn A–V; các cột công thức được hệ thống tự tính lại.',
                    'filters' => ['search' => $search, 'status' => $status],
                    'permission' => 'edit_pharma',
                ], key('supplier-tracking-import-export-' . md5(json_encode([$search, $status]))))
            </div>
        @endif
    @endcan

    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="p-5">
            <div class="grid gap-4 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <label class="text-sm font-medium text-gray-700">Tìm kiếm</label>
                    <input type="text" wire:model.live.debounce.400ms="search"
                        placeholder="Tên thuốc, SĐK, NCC, đại diện, khu vực..."
                        class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                </div>
                <div class="lg:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Trạng thái</label>
                    <select wire:model.live="status" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3">
                        <option value="">Tất cả</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Từ ngày</label>
                    <input type="date" wire:model.live="workingDateFrom" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3">
                </div>
                <div class="lg:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Đến ngày</label>
                    <input type="date" wire:model.live="workingDateTo" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3">
                </div>
                <div class="lg:col-span-2">
                    <label class="text-sm font-medium text-gray-700">Hiển thị</label>
                    <select wire:model.live="perPage" class="mt-1 w-full rounded-xl border border-gray-300 px-4 py-3">
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }} dòng</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-y border-gray-100 bg-gray-50/70 px-5 py-3">
            <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500">
                <span><strong class="font-semibold text-gray-700">{{ $items->total() }}</strong> kết quả</span>
                <span class="text-gray-300">•</span>
                <span>Trang {{ $items->currentPage() }}/{{ max(1, $items->lastPage()) }}</span>
                @if ($this->hasSelected)
                    <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">Đã chọn {{ $this->selectedCount }} dòng</span>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <button type="button" wire:click="resetFilters" class="rounded-lg px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-white">Đặt lại bộ lọc</button>
                @can('delete_pharma')
                    <button type="button" wire:click="confirmBulkDelete"
                        class="rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-500 disabled:opacity-40"
                        @disabled(!$this->hasSelected)>Xóa đã chọn</button>
                @endcan
            </div>
        </div>

        <div wire:loading.class="opacity-60" class="relative overflow-x-auto">
            <table class="w-full min-w-[1080px] divide-y divide-gray-200 text-sm">
                <thead class="sticky top-0 z-10 bg-gray-50">
                    <tr>
                        @can('delete_pharma')
                            <th class="w-10 px-4 py-3 text-left"><input type="checkbox" wire:model.live="selectPage" class="rounded border-gray-300 text-indigo-600"></th>
                        @endcan
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">HSSP / Ngày làm việc</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Nhà cung cấp</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Giá mua → bán</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">LN thực tế</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Cam kết</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Hợp đồng</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Trạng thái</th>
                        <th class="w-28 px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($items as $item)
                        <tr class="group hover:bg-gray-50/80">
                            @can('delete_pharma')
                                <td class="px-4 py-4 align-top"><input type="checkbox" wire:model.live="selectedIds" value="{{ $item->id }}" class="rounded border-gray-300 text-indigo-600"></td>
                            @endcan
                            <td class="px-4 py-4 align-top">
                                <div class="max-w-[260px] font-semibold leading-5 text-gray-900">{{ $item->medicine?->name ?? '---' }}</div>
                                <div class="mt-1 flex flex-wrap gap-x-2 text-xs text-gray-500">
                                    <span>SĐK {{ $item->medicine?->registration_number ?? '---' }}</span>
                                    <span>•</span>
                                    <span>{{ $item->working_date?->format('d/m/Y') ?? 'Chưa có ngày' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="max-w-[220px] font-semibold text-gray-900">{{ $item->supplier_name }}</div>
                                <div class="mt-1 text-xs text-gray-500">{{ $item->supplier_representative ?: 'Chưa có đại diện' }}</div>
                                <div class="mt-0.5 text-xs text-gray-400">{{ $item->area ?: 'Chưa có khu vực' }}</div>
                            </td>
                            <td class="px-4 py-4 text-right align-top tabular-nums">
                                <div class="whitespace-nowrap font-medium text-gray-900">{{ $this->money($item->import_price) }} <span class="px-1 text-gray-300">→</span> {{ $this->money($item->selling_price) }}</div>
                                <button type="button" wire:click="toggleFinancialDetails({{ $item->id }})" class="mt-1 text-xs font-semibold text-indigo-600 hover:text-indigo-500">
                                    {{ $expandedFinancialId === $item->id ? 'Ẩn chi tiết' : 'Chi tiết giá' }}
                                </button>
                            </td>
                            <td class="px-4 py-4 text-right align-top tabular-nums">
                                <span class="inline-flex whitespace-nowrap rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">{{ $this->percent($item->gross_profit_percent) }}</span>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="whitespace-nowrap font-medium text-gray-800">{{ $item->committed_quantity ? $this->money($item->committed_quantity) : '---' }} {{ $item->unit }}</div>
                                <div class="mt-1 whitespace-nowrap text-xs text-gray-500">Cọc: {{ $item->deposit_amount ? $this->money($item->deposit_amount) : '---' }}</div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="whitespace-nowrap text-xs text-gray-500">{{ $item->start_date?->format('d/m/Y') ?? '---' }} → {{ $item->end_date?->format('d/m/Y') ?? '---' }}</div>
                                @if ($item->contract_url)
                                    <a href="{{ $item->contract_url }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-flex whitespace-nowrap text-xs font-semibold text-indigo-600 hover:text-indigo-500">Xem hợp đồng ↗</a>
                                @else
                                    <span class="mt-1 block text-xs text-gray-400">Chưa có liên kết</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-center align-top">
                                <span class="inline-flex whitespace-nowrap rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">{{ $statuses[$item->status] ?? $item->status }}</span>
                            </td>
                            <td class="px-4 py-4 text-right align-top">
                                <div class="flex justify-end gap-3 whitespace-nowrap">
                                    @can('edit_pharma')
                                        <a href="{{ route('admin.pharma.supplier-trackings.edit', $item->id) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">Sửa</a>
                                    @endcan
                                    @can('delete_pharma')
                                        <button type="button" wire:click="delete({{ $item->id }})" wire:confirm="Bạn chắc chắn muốn xóa dòng này?" class="text-sm font-semibold text-red-600 hover:text-red-500">Xóa</button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @if ($expandedFinancialId === $item->id)
                            <tr class="bg-indigo-50/40">
                                <td colspan="9" class="px-4 py-4">
                                    <div class="ml-10 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                                        <div><div class="text-xs font-medium uppercase text-gray-400">Giá hóa đơn</div><div class="mt-1 font-semibold tabular-nums text-gray-800">{{ $this->money($item->invoice_price) }}</div></div>
                                        <div><div class="text-xs font-medium uppercase text-gray-400">Chênh lệch HĐ</div><div class="mt-1 font-semibold tabular-nums text-gray-800">{{ $this->money($item->invoice_difference_amount) }}</div></div>
                                        <div><div class="text-xs font-medium uppercase text-gray-400">Phí chênh lệch</div><div class="mt-1 font-semibold tabular-nums text-gray-800">{{ $this->percent($item->invoice_difference_percent) }} · {{ $this->money($item->invoice_difference_fee) }}</div></div>
                                        <div><div class="text-xs font-medium uppercase text-gray-400">Giá vốn</div><div class="mt-1 font-semibold tabular-nums text-gray-800">{{ $this->money($item->cost_price) }}</div></div>
                                        <div><div class="text-xs font-medium uppercase text-gray-400">Giá bán / LN</div><div class="mt-1 font-semibold tabular-nums text-gray-800">{{ $this->money($item->selling_price) }} · {{ $this->percent($item->gross_profit_percent) }}</div></div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="9" class="px-4 py-14 text-center text-sm text-gray-500">Không có dữ liệu phù hợp bộ lọc hiện tại.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 px-5 py-4">
            <span class="text-sm text-gray-500">Hiển thị {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} / {{ $items->total() }}</span>
            <div class="flex items-center gap-2">
                <button type="button" wire:click="gotoPage({{ max(1, $items->currentPage() - 1) }})" @disabled($items->onFirstPage()) class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 disabled:opacity-40">Trước</button>
                <span class="px-2 text-sm font-semibold text-gray-700">{{ $items->currentPage() }} / {{ max(1, $items->lastPage()) }}</span>
                <button type="button" wire:click="gotoPage({{ min(max(1, $items->lastPage()), $items->currentPage() + 1) }})" @disabled(!$items->hasMorePages()) class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 disabled:opacity-40">Sau</button>
            </div>
        </div>
    </div>

    @if ($showBulkDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-semibold text-gray-900">Xác nhận xóa hàng loạt</h2>
                <p class="mt-2 text-sm text-gray-600">Bạn sắp xóa <strong>{{ $this->selectedCount }}</strong> dòng được chọn trên <strong>trang hiện tại</strong>. Thao tác này không thể hoàn tác.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="cancelBulkDelete" class="rounded-xl border border-gray-300 px-4 py-2 font-semibold text-gray-700">Hủy</button>
                    <button type="button" wire:click="deleteSelected" wire:loading.attr="disabled" class="rounded-xl bg-red-600 px-4 py-2 font-semibold text-white disabled:opacity-50">Xóa {{ $this->selectedCount }} dòng</button>
                </div>
            </div>
        </div>
    @endif
</div>
