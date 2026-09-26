@php
    $currentPage = $products->currentPage();
    $lastPage = $products->lastPage();
    $fmtQty = fn ($value) => rtrim(rtrim(number_format((float) $value, 4, ',', '.'), '0'), ',');
@endphp

<div class="space-y-6">
    <header class="flex flex-col gap-3 border-b border-slate-200 pb-5 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Pharma · Kết quả trúng thầu</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-950 sm:text-3xl">Sản phẩm trúng thầu</h1>
            <p class="mt-2 text-sm text-slate-600">Chọn từng sản phẩm để phân bổ số lượng cho bệnh viện/đơn vị nhận. Chủ đầu tư TBMT chỉ là dữ liệu procurement.</p>
        </div>
        <div class="rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3">
            <p class="text-xs font-semibold uppercase text-indigo-600">Mã TBMT</p>
            <p class="mt-1 font-mono text-base font-bold text-indigo-950">{{ $result->bidding_notice_code ?: '—' }}</p>
        </div>
    </header>

    <section class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-2 xl:grid-cols-4">
        <div><p class="text-xs font-semibold uppercase text-slate-500">Chủ đầu tư</p><p class="mt-1 font-semibold text-slate-950">{{ $result->investor_name ?: '—' }}</p><p class="mt-1 text-xs text-slate-500">{{ $result->investor_code ?: '—' }}</p></div>
        <div><p class="text-xs font-semibold uppercase text-slate-500">Quyết định</p><p class="mt-1 font-semibold text-slate-950">{{ $result->decision_number ?: '—' }}</p><p class="mt-1 text-xs text-slate-500">{{ $result->decision_date?->format('d/m/Y') ?: '—' }}</p></div>
        <div><p class="text-xs font-semibold uppercase text-slate-500">Nhà thầu</p><p class="mt-1 font-semibold text-slate-950">{{ $result->winning_company_name ?: '—' }}</p><p class="mt-1 text-xs text-slate-500">Mỗi sản phẩm vẫn giữ nhà thầu gốc nếu TBMT có nhiều nhà thầu.</p></div>
        <div><p class="text-xs font-semibold uppercase text-slate-500">Số sản phẩm</p><p class="mt-1 text-2xl font-bold text-slate-950">{{ number_format($products->total()) }}</p><p class="mt-1 text-xs text-slate-500">Theo mã TBMT hiện tại</p></div>
    </section>

    @if (session()->has('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"><ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <section x-data="{ open: {{ count($selectedFacilityIds) === 0 ? 'true' : 'false' }} }" class="rounded-2xl border border-indigo-200 bg-indigo-50/30 p-5 shadow-sm">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <div><p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Thiết lập chung</p><h2 class="mt-1 text-lg font-bold text-slate-950">Phạm vi & hiệu lực phân bổ</h2><p class="mt-1 text-sm text-slate-600">Thiết lập một lần cho toàn bộ sản phẩm thuộc TBMT. Mỗi sản phẩm chỉ được phân bổ cho các bệnh viện đã duyệt bên dưới.</p></div>
            <div class="flex flex-wrap items-center gap-2"><span class="rounded-xl bg-white px-4 py-2 text-xs font-medium text-slate-600 shadow-sm">{{ count($selectedFacilityIds) }} cơ sở được chọn</span><button type="button" x-on:click="open = !open" class="min-h-10 rounded-xl border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50"><span x-text="open ? 'Ẩn thiết lập' : 'Hiển thị / Chỉnh sửa'"></span></button></div>
        </div>
        <div x-show="open" x-collapse class="mt-5 grid items-start gap-4 xl:grid-cols-3">
            <div class="space-y-4 rounded-xl border border-slate-200 bg-white p-4">
                <div><p class="text-xs font-bold uppercase tracking-wide text-indigo-600">Bước 1 · Phạm vi</p><label class="mt-2 block text-sm font-semibold text-slate-700">Tỉnh/Thành trúng thầu *</label>
                    @if($selectedProvinces !== [])<div class="mt-2 flex flex-wrap gap-2">@foreach($selectedProvinces as $province)<span wire:key="selected-province-chip-{{ md5($province) }}" class="inline-flex items-center rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ $province }}</span>@endforeach</div>@endif
                    <input type="search" wire:model.live.debounce.250ms="provinceSearch" placeholder="Tìm Tỉnh/Thành..." class="mt-2 min-h-10 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                    <div class="mt-2 grid max-h-40 gap-2 overflow-y-auto rounded-xl border border-slate-200 p-3">@forelse ($provinceOptions as $province)<label wire:key="award-province-{{ md5($province) }}" class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" wire:model.live="selectedProvinces" value="{{ $province }}" class="h-4 w-4 rounded border-slate-300 text-indigo-600"><span>{{ $province }}</span></label>@empty<p class="text-xs text-slate-500">Không tìm thấy Tỉnh/Thành phù hợp.</p>@endforelse</div><p class="mt-1 text-xs text-slate-500">Có thể tìm và chọn nhiều tỉnh/thành. Tìm kiếm không làm mất các tỉnh hoặc cơ sở KCB đã chọn.</p></div>
                <div class="grid grid-cols-2 gap-3"><div><label class="block text-sm font-semibold text-slate-700">Từ ngày *</label><input type="date" wire:model="effectiveFrom" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"></div><div><label class="block text-sm font-semibold text-slate-700">Đến ngày *</label><input type="date" wire:model="effectiveUntil" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"></div></div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-bold uppercase tracking-wide text-indigo-600">Bước 2 · Cơ sở nhận phân bổ</p><label class="mt-2 block text-sm font-semibold text-slate-700">Cơ sở KCB được phân bổ *</label></div><div class="grid w-full gap-2 sm:max-w-xl sm:grid-cols-2"><select wire:model.live="facilityProvince" class="min-h-10 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm"><option value="">Chọn tỉnh đang xem</option>@foreach($selectedProvinces as $province)<option value="{{ $province }}">{{ $province }}</option>@endforeach</select><input type="search" wire:model.live.debounce.300ms="facilitySearch" placeholder="Tìm tên hoặc mã cơ sở..." class="min-h-10 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></div></div>
                <div class="mt-3 max-h-64 overflow-y-auto rounded-xl border border-slate-200">@forelse ($facilities as $facility)<label wire:key="award-facility-{{ $facility->id }}" class="flex cursor-pointer items-start gap-3 border-b border-slate-100 px-3 py-2.5 last:border-0 hover:bg-slate-50"><input type="checkbox" wire:model.live="selectedFacilityIds" value="{{ $facility->id }}" class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600"><span class="min-w-0"><span class="block text-sm font-semibold text-slate-900">{{ $facility->facility_name }}</span><span class="block text-xs text-slate-500">Mã CSKCB: {{ $facility->external_id ?: '—' }}{{ $facility->district_name ? ' · '.$facility->district_name : '' }}</span></span></label>@empty<p class="px-4 py-8 text-center text-sm text-slate-500">{{ $selectedProvinces !== [] ? 'Không có cơ sở KCB nguồn phù hợp.' : 'Chọn ít nhất một Tỉnh/Thành để tải danh sách cơ sở KCB.' }}</p>@endforelse</div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div><p class="text-xs font-bold uppercase tracking-wide text-indigo-600">Bước 3 · Kiểm tra trước khi lưu</p><h3 class="mt-1 text-sm font-bold text-slate-950">Cơ sở KCB đã chọn</h3><p class="mt-1 text-xs text-slate-500">Danh sách này luôn hiển thị đầy đủ các cơ sở đã chọn, không phụ thuộc bộ lọc tìm kiếm ở Bước 2.</p></div>
                <span class="inline-flex w-fit rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700">{{ $selectedFacilities->count() }} cơ sở</span>
            </div>
            <div class="mt-3 grid max-h-64 gap-2 overflow-y-auto">
                @forelse ($selectedFacilities as $facility)
                    <div wire:key="selected-award-facility-{{ $facility->id }}" class="flex min-w-0 items-start justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                        <div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-900">{{ $facility->facility_name }}</p><p class="mt-1 text-xs text-slate-500">Mã CSKCB: {{ $facility->external_id ?: '—' }}{{ $facility->district_name ? ' · '.$facility->district_name : '' }}{{ $facility->province_name ? ' · '.$facility->province_name : '' }}</p></div>
                        <button type="button" wire:click="removeSelectedFacility({{ $facility->id }})" class="shrink-0 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:border-rose-300 hover:text-rose-700">Bỏ chọn</button>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">Chưa có cơ sở KCB nào được chọn. Chọn cơ sở ở Bước 2 để kiểm tra tại đây trước khi lưu.</div>
                @endforelse
            </div>
            <div class="mt-4 flex justify-end"><button type="button" wire:click="saveDistributionScope" wire:loading.attr="disabled" wire:target="saveDistributionScope" @disabled($selectedFacilities->isEmpty()) class="min-h-11 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">Lưu thiết lập phân bổ</button></div>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_180px]">
            <label class="block text-sm font-medium text-slate-700">Tìm sản phẩm<input type="search" wire:model.live.debounce.300ms="search" placeholder="Tên thuốc, hoạt chất, lô, số đăng ký..." class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"></label>
            <label class="block text-sm font-medium text-slate-700">Hiển thị<select wire:model.live="perPage" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">@foreach ($perPageOptions as $option)<option value="{{ $option }}">{{ $option }} / trang</option>@endforeach</select></label>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-[1180px] w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-600"><tr><th class="px-4 py-3">Sản phẩm</th><th class="px-4 py-3">Lô</th><th class="px-4 py-3">Nhà thầu</th><th class="px-4 py-3">Giá trúng</th><th class="px-4 py-3">SL trúng</th><th class="px-4 py-3">Đã phân bổ</th><th class="px-4 py-3">Còn lại</th><th class="px-4 py-3">HSSP</th><th class="px-4 py-3 text-right">Thao tác</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($products as $product)
                    @php
                        $name = $product->effectiveMedicineAttribute('medicine_name');
                        $ingredient = $product->effectiveMedicineAttribute('active_ingredient');
                        $allocated = (float) $product->allocations->sum('allocated_quantity');
                        $winning = (float) ($product->quantity ?? 0);
                        $remaining = $winning - $allocated;
                        $price = $product->winning_price ?? $product->unit_price;
                    @endphp
                    <tr class="align-top hover:bg-slate-50">
                        <td class="min-w-64 px-4 py-4"><div class="font-semibold text-slate-950">{{ $name['value'] ?: '—' }}</div><div class="mt-1 text-xs text-slate-500">{{ $ingredient['value'] ?: '—' }} · {{ $product->concentration ?: '—' }}</div></td>
                        <td class="min-w-40 px-4 py-4"><div class="font-mono text-xs font-semibold">{{ $product->lot_no ?: '—' }}</div><div class="mt-1 text-xs text-slate-500">{{ $product->lot_name ?: '—' }}</div></td>
                        <td class="min-w-52 px-4 py-4"><div class="font-medium text-slate-900">{{ $product->winning_company_name ?: '—' }}</div><div class="mt-1 text-xs text-slate-500">{{ $product->contractor_code ?: '—' }}</div></td>
                        <td class="px-4 py-4 font-semibold text-indigo-700">{{ $price !== null ? number_format((float) $price, 0, ',', '.') . ' VNĐ' : '—' }}</td>
                        <td class="px-4 py-4 font-semibold">{{ $fmtQty($winning) }}</td>
                        <td class="px-4 py-4 font-semibold text-indigo-700">{{ $fmtQty($allocated) }}</td>
                        <td class="px-4 py-4 font-semibold {{ $remaining < 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ $fmtQty($remaining) }}</td>
                        <td class="px-4 py-4"><span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $product->medicine_match_status === 'verified' ? 'Đã đối soát' : 'Cần rà soát' }}</span></td>
                        <td class="px-4 py-4 text-right"><a href="{{ route('admin.pharma.drug-bid-awards.allocation-detail', $product->id) }}" class="inline-flex min-h-10 items-center rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Phân bổ</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-6 py-12 text-center text-slate-500">Không có sản phẩm phù hợp.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($lastPage > 1)<nav class="flex items-center justify-between border-t border-slate-200 px-4 py-4"><p class="text-sm text-slate-500">Trang {{ $currentPage }}/{{ $lastPage }}</p><div class="flex gap-2"><button type="button" wire:click="gotoPage({{ max(1, $currentPage - 1) }})" @disabled($products->onFirstPage()) class="min-h-10 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold disabled:opacity-40">Trước</button><button type="button" wire:click="gotoPage({{ min($lastPage, $currentPage + 1) }})" @disabled(!$products->hasMorePages()) class="min-h-10 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold disabled:opacity-40">Sau</button></div></nav>@endif
    </section>
</div>
