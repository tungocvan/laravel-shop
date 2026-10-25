<div class="space-y-4">
    <section class="rounded-xl border border-slate-200 bg-white p-4" aria-label="Bộ lọc tuổi nợ">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div><h2 class="font-semibold text-slate-900">Phân tích tuổi nợ</h2><p class="text-xs text-slate-500">Ngày đối chiếu: {{ $today }}</p></div>
            <button type="button" wire:click="resetFilters" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">Đặt lại</button>
        </div>
        <div class="grid gap-3 sm:grid-cols-3">
            <label class="text-sm">Đối tác / MST / chứng từ
                <x-search wire:model.live.debounce.400ms="search" maxlength="100" placeholder="Tìm công nợ..." class="mt-1.5" input-class="min-h-11 rounded-xl border-slate-300 bg-white py-3 pr-4 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
            </label>
            <label class="text-sm">Loại công nợ
                <select wire:model.live="direction" class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    <option value="">Tất cả</option><option value="receivable">Phải thu</option><option value="payable">Phải trả</option>
                </select>
            </label>
            <label class="text-sm">Nhóm tuổi nợ
                <select wire:model.live="bucket" class="mt-1 min-h-10 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100">
                    <option value="">Tất cả</option>
                    <option value="upcoming">Chưa đến hạn</option>
                    <option value="due_today">Đến hạn hôm nay</option>
                    <option value="overdue_1_30">Quá hạn 1–30 ngày</option>
                    <option value="overdue_31_60">Quá hạn 31–60 ngày</option>
                    <option value="overdue_61_90">Quá hạn 61–90 ngày</option>
                    <option value="overdue_90_plus">Quá hạn trên 90 ngày</option>
                    <option value="undated">Chưa có ngày đến hạn</option>
                </select>
            </label>
        </div>
        @if($errors->any()) <p role="alert" class="mt-3 text-sm text-rose-700">{{ $errors->first() }}</p> @endif
        <p class="mt-3 text-xs text-amber-800">Chỉ gồm khoản <strong>đã xác nhận</strong>. Tuổi nợ tính theo ngày đến hạn so với ngày đối chiếu; khoản không có ngày đến hạn được tách riêng. Giá trị hiển thị là <strong>gốc sau điều chỉnh, chưa trừ thanh toán</strong>, không đại diện cho nợ còn phải thu/trả thực tế. Đơn vị VND.</p>
    </section>
    @php
        $labels = [
            'upcoming' => 'Chưa đến hạn',
            'due_today' => 'Đến hạn hôm nay',
            'overdue_1_30' => 'Quá hạn 1–30 ngày',
            'overdue_31_60' => 'Quá hạn 31–60 ngày',
            'overdue_61_90' => 'Quá hạn 61–90 ngày',
            'overdue_90_plus' => 'Quá hạn trên 90 ngày',
            'undated' => 'Chưa có ngày đến hạn',
        ];
    @endphp
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($labels as $key => $label)
            <div class="rounded-xl border border-slate-200 bg-white p-4" wire:key="aging-card-{{ $key }}">
                <p class="text-sm text-slate-600">{{ $label }}</p>
                <p class="mt-2 text-lg font-bold tabular-nums">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) ($totals->get($key)->principal_total ?? '0')) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $totals->get($key)->debt_count ?? 0 }} khoản</p>
            </div>
        @endforeach
    </div>
    <section class="rounded-xl border border-slate-200 bg-white p-4" aria-label="Bảng tuổi nợ">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-slate-900">Chi tiết theo tuổi nợ</h2>
            <span class="text-xs text-slate-500">{{ $debts->total() }} khoản</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[950px] w-full text-left text-sm">
                <thead class="border-b bg-slate-50 text-slate-700"><tr>
                    <th class="p-3">Chứng từ</th><th class="p-3">Đối tác</th><th class="p-3">Loại</th>
                    <th class="p-3">Ngày đến hạn</th><th class="p-3">Nhóm tuổi nợ</th>
                    <th class="p-3 text-right">Gốc sau điều chỉnh</th><th class="p-3">Thao tác</th>
                </tr></thead>
                <tbody>
                    @forelse($debts as $debt)
                        <tr class="border-b border-slate-100" wire:key="aging-debt-{{ $debt->id }}">
                            <td class="p-3 font-semibold">{{ $debt->reference_number }}</td>
                            <td class="p-3">{{ $debt->counterparty_name }}</td>
                            <td class="p-3">{{ $debt->direction === 'receivable' ? 'Phải thu' : 'Phải trả' }}</td>
                            <td class="p-3">{{ $debt->due_on ?: '—' }}</td>
                            <td class="p-3">{{ $labels[$debt->aging_bucket] ?? '—' }}</td>
                            <td class="p-3 whitespace-nowrap text-right font-semibold tabular-nums">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $debt->adjusted_principal) }}</td>
                            <td class="p-3"><a class="font-semibold text-indigo-700 underline" href="{{ route('admin.finance.debts.index', ['section' => 'documents', 'debt' => $debt->id]) }}">Xem chứng từ</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-6 text-center text-slate-500">Không có công nợ phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">@include('Finance::livewire.partials.debt-report-pagination', ['paginator' => $debts, 'pageName' => 'agingPage', 'paginationLabel' => 'Phân trang aging công nợ'])</div>
    </section>
</div>
