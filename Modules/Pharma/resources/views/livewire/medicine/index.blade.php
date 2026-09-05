<div class="space-y-6">
    @php
        $admin = auth('admin')->user();
        $canCreate = $admin?->can('create_pharma') ?? false;
        $canEdit = $admin?->can('edit_pharma') ?? false;
        $canDelete = $admin?->can('delete_pharma') ?? false;
        $canSelect = $canEdit || $canDelete;
        $currentPage = $medicines->currentPage();
        $lastPage = $medicines->lastPage();
        $startPage = max(1, $currentPage - 2);
        $endPage = min($lastPage, $currentPage + 2);
    @endphp

    <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Pharma · Medicine Master</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Hồ sơ thuốc / HSSP</h1>
            <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">Nguồn chuẩn hồ sơ sản phẩm thuốc của Pharma. HSSP quản lý định danh, chất lượng dữ liệu và thông tin sản phẩm; dữ liệu trúng thầu chỉ tham chiếu và được phép dùng HSSP để bổ sung các thuộc tính thuốc đang thiếu.</p>
        </div>
        @if ($canCreate)
            <a href="{{ route('admin.pharma.hssp.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">Thêm thuốc / HSSP</a>
        @endif
    </header>

    @if (session()->has('success'))<div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @if (session()->has('error'))<div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>@endif

    @if ($canEdit)
        @livewire('shared.import-export.panel', [
            'serviceClass' => \Modules\Pharma\Services\MedicineImportExport::class,
            'title' => 'Import / Export hồ sơ thuốc',
            'description' => 'Import Excel hiện giữ validation riêng của nguồn; giá trị rỗng không ghi đè dữ liệu canonical đã có.',
            'permission' => 'edit_pharma',
            'filters' => [
                'search' => $search,
                'circular_group' => $filterCircularGroup,
                'is_special_control' => $filterSpecialControl === '' ? null : $filterSpecialControl === 'yes',
                'profile_status' => $filterProfileStatus,
                'selected_ids' => $selectedIds,
            ],
        ], key('medicine-import-export-' . md5(json_encode([$search, $filterCircularGroup, $filterSpecialControl, $filterProfileStatus, $selectedIds]))))
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-labelledby="medicine-filters-heading">
        <div class="mb-4"><h2 id="medicine-filters-heading" class="text-base font-semibold text-slate-900">Data Quality filters</h2><p class="mt-1 text-sm text-slate-500">Tìm theo tên, hoạt chất, số đăng ký, hàm lượng, nhà sản xuất hoặc quốc gia; lọc nhanh hồ sơ thiếu dữ liệu và cần rà soát.</p></div>
        <div class="grid gap-4 lg:grid-cols-12">
            <div class="lg:col-span-4"><label for="medicine-search" class="block text-sm font-medium text-slate-700">Tìm kiếm</label><input id="medicine-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Tên, hoạt chất, SĐK, hàm lượng, NSX..." class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"></div>
            <div class="lg:col-span-2"><label for="medicine-quality" class="block text-sm font-medium text-slate-700">Chất lượng hồ sơ</label><select id="medicine-quality" wire:model.live="filterProfileStatus" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">@foreach ($profileStatusOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div class="lg:col-span-2"><label for="medicine-group" class="block text-sm font-medium text-slate-700">Nhóm Thông tư</label><select id="medicine-group" wire:model.live="filterCircularGroup" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"><option value="">Tất cả nhóm</option>@foreach ($circularGroups as $group)<option value="{{ $group }}">{{ $group }}</option>@endforeach</select></div>
            <div class="lg:col-span-2"><label for="medicine-special" class="block text-sm font-medium text-slate-700">Kiểm soát</label><select id="medicine-special" wire:model.live="filterSpecialControl" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"><option value="">Tất cả</option><option value="yes">KSĐB</option><option value="no">Thuốc thường</option></select></div>
            <div class="lg:col-span-2"><label for="medicine-per-page" class="block text-sm font-medium text-slate-700">Mỗi trang</label><select id="medicine-per-page" wire:model.live="perPage" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">@foreach ($perPageOptions as $option)<option value="{{ $option }}">{{ $option }} bản ghi</option>@endforeach</select></div>
        </div>
        <div class="mt-4 flex justify-end border-t border-slate-100 pt-4"><button type="button" wire:click="resetFilters" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Xóa bộ lọc</button></div>
    </section>

    @if ($canDelete && $selectedIds !== [])
        <section class="flex flex-col gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"><p class="text-sm font-medium text-rose-900">Đã chọn <strong>{{ count($selectedIds) }}</strong> hồ sơ trên trang hiện tại.</p><button type="button" wire:click="deleteSelected" wire:confirm="Xóa vĩnh viễn các hồ sơ thuốc đã chọn trên trang hiện tại?" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Xóa các mục đã chọn</button></section>
    @endif

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5"><div><h2 class="font-semibold text-slate-950">Medicine Master Catalog</h2><p class="mt-1 text-xs text-slate-500">{{ number_format($medicines->total()) }} bản ghi · Trang {{ $currentPage }}/{{ max(1, $lastPage) }}</p></div><div wire:loading wire:target="search,filterCircularGroup,filterSpecialControl,filterProfileStatus,perPage,gotoPage" class="text-sm font-medium text-indigo-600">Đang tải dữ liệu...</div></div>
        <div class="overflow-x-auto">
            <table class="min-w-[1250px] w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600"><tr>@if ($canSelect)<th class="w-12 px-4 py-3 text-center"><input type="checkbox" wire:model.live="selectPage" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></th>@endif<th class="px-4 py-3">Thuốc</th><th class="px-4 py-3">Đăng ký / Định danh</th><th class="px-4 py-3">Hoạt chất / Hàm lượng</th><th class="px-4 py-3">Dạng / Đường dùng</th><th class="px-4 py-3">Nhà sản xuất</th><th class="px-4 py-3">Chất lượng</th><th class="px-4 py-3">Nguồn / Awards</th>@if ($canEdit || $canDelete)<th class="px-4 py-3 text-right">Thao tác</th>@endif</tr></thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($medicines as $medicine)
                        <tr class="align-top transition hover:bg-slate-50 {{ in_array((string) $medicine->id, $selectedIds, true) ? 'bg-indigo-50/60' : '' }}">
                            @if ($canSelect)<td class="px-4 py-4 text-center"><input type="checkbox" wire:model.live="selectedIds" value="{{ $medicine->id }}" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"></td>@endif
                            <td class="min-w-64 px-4 py-4"><div class="font-semibold text-slate-950">{{ $medicine->name }}</div><div class="mt-1 text-xs text-slate-500">{{ $medicine->packaging_specification ?: 'Quy cách chưa có' }}</div>@if ($medicine->is_special_control)<span class="mt-2 inline-flex rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700 ring-1 ring-rose-200">KSĐB</span>@endif</td>
                            <td class="min-w-52 px-4 py-4"><div class="font-mono text-xs font-semibold text-slate-800">{{ $medicine->registration_number ?: 'Chưa có SĐK' }}</div><div class="mt-2 text-xs text-slate-500">Identity: {{ $medicine->identity_status ?: 'unverified' }}</div></td>
                            <td class="min-w-56 px-4 py-4"><div class="font-medium">{{ $medicine->active_ingredients ?: '—' }}</div><div class="mt-1 text-xs text-slate-500">{{ $medicine->concentration ?: '—' }}</div></td>
                            <td class="min-w-48 px-4 py-4"><div class="font-medium">{{ $medicine->dosage_form ?: '—' }}</div><div class="mt-1 text-xs text-slate-500">{{ $medicine->route_of_administration ?: '—' }} · {{ $medicine->unit ?: '—' }}</div></td>
                            <td class="min-w-60 px-4 py-4"><div class="font-medium">{{ $medicine->manufacturing_company ?: '—' }}</div><div class="mt-1 text-xs text-slate-500">{{ $medicine->manufacturing_country ?: '—' }}</div></td>
                            <td class="min-w-40 px-4 py-4">@php $qualityClass = match ($medicine->profile_status) { 'verified' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'complete' => 'bg-sky-50 text-sky-700 ring-sky-200', 'needs_review' => 'bg-amber-50 text-amber-700 ring-amber-200', default => 'bg-rose-50 text-rose-700 ring-rose-200' }; @endphp <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $qualityClass }}">{{ $profileStatusOptions[$medicine->profile_status] ?? $medicine->profile_status }}</span>@if ($medicine->last_verified_at)<div class="mt-2 text-xs text-slate-500">Xác minh {{ $medicine->last_verified_at?->format('d/m/Y') }}</div>@endif</td>
                            <td class="min-w-40 px-4 py-4"><div class="text-sm font-semibold text-slate-800">{{ $medicine->sources_count }} nguồn</div><div class="mt-1 text-xs text-slate-500">{{ $medicine->drug_bid_awards_count }} kết quả trúng thầu</div></td>
                            @if ($canEdit || $canDelete)<td class="px-4 py-4 text-right"><div class="inline-flex gap-2">@if ($canEdit)<a href="{{ route('admin.pharma.hssp.edit', $medicine->id) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Sửa</a>@endif @if ($canDelete)<button type="button" wire:click="deleteMedicine({{ $medicine->id }})" wire:confirm="Xóa vĩnh viễn hồ sơ thuốc này?" class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50">Xóa</button>@endif</div></td>@endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ 8 + ($canSelect ? 1 : 0) + (($canEdit || $canDelete) ? 1 : 0) }}" class="px-6 py-12 text-center text-sm text-slate-500">Không có hồ sơ thuốc phù hợp với bộ lọc hiện tại.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($lastPage > 1)
            <nav class="flex flex-col gap-3 border-t border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5" aria-label="Phân trang hồ sơ thuốc"><p class="text-sm text-slate-500">Hiển thị {{ $medicines->firstItem() }}–{{ $medicines->lastItem() }} / {{ $medicines->total() }}</p><div class="flex flex-wrap items-center justify-end gap-2"><button type="button" wire:click="gotoPage({{ max(1, $currentPage - 1) }})" @disabled($medicines->onFirstPage()) class="min-h-10 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold disabled:opacity-40">Trước</button>@if ($startPage > 1)<button type="button" wire:click="gotoPage(1)" class="min-h-10 min-w-10 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold">1</button>@if ($startPage > 2)<span class="text-slate-400">…</span>@endif @endif @for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++)<button type="button" wire:click="gotoPage({{ $pageNumber }})" class="min-h-10 min-w-10 rounded-xl border px-3 py-2 text-sm font-semibold {{ $pageNumber === $currentPage ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-300 bg-white text-slate-700' }}">{{ $pageNumber }}</button>@endfor @if ($endPage < $lastPage) @if ($endPage < $lastPage - 1)<span class="text-slate-400">…</span>@endif <button type="button" wire:click="gotoPage({{ $lastPage }})" class="min-h-10 min-w-10 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold">{{ $lastPage }}</button>@endif <button type="button" wire:click="gotoPage({{ min($lastPage, $currentPage + 1) }})" @disabled(!$medicines->hasMorePages()) class="min-h-10 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold disabled:opacity-40">Sau</button></div></nav>
        @endif
    </section>
</div>
