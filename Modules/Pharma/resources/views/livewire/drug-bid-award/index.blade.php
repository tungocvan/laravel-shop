@php
    $admin = auth('admin')->user();
    $canCreate = $admin?->can('create_pharma') ?? false;
    $canEdit = $admin?->can('edit_pharma') ?? false;
    $canDelete = $admin?->can('delete_pharma') ?? false;
    $canSelect = $canEdit || $canDelete;
    $currentPage = $awards->currentPage();
    $lastPage = $awards->lastPage();
    $startPage = max(1, $currentPage - 2);
    $endPage = min($lastPage, $currentPage + 2);
@endphp

<div class="mx-auto max-w-7xl space-y-6 p-4 sm:p-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Kết quả trúng thầu</h1>
            <p class="mt-1 text-sm text-gray-500">Workspace Pharma cho kết quả trúng thầu thủ công và dữ liệu nguồn Mua sắm công trong tương lai.</p>
        </div>
        @if ($canCreate)
            <a href="{{ route('admin.pharma.drug-bid-awards.create') }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">Thêm hồ sơ mới</a>
        @endif
    </div>

    @if (session()->has('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700" role="status">{{ session('success') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700" role="alert">{{ session('error') }}</div>
    @endif

    @if ($canEdit)
        @livewire('shared.import-export.panel', [
            'serviceClass' => \Modules\Pharma\Services\DrugBidAwardImportExport::class,
            'title' => 'Import / Export kết quả trúng thầu',
            'description' => 'Dùng file chuẩn của Pharma; dữ liệu rỗng không ghi đè giá trị hiện có.',
            'permission' => 'edit_pharma',
            'filters' => [
                'search' => $search,
                'investor' => $filterInvestor,
                'company' => $filterCompany,
                'source' => $filterSource,
                'selected_ids' => $selectedIds,
            ],
        ], key('drug-bid-award-import-export-' . md5(json_encode([$search, $filterInvestor, $filterCompany, $filterSource]))))
    @endif

    <div class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
        <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <label for="award-search" class="block text-sm font-medium text-gray-700">Tìm kiếm</label>
                <input id="award-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Tên thuốc, mã mời thầu, số quyết định..." class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
            </div>
            <div>
                <label for="filter-investor" class="block text-sm font-medium text-gray-700">Chủ đầu tư</label>
                <input id="filter-investor" type="search" wire:model.live.debounce.300ms="filterInvestor" placeholder="Nhập một phần tên chủ đầu tư..." class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
            </div>
            <div>
                <label for="filter-company" class="block text-sm font-medium text-gray-700">Nhà thầu</label>
                <input id="filter-company" type="search" wire:model.live.debounce.300ms="filterCompany" placeholder="Nhập một phần tên nhà thầu..." class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
            </div>
            <div>
                <label for="filter-source" class="block text-sm font-medium text-gray-700">Nguồn dữ liệu</label>
                <select id="filter-source" wire:model.live="filterSource" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">Tất cả nguồn</option>
                    @foreach ($sourceOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="flex flex-col gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="w-full sm:w-56">
                <label for="award-per-page" class="sr-only">Số bản ghi mỗi trang</label>
                <select id="award-per-page" wire:model.live="perPage" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    @foreach ($perPageOptions as $option)
                        <option value="{{ $option }}">{{ $option }} bản ghi / trang</option>
                    @endforeach
                </select>
            </div>
            <button type="button" wire:click="resetFilters" wire:loading.attr="disabled" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:opacity-60">Xóa bộ lọc</button>
        </div>
    </div>

    @if ($canDelete && $selectedIds !== [])
        <div class="flex flex-col gap-3 rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div class="text-sm font-medium text-indigo-900">Đã chọn <strong>{{ count($selectedIds) }}</strong> hồ sơ trên trang hiện tại.</div>
            <button type="button" wire:click="confirmBulkDelete" class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100">Xóa mục đã chọn</button>
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm" wire:loading.class="opacity-60" wire:target="search,filterInvestor,filterCompany,filterSource,perPage,gotoPage,deleteAward,deleteSelected">
        <div class="overflow-x-auto">
            <table class="min-w-[1100px] w-full border-collapse whitespace-nowrap text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50/75 text-xs font-semibold uppercase tracking-wider text-gray-600">
                        @if ($canSelect)
                            <th class="w-12 px-4 py-4 text-center"><input type="checkbox" wire:model.live="selectPage" aria-label="Chọn tất cả hồ sơ trên trang hiện tại" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"></th>
                        @endif
                        <th class="px-4 py-4">Thuốc trúng thầu</th><th class="px-4 py-4">Khối lượng & giá</th><th class="px-4 py-4">Chủ đầu tư</th><th class="px-4 py-4">Pháp lý</th><th class="px-4 py-4">Nhà thầu</th><th class="px-4 py-4">Nguồn</th>
                        @if ($canEdit || $canDelete)<th class="px-4 py-4 text-right">Hành động</th>@endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse ($awards as $award)
                        <tr class="hover:bg-gray-50/60 {{ in_array((string) $award->id, array_map('strval', $selectedIds), true) ? 'bg-indigo-50/40' : '' }}">
                            @if ($canSelect)<td class="px-4 py-4 text-center"><input type="checkbox" wire:model.live="selectedIds" value="{{ $award->id }}" aria-label="Chọn {{ $award->medicine_name }}" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"></td>@endif
                            <td class="px-4 py-4"><div class="font-semibold text-gray-900">{{ $award->medicine_name }}</div><div class="mt-1 text-xs text-gray-500">{{ $award->packaging_specification }}</div>@if ($award->medicine_id)<div class="mt-1 text-xs text-emerald-700">Đã liên kết HSSP #{{ $award->medicine_id }}</div>@else<div class="mt-1 text-xs text-amber-700">Chưa đối soát HSSP</div>@endif</td>
                            <td class="px-4 py-4"><div class="font-medium">{{ number_format($award->quantity) }} đơn vị</div><div class="mt-1 text-xs text-indigo-700">{{ number_format((float) $award->unit_price, 0, ',', '.') }} VNĐ</div></td>
                            <td class="px-4 py-4"><div class="max-w-xs truncate font-medium" title="{{ $award->investor_name }}">{{ $award->investor_name }}</div><div class="mt-1 font-mono text-xs text-gray-500">{{ $award->bidding_notice_code }}</div></td>
                            <td class="px-4 py-4"><div class="font-medium">QĐ: {{ $award->decision_number }}</div><div class="mt-1 text-xs text-gray-500">{{ $award->decision_date?->format('d/m/Y') }} · {{ $award->contract_duration_months }} tháng</div></td>
                            <td class="px-4 py-4"><div class="max-w-xs truncate font-medium" title="{{ $award->winning_company_name }}">{{ $award->winning_company_name }}</div>@if ($award->decision_document_url)<a href="{{ $award->decision_document_url }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-block text-xs text-indigo-600 hover:underline">Mở văn bản</a>@endif</td>
                            <td class="px-4 py-4">@if ($award->source_type === \Modules\Pharma\Models\DrugBidAward::SOURCE_MUASAMCONG)<span class="inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-200">Mua sắm công</span>@else<span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 ring-1 ring-gray-200">Thủ công</span>@endif</td>
                            @if ($canEdit || $canDelete)<td class="px-4 py-4 text-right"><div class="inline-flex items-center gap-2">@if ($canEdit)<a href="{{ route('admin.pharma.drug-bid-awards.edit', $award->id) }}" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Sửa</a>@endif @if ($canDelete)<button type="button" wire:click="deleteAward({{ $award->id }})" wire:confirm="Xóa vĩnh viễn hồ sơ trúng thầu này?" wire:loading.attr="disabled" class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50 disabled:opacity-60">Xóa</button>@endif</div></td>@endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ 6 + ($canSelect ? 1 : 0) + (($canEdit || $canDelete) ? 1 : 0) }}" class="px-6 py-12 text-center text-gray-500">Không có kết quả phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-3 border-t border-gray-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div class="text-xs font-medium text-gray-500">@if ($awards->total())Hiển thị {{ $awards->firstItem() }}–{{ $awards->lastItem() }} / {{ $awards->total() }} bản ghi@else 0 bản ghi @endif</div>
            @if ($awards->hasPages())
                <nav class="flex flex-wrap items-center justify-end gap-2" aria-label="Phân trang kết quả trúng thầu">
                    <button type="button" wire:click="gotoPage({{ max(1, $currentPage - 1) }})" wire:loading.attr="disabled" @disabled($awards->onFirstPage()) class="min-h-10 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">Trước</button>
                    @if ($startPage > 1)
                        <button type="button" wire:click="gotoPage(1)" class="min-h-10 min-w-10 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">1</button>
                        @if ($startPage > 2)<span class="px-1 text-sm text-slate-400" aria-hidden="true">…</span>@endif
                    @endif
                    @for ($page = $startPage; $page <= $endPage; $page++)
                        <button type="button" wire:click="gotoPage({{ $page }})" @if ($page === $currentPage) aria-current="page" @endif class="min-h-10 min-w-10 rounded-xl border px-3 py-2 text-sm font-semibold transition {{ $page === $currentPage ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">{{ $page }}</button>
                    @endfor
                    @if ($endPage < $lastPage)
                        @if ($endPage < $lastPage - 1)<span class="px-1 text-sm text-slate-400" aria-hidden="true">…</span>@endif
                        <button type="button" wire:click="gotoPage({{ $lastPage }})" class="min-h-10 min-w-10 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ $lastPage }}</button>
                    @endif
                    <button type="button" wire:click="gotoPage({{ min($lastPage, $currentPage + 1) }})" wire:loading.attr="disabled" @disabled(! $awards->hasMorePages()) class="min-h-10 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">Tiếp</button>
                </nav>
            @endif
        </div>
    </div>

    @if ($showBulkDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="bulk-delete-title">
            <button type="button" wire:click="cancelBulkDelete" class="absolute inset-0 bg-gray-900/50" aria-label="Đóng hộp thoại xác nhận"></button>
            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h2 id="bulk-delete-title" class="text-lg font-bold text-gray-900">Xóa {{ count($selectedIds) }} hồ sơ?</h2>
                <p class="mt-2 text-sm text-gray-600">Chỉ các hồ sơ đang được chọn trên <strong>trang hiện tại</strong> sẽ bị xóa. Hành động này không thể hoàn tác.</p>
                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><button type="button" wire:click="cancelBulkDelete" class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700">Hủy</button><button type="button" wire:click="deleteSelected" wire:loading.attr="disabled" wire:target="deleteSelected" class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-60"><span wire:loading.remove wire:target="deleteSelected">Xóa {{ count($selectedIds) }} hồ sơ</span><span wire:loading wire:target="deleteSelected">Đang xóa...</span></button></div>
            </div>
        </div>
    @endif
</div>