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

<div class="space-y-6">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Pharma · Procurement Intelligence</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Kết quả trúng thầu thuốc</h1>
            <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">Catalog nghiệp vụ đa nguồn của Pharma. Giá, số lượng, nhà thầu và quyết định luôn giữ theo nguồn lịch sử; thông tin hồ sơ thuốc chỉ được bổ sung từ HSSP khi bản ghi nguồn đang thiếu.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($canEdit)
                <button type="button" wire:click="syncMuasamcong" wire:loading.attr="disabled" wire:target="syncMuasamcong"
                        class="inline-flex min-h-11 items-center justify-center rounded-xl border border-sky-300 bg-sky-50 px-4 py-2.5 text-sm font-semibold text-sky-800 transition hover:bg-sky-100 disabled:cursor-wait disabled:opacity-60">
                    <span wire:loading.remove wire:target="syncMuasamcong">{{ $syncAfterId ? 'Đồng bộ tiếp KQLCNT' : 'Đồng bộ KQLCNT' }}</span>
                    <span wire:loading wire:target="syncMuasamcong">Đang đồng bộ...</span>
                </button>
                @if ($syncAfterId)
                    <button type="button" wire:click="restartMuasamcongSync" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Bắt đầu lại sync</button>
                @endif
            @endif
            @if ($canCreate)
                <a href="{{ route('admin.pharma.drug-bid-awards.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">Thêm hồ sơ mới</a>
            @endif
        </div>
    </header>

    @if (session()->has('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session()->has('error'))
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
    @endif

    @if ($canEdit)
        @livewire('shared.import-export.panel', [
            'serviceClass' => \Modules\Pharma\Services\DrugBidAwardImportExport::class,
            'title' => 'Import / Export kết quả trúng thầu',
            'description' => 'Nếu có chọn checkbox: export phần đã chọn. Nếu không chọn: export toàn bộ kết quả theo bộ lọc hiện tại.',
            'permission' => 'edit_pharma',
            'filters' => [
                'search' => $search,
                'investor' => $filterInvestor,
                'company' => $filterCompany,
                'source' => $filterSource,
                'medicine_match_status' => $filterMatchStatus,
                'selected_ids' => $selectedIds,
            ],
        ], key('drug-bid-award-import-export-' . md5(json_encode([$search, $filterInvestor, $filterCompany, $filterSource, $filterMatchStatus, $selectedIds]))))
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-labelledby="award-filters-heading">
        <div class="mb-4">
            <h2 id="award-filters-heading" class="text-base font-semibold text-slate-900">Bộ lọc intelligence</h2>
            <p class="mt-1 text-sm text-slate-500">Tìm theo thuốc, hoạt chất, mã thuốc, TBMT hoặc quyết định; lọc theo nguồn và trạng thái đối soát HSSP.</p>
        </div>
        <div class="grid gap-4 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <label for="award-search" class="block text-sm font-medium text-slate-700">Tìm kiếm</label>
                <input id="award-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Thuốc, hoạt chất, mã thuốc, TBMT..." class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
            </div>
            <div class="lg:col-span-2"><label for="filter-investor" class="block text-sm font-medium text-slate-700">Chủ đầu tư</label><input id="filter-investor" type="search" wire:model.live.debounce.300ms="filterInvestor" placeholder="Tên chủ đầu tư..." class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"></div>
            <div class="lg:col-span-2"><label for="filter-company" class="block text-sm font-medium text-slate-700">Nhà thầu</label><input id="filter-company" type="search" wire:model.live.debounce.300ms="filterCompany" placeholder="Tên nhà thầu..." class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"></div>
            <div class="lg:col-span-2"><label for="filter-source" class="block text-sm font-medium text-slate-700">Nguồn</label><select id="filter-source" wire:model.live="filterSource" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"><option value="">Tất cả nguồn</option>@foreach ($sourceOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div class="lg:col-span-2"><label for="filter-match" class="block text-sm font-medium text-slate-700">Đối soát HSSP</label><select id="filter-match" wire:model.live="filterMatchStatus" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"><option value="">Tất cả trạng thái</option>@foreach ($matchStatusOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
        </div>
        <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <select wire:model.live="perPage" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm sm:w-56">@foreach ($perPageOptions as $option)<option value="{{ $option }}">{{ $option }} bản ghi / trang</option>@endforeach</select>
            <button type="button" wire:click="resetFilters" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Xóa bộ lọc</button>
        </div>
    </section>

    @if ($canDelete && $selectedIds !== [])
        <section class="flex flex-col gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"><p class="text-sm font-medium text-rose-900">Đã chọn <strong>{{ count($selectedIds) }}</strong> hồ sơ trên trang hiện tại.</p><button type="button" wire:click="confirmBulkDelete" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Xóa mục đã chọn</button></section>
    @endif

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5"><div><h2 class="font-semibold text-slate-950">Drug Award Business Catalog</h2><p class="mt-1 text-xs text-slate-500">{{ number_format($awards->total()) }} bản ghi · Trang {{ $currentPage }}/{{ max(1, $lastPage) }}</p></div><div wire:loading wire:target="search,filterInvestor,filterCompany,filterSource,filterMatchStatus,perPage,gotoPage,syncMuasamcong" class="text-sm font-medium text-indigo-600">Đang tải dữ liệu...</div></div>
        <div class="overflow-x-auto">
            <table class="min-w-[1450px] w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600"><tr>@if ($canSelect)<th class="w-12 px-4 py-3 text-center"><input type="checkbox" wire:model.live="selectPage" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></th>@endif<th class="px-4 py-3">Thuốc / HSSP</th><th class="px-4 py-3">TBMT / Lô</th><th class="px-4 py-3">Giá & số lượng</th><th class="px-4 py-3">Chủ đầu tư</th><th class="px-4 py-3">Nhà thầu</th><th class="px-4 py-3">Quyết định / Hợp đồng</th><th class="px-4 py-3">Nguồn</th>@if ($canEdit || $canDelete)<th class="px-4 py-3 text-right">Thao tác</th>@endif</tr></thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($awards as $award)
                        @php
                            $name = $award->effectiveMedicineAttribute('medicine_name');
                            $ingredient = $award->effectiveMedicineAttribute('active_ingredient');
                            $strength = $award->effectiveMedicineAttribute('concentration');
                            $route = $award->effectiveMedicineAttribute('route');
                            $dosage = $award->effectiveMedicineAttribute('dosage_form');
                            $price = $award->winning_price ?? $award->unit_price;
                        @endphp
                        <tr class="align-top transition hover:bg-slate-50 {{ in_array((string) $award->id, array_map('strval', $selectedIds), true) ? 'bg-indigo-50/50' : '' }}">
                            @if ($canSelect)<td class="px-4 py-4 text-center"><input type="checkbox" wire:model.live="selectedIds" value="{{ $award->id }}" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></td>@endif
                            <td class="min-w-72 px-4 py-4"><div class="font-semibold text-slate-950">{{ $name['value'] ?: '—' }}</div><div class="mt-1 text-xs leading-5 text-slate-500">{{ $ingredient['value'] ?: '—' }} · {{ $strength['value'] ?: '—' }}</div><div class="mt-1 text-xs text-slate-500">{{ $dosage['value'] ?: '—' }} · {{ $route['value'] ?: '—' }}</div>@if (in_array('hssp', [$name['origin'], $ingredient['origin'], $strength['origin'], $route['origin'], $dosage['origin']], true))<span class="mt-2 inline-flex rounded-full bg-violet-50 px-2 py-0.5 text-xs font-semibold text-violet-700 ring-1 ring-violet-200">Bổ sung từ HSSP</span>@endif <span class="mt-2 inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ring-1 {{ $award->medicine_match_status === 'verified' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : ($award->medicine_match_status === 'provisional' ? 'bg-amber-50 text-amber-700 ring-amber-200' : 'bg-slate-100 text-slate-700 ring-slate-200') }}">{{ $matchStatusOptions[$award->medicine_match_status] ?? $award->medicine_match_status }}</span></td>
                            <td class="min-w-56 px-4 py-4"><div class="font-mono text-xs font-semibold text-slate-800">{{ $award->bidding_notice_code ?: '—' }}</div><div class="mt-1 text-xs text-slate-500">Lô {{ $award->lot_no ?: '—' }} · {{ $award->lot_name ?: '—' }}</div></td>
                            <td class="min-w-52 px-4 py-4"><div class="font-semibold text-indigo-700">{{ $price !== null ? number_format((float) $price, 0, ',', '.') . ' VNĐ' : '—' }}</div><div class="mt-1 text-xs text-slate-500">SL: {{ $award->quantity !== null ? rtrim(rtrim(number_format((float) $award->quantity, 4, '.', ','), '0'), '.') : '—' }} {{ $award->unit ?: '' }}</div><div class="mt-1 text-xs text-slate-500">Giá KH: {{ $award->price_plan !== null ? number_format((float) $award->price_plan, 0, ',', '.') : '—' }}</div></td>
                            <td class="min-w-64 px-4 py-4"><div class="font-medium text-slate-800">{{ $award->investor_name ?: '—' }}</div><div class="mt-1 font-mono text-xs text-slate-500">{{ $award->investor_code ?: '—' }}</div></td>
                            <td class="min-w-64 px-4 py-4"><div class="font-medium text-slate-800">{{ $award->winning_company_name ?: '—' }}</div><div class="mt-1 font-mono text-xs text-slate-500">{{ $award->contractor_code ?: '—' }}</div></td>
                            <td class="min-w-60 px-4 py-4"><div class="font-medium">QĐ: {{ $award->decision_number ?: '—' }}</div><div class="mt-1 text-xs text-slate-500">{{ $award->decision_date?->format('d/m/Y') ?: '—' }}</div><div class="mt-1 text-xs text-slate-500">HĐ: {{ $award->contract_no ?: '—' }} · {{ $award->contract_period_text ?: ($award->contract_duration_months ? $award->contract_duration_months . ' tháng' : '—') }}</div></td>
                            <td class="min-w-44 px-4 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $award->source_type === \Modules\Pharma\Models\DrugBidAward::SOURCE_MUASAMCONG ? 'bg-sky-50 text-sky-700 ring-sky-200' : 'bg-slate-100 text-slate-700 ring-slate-200' }}">{{ $award->source_type === \Modules\Pharma\Models\DrugBidAward::SOURCE_MUASAMCONG ? 'Mua sắm công' : 'Pharma' }}</span><div class="mt-2 text-xs text-slate-500">{{ $award->sources->count() }} lineage</div></td>
                            @if ($canEdit || $canDelete)<td class="px-4 py-4 text-right"><div class="inline-flex gap-2">@if ($canEdit)<a href="{{ route('admin.pharma.drug-bid-awards.edit', $award->id) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Sửa</a>@endif @if ($canDelete)<button type="button" wire:click="deleteAward({{ $award->id }})" wire:confirm="Xóa vĩnh viễn hồ sơ trúng thầu này?" class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50">Xóa</button>@endif</div></td>@endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ 8 + ($canSelect ? 1 : 0) + (($canEdit || $canDelete) ? 1 : 0) }}" class="px-6 py-12 text-center text-sm text-slate-500">Không có kết quả phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($lastPage > 1)
            <nav class="flex flex-col gap-3 border-t border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5" aria-label="Phân trang kết quả trúng thầu"><p class="text-sm text-slate-500">Hiển thị {{ $awards->firstItem() }}–{{ $awards->lastItem() }} / {{ $awards->total() }}</p><div class="flex flex-wrap items-center justify-end gap-2"><button type="button" wire:click="gotoPage({{ max(1, $currentPage - 1) }})" @disabled($awards->onFirstPage()) class="min-h-10 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold disabled:opacity-40">Trước</button>@if ($startPage > 1)<button type="button" wire:click="gotoPage(1)" class="min-h-10 min-w-10 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold">1</button>@if ($startPage > 2)<span class="text-slate-400">…</span>@endif @endif @for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++)<button type="button" wire:click="gotoPage({{ $pageNumber }})" class="min-h-10 min-w-10 rounded-xl border px-3 py-2 text-sm font-semibold {{ $pageNumber === $currentPage ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-300 bg-white text-slate-700' }}">{{ $pageNumber }}</button>@endfor @if ($endPage < $lastPage) @if ($endPage < $lastPage - 1)<span class="text-slate-400">…</span>@endif <button type="button" wire:click="gotoPage({{ $lastPage }})" class="min-h-10 min-w-10 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold">{{ $lastPage }}</button>@endif <button type="button" wire:click="gotoPage({{ min($lastPage, $currentPage + 1) }})" @disabled(!$awards->hasMorePages()) class="min-h-10 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold disabled:opacity-40">Sau</button></div></nav>
        @endif
    </section>

    @if ($showBulkDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4"><div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"><h2 class="text-lg font-semibold text-slate-950">Xóa các hồ sơ đã chọn?</h2><p class="mt-2 text-sm text-slate-600">Thao tác này xóa {{ count($selectedIds) }} bản ghi trên trang hiện tại.</p><div class="mt-5 flex justify-end gap-2"><button type="button" wire:click="cancelBulkDelete" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold">Hủy</button><button type="button" wire:click="deleteSelected" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white">Xóa</button></div></div></div>
    @endif
</div>
