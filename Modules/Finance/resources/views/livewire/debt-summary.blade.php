<div class="space-y-5">
    <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm" aria-label="Bộ lọc tổng hợp công nợ">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div><h2 class="text-lg font-semibold text-slate-900">Tổng hợp công nợ đã xác nhận</h2><p class="mt-1 text-xs font-medium text-indigo-700">Báo cáo tổng hợp · Chỉ đọc</p></div>
            <button type="button" wire:click="resetFilters" class="min-h-10 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600">Đặt lại</button>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <label class="block text-sm font-medium text-slate-700">Đối tác / mã số thuế
                <x-search wire:model.live.debounce.400ms="search" maxlength="100" placeholder="Tìm đối tác..." class="mt-1.5" input-class="min-h-11 rounded-xl border-slate-300 bg-white py-3 pr-4 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
            </label>
            <label class="block text-sm font-medium text-slate-700">Loại công nợ
                <select wire:model.live="direction" class="mt-1.5 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    <option value="">Tất cả</option><option value="receivable">Phải thu</option><option value="payable">Phải trả</option>
                </select>
            </label>
            <label class="block text-sm font-medium text-slate-700">Ghi nhận từ ngày
                <input type="date" wire:model.live="dateFrom" class="mt-1.5 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </label>
            <label class="block text-sm font-medium text-slate-700">Ghi nhận đến ngày
                <input type="date" wire:model.live="dateTo" class="mt-1.5 min-h-11 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </label>
        </div>
        @if($errors->any()) <p role="alert" class="mt-3 text-sm text-rose-700">{{ $errors->first() }}</p> @endif
        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm leading-relaxed text-amber-900"><p class="mb-1 font-semibold">Phạm vi số liệu</p><p>Chỉ gồm công nợ <strong>đã xác nhận</strong>. Khoảng ngày lọc theo <strong>ngày ghi nhận của khoản nợ</strong>, không phải ngày phát sinh điều chỉnh. Tổng điều chỉnh tính đến hiện tại của các khoản được chọn. Đây là báo cáo theo nhóm khoản nợ ghi nhận, <strong>không phải phát sinh kế toán trong kỳ, số dư đầu kỳ/cuối kỳ hay dư nợ chưa thanh toán</strong>. Chưa trừ thanh toán, chưa đồng bộ hóa đơn. Đơn vị VND.</p></div>
    </section>
    <div class="grid gap-3 sm:grid-cols-3">
        <div class="min-w-0 rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-medium text-slate-600">Số khoản đã xác nhận</p><p class="mt-2 break-words text-xl font-semibold tabular-nums text-slate-900">{{ number_format((int) $totals->debt_count, 0, ',', '.') }}</p></div>
        <div class="min-w-0 rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-medium text-slate-600">Giá trị phải thu sau điều chỉnh</p><p class="mt-2 break-words text-xl font-semibold tabular-nums text-slate-900">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $totals->receivable) }}</p></div>
        <div class="min-w-0 rounded-xl border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-medium text-slate-600">Giá trị phải trả sau điều chỉnh</p><p class="mt-2 break-words text-xl font-semibold tabular-nums text-slate-900">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $totals->payable) }}</p></div>
    </div>
    <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm" aria-label="Bảng tổng hợp công nợ">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-slate-900">Tổng hợp theo đối tác</h2>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">{{ number_format($groups->total(), 0, ',', '.') }} nhóm</span>
        </div>
        <div class="max-w-full overflow-x-auto overscroll-x-contain rounded-xl border border-slate-200" role="region" aria-label="Bảng tổng hợp theo đối tác" tabindex="0">
            <p class="px-4 pt-3 text-xs text-slate-500 sm:hidden">Vuốt ngang để xem các cột còn lại.</p>
            <table class="w-full min-w-[1050px] text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th scope="col" class="px-4 py-3">Đối tác</th>
                        <th scope="col" class="px-4 py-3">MST</th>
                        <th scope="col" class="px-4 py-3">Loại</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right">Số khoản</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right">Gốc</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right">Điều chỉnh</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right">Sau điều chỉnh</th>
                        <th scope="col" class="px-4 py-3">Chi tiết</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($groups as $group)
                        <tr class="transition hover:bg-slate-50/80" wire:key="debt-summary-{{ $loop->index }}-{{ $groups->currentPage() }}">
                            <td class="min-w-[230px] px-4 py-3 font-medium text-slate-900">{{ $group->counterparty_name }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $group->counterparty_tax_code ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $group->direction === 'receivable' ? 'Phải thu' : 'Phải trả' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $group->debt_count }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $group->original_total) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $group->adjustment_total) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-slate-900">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $group->adjusted_total) }}</td>
                            <td class="whitespace-nowrap px-4 py-3"><a class="inline-flex min-h-10 items-center rounded-lg px-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600" href="{{ route('admin.finance.debts.index', ['section' => 'ledger']) }}" aria-label="Xem sổ chi tiết công nợ">Xem chi tiết</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-slate-500">Không có công nợ phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            @include('Finance::livewire.partials.debt-report-pagination', ['paginator' => $groups, 'pageName' => 'summaryPage', 'paginationLabel' => 'Phân trang tổng hợp công nợ'])
        </div>
    </section>
</div>
