<section class="space-y-3 rounded-xl border border-slate-200 bg-white p-4" aria-label="Tra cứu hóa đơn Invoices">
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Hóa đơn từ Modules/Invoices</h2>
            <p class="text-sm text-slate-600">Tra cứu hóa đơn đã lưu để đối chiếu nguồn công nợ; chưa tự động tạo công nợ hoặc ghi thanh toán.</p>
        </div>
        @if(config('modules.registry.Invoices.enabled', false) && auth('admin')->user()?->can('invoices-list'))
            <a class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-indigo-700" href="{{ route('admin.invoices.index') }}">Mở Invoices</a>
        @endif
    </div>
    @if(!$available)
        <p class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">Modules/Invoices chưa được bật hoặc bảng invoices chưa sẵn sàng. Không có dữ liệu hóa đơn để hiển thị.</p>
    @else
        <div class="grid gap-3 sm:grid-cols-3">
            <label class="block text-sm sm:col-span-2">Số hóa đơn / ký hiệu / đối tác / MST
                <input type="search" maxlength="100" wire:model.live.debounce.400ms="search" placeholder="Tìm hóa đơn..." class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
            </label>
            <label class="block text-sm">Chiều hóa đơn
                <select wire:model.live="invoiceType" class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">Tất cả</option><option value="sold">Bán ra</option><option value="purchase">Mua vào</option>
                </select>
            </label>
        </div>
        <button type="button" wire:click="resetFilters" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">Đặt lại bộ lọc</button>
        @if($errors->any())<p role="alert" class="text-sm text-red-700">{{ $errors->first() }}</p>@endif
        <div class="overflow-x-auto rounded-lg border border-slate-200" role="region" aria-label="Danh sách hóa đơn từ Invoices" tabindex="0">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50"><tr>
                    <th class="p-3">Hóa đơn</th><th class="p-3">Ngày lập</th><th class="p-3">Đối tác</th>
                    <th class="p-3">Chiều</th><th class="p-3 text-right">Tổng tiền HĐ</th><th class="p-3">Đối chiếu Finance</th>
                </tr></thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr class="border-b border-slate-100" wire:key="invoice-explorer-{{ $invoice->id }}">
                            <td class="p-3"><span class="font-semibold">{{ $invoice->invoice_number ?: 'Chưa có số' }}</span><p class="text-xs text-slate-500">{{ $invoice->symbol ?: '—' }} · ID {{ $invoice->id }}</p></td>
                            <td class="p-3">{{ $invoice->issued_date ?: '—' }}</td>
                            <td class="p-3">{{ $invoice->name ?: '—' }}<p class="text-xs text-slate-500">{{ $invoice->tax_code ?: '—' }}</p></td>
                            <td class="p-3">{{ $invoice->invoice_type === 'sold' ? 'Bán ra' : 'Mua vào' }}</td>
                            <td class="p-3 whitespace-nowrap text-right tabular-nums">{{ $invoice->total_amount === null ? '—' : \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $invoice->total_amount) }}</td>
                            <td class="p-3">
                                @if((int) $invoice->linked_debt_count > 0)
                                    <span class="font-semibold text-indigo-700">Đã có {{ $invoice->linked_debt_count }} bản ghi nguồn</span>
                                @else
                                    <span class="text-slate-500">Chưa có liên kết nguồn</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-5 text-center text-slate-500">Không tìm thấy hóa đơn phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $invoices->links() }}</div>
        <p class="text-xs text-amber-900">Tổng tiền hóa đơn không đồng nghĩa số dư chưa thanh toán. Trạng thái liên kết chỉ dựa trên định danh nguồn Invoices/invoice/ID trong Finance, không xác nhận đã thanh toán, đối trừ hoặc đủ điều kiện ghi nhận công nợ. Không tự động ghi dữ liệu.</p>
    @endif
</section>
