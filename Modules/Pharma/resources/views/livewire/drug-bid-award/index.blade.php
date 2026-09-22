@php
    $admin = auth('admin')->user();
    $canCreate = $admin?->can('create_pharma') ?? false;
    $canEdit = $admin?->can('edit_pharma') ?? false;
    $canViewAllocations = $admin?->can('view_pharma_allocations') ?? false;
    $canViewCommercialPolicies = $admin?->can('view_pharma_commercial_policies') ?? false;
    $currentPage = $awards->currentPage();
    $lastPage = $awards->lastPage();
    $fmtQty = fn ($value) => rtrim(rtrim(number_format((float) $value, 4, ',', '.'), '0'), ',');
@endphp

<div class="space-y-6">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Pharma · Procurement Intelligence</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-950 sm:text-3xl">Quản lý kết quả trúng thầu</h1>
            <p class="mt-2 max-w-4xl text-sm text-slate-600">Mỗi dòng là một mã thông báo mời thầu. Mở hồ sơ để xem các sản phẩm trúng thầu rồi phân bổ từng sản phẩm cho đơn vị nhận.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($canEdit)<button type="button" wire:click="syncMuasamcong" wire:loading.attr="disabled" wire:target="syncMuasamcong" class="min-h-11 rounded-xl border border-sky-300 bg-sky-50 px-4 py-2.5 text-sm font-semibold text-sky-800 disabled:opacity-60"><span wire:loading.remove wire:target="syncMuasamcong">{{ $syncAfterId ? 'Đồng bộ tiếp KQLCNT' : 'Đồng bộ KQLCNT' }}</span><span wire:loading wire:target="syncMuasamcong">Đang đồng bộ...</span></button>@endif
            @if ($canCreate)<a href="{{ route('admin.pharma.drug-bid-awards.create') }}" class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white">Thêm hồ sơ mới</a>@endif
        </div>
    </header>

    @if (session()->has('success'))<div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @if (session()->has('error'))<div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>@endif

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase text-slate-500">Tổng TBMT</p><p class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($dashboard['total']) }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase text-slate-500">Tổng giá trị</p><p class="mt-2 text-xl font-bold text-indigo-700">{{ number_format($dashboard['value'], 0, ',', '.') }} VNĐ</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase text-slate-500">Đã phân bổ</p><p class="mt-2 text-2xl font-bold text-emerald-700">{{ number_format($dashboard['allocated']) }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase text-slate-500">Có CS kinh doanh</p><p class="mt-2 text-2xl font-bold text-sky-700">{{ number_format($dashboard['commercial']) }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase text-slate-500">Cần hoàn thiện</p><p class="mt-2 text-2xl font-bold text-amber-700">{{ number_format($dashboard['pending']) }}</p></div>
    </section>

    @if ($canEdit)
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <button type="button" wire:click="$toggle('showImportExport')" class="flex w-full items-center justify-between px-5 py-4 text-left"><span><span class="block text-sm font-bold text-slate-900">Import / Export chi tiết sản phẩm trúng thầu</span><span class="mt-1 block text-xs text-slate-500">Công cụ dữ liệu chi tiết, mặc định thu gọn để ưu tiên không gian quản trị.</span></span><span class="text-xs font-semibold text-indigo-700">{{ $showImportExport ? 'Ẩn' : 'Hiển thị' }}</span></button>
        @if($showImportExport)<div class="border-t border-slate-100 p-4">@livewire('shared.import-export.panel', ['serviceClass' => \Modules\Pharma\Services\DrugBidAwardImportExport::class,'title' => 'Import / Export chi tiết sản phẩm trúng thầu','description' => 'Workspace hiển thị theo mã TBMT; file Import/Export vẫn giữ từng sản phẩm để bảo toàn dữ liệu nghiệp vụ.','permission' => 'edit_pharma','filters' => ['search' => $search, 'tbmt' => $filterTbmt, 'investor' => $filterInvestor, 'source' => $filterSource, 'medicine_match_status' => $filterMatchStatus]], key('drug-bid-award-import-export-' . md5(json_encode([$search, $filterTbmt, $filterInvestor, $filterSource, $filterMatchStatus]))))</div>@endif
    </section>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <button type="button" wire:click="$toggle('showFilters')" class="flex w-full items-center justify-between px-5 py-4 text-left"><span><span class="block text-sm font-bold text-slate-900">Bộ lọc kết quả</span><span class="mt-1 block text-xs text-slate-500">Lọc theo TBMT, chủ đầu tư, sản phẩm, nguồn và giá trị.</span></span><span class="text-xs font-semibold text-indigo-700">{{ $showFilters ? 'Ẩn' : 'Hiển thị' }}</span></button>
        @if($showFilters)<div class="border-t border-slate-100 p-5">
            <div class="flex justify-end"><button type="button" wire:click="resetFilters" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700">Xóa bộ lọc</button></div>
            <div class="mt-3 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label class="block text-xs font-semibold text-slate-700">Mã TBMT<x-select-search id="drug-award-filter-tbmt" wire:model="filterTbmt" placeholder="Tất cả Mã TBMT" class="mt-1.5"><option value="">Tất cả Mã TBMT</option>@foreach ($tbmtOptions as $value)<option value="{{ $value }}">{{ $value }}</option>@endforeach</x-select-search></label>
                <label class="block text-xs font-semibold text-slate-700">Chủ đầu tư<x-select-search id="drug-award-filter-investor" wire:model="filterInvestor" placeholder="Tất cả chủ đầu tư" class="mt-1.5"><option value="">Tất cả chủ đầu tư</option>@foreach ($investorOptions as $value)<option value="{{ $value }}">{{ $value }}</option>@endforeach</x-select-search></label>
                <label class="block text-xs font-semibold text-slate-700">Sản phẩm<x-select-search id="drug-award-filter-medicine" wire:model="search" placeholder="Tất cả sản phẩm" class="mt-1.5"><option value="">Tất cả sản phẩm</option>@foreach ($medicineOptions as $value)<option value="{{ $value }}">{{ $value }}</option>@endforeach</x-select-search></label>
                <label class="block text-xs font-semibold text-slate-700">Giá trị<select wire:model.live="valueSort" class="mt-1.5 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm"><option value="">Mặc định</option><option value="desc">Cao nhất → thấp nhất</option><option value="asc">Thấp nhất → cao nhất</option></select></label>
            </div>
            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <label class="block text-xs font-semibold text-slate-700">Nguồn<select wire:model.live="filterSource" class="mt-1.5 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm"><option value="">Tất cả nguồn</option>@foreach ($sourceOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                <label class="block text-xs font-semibold text-slate-700">Đối soát HSSP<select wire:model.live="filterMatchStatus" class="mt-1.5 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm"><option value="">Tất cả trạng thái</option>@foreach ($matchStatusOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                <label class="block text-xs font-semibold text-slate-700">Hiển thị<select wire:model.live="perPage" class="mt-1.5 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm">@foreach ($perPageOptions as $option)<option value="{{ $option }}">{{ $option }} kết quả / trang</option>@endforeach</select></label>
            </div>
        </div>@endif
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4"><div><h2 class="font-semibold text-slate-950">Danh sách kết quả trúng thầu</h2><p class="mt-1 text-xs text-slate-500">{{ number_format($awards->total()) }} mã TBMT · Trang {{ $currentPage }}/{{ max(1, $lastPage) }}</p></div><div wire:loading class="text-sm font-semibold text-indigo-600">Đang tải...</div></div>
        <div class="overflow-x-auto"><table class="min-w-[1180px] w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-600"><tr><th class="px-4 py-3">Mã TBMT</th><th class="px-4 py-3">Chủ đầu tư</th><th class="px-4 py-3">Quyết định</th><th class="px-4 py-3 text-right">Sản phẩm</th><th class="px-4 py-3 text-right">Giá trị</th><th class="px-4 py-3">Thời gian HĐ</th><th class="px-4 py-3">Trạng thái thiết lập</th><th class="px-4 py-3 text-right">Thao tác</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($awards as $award)
                <tr class="align-top hover:bg-slate-50">
                    <td class="px-4 py-4"><div class="font-mono text-sm font-bold text-slate-950">{{ $award->bidding_notice_code ?: 'Hồ sơ #' . $award->representative_id }}</div></td>
                    <td class="min-w-64 px-4 py-4"><div class="font-medium text-slate-900">{{ $award->investor_name ?: '—' }}</div><div class="mt-1 text-xs text-slate-500">{{ $award->investor_code ?: '—' }}</div></td>
                    <td class="px-4 py-4"><div class="font-medium">{{ $award->decision_number ?: '—' }}</div><div class="mt-1 text-xs text-slate-500">{{ $award->decision_date ? \Carbon\Carbon::parse($award->decision_date)->format('d/m/Y') : '—' }}</div></td>
                    <td class="px-4 py-4 text-right text-lg font-bold text-slate-950">{{ number_format((int) $award->product_count) }}</td>
                    <td class="px-4 py-4 text-right font-semibold text-indigo-700">{{ number_format((float) $award->total_value, 0, ',', '.') }} VNĐ</td>
                    <td class="px-4 py-4"><div class="font-semibold text-slate-800">{{ $award->contract_duration_months ? $award->contract_duration_months.' tháng' : ($award->contract_period ? $award->contract_period.' '.($award->contract_period_unit ?: '') : ($award->contract_period_text ?: '—')) }}</div></td>
                    <td class="px-4 py-4"><div class="flex flex-col items-start gap-1.5"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ (int)$award->allocation_count > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">Phân bổ: {{ (int)$award->allocation_count > 0 ? 'Đã thiết lập' : 'Chưa thiết lập' }}</span><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ (int)$award->management_assignment_count > 0 ? 'bg-sky-50 text-sky-700' : 'bg-slate-100 text-slate-600' }}">CSKD: {{ (int)$award->management_assignment_count > 0 ? 'Đã thiết lập' : 'Chưa thiết lập' }}</span></div></td>
                    <td class="px-4 py-4 text-right"><div class="flex justify-end gap-2">@if ($canViewAllocations)<a href="{{ route('admin.pharma.drug-bid-awards.allocations', $award->representative_id) }}" class="inline-flex min-h-10 items-center rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Xem sản phẩm / Phân bổ</a>@endif @if($canViewCommercialPolicies)<a href="{{ route('admin.pharma.drug-bid-awards.commercial-policy', $award->representative_id) }}" class="inline-flex min-h-10 items-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-xs font-semibold text-indigo-700">Chính sách kinh doanh</a>@endif</div></td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-6 py-12 text-center text-slate-500">Không có kết quả phù hợp.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        @if ($lastPage > 1)<nav class="flex items-center justify-between border-t border-slate-200 px-5 py-4"><p class="text-sm text-slate-500">Hiển thị {{ $awards->firstItem() }}–{{ $awards->lastItem() }} / {{ $awards->total() }}</p><div class="flex gap-2"><button type="button" wire:click="gotoPage({{ max(1, $currentPage - 1) }})" @disabled($awards->onFirstPage()) class="min-h-10 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold disabled:opacity-40">Trước</button><button type="button" wire:click="gotoPage({{ min($lastPage, $currentPage + 1) }})" @disabled(!$awards->hasMorePages()) class="min-h-10 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold disabled:opacity-40">Sau</button></div></nav>@endif
    </section>
</div>
