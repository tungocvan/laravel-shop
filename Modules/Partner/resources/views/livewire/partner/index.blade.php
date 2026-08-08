<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">
                Quản lý đối tác
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Quản lý nhà cung cấp, khách hàng, hộ kinh doanh, bệnh viện và các đối tác liên quan.
            </p>
        </div>

        <a href="{{ route('admin.partner.partners.create') }}"
            class="inline-flex h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            + Thêm đối tác
        </a>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
            {{ session('success') }}
        </div>
    @endif
    {{-- Tools --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-base font-semibold text-gray-900">Công cụ dữ liệu</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Import, export hoặc xóa hàng loạt dữ liệu đối tác.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div>
                    <label class="text-sm font-medium text-gray-700">File import</label>
                    <input type="file" wire:model="importFile" accept=".xlsx,.csv,.txt"
                        class="mt-1 block w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm text-gray-900 file:mr-4 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-200">
                    @error('importFile')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="button" wire:click="import" wire:loading.attr="disabled"
                    class="inline-flex h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-70">
                    <span wire:loading.remove wire:target="import">Import</span>
                    <span wire:loading wire:target="import">Đang import...</span>
                </button>

                <button type="button" wire:click="export"
                    class="inline-flex h-11 items-center justify-center rounded-xl border border-gray-300 bg-white px-5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                    Export
                </button>

                <button type="button" wire:click="deleteSelected"
                    wire:confirm="Bạn chắc chắn muốn xóa các đối tác đã chọn?" @disabled(empty($selected))
                    class="inline-flex h-11 items-center justify-center rounded-xl bg-red-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-50">
                    Xóa đã chọn
                </button>
            </div>
        </div>
    </div>
    {{-- Filters --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-6">
            <div class="lg:col-span-2">
                <label class="text-sm font-medium text-gray-700">Tìm kiếm</label>
                <input type="text" wire:model.live.debounce.400ms="search" placeholder="Tên, MST, email, SĐT..."
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">Loại pháp lý</label>
                <select wire:model.live="legalType"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    <option value="">Tất cả</option>
                    @foreach ($legalTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">Vai trò</label>
                <select wire:model.live="partnerType"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    <option value="">Tất cả</option>
                    @foreach ($partnerTypes as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">Trạng thái</label>
                <select wire:model.live="status"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    <option value="">Tất cả</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700">Hiển thị</label>
                <select wire:model.live="perPage"
                    class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500">
                    <option value="10">10 dòng</option>
                    <option value="25">25 dòng</option>
                    <option value="50">50 dòng</option>
                    <option value="100">100 dòng</option>
                    <option value="All">Tất cả</option>
                </select>
            </div>
        </div>

        <div class="mt-4 flex justify-end">
            <button type="button" wire:click="resetFilters"
                class="inline-flex h-11 items-center justify-center rounded-xl border border-gray-300 bg-white px-5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                Xóa bộ lọc
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-12 px-4 py-3 text-left">
                            <input type="checkbox" wire:model.live="selectAll"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        </th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Đối tác</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Phân loại</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Liên hệ</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Trạng thái</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">Thao tác</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 bg-white" wire:loading.class="opacity-50">
                    @forelse ($partners as $partner)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4 align-top">
                                <input type="checkbox" wire:model.live="selected" value="{{ $partner->id }}"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="font-semibold text-gray-900">{{ $partner->name }}</div>
                                <div class="mt-1 text-xs text-gray-500">
                                    MST: {{ $partner->tax_code ?: 'Chưa có' }}
                                </div>
                                <div class="mt-1 max-w-md text-xs text-gray-500">
                                    {{ $partner->address ?: 'Chưa có địa chỉ' }}
                                </div>
                            </td>

                            <td class="px-4 py-4 align-top">
                                <div class="text-gray-900">{{ $partner->legal_type_label }}</div>
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach ($partner->partner_types ?? [] as $type)
                                        <span
                                            class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700">
                                            {{ $partnerTypes[$type] ?? $type }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>

                            <td class="px-4 py-4 align-top">
                                <div class="text-gray-900">{{ $partner->contact_person ?: 'Chưa có' }}</div>
                                <div class="mt-1 text-xs text-gray-500">{{ $partner->phone ?: 'Chưa có SĐT' }}</div>
                                <div class="mt-1 text-xs text-gray-500">{{ $partner->email ?: 'Chưa có email' }}</div>
                            </td>

                            <td class="px-4 py-4 align-top">
                                @if ($partner->status === 'active')
                                    <span
                                        class="rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">
                                        {{ $partner->status_label }}
                                    </span>
                                @elseif ($partner->status === 'pending')
                                    <span
                                        class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                                        {{ $partner->status_label }}
                                    </span>
                                @else
                                    <span
                                        class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                        {{ $partner->status_label }}
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-4 text-right align-top">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.partner.partners.edit', $partner->id) }}"
                                        class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                        Sửa
                                    </a>

                                    <button type="button" wire:click="delete({{ $partner->id }})"
                                        wire:confirm="Bạn chắc chắn muốn xóa đối tác này?"
                                        class="inline-flex h-9 items-center justify-center rounded-lg bg-red-600 px-3 text-xs font-semibold text-white hover:bg-red-500">
                                        Xóa
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <div class="text-sm font-medium text-gray-900">Chưa có dữ liệu đối tác</div>
                                <div class="mt-1 text-sm text-gray-500">Bạn có thể thêm mới đối tác để bắt đầu quản lý.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($perPage !== 'All' && $partners->hasPages())
            <div class="border-t border-gray-200 px-4 py-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                    {{-- Info --}}
                    <div class="text-sm text-gray-600">
                        Hiển thị
                        <span class="font-semibold text-gray-900">{{ $partners->firstItem() }}</span>
                        -
                        <span class="font-semibold text-gray-900">{{ $partners->lastItem() }}</span>
                        trong tổng số
                        <span class="font-semibold text-gray-900">{{ $partners->total() }}</span>
                        dòng
                    </div>

                    {{-- Pagination --}}
                    <div class="flex items-center justify-end gap-1">

                        {{-- Previous --}}
                        @if ($partners->onFirstPage())
                            <span
                                class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-200 bg-gray-100 px-3 text-sm font-medium text-gray-400">
                                Trước
                            </span>
                        @else
                            <button type="button" wire:click="previousPage" wire:loading.attr="disabled"
                                class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                                Trước
                            </button>
                        @endif

                        {{-- Pages --}}
                        @foreach ($partners->getUrlRange(1, $partners->lastPage()) as $page => $url)
                            @if ($page == $partners->currentPage())
                                <span
                                    class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg bg-indigo-600 px-3 text-sm font-semibold text-white">
                                    {{ $page }}
                                </span>
                            @else
                                <button type="button" wire:click="gotoPage({{ $page }})"
                                    wire:loading.attr="disabled"
                                    class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                                    {{ $page }}
                                </button>
                            @endif
                        @endforeach

                        {{-- Next --}}
                        @if ($partners->hasMorePages())
                            <button type="button" wire:click="nextPage" wire:loading.attr="disabled"
                                class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50">
                                Sau
                            </button>
                        @else
                            <span
                                class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-200 bg-gray-100 px-3 text-sm font-medium text-gray-400">
                                Sau
                            </span>
                        @endif

                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
