<div class="space-y-5">
    @if($errorMessage)
        <div role="alert" class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-900">{{ $errorMessage }}</div>
    @endif
    @if($successMessage)
        <div role="status" class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ $successMessage }}</div>
    @endif

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
                <p class="mt-1 text-sm text-slate-500">Luồng nhận hàng: nguồn hóa đơn → matching → phiếu nhập DRAFT → xác nhận → movement/balance.</p>
            </div>
            @if($canManageReceipt)
                <button type="button" wire:click="openSourcePicker" wire:loading.attr="disabled" wire:target="openSourcePicker" class="min-h-11 shrink-0 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">+ Lấy hóa đơn từ Invoices</button>
            @endif
        </div>
        <div class="grid gap-3 p-4 md:grid-cols-[minmax(0,1fr)_220px]">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Tìm số hóa đơn, ký hiệu, MST, nhà cung cấp..." class="min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
            <select wire:model.live="status" class="min-h-11 rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                <option value="all">Tất cả trạng thái</option>
                @foreach(['RECEIVED','MATCHING','REVIEW_REQUIRED','READY','RECEIPT_CREATED','ERROR'] as $value)
                    <option value="{{ $value }}">{{ $value }}</option>
                @endforeach
            </select>
        </div>
    </section>

    <div class="grid min-h-[560px] gap-5 xl:grid-cols-[380px_minmax(0,1fr)]">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3">
                <h3 class="font-semibold text-slate-900">Hóa đơn trong Inbox</h3>
                <p class="text-xs text-slate-500">Chọn một hóa đơn để xử lý receiving.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($rows as $row)
                    <button type="button" wire:click="selectInbox({{ $row->id }})" class="block w-full p-4 text-left transition hover:bg-slate-50 {{ $selectedInboxId === $row->id ? 'bg-indigo-50 ring-1 ring-inset ring-indigo-200' : '' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-semibold text-slate-900">#{{ $row->invoice_number_snapshot ?: '-' }}</div>
                                <div class="mt-1 truncate text-sm text-slate-600">{{ $row->seller_name_snapshot ?: '-' }}</div>
                            </div>
                            <span class="shrink-0 rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600">{{ $row->processing_status }}</span>
                        </div>
                        <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                            <span>MST {{ $row->seller_tax_code_snapshot ?: '-' }}</span>
                            <span class="{{ $row->unresolved_lines_count ? 'font-semibold text-amber-700' : 'text-emerald-700' }}">{{ $row->unresolved_lines_count ? $row->unresolved_lines_count.' cần mapping' : 'Đã đối chiếu' }}</span>
                        </div>
                    </button>
                @empty
                    <div class="px-5 py-14 text-center">
                        <div class="font-medium text-slate-700">Inbox đang trống</div>
                        <p class="mt-1 text-sm text-slate-500">Dùng “Lấy hóa đơn từ Invoices” hoặc publish từ Trung tâm tiếp nhận.</p>
                    </div>
                @endforelse
            </div>
            <div class="space-y-3 border-t border-slate-200 p-4">
                <select wire:model.live="perPage" class="min-h-10 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    @foreach([10,25,50,100] as $size)<option value="{{ $size }}">{{ $size }} / trang</option>@endforeach
                </select>
                {{ $rows->links('Inventory::vendor.pagination.admin-inventory') }}
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if(!$selected)
                <div class="flex min-h-[560px] items-center justify-center p-8 text-center">
                    <div>
                        <div class="text-lg font-semibold text-slate-800">Chọn hóa đơn để bắt đầu</div>
                        <p class="mt-2 max-w-md text-sm text-slate-500">Batch D chỉ cho phép nguồn đủ điều kiện đi vào receiving. STOCK chưa mapping sẽ luôn chặn tạo phiếu nhập.</p>
                    </div>
                </div>
            @else
                @php
                    $sourceClass = data_get($selected->metadata, 'source_business_classification', 'UNCLASSIFIED');
                    $receipt = $selected->receipt;
                    $isConfirmed = $receipt?->status === 'CONFIRMED';
                    $hasUnresolved = $selected->lines->contains(fn($line) => $line->classification === 'UNRESOLVED' || ($line->classification === 'STOCK' && !$line->inventory_item_id));
                @endphp

                <div class="border-b border-slate-200 p-5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Chi tiết receiving</div>
                            <h3 class="mt-1 text-xl font-bold text-slate-900">#{{ $selected->invoice_number_snapshot }}</h3>
                            <p class="mt-1 text-sm text-slate-600">{{ $selected->seller_name_snapshot }} · MST {{ $selected->seller_tax_code_snapshot }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $sourceClass === 'GOODS' ? 'bg-emerald-100 text-emerald-800' : ($sourceClass === 'MIXED' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">Nguồn: {{ $sourceClass }}</span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $selected->processing_status }}</span>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-2 sm:grid-cols-4">
                        @foreach([
                            ['1','Source',true],
                            ['2','Matching',!$hasUnresolved],
                            ['3','Draft',$receipt !== null],
                            ['4','Confirmed',$isConfirmed],
                        ] as [$step,$label,$done])
                            <div class="rounded-xl border px-3 py-2 {{ $done ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-slate-50' }}">
                                <div class="text-[11px] font-semibold uppercase tracking-wide {{ $done ? 'text-emerald-700' : 'text-slate-500' }}">Bước {{ $step }}</div>
                                <div class="mt-1 text-sm font-semibold {{ $done ? 'text-emerald-900' : 'text-slate-700' }}">{{ $label }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if($canManageReceipt && !$isConfirmed)
                    <div class="border-b border-slate-200 bg-slate-50 p-4">
                        <div class="flex flex-col gap-3 xl:flex-row xl:items-end">
                            <div class="flex flex-wrap gap-2">
                                <button type="button" wire:click="selectAllUnresolved" class="min-h-10 rounded-xl border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 hover:border-indigo-300">Chọn UNRESOLVED</button>
                                <button type="button" wire:click="clearSelectedLines" class="min-h-10 rounded-xl border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700">Bỏ chọn</button>
                            </div>
                            <label class="min-w-0 flex-1 text-xs font-semibold text-slate-600">Map dòng đã chọn vào InventoryItem
                                <select wire:model="bulkItemId" class="mt-1 min-h-10 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                                    <option value="">Chọn mặt hàng...</option>
                                    @foreach($items as $item)<option value="{{ $item->id }}">{{ $item->sku }} — {{ $item->display_name }}</option>@endforeach
                                </select>
                            </label>
                            <button type="button" wire:click="bulkAssignSelected" wire:loading.attr="disabled" wire:target="bulkAssignSelected" class="min-h-10 rounded-xl bg-indigo-600 px-4 text-xs font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">Mapping hàng loạt</button>
                            <button type="button" wire:click="bulkMarkNonStock" wire:loading.attr="disabled" wire:target="bulkMarkNonStock" class="min-h-10 rounded-xl border border-slate-300 bg-white px-4 text-xs font-semibold text-slate-700 disabled:cursor-not-allowed disabled:opacity-50">NON_STOCK</button>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">Đã chọn {{ count($selectedLineIds) }} dòng. Mapping thủ công sẽ lưu alias deterministic theo nguồn/nhà cung cấp.</p>
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[980px] text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                @if($canManageReceipt && !$isConfirmed)<th class="w-12 px-4 py-3">Chọn</th>@endif
                                <th class="px-4 py-3">Hàng hóa nguồn</th>
                                <th class="px-4 py-3">SL / ĐVT</th>
                                <th class="px-4 py-3">Matching</th>
                                <th class="px-4 py-3">Inventory item / xử lý</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($selected->lines as $line)
                                <tr class="align-top">
                                    @if($canManageReceipt && !$isConfirmed)
                                        <td class="px-4 py-4"><input type="checkbox" wire:model="selectedLineIds" value="{{ $line->id }}" class="rounded border-gray-300" aria-label="Chọn dòng {{ $line->id }}"></td>
                                    @endif
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-slate-900">{{ $line->description_snapshot }}</div>
                                        <div class="mt-1 text-xs text-slate-500">Mã nguồn: {{ $line->source_product_code ?: '—' }}</div>
                                        @if($line->lot_number || $line->expiry_date || $line->manufacture_date)
                                            <div class="mt-2 flex flex-wrap gap-1 text-[11px] text-slate-600">
                                                @if($line->lot_number)<span class="rounded bg-slate-100 px-2 py-1">Lô {{ $line->lot_number }}</span>@endif
                                                @if($line->manufacture_date)<span class="rounded bg-slate-100 px-2 py-1">NSX {{ $line->manufacture_date->format('d/m/Y') }}</span>@endif
                                                @if($line->expiry_date)<span class="rounded bg-slate-100 px-2 py-1">HSD {{ $line->expiry_date->format('d/m/Y') }}</span>@endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <div class="font-semibold text-slate-900">{{ $line->source_quantity }} {{ $line->source_uom }}</div>
                                        @if($line->base_quantity !== null)
                                            <div class="mt-1 text-xs text-slate-500">Base: {{ $line->base_quantity }} {{ $line->base_uom }} · ×{{ $line->conversion_factor }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $line->classification === 'UNRESOLVED' ? 'bg-amber-100 text-amber-800' : ($line->classification === 'NON_STOCK' ? 'bg-slate-100 text-slate-700' : 'bg-emerald-100 text-emerald-800') }}">{{ $line->classification }}</span>
                                        <div class="mt-2 max-w-56 text-xs text-slate-500">{{ $line->match_reason ?: '—' }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        @if($line->item)
                                            <div class="mb-2 font-medium text-slate-900">{{ $line->item->sku }} — {{ $line->item->display_name }}</div>
                                            <div class="mb-3 text-xs text-slate-500">Base UOM: {{ $line->item->base_uom }}</div>
                                        @endif

                                        @php($referenceCandidates = data_get($line->metadata, 'reference_candidates', []))
                                        @if(!empty($referenceCandidates))
                                            <div class="mb-3 max-w-xl rounded-xl border border-amber-200 bg-amber-50 p-3">
                                                <div class="text-xs font-semibold uppercase tracking-wide text-amber-800">Gợi ý Product / Pharma</div>
                                                <div class="mt-2 space-y-2">
                                                    @foreach($referenceCandidates as $candidate)
                                                        <div class="rounded-lg border border-amber-100 bg-white px-3 py-2 text-xs">
                                                            <div class="flex flex-wrap items-center gap-2"><span class="font-semibold text-slate-800">{{ data_get($candidate, 'source') }}</span><span class="rounded bg-slate-100 px-1.5 py-0.5 font-medium text-slate-600">{{ data_get($candidate, 'confidence', 'LOW') }}</span></div>
                                                            <div class="mt-1 text-slate-700">{{ data_get($candidate, 'label') }}</div>
                                                            @if(data_get($candidate, 'blocked_reasons'))<div class="mt-1 text-slate-500">Chỉ tham chiếu: {{ implode(', ', data_get($candidate, 'blocked_reasons', [])) }}</div>@endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <p class="mt-2 text-[11px] text-amber-800">Candidate không tự gán InventoryItem.</p>
                                            </div>
                                        @endif

                                        @if($canManageReceipt && !$isConfirmed)
                                            <div class="flex flex-wrap gap-2">
                                                <select wire:change="assignLine({{ $line->id }}, $event.target.value)" class="min-h-9 max-w-72 rounded-xl border border-gray-300 bg-white px-3 text-xs text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                                                    <option value="">Chọn mặt hàng...</option>
                                                    @foreach($items as $item)<option value="{{ $item->id }}">{{ $item->sku }} — {{ $item->display_name }}</option>@endforeach
                                                </select>
                                                <button type="button" wire:click="markNonStock({{ $line->id }})" wire:loading.attr="disabled" wire:target="markNonStock({{ $line->id }})" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 disabled:opacity-50">Không nhập kho</button>
                                                @if($canManageItem && !$line->inventory_item_id)
                                                    <button type="button" wire:click="beginCreateItem({{ $line->id }})" class="rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700">+ Tạo item có review</button>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 bg-white p-5">
                    @if(!$receipt)
                        @if($canManageReceipt)
                            <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                                <label class="text-sm font-medium text-slate-700">Kho nhận
                                    <select wire:model="warehouseId" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                                        <option value="">Chọn kho...</option>
                                        @foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->code }} — {{ $warehouse->name }}</option>@endforeach
                                    </select>
                                </label>
                                <button type="button" wire:click="createDraftReceipt" wire:loading.attr="disabled" wire:target="createDraftReceipt" class="min-h-11 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">Tạo phiếu nhập DRAFT</button>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">Chỉ tạo DRAFT khi tất cả line đã resolve và UOM/lot/HSD hợp lệ. DRAFT không thay đổi tồn kho.</p>
                        @endif
                    @else
                        <div class="rounded-2xl border {{ $isConfirmed ? 'border-emerald-200 bg-emerald-50' : 'border-indigo-200 bg-indigo-50' }} p-4">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide {{ $isConfirmed ? 'text-emerald-700' : 'text-indigo-700' }}">Phiếu nhập {{ $receipt->status }}</div>
                                    <div class="mt-1 text-lg font-bold text-slate-900">{{ $receipt->number }}</div>
                                    <div class="mt-1 text-sm text-slate-600">Kho: {{ $receipt->warehouse?->code }} — {{ $receipt->warehouse?->name }}</div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('admin.inventory.receipts') }}" class="min-h-10 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">Mở phiếu nhập</a>
                                    @if(!$isConfirmed && $canConfirmReceipt)
                                        <button type="button" wire:click="askConfirmReceipt" class="min-h-10 rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white hover:bg-emerald-700">Xác nhận nhập kho</button>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white">
                                <table class="w-full min-w-[760px] text-sm">
                                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-3 py-2">Item</th><th class="px-3 py-2">SL base</th><th class="px-3 py-2">Lô</th><th class="px-3 py-2">NSX</th><th class="px-3 py-2">HSD</th></tr></thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($receipt->lines as $line)
                                            <tr><td class="px-3 py-2 font-medium text-slate-800">{{ $line->item?->sku }} — {{ $line->item?->display_name }}</td><td class="px-3 py-2">{{ $line->base_quantity }} {{ $line->base_uom }}</td><td class="px-3 py-2">{{ $line->lot_number ?: '—' }}</td><td class="px-3 py-2">{{ $line->manufacture_date?->format('d/m/Y') ?: '—' }}</td><td class="px-3 py-2">{{ $line->expiry_date?->format('d/m/Y') ?: '—' }}</td></tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>

                @if($isConfirmed)
                    <div class="border-t border-slate-200 p-5">
                        <div class="flex items-center justify-between gap-3"><div><h4 class="font-semibold text-slate-900">Audit sau xác nhận</h4><p class="mt-1 text-xs text-slate-500">Trace: {{ $selected->source_invoice_identity }} → Receipt {{ $receipt->number }} → Movement → Balance.</p></div><span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">{{ $movements->count() }} movements</span></div>
                        <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200">
                            <table class="w-full min-w-[820px] text-sm">
                                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-3 py-2">Movement</th><th class="px-3 py-2">Item ID</th><th class="px-3 py-2">Lot ID</th><th class="px-3 py-2">Delta</th><th class="px-3 py-2">Balance hiện tại</th></tr></thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse($movements as $movement)
                                        <tr><td class="px-3 py-2 font-mono text-xs text-slate-700">{{ substr($movement->movement_key, 0, 16) }}…</td><td class="px-3 py-2">{{ $movement->inventory_item_id }}</td><td class="px-3 py-2">{{ $movement->lot_id ?: '—' }}</td><td class="px-3 py-2 font-semibold text-emerald-700">+{{ $movement->quantity_delta }} {{ $movement->base_uom }}</td><td class="px-3 py-2 font-semibold text-slate-900">{{ optional($balances->get($movement->dimension_key))->quantity_on_hand ?? '—' }}</td></tr>
                                    @empty
                                        <tr><td colspan="5" class="px-3 py-8 text-center text-slate-500">Không tìm thấy movement cho receipt đã xác nhận.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endif
        </section>
    </div>

    @if($showSourcePicker)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="closeSourcePicker">
            <div class="flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 p-5">
                    <div><h3 class="text-lg font-semibold text-slate-900">Lấy hóa đơn mua vào từ Invoices</h3><p class="mt-1 text-sm text-slate-500">Eligibility được kiểm tra server-side. SERVICE_EXPENSE/UNCLASSIFIED sẽ bị từ chối.</p></div>
                    <button type="button" wire:click="closeSourcePicker" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">Đóng</button>
                </div>
                <div class="border-b border-slate-200 p-4"><input type="search" wire:model.live.debounce.300ms="sourceSearch" placeholder="Tìm số hóa đơn, MST, nhà cung cấp..." class="min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"></div>
                <div class="overflow-y-auto p-4">
                    <div class="divide-y divide-slate-100 rounded-xl border border-slate-200">
                        @forelse($sourceCandidates as $invoice)
                            <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0"><div class="font-semibold text-slate-900">#{{ $invoice->invoice_number }} · {{ $invoice->symbol }}</div><div class="mt-1 truncate text-sm text-slate-600">{{ $invoice->name }} · MST {{ $invoice->tax_code }}</div><div class="mt-1 text-xs text-slate-500">{{ $invoice->issued_date?->format('d/m/Y') }}</div></div>
                                <button type="button" wire:click="syncSourceInvoice({{ $invoice->id }})" wire:loading.attr="disabled" wire:target="syncSourceInvoice({{ $invoice->id }})" class="min-h-10 shrink-0 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white disabled:opacity-50">Đưa vào Inbox</button>
                            </div>
                        @empty
                            <div class="p-8 text-center text-sm text-slate-500">Không tìm thấy hóa đơn mua vào phù hợp.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($showCreateItem)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="closeCreateItem">
            <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl">
                <div class="border-b border-slate-200 p-5"><h3 class="text-lg font-semibold text-slate-900">Tạo InventoryItem sau khi review</h3><p class="mt-1 text-sm text-slate-500">Không silent-create. Kiểm tra SKU, base UOM và tracking trước khi lưu alias.</p></div>
                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <label class="text-sm font-medium text-slate-700">SKU<input type="text" wire:model="itemForm.sku" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">@error('itemForm.sku')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                    <label class="text-sm font-medium text-slate-700">Base UOM<input type="text" wire:model="itemForm.base_uom" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">@error('itemForm.base_uom')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                    <label class="text-sm font-medium text-slate-700 sm:col-span-2">Tên hiển thị<input type="text" wire:model="itemForm.display_name" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">@error('itemForm.display_name')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                    <label class="flex items-center gap-2 rounded-xl border border-slate-200 p-3 text-sm text-slate-700"><input type="checkbox" wire:model="itemForm.lot_tracking" class="rounded border-gray-300"> Theo dõi số lô</label>
                    <label class="flex items-center gap-2 rounded-xl border border-slate-200 p-3 text-sm text-slate-700"><input type="checkbox" wire:model="itemForm.expiry_tracking" class="rounded border-gray-300"> Theo dõi HSD</label>
                    <label class="flex items-center gap-2 rounded-xl border border-slate-200 p-3 text-sm text-slate-700 sm:col-span-2"><input type="checkbox" wire:model="itemForm.allow_fractional_quantity" class="rounded border-gray-300"> Cho phép số lượng lẻ</label>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-200 p-4"><button type="button" wire:click="closeCreateItem" class="min-h-10 rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700">Hủy</button><button type="button" wire:click="saveStandaloneItem" wire:loading.attr="disabled" wire:target="saveStandaloneItem" class="min-h-10 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white disabled:opacity-50">Tạo item và mapping</button></div>
            </div>
        </div>
    @endif

    @if($showConfirmReceipt && $selected?->receipt)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="closeConfirmReceipt">
            <div class="w-full max-w-lg rounded-2xl bg-white shadow-2xl">
                <div class="p-6"><div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Xác nhận nhập kho</div><h3 class="mt-2 text-xl font-bold text-slate-900">{{ $selected->receipt->number }}</h3><p class="mt-3 text-sm leading-6 text-slate-600">Hành động này sẽ tạo Stock Movement và cập nhật Stock Balance. Phiếu CONFIRMED sẽ được bảo vệ khỏi chỉnh sửa và confirm lại không được tăng stock lần hai.</p></div>
                <div class="flex justify-end gap-2 border-t border-slate-200 p-4"><button type="button" wire:click="closeConfirmReceipt" class="min-h-10 rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700">Hủy</button><button type="button" wire:click="confirmReceipt" wire:loading.attr="disabled" wire:target="confirmReceipt" class="min-h-10 rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">Xác nhận và cộng tồn</button></div>
            </div>
        </div>
    @endif
</div>
