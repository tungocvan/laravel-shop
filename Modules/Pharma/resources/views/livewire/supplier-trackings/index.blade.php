<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Theo dõi nhà cung cấp</h1>
            <p class="mt-1 text-sm text-gray-500">Quản lý giá, chi phí, lợi nhuận, cam kết và hợp đồng theo HSSP.</p>
        </div>

        @can('create_pharma')
            <a href="{{ route('admin.pharma.supplier-trackings.create') }}"
                class="inline-flex h-[50px] items-center justify-center rounded-xl bg-indigo-600 px-5 font-semibold text-white shadow-sm hover:bg-indigo-500">
                + Thêm theo dõi
            </a>
        @endcan
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
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

        <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-5">
            <div class="text-sm text-gray-500">
                {{ $items->total() }} kết quả · Trang {{ $items->currentPage() }}/{{ max(1, $items->lastPage()) }}
            </div>
            <div class="flex items-center gap-3">
                <button type="button" wire:click="resetFilters"
                    class="inline-flex h-10 items-center rounded-xl border border-gray-300 bg-white px-4 font-semibold text-gray-700 hover:bg-gray-50">
                    Reset
                </button>
                @can('delete_pharma')
                    @if ($this->hasSelected)
                        <span class="rounded-full bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-700">Đã chọn {{ $this->selectedCount }} dòng trên trang hiện tại</span>
                    @endif
                    <button type="button" wire:click="confirmBulkDelete"
                        class="inline-flex h-10 items-center rounded-xl bg-red-600 px-4 font-semibold text-white hover:bg-red-500 disabled:opacity-50"
                        @disabled(!$this->hasSelected)>
                        Xóa đã chọn
                    </button>
                @endcan
            </div>
        </div>
    </div>

    @can('edit_pharma')
        @livewire('shared.import-export.panel', [
            'serviceClass' => \Modules\Pharma\Services\ImportExport::class,
            'title' => 'Import / Export theo dõi nhà cung cấp',
            'description' => 'File Excel chuẩn A–V; các cột công thức được hệ thống tự tính lại.',
            'filters' => ['search' => $search, 'status' => $status],
            'permission' => 'edit_pharma',
        ], key('supplier-tracking-import-export-' . md5(json_encode([$search, $status]))))
    @endcan

    <div wire:loading.class="opacity-60" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-[1600px] divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        @can('delete_pharma')
                            <th class="px-4 py-3 text-left"><input type="checkbox" wire:model.live="selectPage" class="rounded border-gray-300 text-indigo-600"></th>
                        @endcan
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Sản phẩm</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Nhà cung cấp</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Giá nhập</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Giá HĐ</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Chênh lệch HĐ</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">% phí</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Phí CL</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Giá vốn</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Giá bán</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">LN thực tế</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Cam kết</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Hợp đồng</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-500">Trạng thái</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($items as $item)
                        <tr class="hover:bg-gray-50">
                            @can('delete_pharma')
                                <td class="px-4 py-4 align-top"><input type="checkbox" wire:model.live="selectedIds" value="{{ $item->id }}" class="rounded border-gray-300 text-indigo-600"></td>
                            @endcan
                            <td class="px-4 py-4 align-top">
                                <div class="max-w-xs font-semibold text-gray-900">{{ $item->medicine?->name ?? '---' }}</div>
                                <div class="mt-1 text-xs text-gray-500">SĐK: {{ $item->medicine?->registration_number ?? '---' }}</div>
                                <div class="mt-1 text-xs text-gray-500">Ngày làm việc: {{ $item->working_date?->format('d/m/Y') ?? '---' }}</div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="font-medium text-gray-900">{{ $item->supplier_name }}</div>
                                <div class="mt-1 text-xs text-gray-500">Đại diện: {{ $item->supplier_representative ?: '---' }}</div>
                                <div class="mt-1 text-xs text-gray-500">Khu vực: {{ $item->area ?: '---' }}</div>
                            </td>
                            <td class="px-4 py-4 text-right align-top font-medium">{{ $this->money($item->import_price) }}</td>
                            <td class="px-4 py-4 text-right align-top">{{ $this->money($item->invoice_price) }}</td>
                            <td class="px-4 py-4 text-right align-top">{{ $this->money($item->invoice_difference_amount) }}</td>
                            <td class="px-4 py-4 text-right align-top">{{ $this->percent($item->invoice_difference_percent) }}</td>
                            <td class="px-4 py-4 text-right align-top text-amber-700">{{ $this->money($item->invoice_difference_fee) }}</td>
                            <td class="px-4 py-4 text-right align-top font-semibold">{{ $this->money($item->cost_price) }}</td>
                            <td class="px-4 py-4 text-right align-top">{{ $this->money($item->selling_price) }}</td>
                            <td class="px-4 py-4 text-right align-top"><span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">{{ $this->percent($item->gross_profit_percent) }}</span></td>
                            <td class="px-4 py-4 align-top">
                                <div class="text-sm">{{ $item->committed_quantity ? $this->money($item->committed_quantity) : '---' }} {{ $item->unit }}</div>
                                <div class="mt-1 text-xs text-gray-500">Cọc: {{ $item->deposit_amount ? $this->money($item->deposit_amount) : '---' }}</div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="text-xs text-gray-500">{{ $item->start_date?->format('d/m/Y') ?? '---' }} → {{ $item->end_date?->format('d/m/Y') ?? '---' }}</div>
                                @if ($item->contract_url)
                                    <a href="{{ $item->contract_url }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-flex text-xs font-semibold text-indigo-600">Xem hợp đồng</a>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-center align-top"><span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">{{ $statuses[$item->status] ?? $item->status }}</span></td>
                            <td class="px-4 py-4 text-right align-top">
                                <div class="flex justify-end gap-3">
                                    @can('edit_pharma')
                                        <a href="{{ route('admin.pharma.supplier-trackings.edit', $item->id) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">Sửa</a>
                                    @endcan
                                    @can('delete_pharma')
                                        <button type="button" wire:click="delete({{ $item->id }})" wire:confirm="Bạn chắc chắn muốn xóa dòng này?" class="text-sm font-semibold text-red-600 hover:text-red-500">Xóa</button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="15" class="px-4 py-12 text-center text-sm text-gray-500">Không có dữ liệu phù hợp bộ lọc hiện tại.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 px-4 py-4">
            <span class="text-sm text-gray-500">Hiển thị {{ $items->firstItem() ?? 0 }}–{{ $items->lastItem() ?? 0 }} / {{ $items->total() }}</span>
            <div class="flex items-center gap-2">
                <button type="button" wire:click="gotoPage({{ max(1, $items->currentPage() - 1) }})" @disabled($items->onFirstPage()) class="rounded-lg border px-3 py-2 text-sm disabled:opacity-40">Trước</button>
                <span class="px-2 text-sm font-medium">{{ $items->currentPage() }} / {{ max(1, $items->lastPage()) }}</span>
                <button type="button" wire:click="gotoPage({{ min(max(1, $items->lastPage()), $items->currentPage() + 1) }})" @disabled(!$items->hasMorePages()) class="rounded-lg border px-3 py-2 text-sm disabled:opacity-40">Sau</button>
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
