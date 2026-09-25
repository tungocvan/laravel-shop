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

    <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Pharma · Canonical Medicine Master</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Danh mục thuốc chuẩn</h1>
            <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">Nguồn chuẩn thuốc dùng chung toàn ERP. Thuốc có hoặc chưa có HSSP đều nằm tại đây; Inventory, Invoices, Muasamcong và các consumer khác resolve về Medicine/Variant/SKU của Pharma.</p>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.pharma.hssp.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Quản lý HSSP</a>
            <a href="{{ route('admin.pharma.medicines.export') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">Export danh mục thuốc chuẩn</a>
            @if($canEdit)
                <a href="{{ route('admin.pharma.medicines.import.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">Import danh mục thuốc chuẩn</a>
            @endif
            @if($canCreate)
                <a href="{{ route('admin.pharma.medicines.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">Thêm thuốc</a>
            @endif
        </div>
    </header>

    @if(session()->has('success'))<div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @if(session()->has('error'))<div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>@endif

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-labelledby="medicine-filters-heading">
        <div class="mb-4">
            <h2 id="medicine-filters-heading" class="text-base font-semibold text-slate-900">Tìm kiếm & Data Quality</h2>
            <p class="mt-1 text-sm text-slate-500">Tìm theo mã thuốc, SKU, tên biệt dược/tên thuốc/tên sản phẩm, GPLH, hoạt chất, hàm lượng, quy cách, nhà sản xuất hoặc alias.</p>
        </div>
        <div class="grid gap-3 xl:grid-cols-12 xl:items-end">
            <div class="xl:col-span-3"><label class="block text-sm font-medium text-slate-700">Tìm kiếm</label><input type="search" wire:model.live.debounce.300ms="search" placeholder="MED-..., SKU, tên thuốc, GPLH..." class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm"></div>
            <div class="xl:col-span-2"><label class="block text-sm font-medium text-slate-700">Nhà cung cấp</label><select wire:model.live="filterSupplier" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="">Tất cả NCC</option>@foreach($supplierOptions as $supplierId => $supplierName)<option value="{{ $supplierId }}">{{ $supplierName }}</option>@endforeach</select></div>
            <div class="xl:col-span-2"><label class="block text-sm font-medium text-slate-700">HSSP</label><select wire:model.live="filterHssp" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="">Tất cả</option><option value="with">Có HSSP</option><option value="without">Chưa có HSSP</option></select></div>
            <div class="xl:col-span-2"><label class="block text-sm font-medium text-slate-700">Chất lượng master</label><select wire:model.live="filterProfileStatus" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">@foreach($profileStatusOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <div class="xl:col-span-2"><label class="block text-sm font-medium text-slate-700">GPLH</label><select wire:model.live="filterRegistration" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="">Tất cả GPLH</option><option value="with">Có GPLH</option><option value="without">Chưa có GPLH</option></select></div>
            <div class="xl:col-span-2"><label class="block text-sm font-medium text-slate-700">Khả năng xóa</label><select wire:model.live="filterDeletable" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="">Tất cả</option><option value="yes">Có thể xóa</option><option value="no">Đang có ràng buộc</option></select></div>
            <div class="xl:col-span-1"><label class="block text-sm font-medium text-slate-700">Nhóm</label><select wire:model.live="filterCircularGroup" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="">Tất cả</option>@foreach($circularGroups as $group)<option value="{{ $group }}">{{ $group }}</option>@endforeach</select></div>
            <div class="xl:col-span-1"><label class="block text-sm font-medium text-slate-700">KSĐB</label><select wire:model.live="filterSpecialControl" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="">Tất cả</option><option value="yes">Có</option><option value="no">Không</option></select></div>
            <div class="xl:col-span-1"><label class="block text-sm font-medium text-slate-700">Mỗi trang</label><select wire:model.live="perPage" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">@foreach($perPageOptions as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach</select></div>
            @if($this->hasActiveSelectFilters())
                <div class="xl:col-span-2"><button type="button" wire:click="resetFilters" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-slate-400 hover:bg-slate-50">Xóa bộ lọc</button></div>
            @endif
        </div>
    </section>

    @if($canDelete && $selectedIds !== [])
        <section class="flex flex-col gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"><p class="text-sm font-medium text-rose-900">Đã chọn <strong>{{ count($selectedIds) }}</strong> thuốc trên trang hiện tại.</p><button type="button" wire:click="deleteSelected" wire:confirm="Xóa các thuốc được chọn?" class="inline-flex min-h-10 items-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white">Xóa mục đã chọn</button></section>
    @endif

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="font-semibold text-slate-950">Medicine Master Catalog</h2><p class="mt-1 text-xs text-slate-500">{{ number_format($medicines->total()) }} thuốc · Trang {{ $currentPage }}/{{ max(1, $lastPage) }}</p></div><div wire:loading class="text-sm font-medium text-indigo-600">Đang tải dữ liệu...</div></div>
        <div class="hidden overflow-x-auto lg:block">
            <table class="min-w-[1180px] w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600"><tr>@if($canSelect)<th class="w-12 px-4 py-3 text-center"><input type="checkbox" wire:model.live="selectPage" class="rounded border-slate-300 text-indigo-600"></th>@endif<th class="px-4 py-3">Thuốc</th><th class="px-4 py-3">Mã thuốc</th><th class="px-4 py-3">GPLH</th><th class="px-4 py-3">Nhóm thuốc</th><th class="px-4 py-3">Hoạt chất / Hàm lượng</th><th class="px-3 py-3">Quy cách</th><th class="px-4 py-3 text-right">Giá kê khai</th><th class="min-w-64 px-4 py-3">Nhà cung cấp</th><th class="px-4 py-3">HSSP</th><th class="px-4 py-3">Nguồn</th><th class="sticky right-0 z-20 border-l border-slate-200 bg-slate-50 px-4 py-3 text-right shadow-[-8px_0_16px_-16px_rgba(15,23,42,0.45)]">Thao tác</th></tr></thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                @forelse($medicines as $medicine)
                    <tr class="align-top hover:bg-slate-50 {{ in_array((string)$medicine->id, $selectedIds, true) ? 'bg-indigo-50/60' : '' }}">
                        @if($canSelect)<td class="px-4 py-4 text-center"><input type="checkbox" wire:model.live="selectedIds" value="{{ $medicine->id }}" class="rounded border-slate-300 text-indigo-600"></td>@endif
                        <td class="min-w-64 px-4 py-4"><div class="font-semibold text-slate-950">{{ $medicine->name }}</div><div class="mt-1 text-xs text-slate-500">{{ $medicine->dosage_form ?: 'Chưa có dạng bào chế' }} · {{ $medicine->route_of_administration ?: 'Chưa có đường dùng' }}</div></td>
                        <td class="min-w-40 px-4 py-4"><div class="font-mono text-xs font-semibold text-indigo-700">{{ $medicine->medicine_code ?: 'Chưa cấp mã' }}</div></td>
                        <td class="min-w-48 px-4 py-4"><div class="font-mono text-xs font-semibold text-slate-800">{{ $medicine->registration_number_primary ?: $medicine->registration_number ?: 'Chưa có GPLH' }}</div></td>
                        <td class="min-w-28 px-4 py-4"><span class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $medicine->circular_group ?: '—' }}</span></td>
                        <td class="w-48 max-w-56 px-4 py-4"><div class="font-medium">{{ $medicine->active_ingredients ?: '—' }}</div><div class="mt-1 text-xs text-slate-500">{{ $medicine->concentration ?: '—' }}</div></td>
                        @php($catalogVariant = $medicine->variants->first())
                        <td class="min-w-56 max-w-72 px-3 py-4 text-sm leading-5">
                            <div class="font-medium text-slate-800">{{ $medicine->packaging_specification ?: '—' }}</div>
                            @if($catalogVariant)<div class="mt-1 break-all font-mono text-[11px] text-slate-500">SKU: {{ $catalogVariant->sku }}</div>@endif
                        </td>
                        <td class="min-w-32 px-4 py-4 text-right">
                            @php($catalogPrice = $catalogVariant?->declared_price ?? $medicine->declared_price)
                            @if($catalogPrice !== null)<span class="whitespace-nowrap font-semibold text-emerald-700">{{ number_format((float)$catalogPrice, 0, ',', '.') }} đ</span>@else<span class="text-slate-400">—</span>@endif
                        </td>
                        <td class="min-w-64 px-4 py-4">@php($medicineSuppliers = $medicine->supplierTrackings->pluck('partner.name')->filter()->unique()->values()) @if($medicineSuppliers->isEmpty())<span class="text-xs text-slate-400">Chưa thiết lập</span>@else<div class="space-y-1">@foreach($medicineSuppliers->take(2) as $supplierName)<div class="text-xs font-semibold text-slate-700">{{ $supplierName }}</div>@endforeach @if($medicineSuppliers->count() > 2)<div class="text-xs text-slate-500">+{{ $medicineSuppliers->count() - 2 }} NCC khác</div>@endif</div>@endif</td>
                        <td class="min-w-44 px-4 py-4">@if($medicine->currentProfile)<span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">Có HSSP · v{{ $medicine->currentProfile->profile_version }}</span><div class="mt-2 text-xs text-slate-500">{{ $medicine->currentProfile->profile_status }}</div>@else<span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">Chưa có HSSP</span>@endif</td>
                        <td class="min-w-36 px-4 py-4"><div class="font-semibold text-slate-800">{{ $medicine->sources_count }} nguồn</div><div class="mt-1 text-xs text-slate-500">{{ $medicine->drug_bid_awards_count }} awards</div></td>
                        <td class="sticky right-0 z-20 w-40 border-l border-slate-100 bg-white px-3 py-4 text-right shadow-[-8px_0_16px_-16px_rgba(15,23,42,0.35)]" x-data="{ open: false, top: 0, right: 0, place() { const rect = this.$refs.trigger.getBoundingClientRect(); this.top = rect.bottom + 4; this.right = Math.max(8, window.innerWidth - rect.right); } }"><button x-ref="trigger" type="button" @click="place(); open = !open" @keydown.escape.window="open = false" class="inline-flex min-h-10 min-w-32 items-center justify-between gap-3 whitespace-nowrap rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-xs font-semibold text-indigo-700 shadow-sm hover:bg-indigo-100">Thao tác <span aria-hidden="true">⌄</span></button><template x-teleport="body"><div x-show="open" x-cloak x-transition.opacity @click.outside="open = false" @click="open = false" @scroll.window="open = false" @resize.window="open = false" class="fixed z-[100] w-44 overflow-hidden rounded-xl border border-slate-200 bg-white p-1 text-left shadow-xl" :style="`top: ${top}px; right: ${right}px;`">@if($medicine->currentProfile)<a href="{{ route('admin.pharma.hssp.edit', [$medicine->id, $medicine->currentProfile->id]) }}" class="block rounded-lg px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Mở HSSP</a>@elseif($canCreate)<a href="{{ route('admin.pharma.hssp.create', $medicine->id) }}" class="block rounded-lg px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-50">Tạo HSSP</a>@endif @if($canEdit)<a href="{{ route('admin.pharma.supplier-trackings.create', ['medicine_id' => $medicine->id]) }}" class="block rounded-lg px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Điều kiện NCC</a><a href="{{ route('admin.pharma.medicines.edit', $medicine->id) }}" class="block rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Sửa thuốc</a>@endif @if($canDelete)@php($deleteBlocked = $medicine->profiles_count > 0 || $medicine->supplier_trackings_count > 0 || $medicine->price_list_items_count > 0 || ($medicine->profile_status === \Modules\Pharma\Models\Medicine::PROFILE_VERIFIED && $medicine->drug_bid_awards_count > 0))<button type="button" @if($deleteBlocked) disabled title="Thuốc đang có dữ liệu nghiệp vụ cần giữ" @else wire:click="confirmDelete({{ $medicine->id }})" @endif class="block w-full rounded-lg px-3 py-2 text-left text-xs font-semibold {{ $deleteBlocked ? 'cursor-not-allowed text-slate-400' : 'text-rose-700 hover:bg-rose-50' }}">Xóa thuốc</button>@endif</div></template></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-6 py-12 text-center text-sm text-slate-500">Không có thuốc phù hợp với bộ lọc hiện tại.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-slate-100 lg:hidden">
            @forelse($medicines as $medicine)
                <article class="space-y-4 p-4 {{ in_array((string)$medicine->id, $selectedIds, true) ? 'bg-indigo-50/60' : '' }}">
                    <div class="flex items-start gap-3">
                        @if($canSelect)<input type="checkbox" wire:model.live="selectedIds" value="{{ $medicine->id }}" class="mt-1 rounded border-slate-300 text-indigo-600">@endif
                        <div class="min-w-0 flex-1"><div class="font-semibold text-slate-950">{{ $medicine->name }}</div><div class="mt-1 text-xs text-slate-500">{{ $medicine->dosage_form ?: 'Chưa có dạng bào chế' }} · {{ $medicine->route_of_administration ?: 'Chưa có đường dùng' }}</div></div>
                    </div>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Mã thuốc</dt><dd class="mt-1 font-mono text-xs font-semibold text-indigo-700">{{ $medicine->medicine_code ?: 'Chưa cấp mã' }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">GPLH</dt><dd class="mt-1 break-words font-mono text-xs font-semibold text-slate-800">{{ $medicine->registration_number_primary ?: $medicine->registration_number ?: 'Chưa có GPLH' }}</dd></div>
                        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nhóm thuốc</dt><dd class="mt-1 font-semibold text-slate-700">{{ $medicine->circular_group ?: '—' }}</dd></div>
                        <div class="col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Hoạt chất / Hàm lượng</dt><dd class="mt-1 font-medium text-slate-800">{{ $medicine->active_ingredients ?: '—' }}</dd><dd class="mt-0.5 text-xs text-slate-500">{{ $medicine->concentration ?: '—' }}</dd></div>
                        <div class="col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Quy cách / SKU</dt><dd class="mt-1 text-slate-700"><div>{{ $medicine->packaging_specification ?: '—' }}</div>@if($medicine->variants->first())<div class="mt-1 break-all font-mono text-[11px] text-slate-500">SKU: {{ $medicine->variants->first()->sku }}</div>@endif</dd></div>
                        <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Giá kê khai</dt><dd class="mt-1 font-semibold text-emerald-700">@php($mobilePrice = $medicine->variants->first()?->declared_price ?? $medicine->declared_price){{ $mobilePrice !== null ? number_format((float)$mobilePrice, 0, ',', '.').' đ' : '—' }}</dd></div>
                        <div class="col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nhà cung cấp</dt><dd class="mt-1 text-slate-700">{{ $medicine->supplierTrackings->pluck('partner.name')->filter()->unique()->join(', ') ?: 'Chưa thiết lập' }}</dd></div>
                    </dl>
                    <div class="flex flex-wrap items-center gap-2">@if($medicine->currentProfile)<span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">Có HSSP · v{{ $medicine->currentProfile->profile_version }}</span>@else<span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">Chưa có HSSP</span>@endif<span class="text-xs text-slate-500">{{ $medicine->sources_count }} nguồn · {{ $medicine->drug_bid_awards_count }} awards</span></div>
                    <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-3">@if($medicine->currentProfile)<a href="{{ route('admin.pharma.hssp.edit', [$medicine->id, $medicine->currentProfile->id]) }}" class="rounded-lg border border-emerald-200 px-3 py-2 text-xs font-semibold text-emerald-700">HSSP</a>@elseif($canCreate)<a href="{{ route('admin.pharma.hssp.create', $medicine->id) }}" class="rounded-lg border border-amber-200 px-3 py-2 text-xs font-semibold text-amber-700">Tạo HSSP</a>@endif @if($canEdit)<a href="{{ route('admin.pharma.supplier-trackings.create', ['medicine_id' => $medicine->id]) }}" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700">Cập nhật NCC</a><a href="{{ route('admin.pharma.medicines.edit', $medicine->id) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700">Sửa thuốc</a>@endif @if($canDelete)<button type="button" @if($medicine->profiles_count > 0) disabled title="Không thể xóa vì thuốc đã có HSSP" @else wire:click="confirmDelete({{ $medicine->id }})" @endif class="rounded-lg border px-3 py-2 text-xs font-semibold {{ $medicine->profiles_count > 0 ? 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400 opacity-60' : 'border-rose-200 text-rose-700' }}">Xóa</button>@endif</div>
                </article>
            @empty
                <div class="px-6 py-12 text-center text-sm text-slate-500">Không có thuốc phù hợp với bộ lọc hiện tại.</div>
            @endforelse
        </div>
        @if($lastPage > 1)
            <nav class="flex flex-col gap-3 border-t border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5"><p class="text-sm text-slate-500">Hiển thị {{ $medicines->firstItem() }}–{{ $medicines->lastItem() }} / {{ $medicines->total() }}</p><div class="flex flex-wrap items-center justify-end gap-2"><button type="button" wire:click="gotoPage({{ max(1,$currentPage-1) }})" @disabled($medicines->onFirstPage()) class="min-h-10 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold disabled:opacity-40">Trước</button>@if($startPage > 1)<button type="button" wire:click="gotoPage(1)" class="min-h-10 min-w-10 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold">1</button>@endif @for($pageNumber=$startPage;$pageNumber<=$endPage;$pageNumber++)<button type="button" wire:click="gotoPage({{ $pageNumber }})" class="min-h-10 min-w-10 rounded-xl border px-3 py-2 text-sm font-semibold {{ $pageNumber === $currentPage ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-300 bg-white text-slate-700' }}">{{ $pageNumber }}</button>@endfor @if($endPage < $lastPage)<button type="button" wire:click="gotoPage({{ $lastPage }})" class="min-h-10 min-w-10 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold">{{ $lastPage }}</button>@endif<button type="button" wire:click="gotoPage({{ min($lastPage,$currentPage+1) }})" @disabled(!$medicines->hasMorePages()) class="min-h-10 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold disabled:opacity-40">Sau</button></div></nav>
        @endif
    </section>

    @if($confirmingDeleteId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 p-4" role="dialog" aria-modal="true">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-slate-950">Xác nhận xóa thuốc</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">Bạn có chắc muốn xóa <strong>{{ $confirmingDeleteName }}</strong> khỏi Medicine Master? Dữ liệu có tham chiếu HSSP hoặc kết quả thầu vẫn được backend bảo vệ.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" wire:click="cancelDelete" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Hủy</button>
                    <button type="button" wire:click="deleteConfirmed" wire:loading.attr="disabled" class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Xóa thuốc</button>
                </div>
            </div>
        </div>
    @endif

    @if($deleteResultMessage)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 p-4" role="dialog" aria-modal="true">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-2xl">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full {{ $deleteResultType === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}"><span class="text-xl font-bold">{{ $deleteResultType === 'success' ? '✓' : '!' }}</span></div>
                <h3 class="mt-4 text-lg font-bold text-slate-950">{{ $deleteResultType === 'success' ? 'Xóa thành công' : 'Không thể xóa thuốc' }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $deleteResultMessage }}</p>
                <button type="button" wire:click="closeDeleteResult" class="mt-6 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">Đóng</button>
            </div>
        </div>
    @endif
</div>
