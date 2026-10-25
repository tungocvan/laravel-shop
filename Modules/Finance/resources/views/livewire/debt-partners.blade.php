<div class="space-y-4">
    <section class="rounded-xl border border-slate-200 bg-white p-4">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-slate-900">Công nợ theo đối tác</h2>
            <button type="button" wire:click="resetFilters" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">Đặt lại</button>
        </div>
        <div class="grid gap-3 sm:grid-cols-3">
            <label class="text-sm">Tên hoặc mã số thuế
                <input type="search" wire:model.live.debounce.400ms="search" maxlength="100" placeholder="Tìm đối tác..." class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
            </label>
            <label class="text-sm">Loại công nợ
                <select wire:model.live="direction" class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    <option value="">Tất cả</option><option value="receivable">Phải thu</option><option value="payable">Phải trả</option>
                </select>
            </label>
            <label class="text-sm">Trạng thái
                <select wire:model.live="status" class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    <option value="confirmed">Đã xác nhận</option><option value="draft">Nháp</option><option value="">Tất cả</option>
                </select>
            </label>
        </div>
        @if($errors->any()) <p role="alert" class="mt-3 text-sm text-rose-700">{{ $errors->first() }}</p> @endif
        <p class="mt-3 text-xs text-amber-800">Chỉ tổng hợp công nợ đã ghi nhận trong Finance, chưa đồng bộ danh bạ đối tác hoặc hóa đơn. Nhóm theo tên + mã số thuế + loại công nợ; đối tác trùng tên nhưng thiếu mã số thuế có thể bị gộp. Giá trị là gốc sau điều chỉnh, <strong>chưa trừ thanh toán</strong>. Đơn vị VND.</p>
    </section>
    <section class="rounded-xl border border-slate-200 bg-white p-4" aria-label="Bảng tổng hợp đối tác">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-slate-900">Danh sách đối tác có công nợ</h2>
            <span class="text-xs text-slate-500">{{ $partners->total() }} nhóm</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b bg-slate-50 text-slate-700"><tr>
                    <th class="p-3">Đối tác</th><th class="p-3">Mã số thuế</th><th class="p-3">Loại</th>
                    <th class="p-3 text-right">Số khoản</th><th class="p-3 text-right">Giá trị sau điều chỉnh</th>
                    <th class="p-3 text-right">Giá trị quá hạn</th><th class="p-3">Thao tác</th>
                </tr></thead>
                <tbody>
                    @forelse($partners as $partner)
                        <tr class="border-b border-slate-100" wire:key="partner-{{ $loop->index }}-{{ $partners->currentPage() }}">
                            <td class="p-3 font-semibold">{{ $partner->counterparty_name }}</td>
                            <td class="p-3">{{ $partner->counterparty_tax_code ?: '—' }}</td>
                            <td class="p-3">{{ $partner->direction === 'receivable' ? 'Phải thu' : 'Phải trả' }}</td>
                            <td class="p-3 text-right tabular-nums">{{ $partner->debt_count }}</td>
                            <td class="p-3 whitespace-nowrap text-right tabular-nums">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $partner->principal_total) }}</td>
                            <td class="p-3 whitespace-nowrap text-right tabular-nums">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $partner->overdue_total) }}</td>
                            <td class="p-3"><a class="font-semibold text-indigo-700 underline" href="{{ route('admin.finance.debts.index', ['section' => 'dashboard', 'search' => $partner->counterparty_name, 'direction' => $partner->direction]) }}">Xem khoản nợ</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-6 text-center text-slate-500">Không có đối tác phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $partners->links() }}</div>
    </section>
</div>
