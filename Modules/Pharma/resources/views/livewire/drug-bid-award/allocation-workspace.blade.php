@php
    $admin = auth('admin')->user();
    $canManageAllocation = $admin?->can('manage_pharma_allocations') ?? false;
    $canCancelAllocation = $admin?->can('cancel_pharma_allocations') ?? false;
    $canViewContracts = $admin?->can('view_pharma_contracts') ?? false;
    $canManageContracts = $admin?->can('manage_pharma_contracts') ?? false;
    $canCancelContracts = $admin?->can('cancel_pharma_contracts') ?? false;
    $currentPage = $allocations->currentPage();
    $lastPage = $allocations->lastPage();
    $fmtQty = fn ($value) => rtrim(rtrim(number_format((float) $value, 4, ',', '.'), '0'), ',');
@endphp

<div class="space-y-6">
    <header class="border-b border-slate-200 pb-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Pharma · Drug Award Allocation</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-950 sm:text-3xl">Phân bổ bệnh viện & hợp đồng</h1>
        <p class="mt-2 text-sm text-slate-600">Bệnh viện nhận phân bổ là Partner nghiệp vụ, không phải Chủ đầu tư TBMT. Dữ liệu procurement bên dưới chỉ đọc và không bị thay đổi bởi workspace này.</p>
    </header>

    @if (session()->has('success'))<div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>@endif
    @if ($errors->any())<div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"><ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <section class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-2 xl:grid-cols-4">
        <div><p class="text-xs font-semibold uppercase text-slate-500">TBMT</p><p class="mt-1 font-mono text-sm font-semibold text-slate-900">{{ $award->bidding_notice_code ?: '—' }}</p><p class="mt-1 text-xs text-slate-500">Lô {{ $award->lot_no ?: '—' }} · {{ $award->lot_name ?: '—' }}</p></div>
        <div><p class="text-xs font-semibold uppercase text-slate-500">Thuốc</p><p class="mt-1 font-semibold text-slate-900">{{ $award->medicine_name ?: '—' }}</p><p class="mt-1 text-xs text-slate-500">{{ $award->active_ingredient ?: '—' }} · {{ $award->concentration ?: '—' }}</p></div>
        <div><p class="text-xs font-semibold uppercase text-slate-500">Chủ đầu tư TBMT</p><p class="mt-1 font-semibold text-slate-900">{{ $award->investor_name ?: '—' }}</p><p class="mt-1 text-xs text-slate-500">{{ $award->investor_code ?: '—' }}</p></div>
        <div><p class="text-xs font-semibold uppercase text-slate-500">Nhà thầu trúng</p><p class="mt-1 font-semibold text-slate-900">{{ $award->winning_company_name ?: '—' }}</p><p class="mt-1 text-xs text-slate-500">QĐ {{ $award->decision_number ?: '—' }} · {{ $award->decision_date?->format('d/m/Y') ?: '—' }}</p></div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-sm text-slate-500">Số lượng trúng</p><p class="mt-1 text-2xl font-bold text-slate-950">{{ $fmtQty($summary['winning_quantity']) }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-sm text-slate-500">Đã phân bổ</p><p class="mt-1 text-2xl font-bold text-indigo-700">{{ $fmtQty($summary['allocated_quantity']) }}</p></div>
        <div class="rounded-2xl border {{ $summary['remaining_quantity'] < 0 ? 'border-rose-300 bg-rose-50' : 'border-slate-200 bg-white' }} p-4 shadow-sm"><p class="text-sm text-slate-500">Còn lại</p><p class="mt-1 text-2xl font-bold {{ $summary['remaining_quantity'] < 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ $fmtQty($summary['remaining_quantity']) }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-sm text-slate-500">Bệnh viện / trạng thái</p><p class="mt-1 text-xl font-bold text-slate-950">{{ $summary['facility_count'] }}</p><span class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $summary['status'] === 'OVER_ALLOCATED' ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-700' }}">{{ $summary['status'] }}</span></div>
    </section>

    @if ($summary['status'] === 'OVER_ALLOCATED')
        <div role="alert" class="rounded-xl border border-rose-300 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">Dữ liệu đang OVER_ALLOCATED. Có thể do nguồn procurement điều chỉnh giảm số lượng trúng. Không tăng hoặc tạo thêm phân bổ cho đến khi được đối soát.</div>
    @endif

    @if ($canManageAllocation)
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="text-lg font-semibold text-slate-950">{{ $editingAllocationId ? 'Sửa phân bổ' : 'Thêm phân bổ' }}</h2><p class="mt-1 text-sm text-slate-500">Chỉ hiển thị Partner có legal_type=hospital và đang hoạt động.</p></div><div wire:loading wire:target="saveAllocation" class="text-sm font-semibold text-indigo-600">Đang lưu...</div></div>
            <div class="mt-4 grid gap-4 lg:grid-cols-12">
                <div class="lg:col-span-4"><label class="block text-sm font-medium text-slate-700">Bệnh viện</label><select wire:model="partnerId" @disabled($editingAllocationId) class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"><option value="">Chọn bệnh viện</option>@foreach ($partners as $partner)<option value="{{ $partner->id }}">{{ $partner->name }}{{ $partner->tax_code ? ' · ' . $partner->tax_code : '' }}</option>@endforeach</select></div>
                <div class="lg:col-span-2"><label class="block text-sm font-medium text-slate-700">Số lượng phân bổ</label><input type="number" step="0.0001" min="0.0001" wire:model="allocatedQuantity" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"></div>
                <div class="lg:col-span-2"><label class="block text-sm font-medium text-slate-700">Từ ngày</label><input type="date" wire:model="effectiveFrom" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm"></div>
                <div class="lg:col-span-2"><label class="block text-sm font-medium text-slate-700">Đến ngày</label><input type="date" wire:model="effectiveUntil" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm"></div>
                <div class="lg:col-span-2"><label class="block text-sm font-medium text-slate-700">Ghi chú</label><input type="text" wire:model="notes" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm"></div>
            </div>
            <div class="mt-4 flex justify-end"><button type="button" wire:click="saveAllocation" wire:loading.attr="disabled" wire:target="saveAllocation" @disabled($summary['status'] === 'OVER_ALLOCATED' && !$editingAllocationId) class="inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"><span wire:loading.remove wire:target="saveAllocation">Lưu phân bổ</span><span wire:loading wire:target="saveAllocation">Đang lưu...</span></button></div>
        </section>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div class="grid flex-1 gap-3 sm:grid-cols-3"><div><label class="block text-sm font-medium text-slate-700">Tìm bệnh viện</label><input type="search" wire:model.live.debounce.300ms="search" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"></div><div><label class="block text-sm font-medium text-slate-700">Trạng thái</label><select wire:model.live="filterStatus" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="">Tất cả</option><option value="active">Đang phân bổ</option><option value="cancelled">Đã hủy</option></select></div><div><label class="block text-sm font-medium text-slate-700">Số dòng</label><select wire:model.live="perPage" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">@foreach ($perPageOptions as $option)<option value="{{ $option }}">{{ $option }} / trang</option>@endforeach</select></div></div>
            <div class="flex flex-wrap gap-2"><button type="button" wire:click="exportAllocations" wire:loading.attr="disabled" wire:target="exportAllocations" class="min-h-11 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Xuất phân bổ{{ $selectedIds !== [] ? ' đã chọn' : '' }}</button>@if ($canViewContracts)<button type="button" wire:click="exportContracts" wire:loading.attr="disabled" wire:target="exportContracts" class="min-h-11 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Xuất hợp đồng</button>@endif</div>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-[1100px] w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-600"><tr><th class="w-12 px-4 py-3"><input type="checkbox" wire:model.live="selectPage" class="rounded border-slate-300"></th><th class="px-4 py-3">Bệnh viện</th><th class="px-4 py-3">Phân bổ</th><th class="px-4 py-3">Hiệu lực</th><th class="px-4 py-3">Hợp đồng</th><th class="px-4 py-3">Trạng thái</th><th class="px-4 py-3 text-right">Thao tác</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($allocations as $allocation)
                        @php $committed = $allocation->contracts->whereIn('status', \Modules\Pharma\Models\DrugBidAwardContract::COMMITTED_STATUSES)->sum(fn($contract) => (float) $contract->contract_quantity); @endphp
                        <tr class="align-top"><td class="px-4 py-4"><input type="checkbox" wire:model.live="selectedIds" value="{{ $allocation->id }}" class="rounded border-slate-300"></td><td class="px-4 py-4"><div class="font-semibold text-slate-950">{{ $allocation->partner?->name ?: '—' }}</div><div class="mt-1 text-xs text-slate-500">{{ $allocation->partner?->tax_code ?: 'Không MST' }}</div></td><td class="px-4 py-4"><div class="font-semibold text-indigo-700">{{ $fmtQty($allocation->allocated_quantity) }}</div><div class="mt-1 text-xs text-slate-500">Đã cam kết HĐ: {{ $fmtQty($committed) }}</div></td><td class="px-4 py-4 text-xs text-slate-600">{{ $allocation->effective_from?->format('d/m/Y') ?: '—' }} → {{ $allocation->effective_until?->format('d/m/Y') ?: '—' }}</td><td class="px-4 py-4"><div class="font-semibold">{{ $allocation->contracts->count() }} hợp đồng</div>@foreach ($allocation->contracts->take(3) as $contract)<div class="mt-1 text-xs text-slate-500">{{ $contract->contract_number }} · {{ $fmtQty($contract->contract_quantity) }} · {{ strtoupper($contract->status) }}</div>@endforeach</td><td class="px-4 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $allocation->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ strtoupper($allocation->status) }}</span></td><td class="px-4 py-4 text-right"><div class="flex flex-wrap justify-end gap-2">@if ($canManageAllocation && $allocation->status === 'active')<button type="button" wire:click="editAllocation({{ $allocation->id }})" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold">Sửa</button>@endif @if ($canManageContracts && $allocation->status === 'active')<button type="button" wire:click="openContractForm({{ $allocation->id }})" class="rounded-lg border border-indigo-200 px-3 py-2 text-xs font-semibold text-indigo-700">Hợp đồng</button>@endif</div></td></tr>
                        @if ($canViewContracts && $allocation->contracts->isNotEmpty())
                            <tr class="bg-slate-50/70"><td></td><td colspan="6" class="px-4 py-3"><div class="flex flex-wrap gap-2">@foreach ($allocation->contracts as $contract)<span class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs"><strong>{{ $contract->contract_number }}</strong><span>{{ strtoupper($contract->status) }}</span>@if ($canManageContracts && $contract->status !== 'cancelled')<button type="button" wire:click="editContract({{ $allocation->id }}, {{ $contract->id }})" class="font-semibold text-indigo-700">Sửa</button>@endif</span>@endforeach</div></td></tr>
                        @endif
                    @empty
                        <tr><td colspan="7" class="px-6 py-12 text-center text-slate-500">Chưa có phân bổ phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($lastPage > 1)<nav class="flex items-center justify-between border-t border-slate-200 px-4 py-4"><p class="text-sm text-slate-500">Trang {{ $currentPage }}/{{ $lastPage }}</p><div class="flex gap-2"><button type="button" wire:click="gotoPage({{ max(1, $currentPage - 1) }})" @disabled($allocations->onFirstPage()) class="min-h-10 rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold disabled:opacity-40">Trước</button><button type="button" wire:click="gotoPage({{ min($lastPage, $currentPage + 1) }})" @disabled(!$allocations->hasMorePages()) class="min-h-10 rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold disabled:opacity-40">Sau</button></div></nav>@endif
    </section>

    @if ($contractAllocationId && $canManageContracts)
        <section class="rounded-2xl border border-indigo-200 bg-indigo-50/30 p-5 shadow-sm"><h2 class="text-lg font-semibold text-slate-950">{{ $editingContractId ? 'Sửa hợp đồng' : 'Thêm hợp đồng' }} cho phân bổ #{{ $contractAllocationId }}</h2><div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4"><div><label class="block text-sm font-medium">Số hợp đồng</label><input wire:model="contractNumber" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5"></div><div><label class="block text-sm font-medium">Ngày hợp đồng</label><input type="date" wire:model="contractDate" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5"></div><div><label class="block text-sm font-medium">Số lượng hợp đồng</label><input type="number" step="0.0001" wire:model="contractQuantity" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5"></div><div><label class="block text-sm font-medium">Giá trị hợp đồng</label><input type="number" step="0.0001" wire:model="contractValue" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5"></div><div><label class="block text-sm font-medium">Từ ngày</label><input type="date" wire:model="contractStartDate" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5"></div><div><label class="block text-sm font-medium">Đến ngày</label><input type="date" wire:model="contractEndDate" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5"></div><div><label class="block text-sm font-medium">Trạng thái</label><select wire:model="contractStatus" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5"><option value="draft">Nháp</option><option value="signed">Đã ký</option><option value="in_progress">Đang thực hiện</option><option value="completed">Hoàn thành</option></select></div><div><label class="block text-sm font-medium">Ghi chú</label><input wire:model="contractNotes" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5"></div></div><div class="mt-4 flex justify-end"><button type="button" wire:click="saveContract" wire:loading.attr="disabled" wire:target="saveContract" class="min-h-11 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50"><span wire:loading.remove wire:target="saveContract">Lưu hợp đồng</span><span wire:loading wire:target="saveContract">Đang lưu...</span></button></div></section>
    @endif

    @if ($canCancelAllocation || $canCancelContracts)
        <section class="rounded-2xl border border-rose-200 bg-rose-50/40 p-5"><h2 class="font-semibold text-rose-900">Hủy có kiểm soát</h2><p class="mt-1 text-sm text-rose-700">Hệ thống không hard-delete allocation/contract. Nhập lý do trước khi dùng thao tác hủy trong nghiệp vụ.</p><div class="mt-3 grid gap-3 md:grid-cols-2">@if ($canCancelAllocation)<input wire:model="allocationCancelReason" placeholder="Lý do hủy phân bổ" class="min-h-11 rounded-xl border border-rose-300 bg-white px-3 py-2.5 text-sm">@endif @if ($canCancelContracts)<input wire:model="contractCancelReason" placeholder="Lý do hủy hợp đồng" class="min-h-11 rounded-xl border border-rose-300 bg-white px-3 py-2.5 text-sm">@endif</div><p class="mt-2 text-xs text-rose-700">Các nút hủy được thực hiện từ nghiệp vụ tương ứng sau khi lý do đã được nhập; allocation có hợp đồng cam kết sẽ bị chặn hủy.</p></section>
    @endif
</div>
