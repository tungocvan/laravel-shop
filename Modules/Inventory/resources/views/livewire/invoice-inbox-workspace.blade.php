<div class="space-y-5">
    @if($errorMessage)<div role="alert" class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-900">{{ $errorMessage }}</div>@endif
    @if($successMessage)<div role="status" class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ $successMessage }}</div>@endif

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach([['Chờ xử lý',$stats['pending'],'RECEIVED'],['Cần đối chiếu',$stats['review'],'REVIEW_REQUIRED'],['Sẵn sàng',$stats['ready'],'READY'],['Đã tạo phiếu',$stats['created'],'RECEIPT_CREATED']] as [$label,$count,$filter])
            <button type="button" wire:click="$set('status', '{{ $filter }}')" class="rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:border-indigo-300 hover:shadow">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</div>
                <div class="mt-2 text-2xl font-bold text-slate-900">{{ $count }}</div>
            </button>
        @endforeach
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 p-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="font-semibold text-slate-900">Inventory Receiving Inbox</h2>
                <p class="mt-1 text-sm text-slate-500">Review ngoại lệ theo dòng hàng. Dữ liệu đến từ structured GDT detail đã chuẩn hóa; không đọc PDF.</p>
            </div>
            @if($canManageReceipt)
                <button type="button" wire:click="openSourcePicker" class="min-h-11 shrink-0 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">+ Lấy hóa đơn từ Invoices</button>
            @endif
        </div>
        <div class="grid gap-3 p-4 md:grid-cols-[minmax(0,1fr)_220px]">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Tìm số hóa đơn, ký hiệu, MST, nhà cung cấp..." class="min-h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <select wire:model.live="status" class="min-h-11 rounded-xl border border-gray-300 bg-white px-3 text-sm">
                <option value="all">Tất cả trạng thái</option>
                @foreach(['RECEIVED','MATCHING','REVIEW_REQUIRED','READY','RECEIPT_CREATED','ERROR'] as $value)<option value="{{ $value }}">{{ $value }}</option>@endforeach
            </select>
        </div>
    </section>

    <div class="grid min-h-[520px] gap-5 xl:grid-cols-[390px_minmax(0,1fr)]">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3"><h3 class="font-semibold text-slate-900">Hóa đơn trong Inbox</h3><p class="text-xs text-slate-500">Chọn một hóa đơn để review hàng hóa.</p></div>
            <div class="divide-y divide-slate-100">
                @forelse($rows as $row)
                    <button type="button" wire:click="selectInbox({{ $row->id }})" class="block w-full p-4 text-left transition hover:bg-slate-50 {{ $selectedInboxId === $row->id ? 'bg-indigo-50 ring-1 ring-inset ring-indigo-200' : '' }}">
                        <div class="flex items-start justify-between gap-3"><div class="min-w-0"><div class="font-semibold text-slate-900">#{{ $row->invoice_number_snapshot ?: '-' }}</div><div class="mt-1 truncate text-sm text-slate-600">{{ $row->seller_name_snapshot ?: '-' }}</div></div><span class="shrink-0 rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600">{{ $row->processing_status }}</span></div>
                        <div class="mt-3 flex items-center justify-between text-xs text-slate-500"><span>MST {{ $row->seller_tax_code_snapshot ?: '-' }}</span><span class="{{ $row->unresolved_lines_count ? 'font-semibold text-amber-700' : 'text-emerald-700' }}">{{ $row->unresolved_lines_count ? $row->unresolved_lines_count.' cần mapping' : 'Đã đối chiếu' }}</span></div>
                    </button>
                @empty
                    <div class="px-5 py-14 text-center"><div class="font-medium text-slate-700">Inbox đang trống</div><p class="mt-1 text-sm text-slate-500">Dùng “Lấy hóa đơn từ Invoices” hoặc publish từ Trung tâm tiếp nhận.</p></div>
                @endforelse
            </div>
            <div class="space-y-3 border-t border-slate-200 p-4"><select wire:model.live="perPage" class="min-h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm">@foreach([10,25,50,100] as $size)<option value="{{ $size }}">{{ $size }} / trang</option>@endforeach</select>{{ $rows->links('Inventory::vendor.pagination.admin-inventory') }}</div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if(!$selected)
                <div class="flex min-h-[520px] items-center justify-center p-8 text-center"><div><div class="text-lg font-semibold text-slate-800">Chọn hóa đơn để review</div><p class="mt-2 max-w-md text-sm text-slate-500">Các dòng UNRESOLVED có thể chọn nhiều để mapping hoặc đánh dấu NON_STOCK theo lô.</p></div></div>
            @else
                <div class="border-b border-slate-200 p-5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between"><div><div class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Chi tiết hóa đơn</div><h3 class="mt-1 text-xl font-bold text-slate-900">#{{ $selected->invoice_number_snapshot }}</h3><p class="mt-1 text-sm text-slate-600">{{ $selected->seller_name_snapshot }} · MST {{ $selected->seller_tax_code_snapshot }}</p></div><div class="rounded-xl bg-slate-50 px-3 py-2 text-xs text-slate-600">Contract {{ $selected->contract_version }}</div></div>
                </div>

                @if($canManageReceipt)
                    <div class="border-b border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-col gap-3 xl:flex-row xl:items-end">
                            <div class="flex flex-wrap gap-2">
                                <button type="button" wire:click="selectAllUnresolved" class="min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700">Chọn tất cả UNRESOLVED</button>
                                <button type="button" wire:click="clearSelectedLines" class="min-h-10 rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700">Bỏ chọn</button>
                            </div>
                            <label class="min-w-0 flex-1 text-xs font-semibold text-slate-600">Map các dòng đã chọn vào InventoryItem
                                <select wire:model="bulkItemId" class="mt-1 min-h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm"><option value="">Chọn mặt hàng...</option>@foreach($items as $item)<option value="{{ $item->id }}">{{ $item->sku }} — {{ $item->display_name }}</option>@endforeach</select>
                            </label>
                            <button type="button" wire:click="bulkAssignSelected" wire:loading.attr="disabled" class="min-h-10 rounded-lg bg-indigo-600 px-4 text-xs font-semibold text-white disabled:opacity-50">Mapping hàng loạt</button>
                            <button type="button" wire:click="bulkMarkNonStock" wire:loading.attr="disabled" class="min-h-10 rounded-lg border border-slate-300 bg-white px-4 text-xs font-semibold text-slate-700 disabled:opacity-50">Đánh dấu NON_STOCK</button>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">Đã chọn {{ count($selectedLineIds) }} dòng. Mapping thủ công sẽ lưu alias theo nhà cung cấp/nguồn để các lần sau có thể auto-match deterministically.</p>
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="min-w-[980px] w-full text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500"><tr>@if($canManageReceipt)<th class="w-12 px-4 py-3">Chọn</th>@endif<th class="px-4 py-3">Hàng hóa nguồn</th><th class="px-4 py-3">SL / ĐVT</th><th class="px-4 py-3">Trạng thái</th><th class="px-4 py-3">Inventory item / xử lý</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($selected->lines as $line)
                                <tr class="align-top">
                                    @if($canManageReceipt)<td class="px-4 py-4"><input type="checkbox" wire:model="selectedLineIds" value="{{ $line->id }}" class="rounded border-gray-300" aria-label="Chọn dòng {{ $line->id }}"></td>@endif
                                    <td class="px-4 py-4"><div class="font-medium text-slate-900">{{ $line->description_snapshot }}</div><div class="mt-1 text-xs text-slate-500">Mã nguồn: {{ $line->source_product_code ?: '—' }}</div>@if($line->lot_number || $line->expiry_date)<div class="mt-1 text-xs text-slate-500">Lô {{ $line->lot_number ?: '—' }} · HSD {{ $line->expiry_date?->format('d/m/Y') ?: '—' }}</div>@endif</td>
                                    <td class="whitespace-nowrap px-4 py-4 font-medium">{{ $line->source_quantity }} {{ $line->source_uom }}</td>
                                    <td class="px-4 py-4"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $line->classification === 'UNRESOLVED' ? 'bg-amber-100 text-amber-800' : ($line->classification === 'NON_STOCK' ? 'bg-slate-100 text-slate-700' : 'bg-emerald-100 text-emerald-800') }}">{{ $line->classification }}</span><div class="mt-2 max-w-48 text-xs text-slate-500">{{ $line->match_reason }}</div></td>
                                    <td class="px-4 py-4">
                                        @if($line->item)<div class="mb-2 font-medium text-slate-900">{{ $line->item->sku }} — {{ $line->item->display_name }}</div>@endif
                                        @php($referenceCandidates = data_get($line->metadata, 'reference_candidates', []))
                                        @if(!empty($referenceCandidates))
                                            <div class="mb-3 max-w-xl rounded-xl border border-amber-200 bg-amber-50 p-3">
                                                <div class="text-xs font-semibold uppercase tracking-wide text-amber-800">Gợi ý tham chiếu Product / Pharma</div>
                                                <div class="mt-2 space-y-2">
                                                    @foreach($referenceCandidates as $candidate)
                                                        <div class="rounded-lg border border-amber-100 bg-white px-3 py-2 text-xs">
                                                            <div class="flex flex-wrap items-center gap-2"><span class="font-semibold text-slate-800">{{ data_get($candidate, 'source') }}</span><span class="rounded bg-slate-100 px-1.5 py-0.5 font-medium text-slate-600">{{ data_get($candidate, 'confidence', 'LOW') }}</span></div>
                                                            <div class="mt-1 text-slate-700">{{ data_get($candidate, 'label') }}</div>
                                                            @if(data_get($candidate, 'blocked_reasons'))<div class="mt-1 text-slate-500">Chỉ tham chiếu: {{ implode(', ', data_get($candidate, 'blocked_reasons', [])) }}</div>@endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <p class="mt-2 text-[11px] text-amber-800">Candidate không tự gán InventoryItem. Hãy review và mapping thủ công khi phù hợp.</p>
                                            </div>
                                        @endif
                                        @if($canManageReceipt)<div class="flex flex-wrap gap-2"><select wire:change="assignLine({{ $line->id }}, $event.target.value)" class="min-h-9 max-w-64 rounded-lg border border-gray-300 bg-white px-2 text-xs"><option value="">Chọn mặt hàng...</option>@foreach($items as $item)<option value="{{ $item->id }}">{{ $item->sku }} — {{ $item->display_name }}</option>@endforeach</select><button type="button" wire:click="markNonStock({{ $line->id }})" class="rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium">Không nhập kho</button>@if($canManageItem)<button type="button" wire:click="createStandaloneItem({{ $line->id }})" class="rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1.5 text-xs font-medium text-indigo-700">+ Tạo item</button>@endif</div>@endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($canManageReceipt)
                    <div class="sticky bottom-0 border-t border-slate-200 bg-white/95 p-4 backdrop-blur"><div class="flex flex-col gap-3 lg:flex-row lg:items-end"><label class="flex-1 text-sm font-medium text-slate-700">Kho nhận<select wire:model="warehouseId" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm"><option value="">Chọn kho...</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->code }} — {{ $warehouse->name }}</option>@endforeach</select></label><button type="button" wire:click="createDraftReceipt" wire:loading.attr="disabled" class="min-h-11 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">Tạo phiếu nhập DRAFT</button>@if($selected->receipt_id)<a href="{{ route('admin.inventory.receipts') }}" class="min-h-11 rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-center text-sm font-semibold">Mở phiếu nhập</a>@endif</div><p class="mt-2 text-xs text-slate-500">Khi toàn bộ dòng đã STOCK hoặc NON_STOCK, trạng thái tự chuyển READY. Tạo DRAFT không làm thay đổi tồn kho.</p></div>
                @endif
            @endif
        </section>
    </div>

    @if($showSourcePicker)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="closeSourcePicker">
            <div class="flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-slate-200 p-5"><div><h3 class="text-lg font-bold text-slate-900">Lấy hóa đơn mua vào từ Invoices</h3><p class="mt-1 text-sm text-slate-500">Tìm và chọn đúng hóa đơn cần đưa vào quy trình nhập kho.</p></div><button type="button" wire:click="closeSourcePicker" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">Đóng</button></div>
                <div class="border-b border-slate-200 p-4"><input type="search" wire:model.live.debounce.300ms="sourceSearch" placeholder="Tìm số hóa đơn, nhà cung cấp, MST..." class="min-h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm"></div>
                <div class="overflow-y-auto p-4"><div class="divide-y divide-slate-100 rounded-xl border border-slate-200">@forelse($sourceCandidates as $invoice)<div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"><div class="min-w-0"><div class="font-semibold text-slate-900">#{{ $invoice->invoice_number }}</div><div class="mt-1 truncate text-sm text-slate-600">{{ $invoice->name ?: 'Chưa có tên nhà cung cấp' }}</div><div class="mt-1 text-xs text-slate-500">MST {{ $invoice->tax_code ?: '—' }}</div></div><button type="button" wire:click="syncSourceInvoice({{ $invoice->id }})" wire:loading.attr="disabled" class="min-h-10 shrink-0 rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white disabled:opacity-50">Đưa vào Inbox</button></div>@empty<div class="p-10 text-center text-sm text-slate-500">Không tìm thấy hóa đơn mua vào phù hợp.</div>@endforelse</div></div>
            </div>
        </div>
    @endif
</div>