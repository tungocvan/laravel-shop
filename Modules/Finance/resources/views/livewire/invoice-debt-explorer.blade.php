<section class="space-y-3 rounded-xl border border-slate-200 bg-white p-4" aria-label="Tra cứu hóa đơn Invoices">
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Đối chiếu hóa đơn & công nợ</h2>
            <p class="text-sm text-slate-600">Tra cứu, kiểm tra và xác nhận công nợ có kiểm soát từ hóa đơn điện tử. Không tự động ghi thanh toán.</p>
        </div>
        @if(config('modules.registry.Invoices.enabled', false) && auth('admin')->user()?->can('invoices-list'))
            <a class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-indigo-700" href="{{ route('admin.invoices.index') }}">Mở Invoices</a>
        @endif
    </div>
    @if(session()->has('invoice_debt_notice'))
        <p role="status" class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">{{ session('invoice_debt_notice') }}</p>
    @endif
    @if(!$available)
        <p class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">Modules/Invoices chưa được bật hoặc bảng invoices chưa sẵn sàng. Không có dữ liệu hóa đơn để hiển thị.</p>
    @else
        <div class="space-y-3 rounded-lg border border-slate-200 bg-slate-50/50 p-3">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <label class="block text-sm xl:col-span-2">Tìm hóa đơn / đối tác / MST
                    <input type="search" maxlength="100" wire:model.live.debounce.400ms="search" placeholder="Số hóa đơn, ký hiệu, tên đối tác, MST..." class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                </label>
                <label class="block text-sm">Chiều hóa đơn
                    <select wire:model.live="invoiceType" class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        <option value="">Tất cả</option><option value="sold">Bán ra</option><option value="purchase">Mua vào</option>
                    </select>
                </label>
                <label class="block text-sm">Trạng thái đối chiếu
                    <select wire:model.live="reconciliationStatus" class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        <option value="unrecorded">Chưa ghi nhận</option><option value="recorded">Đã ghi nhận</option><option value="all">Tất cả</option>
                    </select>
                </label>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <label class="block text-sm">Năm
                    <select wire:model.live="year" class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        <option value="">Tất cả năm</option>
                        @for($y = (int) now()->year; $y >= 2020; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </label>
                <label class="block text-sm">Tháng
                    <select wire:model.live="month" class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        <option value="">Tất cả tháng</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ sprintf('%02d', $m) }}">Tháng {{ $m }}</option>
                        @endfor
                    </select>
                </label>
                <label class="block text-sm">Từ ngày
                    <input type="date" wire:model="dateFrom" class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                </label>
                <label class="block text-sm">Đến ngày
                    <input type="date" wire:model="dateTo" class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                </label>
            </div>
            <div class="flex flex-wrap items-center gap-2 border-t border-slate-200 pt-3">
                <button type="button" wire:click="applyDates" class="min-h-10 rounded-lg bg-indigo-700 px-4 py-2 text-sm font-semibold text-white">Áp dụng khoảng ngày</button>
                <button type="button" wire:click="resetFilters" class="min-h-10 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">Đặt lại</button>
                <span class="text-xs text-slate-600">Đang áp dụng: <strong>{{ $appliedFrom }} → {{ $appliedTo }}</strong>. Thay đổi năm/tháng cần bấm Áp dụng; tìm kiếm, chiều và trạng thái lọc trực tiếp.</span>
            </div>
        </div>
        @if($errors->any())<p role="alert" class="text-sm text-red-700">{{ $errors->first() }}</p>@endif

        @if($canRecognize)
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 sm:p-4 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-slate-900">Thao tác ghi nhận công nợ</h3>
                        <p class="text-xs text-slate-500">Chọn tối đa 10 hóa đơn chưa ghi nhận trên trang hiện tại.</p>
                    </div>
                    <button type="button" wire:click="prepareBulk" @disabled(count($bulkSelected) === 0) class="min-h-10 rounded-lg bg-indigo-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-40">Xem trước {{ count($bulkSelected) }} hóa đơn</button>
                </div>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    <div class="rounded-lg border border-slate-200 bg-white p-3"><p class="text-xs text-slate-500">Đã chọn</p><p class="text-lg font-semibold tabular-nums">{{ count($bulkSelected) }} <span class="text-xs font-normal">hóa đơn</span></p></div>
                    <div class="rounded-lg border border-slate-200 bg-white p-3"><p class="text-xs text-slate-500">Tổng giá trị chọn</p><p class="text-base font-semibold tabular-nums">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) collect($invoices->items())->filter(fn ($i) => in_array((int) $i->id, $bulkSelected, true))->sum(fn ($i) => (float) $i->total_amount)) }}</p></div>
                    <div class="col-span-2 rounded-lg border border-slate-200 bg-white p-3 sm:col-span-1"><p class="text-xs text-slate-500">Đạt kiểm tra kỹ thuật</p><p class="text-lg font-semibold tabular-nums">{{ count($bulkPreview) ? collect($bulkPreview)->filter(fn ($row) => $row['issues'] === [])->count().'/'.count($bulkPreview) : '—' }}</p></div>
                </div>
            </div>
        @endif
        @if($bulkPreview !== [])
            <div class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/50 p-3 sm:p-6" role="presentation" wire:key="invoice-bulk-modal">
                <div role="dialog" aria-modal="true" aria-label="Xem trước xác nhận hàng loạt" class="max-h-[92vh] w-full max-w-5xl overflow-y-auto rounded-2xl bg-white p-4 shadow-2xl sm:p-6">
            <section class="space-y-4 rounded-xl border border-indigo-200 bg-white p-4 shadow-sm" aria-label="Xem trước xác nhận hàng loạt">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div><h3 class="text-base font-semibold text-slate-900">Xem trước ghi nhận công nợ</h3><p class="text-xs text-slate-500">{{ count($bulkPreview) }} hóa đơn · {{ collect($bulkPreview)->filter(fn ($row) => $row['issues'] === [])->count() }} đạt kiểm tra kỹ thuật</p></div>
                    <div class="flex items-center gap-2"><button type="button" wire:click="prepareBulk" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">Làm mới kiểm tra</button><button type="button" wire:click="closeBulkPreview" class="rounded-lg border border-slate-300 px-3 py-2 text-xs" aria-label="Đóng xem trước hàng loạt">Đóng ✕</button></div>
                </div>
                <div class="overflow-x-auto rounded-lg border border-slate-200">
                    <table class="w-full min-w-[840px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs text-slate-600"><tr><th class="p-3">Hóa đơn / đối tác</th><th class="p-3">Ngày xuất</th><th class="p-3">Tổng tiền</th><th class="p-3">Hạn thanh toán</th><th class="p-3">Kết quả kiểm tra</th></tr></thead>
                        <tbody>
                            @foreach($bulkPreview as $item)
                                <tr class="border-t border-slate-100" wire:key="bulk-preview-{{ $item['id'] }}">
                                    <td class="p-3"><strong>{{ $item['reference'] }}</strong><p class="text-xs text-slate-500">{{ $item['partner'] }}</p></td>
                                    <td class="p-3 whitespace-nowrap">{{ $item['issued_on'] ?: '—' }}</td>
                                    <td class="p-3 whitespace-nowrap tabular-nums">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd($item['amount']) }}</td>
                                    <td class="p-3"><input type="date" wire:model="bulkDueDates.{{ $item['id'] }}" aria-label="Hạn thanh toán hóa đơn {{ $item['reference'] }}" class="min-h-9 rounded-lg border border-slate-300 px-2 text-xs"><p class="mt-1 text-xs text-slate-500">Ngày xuất + 90 ngày; có thể sửa</p></td>
                                    <td class="p-3 text-xs {{ $item['issues'] === [] ? 'text-emerald-700' : 'text-rose-700' }}">{{ $item['issues'] === [] ? 'Đạt kỹ thuật · chờ duyệt' : implode(' ', $item['issues']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <label class="block max-w-xs text-sm">Ngày ghi nhận <input type="date" wire:model="bulkRecognizedOn" class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 p-2"></label>
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">Hạn thanh toán mặc định được tính riêng cho từng hóa đơn. Hãy kiểm tra hợp đồng, trạng thái hóa đơn và các khoản đã thanh toán; kết quả kỹ thuật không xác minh hiệu lực pháp lý.</div>
                <label class="flex items-start gap-2 text-sm"><input type="checkbox" wire:model.live="bulkReviewed" class="mt-1"><span>Tôi đã kiểm tra chứng từ gốc, trạng thái pháp lý và các khoản thanh toán liên quan của từng hóa đơn đủ điều kiện. Tôi hiểu hệ thống chưa tự động trừ thanh toán.</span></label>
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <button type="button" wire:click="prepareBulk" class="min-h-10 rounded-lg border border-slate-300 px-4 py-2 text-sm">Kiểm tra lại</button>
                    <button type="button" wire:click="confirmBulk" wire:loading.attr="disabled" wire:target="confirmBulk" @disabled(!$bulkReviewed || collect($bulkPreview)->filter(fn ($row) => $row['issues'] === [])->isEmpty()) class="min-h-10 rounded-lg bg-indigo-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-40">Xác nhận {{ collect($bulkPreview)->filter(fn ($row) => $row['issues'] === [])->count() }} hóa đơn đủ điều kiện</button>
                </div>
            </section>
                </div>
            </div>
        @endif
        @if($bulkResults !== [])
            <section class="space-y-3 rounded-xl border border-slate-200 bg-white p-4" aria-label="Kết quả xác nhận hàng loạt">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h3 class="font-semibold">Kết quả xác nhận {{ count($bulkResults) }} hóa đơn</h3>
                    <button type="button" wire:click="toggleBulkResults" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">{{ $bulkResultsExpanded ? 'Thu gọn' : 'Xem chi tiết' }}</button>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center text-sm">
                    <div class="rounded-lg bg-emerald-50 p-3"><strong class="block text-lg text-emerald-700">{{ collect($bulkResults)->where('status', 'success')->count() }}</strong><span>Thành công</span></div>
                    <div class="rounded-lg bg-amber-50 p-3"><strong class="block text-lg text-amber-700">{{ collect($bulkResults)->where('status', 'skipped')->count() }}</strong><span>Bỏ qua</span></div>
                    <div class="rounded-lg bg-rose-50 p-3"><strong class="block text-lg text-rose-700">{{ collect($bulkResults)->where('status', 'failed')->count() }}</strong><span>Lỗi</span></div>
                </div>
                @if($bulkResultsExpanded)
                    <div class="overflow-x-auto"><table class="w-full text-left text-sm">
                        <thead class="bg-slate-50"><tr><th class="p-2">Hóa đơn</th><th class="p-2">Trạng thái</th><th class="p-2">Chi tiết / lý do</th></tr></thead>
                        <tbody>@foreach($bulkResults as $result)
                            <tr class="border-t border-slate-100" wire:key="bulk-result-{{ $result['id'] }}">
                                <td class="p-2 font-medium">{{ $result['reference'] }}</td>
                                <td class="p-2">{{ $result['status'] === 'success' ? 'Thành công' : ($result['status'] === 'skipped' ? 'Bỏ qua' : 'Lỗi') }}</td>
                                <td class="p-2 text-slate-600">{{ $result['reason'] }}</td>
                            </tr>
                        @endforeach</tbody>
                    </table></div>
                @endif
            </section>
        @endif
        @error('bulk')<p role="alert" class="text-sm text-rose-700">{{ $message }}</p>@enderror
        <div class="overflow-x-auto rounded-lg border border-slate-200" role="region" aria-label="Danh sách hóa đơn từ Invoices" tabindex="0">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50"><tr>
                    <th class="p-3">
                        @if($canRecognize)
                            <input type="checkbox" wire:click="toggleBulkPage" @checked(count($bulkSelected) > 0 && count($bulkSelected) === collect($invoices->items())->filter(fn ($i) => (int) $i->linked_debt_count === 0)->count()) aria-label="Chọn tất cả hóa đơn chưa ghi nhận trên trang" title="Chọn tất cả hóa đơn chưa ghi nhận trên trang" class="rounded border-slate-300">
                        @else — @endif
                    </th><th class="p-3">Hóa đơn</th><th class="p-3">Ngày lập</th><th class="p-3">Đối tác</th>
                    <th class="p-3">Chiều</th><th class="p-3 text-right">Tổng tiền HĐ</th><th class="p-3">Đối chiếu Finance</th><th class="p-3">Thao tác</th>
                </tr></thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr class="border-b border-slate-100" wire:key="invoice-explorer-{{ $invoice->id }}">
                            <td class="p-3">
                                @if($canRecognize && (int) $invoice->linked_debt_count === 0)
                                    <input type="checkbox" aria-label="Chọn hóa đơn {{ $invoice->invoice_number }}" wire:click="toggleBulk({{ $invoice->id }})" @checked(in_array((int) $invoice->id, $bulkSelected, true)) class="rounded border-slate-300">
                                @else <span class="font-semibold text-emerald-700" aria-label="Hóa đơn đã ghi nhận" title="Đã ghi nhận">✓</span> @endif
                            </td>
                            <td class="p-3"><span class="font-semibold">{{ $invoice->invoice_number ?: 'Chưa có số' }}</span><p class="text-xs text-slate-500">{{ $invoice->symbol ?: '—' }} · ID {{ $invoice->id }}</p></td>
                            <td class="p-3">{{ $invoice->issued_date ?: '—' }}</td>
                            <td class="p-3">{{ $invoice->name ?: '—' }}<p class="text-xs text-slate-500">{{ $invoice->tax_code ?: '—' }}</p></td>
                            <td class="p-3">{{ $invoice->invoice_type === 'sold' ? 'Bán ra' : 'Mua vào' }}</td>
                            <td class="p-3 whitespace-nowrap text-right tabular-nums">{{ $invoice->total_amount === null ? '—' : \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $invoice->total_amount) }}</td>
                            <td class="p-3">
                                @if((int) $invoice->linked_debt_count > 0)
                                    <span class="font-semibold text-indigo-700">Đã ghi nhận</span>
                                @else
                                    <span class="text-slate-500">Chưa ghi nhận</span>
                                @endif
                            </td>
                            <td class="p-3">
                                @if($canRecognize && (int) $invoice->linked_debt_count === 0)
                                    <button type="button" wire:click="openReview({{ $invoice->id }})" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-indigo-700">Kiểm tra</button>
                                @else
                                    <span class="text-xs text-slate-500">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="p-5 text-center text-slate-500">Không tìm thấy hóa đơn phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($reviewInvoiceId !== null)
            <div class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/50 p-3 sm:p-6" role="presentation" wire:key="invoice-review-modal">
                <div role="dialog" aria-modal="true" aria-label="Kiểm tra trước khi xác nhận công nợ" class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-5 shadow-2xl sm:p-6">

            <section class="space-y-3 rounded-lg border border-indigo-300 bg-indigo-50 p-4" aria-label="Xác nhận công nợ hóa đơn">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="font-bold text-slate-900">Kiểm tra trước khi xác nhận công nợ</h3>
                    <button type="button" wire:click="closeReview" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">Đóng</button>
                </div>
                <p class="text-sm text-slate-700">Hóa đơn: <strong>{{ $reviewDetails['reference'] ?? '' }}</strong> · {{ $reviewDetails['direction'] ?? '' }}</p>
                <p class="text-sm text-slate-700">Đối tác: <strong>{{ $reviewDetails['name'] ?? '' }}</strong> · MST {{ $reviewDetails['tax_code'] ?? '' }}</p>
                <p class="text-sm text-slate-700">Tổng tiền: <strong>{{ isset($reviewDetails['amount']) ? \Modules\Finance\Livewire\DebtWorkspace::formatVnd($reviewDetails['amount']) : '' }}</strong></p>
                @if($reviewIssues !== [])
                    <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                        <strong>Không đủ điều kiện:</strong>
                        @foreach($reviewIssues as $issue)<p>{{ $issue }}</p>@endforeach
                    </div>
                @else
                    <p class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">Dữ liệu cơ bản đạt kiểm tra kỹ thuật, nhưng hệ thống chưa xác minh tự động trạng thái pháp lý, hóa đơn thay thế/điều chỉnh và lịch sử thanh toán. Người xác nhận phải kiểm tra chứng từ gốc và thanh toán thực tế trước khi tiếp tục.</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="text-sm">Ngày ghi nhận <input type="date" wire:model="recognizedOn" class="mt-1 w-full rounded-lg border border-slate-300 p-2"></label>
                        <label class="text-sm">Hạn thanh toán (ngày xuất + 90 ngày; có thể sửa) <input type="date" wire:model="dueOn" class="mt-1 w-full rounded-lg border border-slate-300 p-2"></label>
                    </div>
                    <label class="flex items-start gap-2 text-sm text-slate-800">
                        <input type="checkbox" wire:model.live="reviewed" class="mt-1">
                        <span>Tôi đã kiểm tra hóa đơn gốc, tình trạng hiệu lực/thay thế/điều chỉnh và các khoản thanh toán liên quan; đồng ý ghi nhận giá trị hóa đơn làm công nợ gốc. Tôi hiểu Finance chưa tự động trừ thanh toán.</span>
                    </label>
                    <button type="button" wire:click="confirmReview" wire:loading.attr="disabled" @disabled(!$reviewed) class="min-h-10 rounded-lg bg-indigo-700 px-4 py-2 font-semibold text-white disabled:opacity-50">Xác nhận ghi nhận công nợ</button>
                @endif
                @error('invoice')<p role="alert" class="text-sm text-red-700">{{ $message }}</p>@enderror
            </section>
                </div>
            </div>
        @endif
                @if($invoices->hasPages())
            <nav class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-3 text-sm" aria-label="Phân trang hóa đơn">
                <p class="text-slate-600">Trang {{ $invoices->currentPage() }} / {{ $invoices->lastPage() }} · {{ number_format($invoices->total(), 0, ',', '.') }} hóa đơn</p>
                <div class="flex flex-wrap items-center gap-1">
                    <button type="button" wire:click="gotoPage(1, 'invoicePage')" @disabled($invoices->currentPage() === 1) class="min-h-9 rounded-lg border border-slate-300 px-2 disabled:opacity-40" aria-label="Trang đầu">Đầu</button>
                    <button type="button" wire:click="previousPage('invoicePage')" @disabled($invoices->currentPage() === 1) class="min-h-9 rounded-lg border border-slate-300 px-2 disabled:opacity-40" aria-label="Trang trước">‹</button>
                    @if(max(1, min($invoices->currentPage() - 2, $invoices->lastPage() - 4)) > 1)
                        <button type="button" wire:click="gotoPage(1, 'invoicePage')" class="min-h-9 rounded-lg border border-slate-300 px-3">1</button>
                        @if(max(1, min($invoices->currentPage() - 2, $invoices->lastPage() - 4)) > 2)<span class="px-1 text-slate-500" aria-hidden="true">…</span>@endif
                    @endif
                    @for($n = max(1, min($invoices->currentPage() - 2, $invoices->lastPage() - 4)); $n <= min($invoices->lastPage(), max(5, $invoices->currentPage() + 2)); $n++)
                        <button type="button" wire:click="gotoPage({{ $n }}, 'invoicePage')" @if($n === $invoices->currentPage()) aria-current="page" @endif class="min-h-9 min-w-9 rounded-lg border px-2 {{ $n === $invoices->currentPage() ? 'border-indigo-700 bg-indigo-700 font-bold text-white' : 'border-slate-300 text-slate-700 hover:bg-slate-50' }}">{{ $n }}</button>
                    @endfor
                    @if(min($invoices->lastPage(), max(5, $invoices->currentPage() + 2)) < $invoices->lastPage())
                        @if(min($invoices->lastPage(), max(5, $invoices->currentPage() + 2)) < $invoices->lastPage() - 1)<span class="px-1 text-slate-500" aria-hidden="true">…</span>@endif
                        <button type="button" wire:click="gotoPage({{ $invoices->lastPage() }}, 'invoicePage')" class="min-h-9 rounded-lg border border-slate-300 px-3">{{ $invoices->lastPage() }}</button>
                    @endif
                    <button type="button" wire:click="nextPage('invoicePage')" @disabled($invoices->currentPage() === $invoices->lastPage()) class="min-h-9 rounded-lg border border-slate-300 px-2 disabled:opacity-40" aria-label="Trang sau">›</button>
                    <button type="button" wire:click="gotoPage({{ $invoices->lastPage() }}, 'invoicePage')" @disabled($invoices->currentPage() === $invoices->lastPage()) class="min-h-9 rounded-lg border border-slate-300 px-2 disabled:opacity-40" aria-label="Trang cuối">Cuối</button>
                </div>
            </nav>
        @endif
        <p class="text-xs text-amber-900">Tổng tiền hóa đơn không đồng nghĩa số dư chưa thanh toán. Trạng thái liên kết chỉ dựa trên định danh nguồn Invoices/invoice/ID trong Finance, không xác nhận đã thanh toán, đối trừ hoặc đủ điều kiện ghi nhận công nợ. Không tự động ghi dữ liệu.</p>
    @endif
</section>
