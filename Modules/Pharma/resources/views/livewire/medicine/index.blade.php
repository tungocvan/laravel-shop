<div class="space-y-6">
    @php
        $admin = auth('admin')->user();
        $canCreate = $admin?->can('create_pharma') ?? false;
        $canEdit = $admin?->can('edit_pharma') ?? false;
        $canDelete = $admin?->can('delete_pharma') ?? false;
    @endphp

    <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Pharma · Medicine</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Hồ sơ thuốc / HSSP</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Tra cứu và quản lý hồ sơ sản phẩm với phân trang giới hạn, chọn theo trang và thao tác theo quyền.</p>
        </div>

        @if ($canCreate)
            <a href="{{ route('admin.pharma.hssp.create') }}"
               class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Thêm thuốc / HSSP
            </a>
        @endif
    </header>

    @if (session()->has('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    @if (session()->has('error'))
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
    @endif

    @if ($canEdit)
        @livewire('shared.import-export.panel', [
            'serviceClass' => \Modules\Pharma\Services\MedicineImportExport::class,
            'title' => 'Import / Export hồ sơ thuốc',
            'description' => 'Dùng file Excel chuẩn A–U; dữ liệu rỗng không ghi đè giá trị hiện có.',
            'permission' => 'edit_pharma',
            'filters' => [
                'search' => $search,
                'circular_group' => $filterCircularGroup,
                'is_special_control' => $filterSpecialControl === '' ? null : $filterSpecialControl === 'yes',
            ],
        ], key('medicine-import-export-' . md5(json_encode([$search, $filterCircularGroup, $filterSpecialControl]))))
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-labelledby="medicine-filters-heading">
        <div class="flex flex-col gap-4">
            <div>
                <h2 id="medicine-filters-heading" class="text-base font-semibold text-slate-900">Bộ lọc</h2>
                <p class="mt-1 text-sm text-slate-500">Thay đổi bộ lọc hoặc trang sẽ tự động bỏ lựa chọn hiện tại.</p>
            </div>

            <div class="grid gap-4 lg:grid-cols-12">
                <div class="lg:col-span-5">
                    <label for="medicine-search" class="block text-sm font-medium text-slate-700">Tìm kiếm</label>
                    <input id="medicine-search" type="search" wire:model.live.debounce.300ms="search"
                           placeholder="Tên thuốc hoặc hoạt chất..."
                           class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div class="lg:col-span-3">
                    <label for="medicine-group" class="block text-sm font-medium text-slate-700">Nhóm Thông tư</label>
                    <select id="medicine-group" wire:model.live="filterCircularGroup"
                            class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Tất cả nhóm</option>
                        @foreach ($circularGroups as $group)
                            <option value="{{ $group }}">{{ $group }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label for="medicine-special" class="block text-sm font-medium text-slate-700">Kiểm soát</label>
                    <select id="medicine-special" wire:model.live="filterSpecialControl"
                            class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Tất cả</option>
                        <option value="yes">KSĐB</option>
                        <option value="no">Thuốc thường</option>
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label for="medicine-per-page" class="block text-sm font-medium text-slate-700">Mỗi trang</label>
                    <select id="medicine-per-page" wire:model.live="perPage"
                            class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }} bản ghi</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex justify-end border-t border-slate-100 pt-4">
                <button type="button" wire:click="resetFilters" wire:loading.attr="disabled"
                        class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-wait disabled:opacity-60">
                    Xóa bộ lọc
                </button>
            </div>
        </div>
    </section>

    @if ($canDelete && $selectedIds !== [])
        <section class="flex flex-col gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between" aria-live="polite">
            <p class="text-sm font-medium text-rose-900">Đã chọn <strong>{{ count($selectedIds) }}</strong> bản ghi trên trang hiện tại.</p>
            <button type="button"
                    wire:click="deleteSelected"
                    wire:confirm="Xóa vĩnh viễn các hồ sơ thuốc đã chọn trên trang hiện tại?"
                    wire:loading.attr="disabled"
                    wire:target="deleteSelected"
                    class="inline-flex min-h-10 items-center justify-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700 disabled:cursor-wait disabled:opacity-60">
                <span wire:loading.remove wire:target="deleteSelected">Xóa các mục đã chọn</span>
                <span wire:loading wire:target="deleteSelected">Đang xóa...</span>
            </button>
        </section>
    @endif

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="medicine-table-heading">
        <div class="flex flex-col gap-2 border-b border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div>
                <h2 id="medicine-table-heading" class="font-semibold text-slate-950">Danh sách hồ sơ thuốc</h2>
                <p class="mt-1 text-xs text-slate-500">{{ number_format($medicines->total()) }} bản ghi · Trang {{ $medicines->currentPage() }}/{{ max(1, $medicines->lastPage()) }}</p>
            </div>
            <div wire:loading wire:target="search,filterCircularGroup,filterSpecialControl,perPage,gotoPage" class="text-sm font-medium text-indigo-600">Đang tải dữ liệu...</div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                        @if ($canDelete)
                            <th scope="col" class="w-12 px-4 py-3 text-center">
                                <input type="checkbox" wire:model.live="selectPage" aria-label="Chọn tất cả bản ghi trên trang hiện tại"
                                       class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            </th>
                        @endif
                        <th scope="col" class="w-16 px-4 py-3 text-center">STT</th>
                        <th scope="col" class="px-4 py-3">Thuốc / HSSP</th>
                        <th scope="col" class="px-4 py-3">Hoạt chất</th>
                        <th scope="col" class="px-4 py-3">Số đăng ký</th>
                        <th scope="col" class="px-4 py-3">Nhà sản xuất</th>
                        <th scope="col" class="px-4 py-3">Hồ sơ</th>
                        @if ($canEdit || $canDelete)
                            <th scope="col" class="w-28 px-4 py-3 text-right">Thao tác</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($medicines as $index => $medicine)
                        <tr wire:key="medicine-{{ $medicine->id }}" class="transition hover:bg-slate-50 {{ in_array((string) $medicine->id, $selectedIds, true) ? 'bg-indigo-50/60' : '' }}">
                            @if ($canDelete)
                                <td class="px-4 py-4 text-center align-top">
                                    <input type="checkbox" wire:model.live="selectedIds" value="{{ $medicine->id }}" aria-label="Chọn {{ $medicine->name }}"
                                           class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                </td>
                            @endif
                            <td class="px-4 py-4 text-center align-top font-medium text-slate-500">{{ ($medicines->currentPage() - 1) * $medicines->perPage() + $index + 1 }}</td>
                            <td class="min-w-64 px-4 py-4 align-top">
                                <div class="font-semibold text-slate-950">{{ $medicine->name }}</div>
                                <div class="mt-1 text-xs leading-5 text-slate-500">{{ $medicine->dosage_form ?: '—' }} · {{ $medicine->packaging_specification ?: '—' }}</div>
                                @if ($medicine->is_special_control)
                                    <span class="mt-2 inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700">KSĐB</span>
                                @endif
                            </td>
                            <td class="min-w-56 px-4 py-4 align-top">
                                <div class="font-medium text-slate-800">{{ $medicine->active_ingredients ?: '—' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $medicine->concentration ?: '—' }}</div>
                            </td>
                            <td class="px-4 py-4 align-top"><span class="inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-700">{{ $medicine->registration_number ?: '—' }}</span></td>
                            <td class="min-w-56 px-4 py-4 align-top">
                                <div class="font-medium text-slate-800">{{ $medicine->manufacturing_country ?: '—' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $medicine->manufacturing_company ?: '—' }}</div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                @if ($medicine->profile_link)
                                    <a href="{{ $medicine->profile_link }}" target="_blank" rel="noopener noreferrer" class="text-sm font-semibold text-indigo-700 hover:underline">Mở tài liệu</a>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            @if ($canEdit || $canDelete)
                                <td class="px-4 py-4 text-right align-top">
                                    <div class="inline-flex items-center gap-2">
                                        @if ($canEdit)
                                            <a href="{{ route('admin.pharma.hssp.edit', $medicine->id) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Sửa</a>
                                        @endif
                                        @if ($canDelete)
                                            <button type="button" wire:click="deleteMedicine({{ $medicine->id }})"
                                                    wire:confirm="Xóa vĩnh viễn hồ sơ thuốc này?"
                                                    wire:loading.attr="disabled"
                                                    wire:target="deleteMedicine({{ $medicine->id }})"
                                                    class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50 disabled:opacity-60">
                                                Xóa
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 6 + ($canDelete ? 1 : 0) + (($canEdit || $canDelete) ? 1 : 0) }}" class="px-6 py-12 text-center text-sm text-slate-500">Không có hồ sơ thuốc phù hợp với bộ lọc hiện tại.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($medicines->lastPage() > 1)
            <nav class="flex flex-col gap-3 border-t border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5" aria-label="Phân trang hồ sơ thuốc">
                <p class="text-sm text-slate-500">Hiển thị {{ $medicines->firstItem() }}–{{ $medicines->lastItem() }} / {{ $medicines->total() }}</p>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="gotoPage({{ max(1, $medicines->currentPage() - 1) }})" @disabled($medicines->onFirstPage())
                            class="min-h-10 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">Trước</button>
                    <span class="min-w-24 text-center text-sm font-medium text-slate-700">{{ $medicines->currentPage() }} / {{ $medicines->lastPage() }}</span>
                    <button type="button" wire:click="gotoPage({{ min($medicines->lastPage(), $medicines->currentPage() + 1) }})" @disabled(! $medicines->hasMorePages())
                            class="min-h-10 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">Sau</button>
                </div>
            </nav>
        @endif
    </section>
</div>
