<div class="space-y-6">
    @if($errorMessage)
        <div role="alert" class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-900">{{ $errorMessage }}</div>
    @endif
    @if($successMessage)
        <div role="status" class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ $successMessage }}</div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="font-semibold text-slate-900">Hóa đơn mua vào từ Invoices</h2>
            <p class="mt-1 text-sm text-slate-500">Đồng bộ dữ liệu chi tiết GDT qua contract V1. Inventory không đọc PDF và không cộng tồn tại bước này.</p>
        </div>
        <div class="grid gap-4 p-5 lg:grid-cols-[1fr_auto]">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Tìm số HĐ, ký hiệu, MST, nhà cung cấp..." class="min-h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <select wire:model.live="status" class="min-h-11 rounded-xl border border-gray-300 bg-white px-3 text-sm">
                <option value="all">Tất cả trạng thái</option>
                @foreach(['RECEIVED','MATCHING','REVIEW_REQUIRED','READY','RECEIPT_CREATED','ERROR'] as $value)
                    <option value="{{ $value }}">{{ $value }}</option>
                @endforeach
            </select>
        </div>
        @if($canManageReceipt)
            <div class="border-t border-slate-200 px-5 py-4">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Hóa đơn gần đây chưa/đang cần đưa vào kho</p>
                <div class="flex flex-wrap gap-2">
                    @forelse($sourceCandidates as $invoice)
                        <button type="button" wire:click="syncSourceInvoice({{ $invoice->id }})" wire:loading.attr="disabled" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-left text-xs hover:border-indigo-400 disabled:opacity-50">
                            <span class="font-semibold text-slate-900">#{{ $invoice->invoice_number }}</span>
                            <span class="text-slate-500"> · {{ $invoice->name ?: $invoice->tax_code }}</span>
                        </button>
                    @empty
                        <span class="text-sm text-slate-500">Không có hóa đơn mua vào phù hợp hoặc Invoices integration không khả dụng.</span>
                    @endforelse
                </div>
            </div>
        @endif
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Hóa đơn</th><th class="px-4 py-3">Nhà cung cấp</th><th class="px-4 py-3">Trạng thái</th><th class="px-4 py-3 text-right">Chưa xử lý</th><th class="px-4 py-3">Phiếu nhập</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $row)
                        <tr class="cursor-pointer hover:bg-slate-50" wire:click="selectInbox({{ $row->id }})">
                            <td class="px-4 py-3"><span class="font-semibold">{{ $row->invoice_number_snapshot ?: '-' }}</span><br><span class="text-xs text-slate-500">{{ $row->invoice_symbol_snapshot }}</span></td>
                            <td class="px-4 py-3">{{ $row->seller_name_snapshot ?: '-' }}<br><span class="text-xs text-slate-500">{{ $row->seller_tax_code_snapshot }}</span></td>
                            <td class="px-4 py-3"><span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold">{{ $row->processing_status }}</span></td>
                            <td class="px-4 py-3 text-right font-semibold">{{ $row->unresolved_lines_count }}</td>
                            <td class="px-4 py-3">{{ $row->receipt_id ? '#'.$row->receipt_id : 'Chưa tạo' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Inbox chưa có hóa đơn.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex flex-col gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <select wire:model.live="perPage" class="min-h-10 rounded-lg border border-gray-300 bg-white px-3 text-sm">
                @foreach([10,25,50,100] as $size)<option value="{{ $size }}">{{ $size }} / trang</option>@endforeach
            </select>
            {{ $rows->links('Inventory::vendor.pagination.admin-inventory') }}
        </div>
    </section>

    @if($selected)
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-semibold text-slate-900">Review hóa đơn #{{ $selected->invoice_number_snapshot }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $selected->seller_name_snapshot }} · {{ $selected->seller_tax_code_snapshot }} · {{ $selected->contract_version }}</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Dòng</th><th class="px-4 py-3">Mô tả nguồn</th><th class="px-4 py-3">SL / ĐVT</th><th class="px-4 py-3">Phân loại</th><th class="px-4 py-3">Inventory item</th><th class="px-4 py-3">Hành động</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($selected->lines as $line)
                            <tr>
                                <td class="px-4 py-3">{{ $line->line_number }}</td>
                                <td class="px-4 py-3"><span class="font-medium">{{ $line->description_snapshot }}</span><br><span class="text-xs text-slate-500">{{ $line->source_product_code ?: 'không có mã nguồn' }}</span></td>
                                <td class="px-4 py-3">{{ $line->source_quantity }} {{ $line->source_uom }}</td>
                                <td class="px-4 py-3">{{ $line->classification }}<br><span class="text-xs text-slate-500">{{ $line->match_reason }}</span></td>
                                <td class="px-4 py-3">{{ $line->item?->sku }} {{ $line->item?->display_name }}</td>
                                <td class="px-4 py-3">
                                    @if($canManageReceipt)
                                        <div class="flex flex-wrap gap-2">
                                            <select wire:change="assignLine({{ $line->id }}, $event.target.value)" class="min-h-9 max-w-56 rounded-lg border border-gray-300 bg-white px-2 text-xs">
                                                <option value="">Chọn mặt hàng...</option>
                                                @foreach($items as $item)<option value="{{ $item->id }}">{{ $item->sku }} — {{ $item->display_name }}</option>@endforeach
                                            </select>
                                            <button type="button" wire:click="markNonStock({{ $line->id }})" class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs">NON_STOCK</button>
                                            @if($canManageItem)<button type="button" wire:click="createStandaloneItem({{ $line->id }})" class="rounded-lg border border-indigo-300 bg-indigo-50 px-2 py-1 text-xs text-indigo-800">Tạo item</button>@endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($canManageReceipt)
                <div class="flex flex-col gap-3 border-t border-slate-200 p-5 sm:flex-row sm:items-end">
                    <label class="flex-1 text-sm font-medium text-slate-700">Kho nhận
                        <select wire:model="warehouseId" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-sm">
                            <option value="">Chọn kho...</option>
                            @foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->code }} — {{ $warehouse->name }}</option>@endforeach
                        </select>
                    </label>
                    <button type="button" wire:click="createDraftReceipt" wire:loading.attr="disabled" class="min-h-11 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">Tạo / cập nhật phiếu nhập DRAFT</button>
                    @if($selected->receipt_id)<a href="{{ route('admin.inventory.receipts') }}" class="min-h-11 rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-center text-sm font-semibold">Mở phiếu nhập</a>@endif
                </div>
            @endif
        </section>
    @endif
</div>
