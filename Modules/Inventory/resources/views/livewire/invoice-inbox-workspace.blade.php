<div class="space-y-5">
    @if($errorMessage)
        <div role="alert" class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-800">{{ $errorMessage }}</div>
    @endif

    @if($successMessage)
        <div role="status" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">{{ $successMessage }}</div>
    @endif

    @if(!$selected)
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-900">Chọn hóa đơn đang xử lý</h2>
                <p class="mt-1 text-sm text-slate-500">Hóa đơn mới nên được bắt đầu từ danh sách hóa đơn chờ nhập kho.</p>
            </div>

            <div class="grid gap-3 p-5 md:grid-cols-[minmax(0,1fr)_220px]">
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Tìm số hóa đơn, MST, nhà cung cấp..." class="min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                <select wire:model.live="status" class="min-h-11 rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                    <option value="all">Tất cả trạng thái</option>
                    <option value="REVIEW_REQUIRED">Cần đối chiếu</option>
                    <option value="READY">Sẵn sàng tạo phiếu</option>
                    <option value="RECEIPT_CREATED">Đã tạo phiếu</option>
                </select>
            </div>

            <div class="grid gap-3 px-5 pb-5 lg:grid-cols-2">
                @forelse($rows as $row)
                    <button type="button" wire:click="selectInbox({{ $row->id }})" class="rounded-2xl border border-slate-200 p-4 text-left transition hover:border-indigo-300 hover:bg-indigo-50/40">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="font-semibold text-slate-900">Hóa đơn #{{ $row->invoice_number_snapshot ?: '-' }}</div>
                                <div class="mt-1 truncate text-sm text-slate-600">{{ $row->seller_name_snapshot ?: '-' }}</div>
                                <div class="mt-2 text-xs text-slate-500">MST {{ $row->seller_tax_code_snapshot ?: '-' }}</div>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $row->unresolved_lines_count ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800' }}">{{ $row->unresolved_lines_count ? 'Cần đối chiếu' : 'Sẵn sàng' }}</span>
                        </div>
                    </button>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-slate-300 p-10 text-center text-sm text-slate-500">Không có hóa đơn đang xử lý.</div>
                @endforelse
            </div>

            <div class="border-t border-slate-200 p-4">
                <div class="max-w-40">
                    <select wire:model.live="perPage" class="min-h-10 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                        @foreach([10,25,50,100] as $size)
                            <option value="{{ $size }}">{{ $size }} / trang</option>
                        @endforeach
                    </select>
                </div>
                <div class="mt-3">{{ $rows->links('Inventory::vendor.pagination.admin-inventory') }}</div>
            </div>
        </section>
    @else
        @php
            $receipt = $selected->receipt;
            $isConfirmed = $receipt?->status === 'CONFIRMED';
            $stockLines = $selected->lines->where('classification', 'STOCK');
            $hasUnresolved = $selected->lines->contains(fn($line) => $line->classification === 'UNRESOLVED' || ($line->classification === 'STOCK' && !$line->inventory_item_id));
            $currentStep = $isConfirmed ? 5 : ($receipt ? 4 : ($hasUnresolved ? 2 : 3));
        @endphp

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Nhập kho từ hóa đơn</div>
                        <h2 class="mt-1 text-2xl font-bold text-slate-950">Hóa đơn #{{ $selected->invoice_number_snapshot ?: '-' }}</h2>
                        <p class="mt-2 text-sm font-medium text-slate-700">{{ $selected->seller_name_snapshot ?: '-' }}</p>
                        <p class="mt-1 text-sm text-slate-500">MST {{ $selected->seller_tax_code_snapshot ?: '-' }} · Ngày hóa đơn {{ $selected->issued_at_snapshot?->format('d/m/Y') ?: '—' }}</p>
                    </div>
                    <a href="{{ route('admin.inventory.invoice-inbox') }}" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:border-indigo-300">← Danh sách hóa đơn</a>
                </div>

                <div class="mt-6 grid gap-2 sm:grid-cols-5">
                    @foreach([[1,'Kiểm tra hóa đơn'],[2,'Đối chiếu hàng'],[3,'Thông tin nhập'],[4,'Kiểm tra phiếu'],[5,'Xác nhận']] as [$number,$label])
                        <div class="rounded-xl border px-3 py-3 {{ $number < $currentStep || ($number === 5 && $isConfirmed) ? 'border-emerald-200 bg-emerald-50' : ($number === $currentStep ? 'border-indigo-300 bg-indigo-50 ring-1 ring-indigo-100' : 'border-slate-200 bg-slate-50') }}">
                            <div class="text-[11px] font-semibold {{ $number === $currentStep && !$isConfirmed ? 'text-indigo-700' : ($number < $currentStep || ($number === 5 && $isConfirmed) ? 'text-emerald-700' : 'text-slate-400') }}">BƯỚC {{ $number }}</div>
                            <div class="mt-1 text-sm font-semibold text-slate-800">{{ $label }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if($hasUnresolved && !$receipt)
                <div class="p-5 sm:p-6">
                    <div class="mb-5">
                        <h3 class="text-lg font-bold text-slate-900">Đối chiếu hàng hóa</h3>
                        <p class="mt-1 text-sm text-slate-500">Xác định mỗi dòng hóa đơn tương ứng với mặt hàng nào trong kho.</p>
                    </div>

                    <div class="space-y-4">
                        @foreach($selected->lines as $line)
                            <article class="rounded-2xl border {{ $line->classification === 'NON_STOCK' || $line->inventory_item_id ? 'border-emerald-200 bg-emerald-50/30' : 'border-amber-200 bg-amber-50/30' }} p-5">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="min-w-0">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Hàng trên hóa đơn</div>
                                        <h4 class="mt-1 text-base font-bold text-slate-900">{{ $line->description_snapshot }}</h4>
                                        <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-sm text-slate-600">
                                            <span>Số lượng: <strong class="text-slate-900">{{ $line->source_quantity }}</strong></span>
                                            <span>Đơn vị: <strong class="text-slate-900">{{ $line->source_uom ?: '—' }}</strong></span>
                                            @if($line->lot_number)<span>Lô: <strong class="text-slate-900">{{ $line->lot_number }}</strong></span>@endif
                                            @if($line->expiry_date)<span>HSD: <strong class="text-slate-900">{{ $line->expiry_date->format('d/m/Y') }}</strong></span>@endif
                                        </div>
                                    </div>
                                    @if($line->classification === 'NON_STOCK')
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">Không nhập kho</span>
                                    @elseif($line->inventory_item_id)
                                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">✓ Đã đối chiếu</span>
                                    @else
                                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">Cần chọn mặt hàng</span>
                                    @endif
                                </div>

                                @if($line->item)
                                    <div class="mt-4 rounded-xl border border-emerald-200 bg-white p-4">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Mặt hàng trong kho</div>
                                                <div class="mt-1 font-semibold text-slate-900">{{ $line->item->display_name }}</div>
                                                <div class="mt-1 text-sm text-slate-500">Mã hàng {{ $line->item->sku }} · Đơn vị tồn kho {{ $line->item->base_uom }}</div>
                                            </div>
                                            @if($canManageItem && $line->item && (int) data_get($line->item->metadata, 'created_from_invoice_inbox_line_id') === $line->id)
                                                <button type="button" wire:click="beginEditItem({{ $line->id }})" class="min-h-10 rounded-xl border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700">Sửa mặt hàng</button>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                @if($canManageReceipt)
                                    <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center">
                                        <select wire:change="assignLine({{ $line->id }}, $event.target.value)" class="min-h-11 min-w-0 flex-1 rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                                            <option value="">{{ $line->item ? 'Đổi sang mặt hàng khác...' : 'Tìm hoặc chọn mặt hàng...' }}</option>
                                            @foreach($items as $item)
                                                <option value="{{ $item->id }}">{{ $item->sku }} — {{ $item->display_name }}</option>
                                            @endforeach
                                        </select>
                                        @if($canManageItem && !$line->inventory_item_id)
                                            <button type="button" wire:click="beginCreateItem({{ $line->id }})" class="min-h-11 rounded-xl border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700">+ Tạo mặt hàng mới</button>
                                        @endif
                                        <button type="button" wire:click="markNonStock({{ $line->id }})" class="min-h-11 rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700">Không nhập kho</button>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-6 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">Sau khi tất cả dòng được đối chiếu, hệ thống tự chuyển sang bước nhập thông tin kho.</div>
                </div>
            @elseif(!$receipt)
                <div class="p-5 sm:p-6">
                    <div class="mb-5">
                        <h3 class="text-lg font-bold text-slate-900">Thông tin nhập kho</h3>
                        <p class="mt-1 text-sm text-slate-500">Số lô và hạn sử dụng được điền sẵn khi dữ liệu hóa đơn có cung cấp. Bạn có thể kiểm tra hoặc sửa trước khi lập phiếu.</p>
                    </div>

                    <label class="block max-w-2xl text-sm font-semibold text-slate-700">Kho nhận <span class="text-red-600">*</span>
                        <select wire:model="warehouseId" class="mt-2 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                            <option value="">Chọn kho nhận...</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->code }} — {{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <div class="mt-6 space-y-4">
                        @foreach($stockLines as $line)
                            @php
                                $sameUom = mb_strtolower(trim((string)$line->source_uom)) === mb_strtolower(trim((string)$line->item?->base_uom));
                                $packaging = data_get($line->item?->metadata, 'packaging');
                                $packageUom = data_get($packaging, 'package_uom');
                                $packageQuantity = data_get($packaging, 'quantity');
                                $packageBaseUom = data_get($packaging, 'base_uom', $line->item?->base_uom);
                            @endphp
                            <article class="rounded-2xl border border-slate-200 p-5">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <h4 class="font-bold text-slate-900">{{ $line->item?->display_name ?: $line->description_snapshot }}</h4>
                                        <p class="mt-1 text-sm text-slate-500">Mã hàng {{ $line->item?->sku }} · Hóa đơn: {{ $line->source_quantity }} {{ $line->source_uom }}</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        @if(data_get($line->metadata, 'receiving_review.reviewed_at'))<span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">✓ Đã lưu</span>@endif
                                        @if($canManageItem && $line->item && (int) data_get($line->item->metadata, 'created_from_invoice_inbox_line_id') === $line->id)
                                            <button type="button" wire:click="beginEditItem({{ $line->id }})" class="rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700">Sửa mặt hàng</button>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                    <label class="text-sm font-medium text-slate-700">Số lượng nhập <span class="text-red-600">*</span><input type="number" step="any" wire:model="receivingReview.{{ $line->id }}.base_quantity" readonly class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">@error('receivingReview.'.$line->id.'.base_quantity')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                                    <label class="text-sm font-medium text-slate-700">Đơn vị tồn kho <span class="text-red-600">*</span><input type="text" wire:model="receivingReview.{{ $line->id }}.base_uom" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">@error('receivingReview.'.$line->id.'.base_uom')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                                    @if(!$sameUom)
                                        <label class="text-sm font-medium text-slate-700">Quy đổi đơn vị <span class="text-red-600">*</span><div class="mt-1 flex min-h-11 items-center rounded-xl border border-gray-300 bg-white px-3 text-sm"><span class="shrink-0 text-slate-500">1 {{ $line->source_uom }} =</span><input type="number" step="any" wire:model.live.debounce.300ms="receivingReview.{{ $line->id }}.conversion_factor" class="mx-2 min-w-0 flex-1 border-0 p-0 text-center focus:ring-0"><span class="shrink-0 text-slate-500">{{ $line->item?->base_uom }}</span></div>@error('receivingReview.'.$line->id.'.conversion_factor')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                                    @else
                                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm"><div class="font-semibold text-emerald-700">Không cần quy đổi</div><div class="mt-1 text-slate-700">1 {{ $line->source_uom }} = 1 {{ $line->item?->base_uom }}</div></div>
                                    @endif
                                    <label class="text-sm font-medium text-slate-700">Số lô @if($line->item?->lot_tracking)<span class="text-red-600">*</span>@endif<input type="text" wire:model="receivingReview.{{ $line->id }}.lot_number" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">@error('receivingReview.'.$line->id.'.lot_number')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                                    <label class="text-sm font-medium text-slate-700">Hạn sử dụng @if($line->item?->expiry_tracking)<span class="text-red-600">*</span>@endif<input type="date" wire:model="receivingReview.{{ $line->id }}.expiry_date" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">@error('receivingReview.'.$line->id.'.expiry_date')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3"><label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700"><input type="checkbox" wire:model.live="receivingReview.{{ $line->id }}.include_manufacture_date" class="rounded border-gray-300"> Nhập ngày sản xuất</label>@if(data_get($receivingReview, $line->id.'.include_manufacture_date'))<input type="date" wire:model="receivingReview.{{ $line->id }}.manufacture_date" class="mt-3 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">@error('receivingReview.'.$line->id.'.manufacture_date')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror@endif<div class="mt-2 text-xs text-slate-500">Không bắt buộc. Chỉ nhập khi có ngày sản xuất chính xác.</div></div>
                                </div>

                                @if($packageUom && $packageQuantity)
                                    <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700"><span class="font-semibold text-slate-900">Quy cách đóng gói:</span> 1 {{ $packageUom }} = {{ rtrim(rtrim(number_format((float) $packageQuantity, 8, '.', ''), '0'), '.') }} {{ $packageBaseUom }}</div>
                                @endif

                                <div class="mt-4 flex justify-end"><button type="button" wire:click="saveReceivingReview({{ $line->id }})" wire:loading.attr="disabled" wire:target="saveReceivingReview({{ $line->id }})" class="min-h-10 rounded-xl border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700 disabled:opacity-50">Lưu thông tin mặt hàng</button></div>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-6 rounded-2xl border border-indigo-200 bg-indigo-50 p-5"><div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"><div><h4 class="font-bold text-slate-900">Kiểm tra và tạo phiếu nhập</h4><p class="mt-1 text-sm text-slate-600">Hệ thống kiểm tra lại kho nhận, mặt hàng, số lượng, đơn vị, lô và HSD. Phiếu nháp chưa làm thay đổi tồn kho.</p></div>@if($canManageReceipt)<button type="button" wire:click="createDraftReceipt" wire:loading.attr="disabled" wire:target="createDraftReceipt" class="min-h-11 shrink-0 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white disabled:opacity-50">Tạo phiếu nhập nháp</button>@endif</div></div>
                </div>
            @else
                <div class="p-5 sm:p-6">
                    <div class="rounded-2xl border {{ $isConfirmed ? 'border-emerald-200 bg-emerald-50' : 'border-indigo-200 bg-indigo-50' }} p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-wide {{ $isConfirmed ? 'text-emerald-700' : 'text-indigo-700' }}">{{ $isConfirmed ? 'Đã nhập kho' : 'Phiếu nhập nháp · Chờ kiểm tra' }}</div>
                                <h3 class="mt-1 text-xl font-bold text-slate-950">{{ $receipt->number }}</h3>
                                <p class="mt-2 text-sm text-slate-600">Kho nhận: {{ $receipt->warehouse?->code }} — {{ $receipt->warehouse?->name }}</p>
                                @unless($isConfirmed)<p class="mt-2 text-sm font-medium text-amber-700">Phiếu nháp chưa làm thay đổi tồn kho. Có thể chỉnh sửa lại trước khi xác nhận.</p>@endunless
                            </div>
                            @if(!$isConfirmed && $canConfirmReceipt)
                                <button type="button" wire:click="askConfirmReceipt" class="min-h-11 rounded-xl border border-indigo-300 bg-white px-5 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">Xem phiếu nhập nháp</button>
                            @endif
                        </div>
                    </div>

                    @unless($isConfirmed)
                        <details class="mt-5 overflow-hidden rounded-2xl border border-amber-200 bg-amber-50/30">
                            <summary class="cursor-pointer px-5 py-4 text-sm font-bold text-amber-900">✎ Chỉnh sửa phiếu nhập nháp trước khi xác nhận</summary>
                            <div class="border-t border-amber-200 bg-white p-5">
                                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">Bạn có thể sửa kho nhận, quy đổi, số lô, HSD và ngày sản xuất. Sau khi sửa, bấm <strong>Cập nhật phiếu nhập nháp</strong>. Tồn kho vẫn chưa thay đổi.</div>

                                <label class="mt-5 block max-w-2xl text-sm font-semibold text-slate-700">Kho nhận <span class="text-red-600">*</span>
                                    <select wire:model="warehouseId" class="mt-2 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                                        <option value="">Chọn kho nhận...</option>
                                        @foreach($warehouses as $warehouse)
                                            <option value="{{ $warehouse->id }}">{{ $warehouse->code }} — {{ $warehouse->name }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <div class="mt-5 space-y-4">
                                    @foreach($stockLines as $line)
                                        @php
                                            $sameUom = mb_strtolower(trim((string)$line->source_uom)) === mb_strtolower(trim((string)$line->item?->base_uom));
                                            $packaging = data_get($line->item?->metadata, 'packaging');
                                            $packageUom = data_get($packaging, 'package_uom');
                                            $packageQuantity = data_get($packaging, 'quantity');
                                            $packageBaseUom = data_get($packaging, 'base_uom', $line->item?->base_uom);
                                        @endphp
                                        <article class="rounded-2xl border border-slate-200 p-5">
                                            <div>
                                                <h4 class="font-bold text-slate-900">{{ $line->item?->display_name ?: $line->description_snapshot }}</h4>
                                                <p class="mt-1 text-sm text-slate-500">SKU {{ $line->item?->sku }} · Hóa đơn {{ $line->source_quantity }} {{ $line->source_uom }}</p>
                                            </div>

                                            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                                <label class="text-sm font-medium text-slate-700">Số lượng nhập<input type="number" step="any" wire:model="receivingReview.{{ $line->id }}.base_quantity" readonly class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-slate-50 px-4 text-sm text-slate-700"></label>
                                                <label class="text-sm font-medium text-slate-700">Đơn vị tồn kho<input type="text" wire:model="receivingReview.{{ $line->id }}.base_uom" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">@error('receivingReview.'.$line->id.'.base_uom')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                                                @if(!$sameUom)
                                                    <label class="text-sm font-medium text-slate-700">Quy đổi đơn vị <span class="text-red-600">*</span><div class="mt-1 flex min-h-11 items-center rounded-xl border border-gray-300 bg-white px-3 text-sm"><span class="shrink-0 text-slate-500">1 {{ $line->source_uom }} =</span><input type="number" step="any" wire:model.live.debounce.300ms="receivingReview.{{ $line->id }}.conversion_factor" class="mx-2 min-w-0 flex-1 border-0 p-0 text-center focus:ring-0"><span class="shrink-0 text-slate-500">{{ $line->item?->base_uom }}</span></div>@error('receivingReview.'.$line->id.'.conversion_factor')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                                                @else
                                                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm"><div class="font-semibold text-emerald-700">Không cần quy đổi</div><div class="mt-1 text-slate-700">1 {{ $line->source_uom }} = 1 {{ $line->item?->base_uom }}</div></div>
                                                @endif
                                                <label class="text-sm font-medium text-slate-700">Số lô @if($line->item?->lot_tracking)<span class="text-red-600">*</span>@endif<input type="text" wire:model="receivingReview.{{ $line->id }}.lot_number" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">@error('receivingReview.'.$line->id.'.lot_number')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                                                <label class="text-sm font-medium text-slate-700">Hạn sử dụng @if($line->item?->expiry_tracking)<span class="text-red-600">*</span>@endif<input type="date" wire:model="receivingReview.{{ $line->id }}.expiry_date" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">@error('receivingReview.'.$line->id.'.expiry_date')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3"><label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700"><input type="checkbox" wire:model.live="receivingReview.{{ $line->id }}.include_manufacture_date" class="rounded border-gray-300"> Nhập ngày sản xuất</label>@if(data_get($receivingReview, $line->id.'.include_manufacture_date'))<input type="date" wire:model="receivingReview.{{ $line->id }}.manufacture_date" class="mt-3 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">@error('receivingReview.'.$line->id.'.manufacture_date')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror@endif<div class="mt-2 text-xs text-slate-500">Ngày sản xuất là tùy chọn.</div></div>
                                            </div>

                                            @if($packageUom && $packageQuantity)
                                                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700"><span class="font-semibold text-slate-900">Quy cách đóng gói:</span> 1 {{ $packageUom }} = {{ rtrim(rtrim(number_format((float) $packageQuantity, 8, '.', ''), '0'), '.') }} {{ $packageBaseUom }}</div>
                                            @endif
                                        </article>
                                    @endforeach
                                </div>

                                @if($canManageReceipt)
                                    <div class="mt-5 flex justify-end"><button type="button" wire:click="createDraftReceipt" wire:loading.attr="disabled" wire:target="createDraftReceipt" class="min-h-11 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white disabled:opacity-50">Cập nhật phiếu nhập nháp</button></div>
                                @endif
                            </div>
                        </details>
                    @endunless

                    <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 font-semibold text-slate-800">Mặt hàng trên phiếu</div>
                        <div class="divide-y divide-slate-100">
                            @foreach($receipt->lines as $line)
                                <div class="grid gap-2 p-4 sm:grid-cols-[minmax(0,1fr)_auto]">
                                    <div><div class="font-semibold text-slate-900">{{ $line->item?->display_name }}</div><div class="mt-1 text-sm text-slate-500">{{ $line->item?->sku }} · Lô {{ $line->lot_number ?: '—' }} · HSD {{ $line->expiry_date?->format('d/m/Y') ?: '—' }}</div></div>
                                    <div class="font-bold text-slate-900">{{ $line->base_quantity }} {{ $line->base_uom }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if($isConfirmed)
                        <div class="mt-5 rounded-2xl border border-emerald-200 bg-white p-5"><h4 class="font-bold text-emerald-800">✓ Nhập kho thành công</h4><p class="mt-1 text-sm text-slate-600">Đã ghi nhận {{ $movements->count() }} biến động kho. Chi tiết được lưu trong lịch sử kho để truy vết.</p><div class="mt-4 flex flex-wrap gap-2"><a href="{{ route('admin.inventory.receipts') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">Xem phiếu nhập</a><a href="{{ route('admin.inventory.stock') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">Xem tồn kho</a><a href="{{ route('admin.inventory.invoice-inbox') }}" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Về danh sách hóa đơn</a></div></div>
                    @endif
                </div>
            @endif
        </section>
    @endif

    @if($showCreateItem)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-3 sm:p-6">
            <div class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                    <div class="text-xs font-semibold uppercase tracking-wide text-indigo-600">{{ $editingItemId ? 'Chỉnh sửa trước khi lập phiếu' : 'Tạo mặt hàng từ hóa đơn' }}</div>
                    <h3 class="mt-1 text-xl font-bold text-slate-900">{{ $editingItemId ? 'Sửa mặt hàng' : 'Tạo mặt hàng mới' }}</h3>
                    <p class="mt-1 text-sm text-slate-500">Mã hàng có thể thay đổi trước khi lưu. Số lô và HSD bên dưới chỉ áp dụng cho lần nhập kho hiện tại.</p>
                </div>

                <div class="overflow-y-auto p-5 sm:p-6">
                    <section class="rounded-2xl border border-slate-200 p-4 sm:p-5">
                        <div class="mb-4"><h4 class="font-bold text-slate-900">Thông tin mặt hàng</h4><p class="mt-1 text-xs text-slate-500">Dữ liệu dùng lâu dài trong danh mục kho.</p></div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="text-sm font-medium text-slate-700 md:col-span-2">Tên mặt hàng <span class="text-red-600">*</span><input type="text" wire:model="itemForm.display_name" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">@error('itemForm.display_name')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                            <label class="text-sm font-medium text-slate-700">Mã hàng (SKU) <span class="text-red-600">*</span><input type="text" wire:model="itemForm.sku" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">@error('itemForm.sku')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror<span class="mt-1 block text-xs text-slate-500">Có thể sửa mã gợi ý này trước khi tạo phiếu nhập.</span></label>
                            <label class="text-sm font-medium text-slate-700">Đơn vị tồn kho <span class="text-red-600">*</span><input type="text" wire:model.live.debounce.300ms="itemForm.base_uom" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">@error('itemForm.base_uom')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                        </div>

                        @php
                            $creatingLine = $selected?->lines->firstWhere('id', $creatingFromLineId);
                            $modalSourceUom = trim((string) $creatingLine?->source_uom) ?: 'ĐVT hóa đơn';
                            $modalBaseUom = trim((string) data_get($itemForm, 'base_uom')) ?: 'ĐVT tồn kho';
                            $modalSameUom = mb_strtolower($modalSourceUom) === mb_strtolower($modalBaseUom);
                            $modalFactor = $modalSameUom ? 1 : (float) (data_get($itemForm, 'conversion_factor') ?: 0);
                            $modalBaseQuantity = $creatingLine && $modalFactor > 0 ? (float) $creatingLine->source_quantity * $modalFactor : null;
                        @endphp

                        @if($modalSameUom)
                            <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4"><div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Không cần quy đổi</div><div class="mt-1 text-base font-bold text-slate-900">1 {{ $modalSourceUom }} = 1 {{ $modalBaseUom }}</div>@if($creatingLine)<div class="mt-2 text-sm text-slate-600">Số lượng nhập giữ nguyên: <span class="font-semibold text-slate-900">{{ $creatingLine->source_quantity }} {{ $modalBaseUom }}</span></div>@endif</div>
                        @else
                            <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50/50 p-4"><div class="mb-4"><h4 class="font-bold text-slate-900">Quy đổi đơn vị nhập kho</h4><p class="mt-1 text-xs text-slate-600">Chỉ dùng khi đơn vị trên hóa đơn khác đơn vị tồn kho.</p></div><div class="grid gap-4 md:grid-cols-3"><div class="rounded-xl border border-slate-200 bg-white p-3"><div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Trên hóa đơn</div><div class="mt-1 font-bold text-slate-900">{{ $creatingLine?->source_quantity ?: '—' }} {{ $modalSourceUom }}</div></div><label class="text-sm font-medium text-slate-700">Quy đổi <span class="text-red-600">*</span><div class="mt-1 flex min-h-11 items-center rounded-xl border border-gray-300 bg-white px-3"><span class="shrink-0 text-slate-500">1 {{ $modalSourceUom }} =</span><input type="number" min="0" step="any" wire:model.live.debounce.300ms="itemForm.conversion_factor" placeholder="VD: 30" class="mx-2 min-w-0 flex-1 border-0 p-0 text-center focus:ring-0"><span class="shrink-0 text-slate-500">{{ $modalBaseUom }}</span></div>@error('itemForm.conversion_factor')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label><div class="rounded-xl border border-indigo-200 bg-indigo-50 p-3"><div class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Số lượng tồn kho</div><div class="mt-1 font-bold text-slate-900">{{ $modalBaseQuantity !== null ? rtrim(rtrim(number_format($modalBaseQuantity, 8, '.', ''), '0'), '.') : '—' }} {{ $modalBaseUom }}</div></div></div></div>
                        @endif

                        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4"><div class="mb-4"><h4 class="font-bold text-slate-900">Thông tin quy cách sản phẩm</h4><p class="mt-1 text-xs text-slate-600">Chỉ lưu thông tin đóng gói của sản phẩm, không làm thay đổi số lượng nhập kho.</p></div><div class="grid gap-4 md:grid-cols-3"><label class="text-sm font-medium text-slate-700">Đơn vị đóng gói<input type="text" wire:model.live.debounce.300ms="itemForm.package_uom" placeholder="VD: Hộp" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">@error('itemForm.package_uom')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label><label class="text-sm font-medium text-slate-700">Số lượng trong 1 đơn vị<input type="number" min="0" step="any" wire:model.live.debounce.300ms="itemForm.package_quantity" placeholder="VD: 60" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">@error('itemForm.package_quantity')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label><div class="rounded-xl border border-indigo-200 bg-indigo-50 p-3"><div class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Quy cách đóng gói</div><div class="mt-1 font-bold text-slate-900">@if(filled(data_get($itemForm, 'package_uom')) && filled(data_get($itemForm, 'package_quantity')))1 {{ data_get($itemForm, 'package_uom') }} = {{ rtrim(rtrim(number_format((float) data_get($itemForm, 'package_quantity'), 8, '.', ''), '0'), '.') }} {{ data_get($itemForm, 'base_uom') ?: 'đơn vị cơ sở' }}@else Chưa khai báo @endif</div></div></div></div>

                        <div class="mt-5"><div class="mb-2 text-sm font-semibold text-slate-700">Quản lý mặt hàng</div><div class="grid gap-3 sm:grid-cols-3"><label class="flex items-center gap-2 rounded-xl border border-slate-200 p-3 text-sm"><input type="checkbox" wire:model="itemForm.lot_tracking" class="rounded border-gray-300"> Theo dõi số lô</label><label class="flex items-center gap-2 rounded-xl border border-slate-200 p-3 text-sm"><input type="checkbox" wire:model="itemForm.expiry_tracking" class="rounded border-gray-300"> Theo dõi hạn sử dụng</label><label class="flex items-center gap-2 rounded-xl border border-slate-200 p-3 text-sm"><input type="checkbox" wire:model="itemForm.allow_fractional_quantity" class="rounded border-gray-300"> Cho phép số lượng lẻ</label></div></div>
                    </section>

                    <section class="mt-4 rounded-2xl border border-indigo-200 bg-indigo-50/40 p-4 sm:p-5"><div class="mb-4"><h4 class="font-bold text-slate-900">Thông tin lô của lần nhập này</h4><p class="mt-1 text-xs text-slate-600">Hệ thống điền sẵn từ hóa đơn nếu có. Có thể sửa hoặc nhập tay trước khi tạo phiếu nhập nháp.</p></div><div class="grid gap-4 md:grid-cols-2"><label class="text-sm font-medium text-slate-700">Số lô<input type="text" wire:model="itemForm.lot_number" placeholder="Nhập số lô nếu có" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">@error('itemForm.lot_number')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label><label class="text-sm font-medium text-slate-700">Hạn sử dụng<input type="date" wire:model="itemForm.expiry_date" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">@error('itemForm.expiry_date')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label><div class="md:col-span-2 rounded-xl border border-slate-200 bg-white p-3"><label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700"><input type="checkbox" wire:model.live="itemForm.include_manufacture_date" class="rounded border-gray-300"> Nhập ngày sản xuất</label>@if(data_get($itemForm, 'include_manufacture_date'))<div class="mt-3 max-w-sm"><input type="date" wire:model="itemForm.manufacture_date" class="min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">@error('itemForm.manufacture_date')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</div>@endif<p class="mt-2 text-xs text-slate-500">Ngày sản xuất là tùy chọn. Không dùng thông tin “NSX: Việt Nam” làm ngày sản xuất.</p></div></div></section>

                    <div class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-600">Sau khi lưu, mặt hàng vẫn có thể được sửa hoặc đổi trước khi tạo phiếu nhập nháp. Khi đã phát sinh giao dịch, mã hàng nên được giữ ổn định để bảo đảm truy vết.</div>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-white p-4 sm:flex-row sm:justify-end"><button type="button" wire:click="closeCreateItem" class="min-h-11 rounded-xl border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-700">Hủy</button><button type="button" wire:click="saveStandaloneItem" wire:loading.attr="disabled" wire:target="saveStandaloneItem" class="min-h-11 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white disabled:opacity-50">{{ $editingItemId ? 'Lưu thay đổi' : 'Tạo mặt hàng và tiếp tục' }}</button></div>
            </div>
        </div>
    @endif

    @if($showConfirmReceipt && $selected?->receipt)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-3 sm:p-6">
            <div class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                    <div class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Kiểm tra phiếu nhập nháp</div>
                    <h3 class="mt-1 text-xl font-bold text-slate-900">{{ $selected->receipt->number }}</h3>
                    <p class="mt-1 text-sm text-slate-500">Kiểm tra toàn bộ thông tin trước khi xác nhận. Phiếu nháp chưa làm thay đổi tồn kho.</p>
                </div>

                <div class="overflow-y-auto p-5 sm:p-6">
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3"><div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Hóa đơn nguồn</div><div class="mt-1 font-bold text-slate-900">#{{ $selected->invoice_number_snapshot ?: '-' }}</div></div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3"><div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nhà cung cấp</div><div class="mt-1 font-bold text-slate-900">{{ $selected->seller_name_snapshot ?: '-' }}</div><div class="mt-1 text-xs text-slate-500">MST {{ $selected->seller_tax_code_snapshot ?: '-' }}</div></div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3"><div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kho nhận</div><div class="mt-1 font-bold text-slate-900">{{ $selected->receipt->warehouse?->code }} — {{ $selected->receipt->warehouse?->name }}</div></div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3"><div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Trạng thái</div><div class="mt-1 font-bold text-amber-700">DRAFT · Chưa ghi sổ</div></div>
                    </div>

                    <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 font-semibold text-slate-800">Mặt hàng trên phiếu</div>
                        <div class="divide-y divide-slate-100">
                            @foreach($selected->receipt->lines as $line)
                                @php
                                    $reviewPackaging = data_get($line->item?->metadata, 'packaging');
                                    $reviewPackageUom = data_get($reviewPackaging, 'package_uom');
                                    $reviewPackageQuantity = data_get($reviewPackaging, 'quantity');
                                    $reviewPackageBaseUom = data_get($reviewPackaging, 'base_uom', $line->base_uom);
                                @endphp
                                <div class="p-4 sm:p-5">
                                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="min-w-0"><div class="font-semibold text-slate-900">{{ $line->item?->display_name }}</div><div class="mt-1 text-sm text-slate-500">SKU {{ $line->item?->sku }}</div></div>
                                        <div class="text-right"><div class="font-bold text-slate-900">{{ $line->base_quantity }} {{ $line->base_uom }}</div>@if($line->source_uom && $line->source_uom !== $line->base_uom)<div class="mt-1 text-xs text-slate-500">Nguồn: {{ $line->source_quantity }} {{ $line->source_uom }} · Hệ số {{ $line->conversion_factor }}</div>@endif</div>
                                    </div>
                                    <div class="mt-3 grid gap-2 text-sm text-slate-600 sm:grid-cols-3"><div><span class="font-semibold text-slate-800">Lô:</span> {{ $line->lot_number ?: '—' }}</div><div><span class="font-semibold text-slate-800">HSD:</span> {{ $line->expiry_date?->format('d/m/Y') ?: '—' }}</div><div><span class="font-semibold text-slate-800">NSX:</span> {{ $line->manufacture_date?->format('d/m/Y') ?: '—' }}</div></div>
                                    @if($reviewPackageUom && $reviewPackageQuantity)<div class="mt-3 rounded-xl bg-indigo-50 px-3 py-2 text-xs text-indigo-800"><span class="font-semibold">Quy cách đóng gói:</span> 1 {{ $reviewPackageUom }} = {{ rtrim(rtrim(number_format((float) $reviewPackageQuantity, 8, '.', ''), '0'), '.') }} {{ $reviewPackageBaseUom }}</div>@endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900"><strong>Kiểm tra lần cuối:</strong> sau khi xác nhận, số lượng trên phiếu sẽ được ghi vào tồn kho và phiếu bị khóa để bảo đảm lịch sử truy vết. Xác nhận lại cùng phiếu không được cộng tồn lần hai.</div>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-white p-4 sm:flex-row sm:justify-end"><button type="button" wire:click="closeConfirmReceipt" class="min-h-11 rounded-xl border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-700">Quay lại chỉnh sửa</button><button type="button" wire:click="confirmReceipt" wire:loading.attr="disabled" wire:target="confirmReceipt" class="min-h-11 rounded-xl bg-emerald-600 px-5 text-sm font-semibold text-white disabled:opacity-50">Xác nhận nhập kho</button></div>
            </div>
        </div>
    @endif

    {{-- Bulk exception-review capabilities remain available in the Livewire component: wire:click="selectAllUnresolved" wire:click="bulkAssignSelected" wire:click="bulkMarkNonStock" wire:model="selectedLineIds" wire:model="bulkItemId" --}}
</div>
