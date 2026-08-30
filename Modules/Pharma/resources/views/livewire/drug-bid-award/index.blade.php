@php
    $admin = auth('admin')->user();
    $canCreate = $admin?->can('create_pharma') ?? false;
    $canEdit = $admin?->can('edit_pharma') ?? false;
    $canDelete = $admin?->can('delete_pharma') ?? false;
@endphp

<div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Kết quả trúng thầu</h1>
            <p class="text-sm text-gray-500 mt-1">Workspace Pharma cho kết quả trúng thầu thủ công và dữ liệu nguồn Mua sắm công trong tương lai.</p>
        </div>
        @if ($canCreate)
            <a href="{{ route('admin.pharma.drug-bid-awards.create') }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-3 font-semibold text-sm text-white hover:bg-blue-700 shadow-sm">Thêm hồ sơ mới</a>
        @endif
    </div>

    @if (session()->has('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 p-4 rounded-xl text-sm" role="status">{{ session('success') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-700 p-4 rounded-xl text-sm" role="alert">{{ session('error') }}</div>
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
            ],
        ], key('drug-bid-award-import-export-' . md5(json_encode([$search, $filterInvestor, $filterCompany]))))
    @endif

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 sm:p-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 items-end">
            <div>
                <label for="award-search" class="text-sm font-medium text-gray-600 block">Tìm kiếm</label>
                <input id="award-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Tên thuốc, mã mời thầu, số quyết định..." class="w-full rounded-xl border border-gray-300 px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600 block">Chủ đầu tư</label>
                <div class="mt-1"><x-select-search id="filter-investor-id" wire:model.live="filterInvestor" placeholder="Tất cả chủ đầu tư"><option value="">Tất cả chủ đầu tư</option>@foreach ($investors as $investor)<option value="{{ $investor }}">{{ $investor }}</option>@endforeach</x-select-search></div>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600 block">Nhà thầu</label>
                <div class="mt-1"><x-select-search id="filter-company-id" wire:model.live="filterCompany" placeholder="Tất cả nhà thầu"><option value="">Tất cả nhà thầu</option>@foreach ($companies as $company)<option value="{{ $company }}">{{ $company }}</option>@endforeach</x-select-search></div>
            </div>
            <div>
                <label for="filter-source" class="text-sm font-medium text-gray-600 block">Nguồn dữ liệu</label>
                <select id="filter-source" wire:model.live="filterSource" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 mt-1 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    <option value="">Tất cả nguồn</option>
                    @foreach ($sourceOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select>
            </div>
        </div>
        <div class="flex flex-col gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="w-full sm:w-56">
                <label for="award-per-page" class="sr-only">Số bản ghi mỗi trang</label>
                <select id="award-per-page" wire:model.live="perPage" class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    @foreach ($perPageOptions as $option)<option value="{{ $option }}">{{ $option }} bản ghi / trang</option>@endforeach
                </select>
            </div>
            <button type="button" wire:click="resetFilters" wire:loading.attr="disabled" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-5 py-3 font-semibold text-sm text-gray-700 hover:bg-gray-50 disabled:opacity-60">Xóa bộ lọc</button>
        </div>
    </div>

    @if ($canDelete && $selectedIds !== [])
        <div class="bg-indigo-50 border border-indigo-100 rounded-2xl px-4 sm:px-6 py-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-indigo-900 font-medium">Đã chọn <strong>{{ count($selectedIds) }}</strong> hồ sơ trên trang hiện tại.</div>
            <button type="button" wire:click="confirmBulkDelete" class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 font-semibold text-sm text-rose-700 hover:bg-rose-100">Xóa mục đã chọn</button>
        </div>
    @endif

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden" wire:loading.class="opacity-60" wire:target="search,filterInvestor,filterCompany,filterSource,perPage,gotoPage,deleteAward,deleteSelected">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse whitespace-nowrap min-w-[1100px] text-sm">
                <thead><tr class="bg-gray-50/75 border-b border-gray-200 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    @if ($canDelete)<th class="py-4 px-4 text-center w-12"><input type="checkbox" wire:model.live="selectPage" aria-label="Chọn tất cả hồ sơ trên trang hiện tại" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"></th>@endif
                    <th class="py-4 px-4">Thuốc trúng thầu</th><th class="py-4 px-4">Khối lượng & giá</th><th class="py-4 px-4">Chủ đầu tư</th><th class="py-4 px-4">Pháp lý</th><th class="py-4 px-4">Nhà thầu</th><th class="py-4 px-4">Nguồn</th>
                    @if ($canEdit || $canDelete)<th class="py-4 px-4 text-right">Hành động</th>@endif
                </tr></thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse ($awards as $award)
                        <tr class="hover:bg-gray-50/60 {{ in_array((string) $award->id, array_map('strval', $selectedIds), true) ? 'bg-indigo-50/40' : '' }}">
                            @if ($canDelete)<td class="py-4 px-4 text-center"><input type="checkbox" wire:model.live="selectedIds" value="{{ $award->id }}" aria-label="Chọn {{ $award->medicine_name }}" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"></td>@endif
                            <td class="py-4 px-4"><div class="font-semibold text-gray-900">{{ $award->medicine_name }}</div><div class="text-xs text-gray-500 mt-1">{{ $award->packaging_specification }}</div>@if ($award->medicine_id)<div class="text-xs text-emerald-700 mt-1">Đã liên kết HSSP #{{ $award->medicine_id }}</div>@else<div class="text-xs text-amber-700 mt-1">Chưa đối soát HSSP</div>@endif</td>
                            <td class="py-4 px-4"><div class="font-medium">{{ number_format($award->quantity) }} đơn vị</div><div class="text-xs text-indigo-700 mt-1">{{ number_format((float) $award->unit_price, 0, ',', '.') }} VNĐ</div></td>
                            <td class="py-4 px-4"><div class="max-w-xs truncate font-medium" title="{{ $award->investor_name }}">{{ $award->investor_name }}</div><div class="text-xs text-gray-500 mt-1 font-mono">{{ $award->bidding_notice_code }}</div></td>
                            <td class="py-4 px-4"><div class="font-medium">QĐ: {{ $award->decision_number }}</div><div class="text-xs text-gray-500 mt-1">{{ $award->decision_date?->format('d/m/Y') }} · {{ $award->contract_duration_months }} tháng</div></td>
                            <td class="py-4 px-4"><div class="max-w-xs truncate font-medium" title="{{ $award->winning_company_name }}">{{ $award->winning_company_name }}</div>@if ($award->decision_document_url)<a href="{{ $award->decision_document_url }}" target="_blank" rel="noopener noreferrer" class="text-xs text-blue-600 hover:underline mt-1 inline-block">Mở văn bản</a>@endif</td>
                            <td class="py-4 px-4">@if ($award->source_type === \Modules\Pharma\Models\DrugBidAward::SOURCE_MUASAMCONG)<span class="inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-200">Mua sắm công</span>@else<span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 ring-1 ring-gray-200">Thủ công</span>@endif</td>
                            @if ($canEdit || $canDelete)<td class="py-4 px-4 text-right"><div class="inline-flex items-center gap-2">@if ($canEdit)<a href="{{ route('admin.pharma.drug-bid-awards.edit', $award->id) }}" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">Sửa</a>@endif @if ($canDelete)<button type="button" wire:click="deleteAward({{ $award->id }})" wire:confirm="Xóa vĩnh viễn hồ sơ trúng thầu này?" wire:loading.attr="disabled" class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50 disabled:opacity-60">Xóa</button>@endif</div></td>@endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ 6 + ($canDelete ? 1 : 0) + (($canEdit || $canDelete) ? 1 : 0) }}" class="py-12 px-6 text-center text-gray-500">Không có kết quả phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 px-4 sm:px-6 py-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-xs font-medium text-gray-500">@if ($awards->total())Hiển thị {{ $awards->firstItem() }}–{{ $awards->lastItem() }} / {{ $awards->total() }} bản ghi@else 0 bản ghi @endif</div>
            @if ($awards->hasPages())
                <div class="inline-flex items-center gap-2">
                    <button type="button" wire:click="gotoPage({{ max(1, $awards->currentPage() - 1) }})" wire:loading.attr="disabled" @disabled($awards->onFirstPage()) class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 disabled:cursor-not-allowed disabled:opacity-40">Trước</button>
                    <span class="px-2 text-xs font-medium text-gray-600">Trang {{ $awards->currentPage() }} / {{ $awards->lastPage() }}</span>
                    <button type="button" wire:click="gotoPage({{ min($awards->lastPage(), $awards->currentPage() + 1) }})" wire:loading.attr="disabled" @disabled(! $awards->hasMorePages()) class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 disabled:cursor-not-allowed disabled:opacity-40">Tiếp</button>
                </div>
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
