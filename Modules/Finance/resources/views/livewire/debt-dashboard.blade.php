<div class="space-y-5">
    <section class="rounded-xl border border-slate-200 bg-white p-4" aria-label="Việc cần xử lý">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div><h2 class="font-semibold text-slate-900">Việc cần xử lý</h2><p class="text-xs text-slate-500">Hóa đơn nguồn chưa ghi nhận và công nợ nháp, không cộng vào số tiền đã xác nhận.</p></div>
            <a class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-indigo-700" href="{{ route('admin.finance.debts.index', ['section' => 'documents']) }}">Mở Chứng từ</a>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-xs text-slate-600">Hóa đơn chưa ghi nhận</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $invoiceInbox ? number_format((int) $invoiceInbox->unrecorded_count, 0, ',', '.') : '—' }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $invoiceInbox ? 'Cần kiểm tra và xác nhận' : 'Chưa thể truy cập dữ liệu Invoices' }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-xs text-slate-600">Hóa đơn bán ra chưa ghi nhận</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $invoiceInbox ? number_format((int) $invoiceInbox->sold_count, 0, ',', '.') : '—' }}</p>
                <p class="mt-1 text-xs text-slate-500">Nguồn có thể phát sinh phải thu</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-xs text-slate-600">Hóa đơn mua vào chưa ghi nhận</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $invoiceInbox ? number_format((int) $invoiceInbox->purchase_count, 0, ',', '.') : '—' }}</p>
                <p class="mt-1 text-xs text-slate-500">Nguồn có thể phát sinh phải trả</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-xs text-slate-600">Công nợ nháp</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums">{{ number_format((int) $draftCount, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-slate-500">Chưa xác nhận</p>
            </div>
        </div>
    </section>
    <section class="rounded-xl border border-slate-200 bg-white p-4" aria-label="Bộ lọc tổng quan công nợ">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-slate-900">Bộ lọc công nợ</h2>
            <button type="button" wire:click="resetFilters" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">Đặt lại</button>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <label class="text-sm">Loại công nợ
                <select wire:model.live="direction" class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition-colors focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    <option value="">Tất cả</option><option value="receivable">Phải thu</option><option value="payable">Phải trả</option>
                </select>
            </label>
            <label class="text-sm">Trạng thái
                <select wire:model.live="status" class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition-colors focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    <option value="confirmed">Đã xác nhận</option><option value="draft">Nháp</option><option value="">Tất cả</option>
                </select>
            </label>
            <label class="text-sm">Đối tác hoặc mã
                <input type="search" wire:model.live.debounce.400ms="search" maxlength="100" placeholder="Tìm công nợ..." class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition-colors focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </label>
            <label class="text-sm">Đến hạn từ
                <input type="date" wire:model.live="dueFrom" class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition-colors focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </label>
            <label class="text-sm">Đến hạn đến
                <input type="date" wire:model.live="dueTo" class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm outline-none transition-colors focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </label>
        </div>
        @if($errors->any()) <p role="alert" class="mt-3 text-sm text-rose-700">{{ $errors->first() }}</p> @endif
        <p class="mt-3 text-xs text-amber-800">Số liệu là <strong>giá trị gốc sau điều chỉnh</strong>, chưa trừ thanh toán hoặc đối trừ. Không phải số dư công nợ còn phải thu/trả. Chỉ tính các bản ghi theo bộ lọc; mặc định chỉ xem công nợ đã xác nhận. Đơn vị VND.</p>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5" aria-label="Chỉ số tổng quan">
        @foreach([
            ['Số khoản', number_format((int) $totals->debt_count, 0, ',', '.'), ''],
            ['Giá trị phải thu', \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $totals->receivable), ''],
            ['Giá trị phải trả', \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $totals->payable), ''],
            ['Giá trị quá hạn', \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $totals->overdue), 'Ngày đến hạn trước hôm nay'],
            ['Đến hạn hôm nay', \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $totals->due_today), 'Ngày đến hạn hôm nay'],
        ] as $metric)
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold text-slate-600">{{ $metric[0] }}</p>
                <p class="mt-2 break-words text-lg font-bold tabular-nums text-indigo-800">{{ $metric[1] }}</p>
                @if($metric[2] !== '') <p class="mt-1 text-xs text-slate-500">{{ $metric[2] }}</p> @endif
            </div>
        @endforeach
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-4" aria-label="Danh sách công nợ cần theo dõi">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-slate-900">Danh sách công nợ theo bộ lọc</h2>
            <span class="text-xs text-slate-500">{{ $debts->total() }} khoản</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b bg-slate-50 text-slate-700"><tr>
                    <th class="p-3">Mã</th><th class="p-3">Đối tác</th><th class="p-3">Loại</th>
                    <th class="p-3 text-right">Giá trị sau điều chỉnh</th><th class="p-3">Ngày đến hạn</th>
                    <th class="p-3">Trạng thái</th><th class="p-3">Thao tác</th>
                </tr></thead>
                <tbody>
                    @forelse($debts as $debt)
                        <tr class="border-b border-slate-100" wire:key="dashboard-debt-{{ $debt->id }}">
                            <td class="whitespace-nowrap p-3 font-semibold">{{ $debt->reference_number }}</td>
                            <td class="p-3">{{ $debt->counterparty_name }}</td>
                            <td class="p-3">{{ $debt->direction === 'receivable' ? 'Phải thu' : 'Phải trả' }}</td>
                            <td class="whitespace-nowrap p-3 text-right tabular-nums">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $debt->adjusted_principal) }}</td>
                            <td class="whitespace-nowrap p-3 {{ $debt->status === 'confirmed' && $debt->due_on && $debt->due_on < $today ? 'font-semibold text-rose-700' : '' }}">{{ $debt->due_on ?: 'Chưa xác định' }}</td>
                            <td class="p-3">{{ $debt->status === 'confirmed' ? 'Đã xác nhận' : 'Nháp' }}</td>
                            <td class="p-3"><a class="font-semibold text-indigo-700 underline" href="{{ route('admin.finance.debts.index', ['section' => 'documents', 'debt' => $debt->id]) }}">Xem chi tiết</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-6 text-center text-slate-500">Không có công nợ phù hợp với bộ lọc.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $debts->links() }}</div>
    </section>
</div>
