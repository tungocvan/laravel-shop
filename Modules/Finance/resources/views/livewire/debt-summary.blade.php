<div class="space-y-4">
    <section class="rounded-xl border border-slate-200 bg-white p-4" aria-label="Bộ lọc tổng hợp công nợ">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-slate-900">Tổng hợp công nợ đã xác nhận</h2>
            <button type="button" wire:click="resetFilters" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">Đặt lại</button>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <label class="text-sm">Đối tác / mã số thuế
                <input type="search" maxlength="100" wire:model.live.debounce.400ms="search" placeholder="Tìm đối tác..." class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </label>
            <label class="text-sm">Loại công nợ
                <select wire:model.live="direction" class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    <option value="">Tất cả</option><option value="receivable">Phải thu</option><option value="payable">Phải trả</option>
                </select>
            </label>
            <label class="text-sm">Ghi nhận từ ngày
                <input type="date" wire:model.live="dateFrom" class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </label>
            <label class="text-sm">Ghi nhận đến ngày
                <input type="date" wire:model.live="dateTo" class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </label>
        </div>
        @if($errors->any()) <p role="alert" class="mt-3 text-sm text-rose-700">{{ $errors->first() }}</p> @endif
        <p class="mt-3 text-xs text-amber-800">Chỉ gồm công nợ <strong>đã xác nhận</strong>. Khoảng ngày lọc theo <strong>ngày ghi nhận của khoản nợ</strong>, không phải ngày phát sinh điều chỉnh. Tổng điều chỉnh tính đến hiện tại của các khoản được chọn. Đây là báo cáo theo nhóm khoản nợ ghi nhận, <strong>không phải phát sinh kế toán trong kỳ, số dư đầu kỳ/cuối kỳ hay dư nợ chưa thanh toán</strong>. Chưa trừ thanh toán, chưa đồng bộ hóa đơn. Đơn vị VND.</p>
    </section>
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-sm text-slate-600">Số khoản đã xác nhận</p><p class="mt-2 text-xl font-bold tabular-nums">{{ number_format((int) $totals->debt_count, 0, ',', '.') }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-sm text-slate-600">Giá trị phải thu sau điều chỉnh</p><p class="mt-2 text-xl font-bold tabular-nums">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $totals->receivable) }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-sm text-slate-600">Giá trị phải trả sau điều chỉnh</p><p class="mt-2 text-xl font-bold tabular-nums">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $totals->payable) }}</p></div>
    </div>
    <section class="rounded-xl border border-slate-200 bg-white p-4" aria-label="Bảng tổng hợp công nợ">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-slate-900">Tổng hợp theo đối tác</h2>
            <span class="text-xs text-slate-500">{{ $groups->total() }} nhóm</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[900px] w-full text-left text-sm">
                <thead class="border-b bg-slate-50 text-slate-700"><tr>
                    <th class="p-3">Đối tác</th><th class="p-3">MST</th><th class="p-3">Loại</th><th class="p-3 text-right">Số khoản</th>
                    <th class="p-3 text-right">Gốc</th><th class="p-3 text-right">Điều chỉnh</th><th class="p-3 text-right">Sau điều chỉnh</th><th class="p-3">Chi tiết</th>
                </tr></thead>
                <tbody>
                    @forelse($groups as $group)
                        <tr class="border-b border-slate-100" wire:key="debt-summary-{{ $loop->index }}-{{ $groups->currentPage() }}">
                            <td class="p-3 font-semibold">{{ $group->counterparty_name }}</td>
                            <td class="p-3">{{ $group->counterparty_tax_code ?: '—' }}</td>
                            <td class="p-3">{{ $group->direction === 'receivable' ? 'Phải thu' : 'Phải trả' }}</td>
                            <td class="p-3 text-right tabular-nums">{{ $group->debt_count }}</td>
                            <td class="p-3 whitespace-nowrap text-right tabular-nums">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $group->original_total) }}</td>
                            <td class="p-3 whitespace-nowrap text-right tabular-nums">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $group->adjustment_total) }}</td>
                            <td class="p-3 whitespace-nowrap text-right font-semibold tabular-nums">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $group->adjusted_total) }}</td>
                            <td class="p-3"><a class="font-semibold text-indigo-700 underline" href="{{ route('admin.finance.debts.index', ['section' => 'ledger']) }}">Mở sổ chi tiết</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="p-6 text-center text-slate-500">Không có công nợ phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $groups->links() }}</div>
    </section>
</div>
