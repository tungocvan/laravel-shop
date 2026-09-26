@php
    $admin = auth('admin')->user();
    $canCreate = $admin?->can('create_pharma') ?? false;
    $canEdit = $admin?->can('edit_pharma') ?? false;
    $canDelete = $admin?->can('delete_pharma') ?? false;
    $canViewAllocations = $admin?->can('view_pharma_allocations') ?? false;
    $canViewCommercialPolicies = $admin?->can('view_pharma_commercial_policies') ?? false;
    $currentPage = $awards->currentPage();
    $lastPage = $awards->lastPage();
    $fmtQty = fn ($value) => rtrim(rtrim(number_format((float) $value, 4, ',', '.'), '0'), ',');
    $remainingContractMonths = function ($award, bool $durationOnly = false): ?int {
        $durationMonths = null;
        if ((int) $award->contract_duration_months > 0) {
            $durationMonths = (int) $award->contract_duration_months;
        } elseif ((int) $award->contract_period > 0) {
            $unit = mb_strtolower(trim((string) $award->contract_period_unit));
            $period = (int) $award->contract_period;
            if ($unit === '' || in_array($unit, ['m', 'mo'], true) || str_contains($unit, 'tháng') || str_contains($unit, 'month')) {
                $durationMonths = $period;
            } elseif (in_array($unit, ['y', 'yr'], true) || str_contains($unit, 'năm') || str_contains($unit, 'year')) {
                $durationMonths = $period * 12;
            } elseif (in_array($unit, ['d', 'day', 'days'], true) || str_contains($unit, 'ngày')) {
                $durationMonths = max(1, (int) round($period / 30.4375));
            }
        }
        if ($durationOnly) {
            return $durationMonths;
        }
        if (! $durationMonths) {
            return null;
        }
        $contractStart = $award->decision_date ?: $award->published_at;
        if (! $contractStart) {
            return null;
        }
        $endDate = \Carbon\Carbon::parse($contractStart)->addMonthsNoOverflow($durationMonths)->endOfDay();
        if (now()->greaterThanOrEqualTo($endDate)) {
            return 0;
        }
        return max(1, (int) ceil(now()->diffInDays($endDate) / 30.4375));
    };
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
        @if($showImportExport)<div class="border-t border-slate-100 p-4">@livewire('shared.import-export.panel', ['serviceClass' => \Modules\Pharma\Services\DrugBidAwardImportExport::class,'title' => 'Import / Export chi tiết sản phẩm trúng thầu','description' => 'Workspace hiển thị theo mã TBMT; file Import/Export vẫn giữ từng sản phẩm để bảo toàn dữ liệu nghiệp vụ.','permission' => 'edit_pharma','filters' => ['search' => $search, 'tbmt' => $filterTbmt, 'investor' => $filterInvestor, 'source' => $filterSource, 'selected_ids' => $selectedIds]], key('drug-bid-award-import-export-' . md5(json_encode([$search, $filterTbmt, $filterInvestor, $filterSource, $selectedIds]))))</div>@endif
    </section>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <button type="button" wire:click="$toggle('showFilters')" class="flex w-full items-center justify-between px-5 py-4 text-left"><span><span class="block text-sm font-bold text-slate-900">Bộ lọc kết quả</span><span class="mt-1 block text-xs text-slate-500">Lọc theo TBMT, chủ đầu tư, sản phẩm, thiết lập kinh doanh và giá trị.</span></span><span class="text-xs font-semibold text-indigo-700">{{ $showFilters ? 'Ẩn' : 'Hiển thị' }}</span></button>
        @if($showFilters)<div class="border-t border-slate-100 p-5">
            <div class="grid items-end gap-3 md:grid-cols-2 xl:grid-cols-[minmax(145px,0.7fr)_minmax(250px,1.45fr)_minmax(250px,1.45fr)_minmax(140px,0.7fr)_minmax(190px,0.95fr)_minmax(105px,0.5fr)_auto]">
                <label class="block text-xs font-semibold text-slate-700">Mã TBMT<x-select-search id="drug-award-filter-tbmt" wire:model="filterTbmt" placeholder="Tất cả Mã TBMT" class="mt-1.5"><option value="">Tất cả Mã TBMT</option>@foreach ($tbmtOptions as $value)<option value="{{ $value }}">{{ $value }}</option>@endforeach</x-select-search></label>
                <label class="block text-xs font-semibold text-slate-700">Chủ đầu tư<x-select-search id="drug-award-filter-investor" wire:model="filterInvestor" placeholder="Tất cả chủ đầu tư" class="mt-1.5"><option value="">Tất cả chủ đầu tư</option>@foreach ($investorOptions as $value)<option value="{{ $value }}">{{ $value }}</option>@endforeach</x-select-search></label>
                <label class="block text-xs font-semibold text-slate-700">Sản phẩm<x-select-search id="drug-award-filter-medicine" wire:model="search" placeholder="Tất cả sản phẩm" class="mt-1.5"><option value="">Tất cả sản phẩm</option>@foreach ($medicineOptions as $value)<option value="{{ $value }}">{{ $value }}</option>@endforeach</x-select-search></label>
                <label class="block text-xs font-semibold text-slate-700">Giá trị<select wire:model.live="valueSort" class="mt-1.5 h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm"><option value="">Mặc định</option><option value="desc">Cao nhất → thấp nhất</option><option value="asc">Thấp nhất → cao nhất</option></select></label>
                <label class="block text-xs font-semibold text-slate-700">Thiết lập kinh doanh<select wire:model.live="filterBusinessSetup" class="mt-1.5 h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm"><option value="">Tất cả</option><option value="commercial_missing">Chưa có CS kinh doanh</option><option value="commercial_ready">Đã có CS kinh doanh</option><option value="allocation_missing">Chưa phân bổ SL</option><option value="allocation_ready">Đã phân bổ SL</option></select></label>
                <label class="block text-xs font-semibold text-slate-700">Hiển thị<select wire:model.live="perPage" class="mt-1.5 h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">@foreach ($perPageOptions as $option)<option value="{{ $option }}">{{ $option }} / trang</option>@endforeach</select></label>
                <button type="button" wire:click="resetFilters" class="h-11 whitespace-nowrap rounded-xl border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50">Xóa bộ lọc</button>
            </div>
        </div>@endif
    </section>

    <section class="relative overflow-visible rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 rounded-t-2xl border-b border-slate-200 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
            <div><h2 class="font-semibold text-slate-950">Danh sách kết quả trúng thầu</h2><p class="mt-1 text-xs text-slate-500">{{ number_format($awards->total()) }} mã TBMT · Trang {{ $currentPage }}/{{ max(1, $lastPage) }}</p></div>
            <div class="flex flex-wrap items-center justify-end gap-2">
                <span wire:loading class="mr-2 text-sm font-semibold text-indigo-600">Đang tải...</span>
                @if($selectedIds !== [])
                    <span class="rounded-full bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-800">Đã chọn {{ count($selectedIds) }} TBMT</span>
                    @if($canEdit)
                        <button type="button" wire:click="exportSelectedAwards" wire:loading.attr="disabled" wire:target="exportSelectedAwards" class="inline-flex min-h-10 items-center rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 disabled:cursor-wait disabled:opacity-60"><span wire:loading.remove wire:target="exportSelectedAwards">Export Excel</span><span wire:loading wire:target="exportSelectedAwards">Đang xuất...</span></button>
                    @endif
                    <button type="button" wire:click="clearAwardSelection" class="inline-flex min-h-10 items-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Bỏ chọn</button>
                @endif
            </div>
        </div>
        <div class="overflow-x-auto overflow-y-visible"><table class="min-w-[1060px] w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-600"><tr><th class="w-12 px-4 py-3 text-center"><input type="checkbox" wire:model.live="selectPage" wire:key="award-select-page-{{ $selectPage ? '1' : '0' }}" aria-label="Chọn tất cả TBMT trên trang" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></th><th class="px-4 py-3">Mã TBMT</th><th class="px-4 py-3">Chủ đầu tư</th><th class="px-4 py-3">Quyết định</th><th class="px-3 py-3 text-right">SP</th><th class="px-4 py-3 text-right">Giá trị</th><th class="px-3 py-3">HĐ</th><th class="px-3 py-3">Còn lại</th><th class="px-4 py-3">Thiết lập</th><th class="w-52 px-4 py-3 text-right">Thao tác</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($awards as $award)
                <tr wire:key="award-group-{{ $award->representative_id }}" class="align-top hover:bg-slate-50">
                    <td class="px-4 py-4 text-center"><input type="checkbox" wire:key="award-checkbox-{{ $award->representative_id }}-{{ in_array((string) $award->representative_id, array_map('strval', $selectedIds), true) ? '1' : '0' }}" x-data="{ selected: @js(in_array((string) $award->representative_id, array_map('strval', $selectedIds), true)) }" x-model="selected" x-on:change="$wire.call('setAwardSelected', {{ $award->representative_id }}, $event.target.checked)" aria-label="Chọn TBMT {{ $award->bidding_notice_code ?: $award->id }}" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></td>
                    <td class="px-4 py-4"><div class="font-mono text-sm font-bold text-slate-950">{{ $award->bidding_notice_code ?: 'Hồ sơ #' . $award->representative_id }}</div></td>
                    <td class="min-w-64 px-4 py-4"><div class="font-medium text-slate-900">{{ $award->investor_name ?: '—' }}</div><div class="mt-1 text-xs text-slate-500">{{ $award->investor_code ?: '—' }}</div></td>
                    <td class="px-4 py-4"><div class="font-medium">{{ $award->decision_number ?: '—' }}</div><div class="mt-1 text-xs text-slate-500">{{ $award->decision_date ? \Carbon\Carbon::parse($award->decision_date)->format('d/m/Y') : '—' }}</div></td>
                    <td class="px-4 py-4 text-right text-lg font-bold text-slate-950">{{ number_format((int) $award->product_count) }}</td>
                    <td class="px-4 py-4 text-right font-semibold text-indigo-700">{{ number_format((float) $award->total_value, 0, ',', '.') }} VNĐ</td>
                    <td class="px-4 py-4"><div class="font-semibold text-slate-800">{{ ($duration = $remainingContractMonths($award, true)) !== null ? $duration.' tháng' : ($award->contract_period_text ?: '—') }}</div></td>
                    @php($remainingMonths = $remainingContractMonths($award))
                    <td class="px-4 py-4" title="Ước tính từ ngày quyết định; nếu nguồn không có ngày quyết định thì dùng ngày công bố và thời gian thực hiện hợp đồng">
                        @if($remainingMonths === null)<span class="text-slate-400">—</span>
                        @elseif($remainingMonths === 0)<span class="inline-flex rounded-full bg-rose-100 px-2.5 py-1 text-xs font-bold text-rose-800">Hết hiệu lực HĐ</span>
                        @elseif($remainingMonths <= 3)<span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700">{{ str_pad((string)$remainingMonths, 2, '0', STR_PAD_LEFT) }} tháng</span>
                        @else<span class="font-semibold text-slate-700">{{ str_pad((string)$remainingMonths, 2, '0', STR_PAD_LEFT) }} tháng</span>@endif
                    </td>
                    <td class="px-4 py-4"><div class="flex flex-col items-start gap-1.5"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ (int)$award->allocated_product_count > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">Phân bổ: {{ (int)$award->allocated_product_count > 0 ? 'Đã thiết lập' : 'Chưa thiết lập' }}</span><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ (int)$award->managed_product_count > 0 ? 'bg-sky-50 text-sky-700' : 'bg-slate-100 text-slate-600' }}">CSKD: {{ (int)$award->managed_product_count > 0 ? 'Đã thiết lập' : 'Chưa thiết lập' }}</span></div></td>
                    <td class="px-4 py-4 text-right"><div class="flex items-center justify-end gap-2">@if ($canViewAllocations)<a href="{{ route('admin.pharma.drug-bid-awards.allocations', $award->representative_id) }}" class="inline-flex min-h-10 items-center whitespace-nowrap rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700">Sản phẩm / Phân bổ</a>@endif @if($canEdit || $canViewCommercialPolicies)<details class="relative z-40"><summary class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-xl border border-slate-300 bg-white text-lg font-bold text-slate-600 hover:bg-slate-50" aria-label="Thêm thao tác">⋯</summary><div class="absolute right-0 top-full z-50 mt-2 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-xl">@if($canEdit)<button type="button" wire:click="editResultGroup({{ $award->representative_id }})" class="block w-full rounded-lg px-3 py-2.5 text-left text-xs font-semibold text-slate-700 hover:bg-slate-50">Sửa hồ sơ</button>@endif @if($canViewCommercialPolicies)<a href="{{ route('admin.pharma.drug-bid-awards.commercial-policy', $award->representative_id) }}" class="block rounded-lg px-3 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Chính sách kinh doanh</a>@endif @if($canDelete)<button type="button" wire:click="deleteResultGroup({{ $award->representative_id }})" wire:confirm="Xóa toàn bộ TBMT {{ $award->bidding_notice_code ?: '#' . $award->representative_id }} và các sản phẩm trúng thầu liên quan? Thao tác này không thể hoàn tác." class="block w-full rounded-lg px-3 py-2.5 text-left text-xs font-semibold text-rose-700 hover:bg-rose-50">Xóa TBMT</button>@endif</div></details>@endif</div></td>
                </tr>
            @empty
                <tr><td colspan="10" class="px-6 py-12 text-center text-slate-500">Không có kết quả phù hợp.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        @if ($lastPage > 1)<nav class="flex items-center justify-between border-t border-slate-200 px-5 py-4"><p class="text-sm text-slate-500">Hiển thị {{ $awards->firstItem() }}–{{ $awards->lastItem() }} / {{ $awards->total() }}</p><div class="flex gap-2"><button type="button" wire:click="gotoPage({{ max(1, $currentPage - 1) }})" @disabled($awards->onFirstPage()) class="min-h-10 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold disabled:opacity-40">Trước</button><button type="button" wire:click="gotoPage({{ min($lastPage, $currentPage + 1) }})" @disabled(!$awards->hasMorePages()) class="min-h-10 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold disabled:opacity-40">Sau</button></div></nav>@endif
    </section>
</div>
