@php
    $isHospitalScope = $legalType === 'hospital';
    $createParams = $isHospitalScope ? ['legal_type' => 'hospital'] : [];
    $selectedCount = count($selected);
@endphp

<div class="w-full space-y-5 px-4 py-5 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">
                    {{ $isHospitalScope ? 'Quản lý bệnh viện' : 'Quản lý đối tác' }}
                </h1>
                @if ($isHospitalScope)
                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                        Phạm vi: Bệnh viện
                    </span>
                @endif
            </div>
            <p class="mt-1 max-w-3xl text-sm text-gray-500">
                {{ $isHospitalScope
                    ? 'Quản lý danh mục bệnh viện/cơ sở y tế dùng cho phân bổ thuốc và các nghiệp vụ liên quan.'
                    : 'Quản lý nhà cung cấp, khách hàng, hộ kinh doanh, bệnh viện và các đối tác liên quan.' }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($isHospitalScope)
                <a href="{{ route('admin.partner.partners.index') }}"
                    class="inline-flex h-10 items-center justify-center rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-gray-400 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    Tất cả đối tác
                </a>
            @endif

            <a href="{{ route('admin.partner.partners.create', $createParams) }}"
                class="inline-flex h-10 items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                + {{ $isHospitalScope ? 'Thêm bệnh viện' : 'Thêm đối tác' }}
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0 flex-1">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <div class="md:col-span-2 xl:col-span-1">
                        <label class="text-sm font-medium text-gray-700">Tìm kiếm</label>
                        <input type="text" wire:model.live.debounce.400ms="search"
                            placeholder="Tên, MST, email, SĐT..."
                            class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Loại pháp lý</label>
                        <select wire:model.live="legalType"
                            class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                            <option value="">Tất cả</option>
                            @foreach ($legalTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Vai trò</label>
                        <select wire:model.live="partnerType"
                            class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                            <option value="">Tất cả</option>
                            @foreach ($partnerTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Trạng thái</label>
                        <select wire:model.live="status"
                            class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                            <option value="">Tất cả</option>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-700">Hiển thị</label>
                        <select wire:model.live="perPage"
                            class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                            <option value="10">10 dòng</option>
                            <option value="25">25 dòng</option>
                            <option value="50">50 dòng</option>
                            <option value="100">100 dòng</option>
                        </select>
                    </div>
                </div>
            </div>

            <button type="button" wire:click="resetFilters"
                class="inline-flex h-10 shrink-0 items-center justify-center rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-gray-400 hover:bg-gray-50">
                Xóa bộ lọc
            </button>
        </div>
    </div>

    <details class="group rounded-2xl border border-gray-200 bg-white shadow-sm">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-sm font-semibold text-gray-900">Công cụ dữ liệu</h2>
                    @if ($selectedCount > 0)
                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                            Đã chọn {{ $selectedCount }}
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    Import, tải file mẫu, export và thao tác hàng loạt. Chỉ name + legal_type là bắt buộc khi import.
                </p>
            </div>
            <span class="text-sm font-semibold text-indigo-600 group-open:hidden">Mở công cụ</span>
            <span class="hidden text-sm font-semibold text-indigo-600 group-open:inline">Thu gọn</span>
        </summary>

        <div class="border-t border-gray-200 px-5 py-5">
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-end">
                <div>
                    <label class="text-sm font-medium text-gray-700">File import</label>
                    <input type="file" wire:model="importFile" accept=".xlsx,.csv,.txt"
                        class="mt-1 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 file:mr-4 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-200 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    @error('importFile')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="downloadTemplate" wire:loading.attr="disabled"
                        class="inline-flex h-10 items-center justify-center rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60">
                        <span wire:loading.remove wire:target="downloadTemplate">Tải file mẫu</span>
                        <span wire:loading wire:target="downloadTemplate">Đang tạo...</span>
                    </button>

                    <button type="button" wire:click="import" wire:loading.attr="disabled"
                        class="inline-flex h-10 items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60">
                        <span wire:loading.remove wire:target="import">Import</span>
                        <span wire:loading wire:target="import">Đang import...</span>
                    </button>

                    <button type="button" wire:click="export" wire:loading.attr="disabled"
                        class="inline-flex h-10 items-center justify-center rounded-xl border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60">
                        <span wire:loading.remove wire:target="export">
                            {{ $selectedCount > 0 ? 'Export đã chọn' : 'Export theo bộ lọc' }}
                        </span>
                        <span wire:loading wire:target="export">Đang export...</span>
                    </button>

                    <button type="button" wire:click="deleteSelected"
                        wire:confirm="Bạn chắc chắn muốn xóa {{ $selectedCount }} đối tác đã chọn?"
                        wire:loading.attr="disabled" @disabled($selectedCount === 0)
                        class="inline-flex h-10 items-center justify-center rounded-xl bg-red-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-40">
                        Xóa đã chọn
                    </button>
                </div>
            </div>
        </div>
    </details>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-semibold text-gray-900">Danh sách {{ $isHospitalScope ? 'bệnh viện' : 'đối tác' }}</h2>
                <p class="mt-1 text-xs text-gray-500">Checkbox đầu bảng chỉ chọn các dòng trên trang hiện tại.</p>
            </div>
            <div class="text-sm text-gray-600">
                Tổng: <span class="font-semibold text-gray-900">{{ $partners->total() }}</span>
            </div>
        </div>

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
                        <tr class="transition hover:bg-gray-50">
                            <td class="px-4 py-4 align-top">
                                <input type="checkbox" wire:model.live="selected" value="{{ $partner->id }}"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            </td>

                            <td class="px-4 py-4 align-top">
                                <div class="font-semibold text-gray-900">{{ $partner->name }}</div>
                                <div class="mt-1 text-xs text-gray-500">MST: {{ $partner->tax_code ?: '—' }}</div>
                                <div class="mt-1 max-w-xl text-xs text-gray-500">{{ $partner->address ?: 'Chưa có địa chỉ' }}</div>
                            </td>

                            <td class="px-4 py-4 align-top">
                                <div class="font-medium text-gray-900">{{ $partner->legal_type_label }}</div>
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @forelse ($partner->partner_types ?? [] as $type)
                                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700">
                                            {{ $partnerTypes[$type] ?? $type }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-400">Chưa phân vai trò</span>
                                    @endforelse
                                </div>
                            </td>

                            <td class="px-4 py-4 align-top">
                                <div class="font-medium text-gray-900">{{ $partner->contact_person ?: '—' }}</div>
                                <div class="mt-1 text-xs text-gray-500">{{ $partner->phone ?: 'Chưa có SĐT' }}</div>
                                <div class="mt-1 text-xs text-gray-500">{{ $partner->email ?: 'Chưa có email' }}</div>
                            </td>

                            <td class="px-4 py-4 align-top">
                                @if ($partner->status === 'active')
                                    <span class="rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">{{ $partner->status_label }}</span>
                                @elseif ($partner->status === 'pending')
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">{{ $partner->status_label }}</span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">{{ $partner->status_label }}</span>
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
                            <td colspan="6" class="px-4 py-14 text-center">
                                <div class="text-sm font-semibold text-gray-900">
                                    Chưa có {{ $isHospitalScope ? 'bệnh viện' : 'đối tác' }} phù hợp
                                </div>
                                <div class="mt-1 text-sm text-gray-500">
                                    Hãy thêm mới hoặc điều chỉnh bộ lọc để tiếp tục.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($partners->hasPages())
            <div class="border-t border-gray-200 px-4 py-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm text-gray-600">
                        Hiển thị <span class="font-semibold text-gray-900">{{ $partners->firstItem() }}</span>
                        - <span class="font-semibold text-gray-900">{{ $partners->lastItem() }}</span>
                        trong <span class="font-semibold text-gray-900">{{ $partners->total() }}</span> dòng
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-1">
                        @if ($partners->onFirstPage())
                            <span class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-400">Trước</span>
                        @else
                            <button type="button" wire:click="previousPage" wire:loading.attr="disabled"
                                class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 disabled:opacity-50">Trước</button>
                        @endif

                        @php
                            $startPage = max(1, $partners->currentPage() - 2);
                            $endPage = min($partners->lastPage(), $partners->currentPage() + 2);
                        @endphp

                        @if ($startPage > 1)
                            <button type="button" wire:click="gotoPage(1)"
                                class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">1</button>
                            @if ($startPage > 2)
                                <span class="px-1 text-gray-400">…</span>
                            @endif
                        @endif

                        @for ($page = $startPage; $page <= $endPage; $page++)
                            @if ($page === $partners->currentPage())
                                <span class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-indigo-600 bg-indigo-600 px-3 text-sm font-semibold text-white">{{ $page }}</span>
                            @else
                                <button type="button" wire:click="gotoPage({{ $page }})"
                                    class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">{{ $page }}</button>
                            @endif
                        @endfor

                        @if ($endPage < $partners->lastPage())
                            @if ($endPage < $partners->lastPage() - 1)
                                <span class="px-1 text-gray-400">…</span>
                            @endif
                            <button type="button" wire:click="gotoPage({{ $partners->lastPage() }})"
                                class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">{{ $partners->lastPage() }}</button>
                        @endif

                        @if ($partners->hasMorePages())
                            <button type="button" wire:click="nextPage" wire:loading.attr="disabled"
                                class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 disabled:opacity-50">Sau</button>
                        @else
                            <span class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-400">Sau</span>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
