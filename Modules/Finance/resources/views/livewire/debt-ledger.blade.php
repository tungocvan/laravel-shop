<div class="space-y-4">
    <section class="rounded-xl border border-slate-200 bg-white p-4" aria-label="Bộ lọc sổ chi tiết công nợ">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-slate-900">Tra cứu chi tiết công nợ</h2>
            <button type="button" wire:click="resetFilters" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">Đặt lại</button>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <label class="text-sm">Đối tác / MST / mã chứng từ
                <input type="search" maxlength="100" wire:model.live.debounce.400ms="search" placeholder="Tìm công nợ..." class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </label>
            <label class="text-sm">Loại
                <select wire:model.live="direction" class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    <option value="">Tất cả</option><option value="receivable">Phải thu</option><option value="payable">Phải trả</option>
                </select>
            </label>
            <label class="text-sm">Trạng thái
                <select wire:model.live="status" class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    <option value="confirmed">Đã xác nhận</option><option value="draft">Nháp</option><option value="">Tất cả</option>
                </select>
            </label>
            <label class="text-sm">Đến hạn từ
                <input type="date" wire:model.live="dateFrom" class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </label>
            <label class="text-sm">Đến hạn đến
                <input type="date" wire:model.live="dateTo" class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </label>
        </div>
        @if($errors->any()) <p role="alert" class="mt-3 text-sm text-rose-700">{{ $errors->first() }}</p> @endif
        <p class="mt-3 text-xs text-amber-800">Bộ lọc ngày áp dụng theo <strong>ngày đến hạn</strong>, không phải ngày ghi nhận. Giá trị sau điều chỉnh = công nợ gốc + tổng điều chỉnh; <strong>chưa trừ thanh toán hoặc đối trừ</strong>. Đây không phải sổ dư nợ thực tế.</p>
    </section>
    <section class="rounded-xl border border-slate-200 bg-white p-4" aria-label="Sổ chi tiết công nợ">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-slate-900">Sổ chi tiết theo khoản công nợ</h2>
            <span class="text-xs text-slate-500">{{ $debts->total() }} khoản</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[1050px] w-full text-left text-sm">
                <thead class="border-b bg-slate-50 text-slate-700"><tr>
                    <th class="p-3">Chứng từ</th><th class="p-3">Đối tác</th><th class="p-3">Loại</th>
                    <th class="p-3 text-right">Gốc</th><th class="p-3 text-right">Điều chỉnh</th>
                    <th class="p-3 text-right">Sau điều chỉnh</th><th class="p-3">Ngày ghi nhận</th>
                    <th class="p-3">Ngày đến hạn</th><th class="p-3">Trạng thái</th><th class="p-3">Chi tiết</th>
                </tr></thead>
                <tbody>
                    @forelse($debts as $debt)
                        <tr class="border-b border-slate-100" wire:key="debt-ledger-{{ $debt->id }}">
                            <td class="p-3 font-semibold">{{ $debt->reference_number }}</td>
                            <td class="p-3">{{ $debt->counterparty_name }}</td>
                            <td class="p-3">{{ $debt->direction === 'receivable' ? 'Phải thu' : 'Phải trả' }}</td>
                            <td class="p-3 whitespace-nowrap text-right tabular-nums">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $debt->original_amount) }}</td>
                            <td class="p-3 whitespace-nowrap text-right tabular-nums">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $debt->adjustment_total) }}</td>
                            <td class="p-3 whitespace-nowrap text-right font-semibold tabular-nums">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $debt->adjusted_principal) }}</td>
                            <td class="p-3">{{ $debt->recognized_on ?: '—' }}</td>
                            <td class="p-3">{{ $debt->due_on ?: '—' }}</td>
                            <td class="p-3">{{ $debt->status === 'confirmed' ? 'Đã xác nhận' : 'Nháp' }}</td>
                            <td class="p-3"><a class="font-semibold text-indigo-700 underline" href="{{ route('admin.finance.debts.index', ['section' => 'documents', 'debt' => $debt->id]) }}">Xem chi tiết</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="p-6 text-center text-slate-500">Không có công nợ phù hợp với bộ lọc.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $debts->links() }}</div>
    </section>
</div>
