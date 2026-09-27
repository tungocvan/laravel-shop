@php
    $admin = auth('admin')->user();
    $canManageAllocation = $admin?->can('manage_pharma_allocations') ?? false;
    $canCancelAllocation = $admin?->can('cancel_pharma_allocations') ?? false;
    $canViewContracts = $admin?->can('view_pharma_contracts') ?? false;
    $canManageContracts = $admin?->can('manage_pharma_contracts') ?? false;
    $currentPage = $allocations->currentPage();
    $lastPage = $allocations->lastPage();
    $fmtQty = fn ($value) => rtrim(rtrim(number_format((float) $value, 4, ',', '.'), '0'), ',');
@endphp

<div class="space-y-6">
    <header class="border-b border-slate-200 pb-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Pharma · Drug Award Allocation</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-950 sm:text-3xl">Phân bổ bệnh viện & hợp đồng</h1>
        <p class="mt-2 text-sm text-slate-600">Bệnh viện nhận phân bổ là Partner nghiệp vụ, không phải Chủ đầu tư TBMT. Dữ liệu procurement bên dưới chỉ đọc.</p>
    </header>

    @if (session()->has('success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"><ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

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
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-sm text-slate-500">Số bệnh viện</p><p class="mt-1 text-xl font-bold text-slate-950">{{ $summary['facility_count'] }} bệnh viện</p><span class="mt-2 inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $summary['status'] }}</span></div>
    </section>

    @if ($summary['status'] === 'OVER_ALLOCATED')
        <div role="alert" class="rounded-xl border border-rose-300 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">Dữ liệu đang OVER_ALLOCATED. Không tăng hoặc tạo thêm phân bổ cho đến khi được đối soát.</div>
    @endif

    <section class="rounded-2xl border border-indigo-200 bg-indigo-50/30 p-4 shadow-sm">
        <div class="grid gap-4 md:grid-cols-3">
            <div><p class="text-xs font-semibold uppercase text-indigo-600">Tỉnh/Thành trúng thầu</p><p class="mt-1 font-semibold text-slate-950">{{ $distributionScope?->province_code ?: 'Chưa thiết lập' }}</p></div>
            <div><p class="text-xs font-semibold uppercase text-indigo-600">Hiệu lực chung</p><p class="mt-1 font-semibold text-slate-950">{{ $distributionScope?->effective_from?->format('d/m/Y') ?: '—' }} → {{ $distributionScope?->effective_until?->format('d/m/Y') ?: '—' }}</p></div>
            <div><p class="text-xs font-semibold uppercase text-indigo-600">Bệnh viện được phép nhận</p><p class="mt-1 font-semibold text-slate-950">{{ $distributionScope?->partners?->count() ?? 0 }} bệnh viện</p></div>
        </div>
        @if (!$distributionScope)<p class="mt-3 text-sm font-medium text-amber-700">Hãy quay lại “Danh sách sản phẩm” để thiết lập phạm vi phân bổ trước khi tạo phân bổ số lượng.</p>@endif
    </section>

    @if ($canManageAllocation)
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div><h2 class="text-lg font-semibold text-slate-950">{{ $editingAllocationId ? 'Sửa phân bổ' : 'Thêm phân bổ' }}</h2><p class="mt-1 text-sm text-slate-500">Chỉ hiển thị các bệnh viện đã được duyệt trong phạm vi phân bổ của TBMT.</p></div>
                <div wire:loading wire:target="saveAllocation" class="text-sm font-semibold text-indigo-600">Đang lưu...</div>
            </div>
            <div class="mt-4 grid gap-4 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <label class="block text-sm font-medium text-slate-700">Bệnh viện</label>
                    @if ($editingAllocationId)
                        <div class="mt-1 min-h-11 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-900">{{ $editingPartnerName ?: '—' }}</div>
                        <p class="mt-1 text-xs text-slate-500">Bệnh viện được khóa khi sửa để tránh chuyển nhầm phân bổ.</p>
                    @else
                        <div class="mt-1"><x-select-search id="pharma-allocation-hospital" wire:model="partnerId" placeholder="Tìm hoặc chọn bệnh viện..."><option value="">Chọn bệnh viện</option>@foreach ($partners as $partner)<option value="{{ $partner->id }}">{{ $partner->name }}{{ $partner->tax_code ? ' · '.$partner->tax_code : '' }}</option>@endforeach</x-select-search></div>
                    @endif
                </div>
                <div class="lg:col-span-2"><label class="block text-sm font-medium text-slate-700">Số lượng phân bổ</label><input type="{{ $editingAllocationId ? 'text' : 'number' }}" inputmode="decimal" step="0.0001" min="0.0001" wire:model="allocatedQuantity" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm"><p class="mt-1 text-xs text-slate-500">{{ $editingAllocationId ? 'Hiển thị theo định dạng 20.000; không còn .0000.' : 'Nhập số lượng cần phân bổ.' }}</p></div>
                <div class="lg:col-span-4"><label class="block text-sm font-medium text-slate-700">Ghi chú</label><input type="text" wire:model="notes" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm shadow-sm"></div>
            </div>
            <div class="mt-4 flex justify-end gap-2">@if($editingAllocationId)<button type="button" wire:click="cancelAllocationEdit" class="min-h-11 rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700">Hủy sửa</button>@endif<button type="button" wire:click="saveAllocation" wire:loading.attr="disabled" wire:target="saveAllocation" @disabled(!$distributionScope || ($summary['status'] === 'OVER_ALLOCATED' && !$editingAllocationId)) class="min-h-11 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Lưu phân bổ</button></div>
        </section>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div class="grid flex-1 gap-3 sm:grid-cols-3">
                <div><label class="block text-sm font-medium text-slate-700">Tìm bệnh viện</label><input type="search" wire:model.live.debounce.300ms="search" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"></div>
                <div><label class="block text-sm font-medium text-slate-700">Trạng thái</label><select wire:model.live="filterStatus" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="">Tất cả</option><option value="active">Đang phân bổ</option><option value="cancelled">Tạm ngưng</option></select></div>
                <div><label class="block text-sm font-medium text-slate-700">Số dòng</label><select wire:model.live="perPage" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">@foreach ($perPageOptions as $option)<option value="{{ $option }}">{{ $option }} / trang</option>@endforeach</select></div>
            </div>
            <div class="flex flex-wrap items-center gap-2">@if($selectedIds !== [])<span class="rounded-lg bg-indigo-50 px-3 py-2 text-sm font-semibold text-indigo-700">Đã chọn {{ count($selectedIds) }} bệnh viện</span>@endif<button type="button" wire:click="exportAllocations" class="min-h-11 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold">Xuất phân bổ{{ $selectedIds !== [] ? ' đã chọn' : '' }}</button>@if ($canViewContracts)<button type="button" wire:click="exportContracts" class="min-h-11 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold">Xuất hợp đồng{{ $selectedIds !== [] ? ' đã chọn' : '' }}</button>@endif</div>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto"><table class="min-w-[1180px] w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-600"><tr><th class="w-12 px-4 py-3"><input type="checkbox" wire:model.live="selectPage"></th><th class="px-4 py-3">Bệnh viện</th><th class="px-4 py-3">Phân bổ</th><th class="px-4 py-3">Hiệu lực</th><th class="px-4 py-3">Hợp đồng</th><th class="px-4 py-3">Trạng thái</th><th class="px-4 py-3">Tạm ngưng</th><th class="px-4 py-3 text-right">Thao tác</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($allocations as $allocation)
                    @php $committed = $allocation->contracts->whereIn('status', \Modules\Pharma\Models\DrugBidAwardContract::COMMITTED_STATUSES)->sum(fn ($contract) => (float) $contract->contract_quantity); @endphp
                    <tr class="align-top">
                        <td class="px-4 py-4"><input type="checkbox" wire:model.live="selectedIds" value="{{ $allocation->id }}"></td>
                        <td class="px-4 py-4"><div class="font-semibold text-slate-950">{{ $allocation->partner?->name ?: '—' }}</div><div class="mt-1 text-xs text-slate-500">{{ $allocation->partner?->tax_code ?: 'Không MST' }}</div></td>
                        <td class="px-4 py-4"><div class="font-semibold text-indigo-700">{{ $fmtQty($allocation->allocated_quantity) }}</div><div class="mt-1 text-xs text-slate-500">Đã cam kết HĐ: {{ $fmtQty($committed) }}</div></td>
                        <td class="px-4 py-4 text-xs text-slate-600">{{ $allocation->effective_from?->format('d/m/Y') ?: '—' }} → {{ $allocation->effective_until?->format('d/m/Y') ?: '—' }}</td>
                        <td class="px-4 py-4"><div class="font-semibold {{ $allocation->contracts->isEmpty() ? 'text-slate-500' : 'text-slate-950' }}">{{ $allocation->contracts->isEmpty() ? 'Chưa có hợp đồng' : $allocation->contracts->count().' hợp đồng' }}</div>@foreach ($allocation->contracts->take(3) as $contract)<div class="mt-1 text-xs text-slate-500">{{ $contract->contract_number }} · {{ $fmtQty($contract->contract_quantity) }} · {{ strtoupper($contract->status) }}</div>@endforeach</td>
                        <td class="px-4 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $allocation->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $allocation->status === 'active' ? 'ACTIVE' : 'TẠM NGƯNG' }}</span></td>
                        <td class="px-4 py-4"><input type="checkbox" @checked($allocation->status !== 'active') @disabled(($allocation->status === 'active' && !$canCancelAllocation) || ($allocation->status !== 'active' && !$canManageAllocation)) wire:change="toggleAllocationPause({{ $allocation->id }}, $event.target.checked)" class="h-4 w-4 rounded border-slate-300 text-indigo-600"><span class="ml-2 text-xs text-slate-500">Tạm ngưng</span></td>
                        <td class="px-4 py-4 text-right"><div class="flex justify-end gap-2">@if ($canManageAllocation && $allocation->status === 'active')<button type="button" wire:click="editAllocation({{ $allocation->id }})" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold">Sửa</button>@endif @if ($canManageContracts && $allocation->status === 'active')@if($allocation->contracts->isEmpty())<button type="button" wire:click="openContractForm({{ $allocation->id }})" class="rounded-lg border border-indigo-200 px-3 py-2 text-xs font-semibold text-indigo-700">Tạo hợp đồng</button>@else<button type="button" wire:click="editContract({{ $allocation->id }}, {{ $allocation->contracts->first()->id }})" class="rounded-lg border border-indigo-200 px-3 py-2 text-xs font-semibold text-indigo-700">Sửa hợp đồng</button>@endif @endif</div></td>
                    </tr>
                    @if ($canViewContracts && $allocation->contracts->isNotEmpty())
                        <tr class="bg-slate-50/70"><td></td><td colspan="7" class="px-4 py-3"><div class="flex flex-wrap gap-2">@foreach ($allocation->contracts as $contract)<span class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs"><strong>{{ $contract->contract_number }}</strong><span>{{ strtoupper($contract->status) }}</span>@if ($canManageContracts && $contract->status !== 'cancelled')<button type="button" wire:click="editContract({{ $allocation->id }}, {{ $contract->id }})" class="font-semibold text-indigo-700">Sửa</button>@endif</span>@endforeach</div></td></tr>
                    @endif
                @empty
                    <tr><td colspan="8" class="px-6 py-12 text-center text-slate-500">Chưa có phân bổ phù hợp.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        @if ($lastPage > 1)<nav class="flex items-center justify-between border-t border-slate-200 px-4 py-4"><p class="text-sm text-slate-500">Trang {{ $currentPage }}/{{ $lastPage }}</p><div class="flex gap-2"><button type="button" wire:click="gotoPage({{ max(1, $currentPage - 1) }})" @disabled($allocations->onFirstPage()) class="min-h-10 rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold disabled:opacity-40">Trước</button><button type="button" wire:click="gotoPage({{ min($lastPage, $currentPage + 1) }})" @disabled(!$allocations->hasMorePages()) class="min-h-10 rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold disabled:opacity-40">Sau</button></div></nav>@endif
    </section>

    @if ($contractAllocationId && $canManageContracts)
        @php($editingContract = $editingContractId ? $allocations->getCollection()->flatMap->contracts->firstWhere('id', $editingContractId) : null)
        @php($localExists = (bool) ($editingContract?->signed_file_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($editingContract->signed_file_path)))
        @php($driveExists = (bool) ($editingContract?->signed_file_remote_id))
        <section id="contract-editor" x-init="$nextTick(() => $el.scrollIntoView({ behavior: 'smooth', block: 'center' }))" class="rounded-2xl border border-indigo-300 bg-indigo-50/30 p-5 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div><h2 class="text-lg font-semibold text-slate-950">{{ $editingContractId ? 'Sửa hợp đồng' : 'Thêm hợp đồng' }} · {{ $contractPartnerName ?: 'Bệnh viện' }}</h2><p class="mt-1 text-xs text-slate-500">Thông tin ký kết và tài liệu hợp đồng được quản lý độc lập.</p></div>
                <button type="button" wire:click="closeContractForm" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700">Đóng</button>
            </div>

            <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                <h3 class="text-sm font-semibold text-slate-900">Thông tin hợp đồng</h3>
                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-10">
                    <div class="lg:col-span-2"><label class="block text-sm font-medium">Số hợp đồng</label><input wire:model="contractNumber" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2.5"></div>
                    <div class="lg:col-span-2"><label class="block text-sm font-medium">Ngày ký</label><input type="date" wire:model="contractDate" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2.5"></div>
                    <div class="lg:col-span-2"><label class="block text-sm font-medium">Ngày kết thúc</label><input type="date" wire:model="contractEndDate" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2.5"></div>
                    <div class="lg:col-span-2"><label class="block text-sm font-medium">Giá trị hợp đồng</label><div class="relative mt-1"><input type="text" inputmode="numeric" wire:model="contractValue" class="min-h-11 w-full rounded-xl border border-slate-300 py-2.5 pl-3 pr-12 text-right font-medium" placeholder="0"><span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs font-semibold text-slate-400">VNĐ</span></div></div>
                    <div class="lg:col-span-2"><label class="block text-sm font-medium">Trạng thái</label><select wire:model="contractStatus" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2.5"><option value="draft">Nháp</option><option value="signed">Đã ký</option><option value="in_progress">Đang thực hiện</option><option value="completed">Hoàn thành</option></select></div>
                    <div class="sm:col-span-2 lg:col-span-10"><label class="block text-sm font-medium">Ghi chú</label><input wire:model="contractNotes" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3 py-2.5" placeholder="Ghi chú nội bộ về hợp đồng (nếu có)"></div>
                </div>
            </div>

            <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div><h3 class="text-sm font-semibold text-slate-900">File hợp đồng đã ký</h3><p class="mt-1 text-xs text-slate-500">{{ $editingContractId ? 'Quản lý file và đồng bộ Local ↔ Google Drive độc lập với thông tin hợp đồng.' : 'Chọn file và nơi lưu trước khi tạo hợp đồng.' }}</p></div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $googleDriveConnected ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">Google Drive {{ $googleDriveConnected ? 'đã kết nối' : 'chưa kết nối' }}</span>
                </div>

                @if($editingContractId)
                    @if($editingContract && $editingContract->signed_file_name)
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="min-w-0"><div class="truncate text-sm font-semibold text-slate-900">{{ $editingContract->signed_file_name }}</div><div class="mt-1 text-xs text-slate-500">{{ strtoupper(pathinfo($editingContract->signed_file_name, PATHINFO_EXTENSION)) ?: 'FILE' }}@if($editingContract->signed_file_size) · {{ number_format($editingContract->signed_file_size / 1048576, 2, ',', '.') }} MB @endif</div></div>
                            <div x-data="{ open: false }" class="relative flex items-center gap-2">
                                @if($localExists)<button type="button" wire:click="downloadSignedContract({{ $editingContract->id }})" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold">Tải xuống</button>@endif
                                <button type="button" @click="open = !open" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-bold text-slate-600" aria-label="Thao tác file">•••</button>
                                <div x-cloak x-show="open" @click.outside="open = false" class="absolute right-0 top-10 z-20 w-44 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg">
                                    @if($localExists)<button type="button" wire:click="deleteSignedContractLocal({{ $editingContract->id }})" wire:confirm="Xóa bản Local của file hợp đồng?" class="block w-full rounded-lg px-3 py-2 text-left text-xs font-semibold text-rose-600 hover:bg-rose-50">Xóa bản Local</button>@endif
                                    @if($driveExists)<button type="button" wire:click="deleteSignedContractDrive({{ $editingContract->id }})" wire:confirm="Xóa bản Google Drive của file hợp đồng?" class="block w-full rounded-lg px-3 py-2 text-left text-xs font-semibold text-rose-600 hover:bg-rose-50">Xóa bản Google Drive</button>@endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="mt-3 grid gap-3 lg:grid-cols-2">
                        <div class="rounded-xl border px-4 py-3 {{ $localExists ? 'border-emerald-200 bg-emerald-50/40' : 'border-slate-200' }}">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div><div class="text-sm font-semibold">Local <span class="ml-2 text-xs font-bold {{ $localExists ? 'text-emerald-700' : 'text-slate-500' }}">{{ $localExists ? '✓ Có file' : '— Chưa có' }}</span></div><p class="mt-1 text-xs text-slate-500">Bản riêng tư trên máy chủ.</p></div>
                                @if(!$localExists && $driveExists)<button type="button" wire:click="restoreSignedContractFromDrive({{ $editingContract->id }})" class="rounded-lg border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700">Drive → Local</button>@endif
                            </div>
                        </div>
                        <div class="rounded-xl border px-4 py-3 {{ $driveExists ? 'border-sky-200 bg-sky-50/40' : 'border-slate-200' }}">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div><div class="text-sm font-semibold">Google Drive <span class="ml-2 text-xs font-bold {{ $driveExists ? 'text-sky-700' : 'text-slate-500' }}">{{ $driveExists ? '✓ Có file' : '— Chưa có' }}</span></div><p class="mt-1 text-xs text-slate-500">Bản sao trên Google Drive.</p></div>
                                @if($localExists && !$driveExists && $googleDriveConnected)<button type="button" wire:click="backupSignedContractToDrive({{ $editingContract->id }})" class="rounded-lg border border-sky-200 bg-white px-3 py-2 text-xs font-semibold text-sky-700">Local → Drive</button>@endif
                            </div>
                        </div>
                    </div>
                    @if($localExists && $driveExists)<div class="mt-2 text-center text-xs font-semibold text-emerald-700">✓ File hiện có ở cả Local và Google Drive</div>@endif
                @endif

                <div class="{{ $editingContractId ? 'mt-4 border-t border-slate-100 pt-4' : 'mt-3' }}">
                    <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                        <div class="max-w-3xl">
                            <label class="block text-sm font-semibold text-slate-800">{{ $editingContract?->signed_file_name ? 'Thay file hợp đồng' : 'Chọn file hợp đồng' }}</label>
                            <div class="mt-2 rounded-xl border border-dashed border-slate-300 bg-slate-50/70 px-4 py-3">
                                <input type="file" wire:model="signedContractFile" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm text-slate-600">
                                <p class="mt-1 text-xs text-slate-500">PDF/JPG/PNG · tối đa 20 MB.</p>
                            </div>
                        </div>
                        <div>
                            <div class="mb-1 text-xs font-semibold text-slate-600">Lưu tại</div>
                            <div class="flex flex-wrap gap-2">
                                <label class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-xs font-semibold"><input type="checkbox" wire:model="contractStorageTargets" value="local" class="rounded border-slate-300"> Local</label>
                                <label class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-xs font-semibold {{ $googleDriveConnected ? '' : 'opacity-50' }}"><input type="checkbox" wire:model="contractStorageTargets" value="google_drive" @disabled(!$googleDriveConnected) class="rounded border-slate-300"> Google Drive</label>
                            </div>
                        </div>
                    </div>
                    @error('signedContractFile')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                    @error('contractStorageTargets')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    @if(!$editingContractId && $returnToContractId)<button type="button" wire:click="cancelCreateAnotherContract" class="min-h-11 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">← Hủy thêm · Quay lại hợp đồng trước</button>@endif
                    @if($editingContractId)<button type="button" wire:click="createAnotherContract" class="min-h-11 rounded-xl border border-indigo-200 bg-white px-5 py-2.5 text-sm font-semibold text-indigo-700">+ Thêm hợp đồng khác</button>@endif
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="closeContractForm" class="min-h-11 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">{{ $editingContractId ? 'Đóng' : 'Hủy' }}</button>
                    <button type="button" wire:click="saveContract" wire:loading.attr="disabled" wire:target="saveContract,signedContractFile" class="min-h-11 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50">{{ $editingContractId ? 'Lưu thay đổi' : 'Lưu hợp đồng' }}</button>
                </div>
            </div>
        </section>
    @endif
</div>