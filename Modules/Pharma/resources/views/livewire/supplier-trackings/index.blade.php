<div class="mx-auto max-w-7xl space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">
                Theo dõi nhà cung cấp
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Quản lý giá nhập, giá hóa đơn, phí chênh lệch, giá vốn, lợi nhuận và hợp đồng NCC.
            </p>
        </div>

        <a href="{{ route('admin.pharma.supplier-trackings.create') }}"
            class="inline-flex h-[50px] items-center justify-center rounded-xl bg-indigo-600 px-5 font-semibold text-white shadow-sm hover:bg-indigo-500">
            + Thêm theo dõi
        </a>
    </div>

    {{-- ALERT --}}
    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- FILTER --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-12">

            <div class="lg:col-span-4">
                <label class="text-sm font-medium text-gray-700">Tìm kiếm</label>
                <input type="text" wire:model.live.debounce.400ms="search"
                    placeholder="Tên thuốc, SĐK, NCC, đại diện, khu vực..."
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>

            <div class="lg:col-span-2">
                <label class="text-sm font-medium text-gray-700">Trạng thái</label>
                <select wire:model.live="status"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    <option value="">Tất cả</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <label class="text-sm font-medium text-gray-700">Hiển thị</label>
                <select wire:model.live="perPage"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    <option value="10">10 dòng</option>
                    <option value="15">15 dòng</option>
                    <option value="25">25 dòng</option>
                    <option value="50">50 dòng</option>
                    <option value="100">100 dòng</option>
                </select>
            </div>

            <div class="flex items-end gap-3 lg:col-span-4">
                <button type="button" wire:click="resetFilters"
                    class="inline-flex h-[50px] items-center justify-center rounded-xl border border-gray-300 bg-white px-4 font-semibold text-gray-700 hover:bg-gray-50">
                    Reset
                </button>

            </div>
        </div>

        <div
            class="mt-5 flex items-center justify-end gap-3 border-t border-gray-100 pt-5">
            <div class="flex items-center gap-3">
                @if ($this->hasSelected)
                    <span class="rounded-full bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-700">
                        Đã chọn {{ $this->selectedCount }} dòng
                    </span>
                @endif

                <button type="button" wire:click="deleteSelected"
                    wire:confirm="Bạn chắc chắn muốn xóa các dòng đã chọn?"
                    class="inline-flex h-[50px] items-center justify-center rounded-xl bg-red-600 px-5 font-semibold text-white shadow-sm hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-50"
                    @disabled(!$this->hasSelected)>
                    Xóa đã chọn
                </button>
            </div>
        </div>
    </div>

    @livewire('shared.import-export.panel', [
        'serviceClass' => \Modules\Pharma\Services\ImportExport::class,
        'title' => 'Import / Export theo dõi nhà cung cấp',
        'description' => 'File Excel chuẩn A–V; các cột công thức được hệ thống tự tính lại.',
        'filters' => ['search' => $search, 'status' => $status],
    ], key('supplier-tracking-import-export-' . md5(json_encode([$search, $status]))))

    {{-- TABLE --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-[1600px] divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">
                            <input type="checkbox" wire:model.live="selectAll"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        </th>

                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Sản phẩm</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500">Nhà cung cấp</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Giá nhập</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Giá HĐ</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500">Chênh lệch HĐ
                        </th>
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
                            <td class="px-4 py-4 align-top">
                                <input type="checkbox" wire:model.live="selected" value="{{ $item->id }}"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </td>

                            <td class="px-4 py-4 align-top">
                                <div class="max-w-xs">
                                    <div class="font-semibold text-gray-900">
                                        {{ $item->medicine?->name ?? '---' }}
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500">
                                        SĐK: {{ $item->medicine?->registration_number ?? '---' }}
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500">
                                        Ngày làm việc:
                                        {{ $item->working_date ? $item->working_date->format('d/m/Y') : '---' }}
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-4 align-top">
                                <div class="font-medium text-gray-900">
                                    {{ $item->supplier_name }}
                                </div>
                                <div class="mt-1 text-xs text-gray-500">
                                    Đại diện: {{ $item->supplier_representative ?: '---' }}
                                </div>
                                <div class="mt-1 text-xs text-gray-500">
                                    Khu vực: {{ $item->area ?: '---' }}
                                </div>
                            </td>

                            <td class="px-4 py-4 text-right align-top font-medium text-gray-900">
                                {{ $this->money($item->import_price) }}
                            </td>

                            <td class="px-4 py-4 text-right align-top">
                                {{ $this->money($item->invoice_price) }}
                            </td>

                            <td class="px-4 py-4 text-right align-top">
                                {{ $this->money($item->invoice_difference_amount) }}
                            </td>

                            <td class="px-4 py-4 text-right align-top">
                                {{ $this->percent($item->invoice_difference_percent) }}
                            </td>

                            <td class="px-4 py-4 text-right align-top text-amber-700">
                                {{ $this->money($item->invoice_difference_fee) }}
                            </td>

                            <td class="px-4 py-4 text-right align-top font-semibold text-gray-900">
                                {{ $this->money($item->cost_price) }}
                            </td>

                            <td class="px-4 py-4 text-right align-top">
                                {{ $this->money($item->selling_price) }}
                            </td>

                            <td class="px-4 py-4 text-right align-top">
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-semibold
                                    {{ $item->gross_profit_percent >= 30 ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-700' }}">
                                    {{ $this->percent($item->gross_profit_percent) }}
                                </span>
                            </td>

                            <td class="px-4 py-4 align-top">
                                <div class="text-sm text-gray-900">
                                    {{ $item->committed_quantity ? $this->money($item->committed_quantity) : '---' }}
                                    {{ $item->unit }}
                                </div>
                                <div class="mt-1 text-xs text-gray-500">
                                    Cọc: {{ $item->deposit_amount ? $this->money($item->deposit_amount) : '---' }}
                                </div>
                            </td>

                            <td class="px-4 py-4 align-top">
                                <div class="text-xs text-gray-500">
                                    {{ $item->start_date ? $item->start_date->format('d/m/Y') : '---' }}
                                    →
                                    {{ $item->end_date ? $item->end_date->format('d/m/Y') : '---' }}
                                </div>

                                @if ($item->contract_url)
                                    <a href="{{ $item->contract_url }}" target="_blank"
                                        class="mt-1 inline-flex text-xs font-semibold text-indigo-600 hover:text-indigo-500">
                                        Xem hợp đồng
                                    </a>
                                @else
                                    <div class="mt-1 text-xs text-gray-400">Chưa có URL</div>
                                @endif
                            </td>

                            <td class="px-4 py-4 text-center align-top">
                                @php
                                    $statusClasses = [
                                        'active' => 'bg-blue-50 text-blue-700',
                                        'completed' => 'bg-green-50 text-green-700',
                                        'paused' => 'bg-yellow-50 text-yellow-700',
                                        'cancelled' => 'bg-red-50 text-red-700',
                                    ];
                                @endphp

                                <span
                                    class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$item->status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $statuses[$item->status] ?? $item->status }}
                                </span>
                            </td>

                            <td class="px-4 py-4 text-right align-top">
                                <div class="flex justify-end gap-3">
                                    <a href="{{ route('admin.pharma.supplier-trackings.edit', $item->id) }}"
                                        class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                                        Sửa
                                    </a>

                                    <button type="button" wire:click="delete({{ $item->id }})"
                                        wire:confirm="Bạn chắc chắn muốn xóa dòng này?"
                                        class="text-sm font-semibold text-red-600 hover:text-red-500">
                                        Xóa
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="px-4 py-12 text-center">
                                <div class="text-sm font-medium text-gray-900">
                                    Chưa có dữ liệu theo dõi nhà cung cấp
                                </div>
                                <div class="mt-1 text-sm text-gray-500">
                                    Hãy thêm mới hoặc import Excel để bắt đầu quản lý.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 px-4 py-4">
            {{ $items->links() }}
        </div>
    </div>
</div>
