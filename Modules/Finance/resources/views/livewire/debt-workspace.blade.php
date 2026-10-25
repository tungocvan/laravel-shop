<div class="space-y-4">
    @if (session()->has('debt_notice')) <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">{{ session('debt_notice') }}</div> @endif
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><h2 class="text-xl font-semibold text-slate-900">Chứng từ công nợ</h2><p class="mt-1 text-sm text-slate-500">Tra cứu hóa đơn, ghi nhận công nợ và quản lý chứng từ đã xác nhận.</p></div>
        @if ($activeTab === 'debts' && $canManage)
            <button type="button" wire:click="openCreate" class="inline-flex min-h-10 items-center gap-2 whitespace-nowrap rounded-lg bg-indigo-700 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-800">+ Tạo công nợ</button>
        @endif
    </div>
    <div class="flex flex-wrap gap-1 rounded-xl border border-slate-200 bg-slate-50 p-1.5" role="tablist" aria-label="Loại chứng từ">
        <button type="button" role="tab" aria-selected="{{ $activeTab === 'invoices' ? 'true' : 'false' }}" wire:click="setTab('invoices')" class="min-h-10 rounded-lg px-4 py-2 text-sm font-semibold transition {{ $activeTab === 'invoices' ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white/70' }}">Hóa đơn nguồn</button>
        <button type="button" role="tab" aria-selected="{{ $activeTab === 'debts' ? 'true' : 'false' }}" wire:click="setTab('debts')" class="min-h-10 rounded-lg px-4 py-2 text-sm font-semibold transition {{ $activeTab === 'debts' ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white/70' }}">Công nợ đã ghi nhận</button>
        <button type="button" role="tab" aria-selected="{{ $activeTab === 'reconciliation' ? 'true' : 'false' }}" wire:click="setTab('reconciliation')" class="min-h-10 rounded-lg px-4 py-2 text-sm font-semibold {{ $activeTab === 'reconciliation' ? 'bg-white text-indigo-700 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white/70' }}">Đối soát 11G</button>
    </div>
    @if ($activeTab === 'invoices')
        <section role="tabpanel" aria-label="Hóa đơn nguồn">
            @if(\Illuminate\Support\Facades\Schema::hasTable('permissions') && auth('admin')->user()?->can('invoices-list'))
                <livewire:finance.invoice-debt-explorer />
            @else
                <p class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">Tra cứu hóa đơn yêu cầu đồng thời quyền finance.view và invoices-list.</p>
            @endif
        </section>
    @elseif ($activeTab === 'reconciliation')
        <section role="tabpanel" aria-label="Đối soát công nợ 11G" class="space-y-4">
            <h3 class="text-lg font-semibold">Đối soát công nợ và phiếu thanh toán</h3>
            <p class="text-sm text-slate-600">Ảnh chụp số dư lũy kế hiện tại của công nợ đã xác nhận. Chỉ số dư của dòng đối soát khớp được tính vào tổng đã xác minh. Đây không phải báo cáo số dư đầu kỳ/cuối kỳ hoặc báo cáo kế toán theo kỳ.</p>
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="text-sm">Tìm công nợ / đối tác<input type="search" wire:model.live.debounce.350ms="reconcileSearch" class="mt-1 w-full rounded-lg border border-slate-300 p-2"></label>
                <label class="text-sm">Loại công nợ<select wire:model.live="reconcileDirection" class="mt-1 w-full rounded-lg border border-slate-300 p-2"><option value="">Tất cả</option><option value="receivable">Phải thu</option><option value="payable">Phải trả</option></select></label>
            </div>
            @if (!$reconciliation['ready'])
                <p class="rounded-lg border border-amber-300 p-3 text-sm text-amber-800">Chưa thể đối soát: thiếu bảng {{ $reconciliation['missing'] }}.</p>
            @else
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-lg border p-3"><p class="text-xs text-slate-500">Gốc phải thu sau điều chỉnh</p><strong>{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $reconciliation['totals']->receivable_principal) }}</strong></div>
                    <div class="rounded-lg border p-3"><p class="text-xs text-slate-500">Gốc phải trả sau điều chỉnh</p><strong>{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $reconciliation['totals']->payable_principal) }}</strong></div>
                    <div class="rounded-lg border p-3"><p class="text-xs text-slate-500">Còn phải thu đã đối soát</p><strong>{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $reconciliation['totals']->receivable_outstanding_verified) }}</strong></div>
                    <div class="rounded-lg border p-3"><p class="text-xs text-slate-500">Còn phải trả đã đối soát</p><strong>{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $reconciliation['totals']->payable_outstanding_verified) }}</strong></div>
                </div>
                <p class="text-sm">Thanh toán ròng đã đối soát: <strong>{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $reconciliation['totals']->verified_net_payments) }}</strong> · Dòng cần kiểm tra: <strong>{{ $reconciliation['totals']->review_count }}</strong></p>
                <div class="overflow-x-auto rounded-lg border"><table class="w-full min-w-[850px] text-left text-sm">
                    <thead class="bg-slate-50"><tr><th class="p-2">Chứng từ</th><th class="p-2">Đối tác</th><th class="p-2">Loại</th><th class="p-2 text-right">Gốc điều chỉnh</th><th class="p-2 text-right">Thanh toán ròng</th><th class="p-2 text-right">Còn nợ (dự kiến)</th><th class="p-2">Đối soát</th></tr></thead>
                    <tbody>@forelse ($reconciliation['rows'] as $item)
                        <tr class="border-t"><td class="p-2">{{ $item->reference_number }}</td><td class="p-2">{{ $item->counterparty_name }}</td><td class="p-2">{{ $item->direction === 'receivable' ? 'Phải thu' : 'Phải trả' }}</td><td class="p-2 text-right">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $item->principal) }}</td><td class="p-2 text-right">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $item->net_mapped) }}</td><td class="p-2 text-right">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $item->outstanding) }}</td><td class="p-2">{{ $item->reconciliation_status === 'matched' ? 'Khớp' : 'Cần đối soát' }}</td></tr>
                    @empty <tr><td colspan="7" class="p-3 text-slate-500">Không có công nợ phù hợp.</td></tr> @endforelse</tbody>
                </table></div>
                {{ $reconciliation['rows']->links() }}
            @endif
        </section>
    @else
        <section role="tabpanel" aria-label="Công nợ đã ghi nhận" class="space-y-4">
            <p class="text-xs text-slate-500">Giá trị sau điều chỉnh là gốc công nợ; khoản đã thanh toán được theo dõi riêng theo chứng từ đã ghi sổ.</p>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <label class="block text-sm font-medium">Tìm chứng từ / đối tác<input aria-label="Tìm công nợ" wire:model.live.debounce.350ms="search" type="search" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm"></label>
        <label class="block text-sm font-medium">Loại công nợ<select wire:model.live="direction" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm"><option value="">Tất cả</option><option value="receivable">Phải thu</option><option value="payable">Phải trả</option></select></label>
        <label class="block text-sm font-medium">Trạng thái<select wire:model.live="status" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm"><option value="">Tất cả</option><option value="draft">Nháp</option><option value="confirmed">Đã xác nhận</option></select></label>
        <label class="block text-sm font-medium">Thanh toán<select wire:model.live="paymentStatus" aria-label="Lọc trạng thái thanh toán" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm"><option value="">Tất cả</option><option value="unpaid">Chưa thanh toán</option><option value="partial">Thanh toán một phần</option><option value="paid">Đã thanh toán</option></select></label>
    </div>
    @if ($creating && $canManage)
        <div class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/50 p-3 sm:p-6" role="presentation" wire:key="finance-create-modal">
            <div role="dialog" aria-modal="true" aria-label="Tạo công nợ thủ công" class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-5 shadow-2xl sm:p-6">
        <form wire:submit="create" class="space-y-4">
            <h3 class="font-semibold">Tạo công nợ thủ công</h3>
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="text-sm">Số chứng từ<input wire:model="reference" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3" required maxlength="100"></label>
                <label class="text-sm">Loại<select wire:model="draftDirection" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"><option value="receivable">Phải thu</option><option value="payable">Phải trả</option></select></label>
                @include("Finance::livewire.partials.partner-picker")
                <label class="text-sm">Số tiền VND<input x-data="{ display: '', format(v) { let digits = v.replace(/[^0-9]/g, ''); return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.'); } }" x-bind:value="display" x-on:input="display = format($event.target.value); $event.target.value = display; $wire.set('amount', display.replace(/\./g, ''))" inputmode="numeric" placeholder="2.000.000" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3" required></label>
                <label class="text-sm">Hạn thanh toán<input wire:model="dueOn" type="date" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3"></label>
            </div>
            @if ($errors->any()) <div role="alert" class="text-sm text-red-700">{{ $errors->first() }}</div> @endif
            <button wire:loading.attr="disabled" wire:target="create" type="submit" class="rounded-xl bg-indigo-700 px-4 py-2 text-sm font-semibold text-white">Lưu nháp</button>
            <button type="button" wire:click="$set('creating', false)" class="rounded-xl border border-slate-300 px-4 py-2 text-sm">Đóng</button>
        </form>
            </div>
        </div>
    @endif
    <div class="min-w-0 max-w-full overflow-x-auto overscroll-x-contain rounded-xl border border-slate-200" role="region" aria-label="Bảng danh sách công nợ, vuốt ngang để xem các cột" tabindex="0">
        <p class="px-3 pt-2 text-xs text-slate-500 sm:hidden">Vuốt ngang trong bảng để xem thêm cột.</p>
        <table class="w-full min-w-[960px] divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50"><tr><th class="whitespace-nowrap p-3">Chứng từ</th><th class="p-3">Đối tác</th><th class="p-3">Loại</th><th class="p-3">Số tiền gốc</th><th class="p-3">Số dư sau điều chỉnh</th><th class="p-3">Hạn</th><th class="p-3">Trạng thái</th><th class="p-3">Chi tiết</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($debts as $debt)
                    <tr><td class="p-3 whitespace-nowrap">{{ $debt->reference_number }}</td><td class="p-3">{{ $debt->counterparty_name }}</td><td class="p-3">{{ $debt->direction === 'receivable' ? 'Phải thu' : 'Phải trả' }}</td><td class="p-3 whitespace-nowrap">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $debt->original_amount) }}</td><td class="p-3 whitespace-nowrap font-semibold tabular-nums text-indigo-800">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $debt->adjusted_balance) }}</td><td class="p-3">{{ $debt->due_on ?: '—' }}</td><td class="p-3">{{ $debt->status === 'draft' ? 'Nháp' : ($debt->status === 'confirmed' ? 'Đã xác nhận' : $debt->status) }}@if(isset($debt->paid_amount) && (float) $debt->paid_amount > 0)<div class="mt-1 text-xs font-semibold text-emerald-700">{{ (float) $debt->paid_amount >= (float) $debt->adjusted_balance ? 'Đã thanh toán' : 'Thanh toán một phần' }}</div><div class="text-xs text-slate-500">Đã trả: {{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $debt->paid_amount) }}</div>@endif</td><td class="p-3"><button wire:click="selectDebt({{ $debt->id }})" class="inline-flex min-h-9 items-center justify-center whitespace-nowrap rounded-lg border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:border-indigo-300 hover:bg-indigo-50">Xem chi tiết</button>@if ($debt->status === 'confirmed' && (!isset($debt->paid_amount) || (float) $debt->paid_amount < (float) $debt->adjusted_balance)) <button type="button" wire:click="openPaymentReview({{ $debt->id }})" class="mt-2 inline-flex min-h-9 items-center justify-center whitespace-nowrap rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">Thanh toán</button>@endif</td></tr>
                @empty <tr><td colspan="8" class="p-6 text-center text-slate-500">Chưa có công nợ phù hợp.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($debts->hasPages())
        @php
            $debtPage = $debts->currentPage();
            $debtLast = $debts->lastPage();
            $debtStart = max(1, min($debtPage - 2, max(1, $debtLast - 4)));
            $debtEnd = min($debtLast, $debtStart + 4);
        @endphp
        <nav class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm" aria-label="Phân trang công nợ">
            <p class="text-slate-600">Trang {{ $debtPage }} / {{ $debtLast }} · {{ number_format($debts->total(), 0, ',', '.') }} công nợ</p>
            <div class="flex flex-wrap items-center gap-1">
                <button type="button" wire:click="gotoPage(1)" @disabled($debtPage === 1) class="min-h-9 rounded-lg border border-slate-300 px-3 disabled:opacity-40">Đầu</button>
                <button type="button" wire:click="previousPage" @disabled($debtPage === 1) class="min-h-9 rounded-lg border border-slate-300 px-3 disabled:opacity-40" aria-label="Trang trước">‹</button>
                @if($debtStart > 1)
                    <button type="button" wire:click="gotoPage(1)" class="min-h-9 rounded-lg border border-slate-300 px-3">1</button>
                    @if($debtStart > 2)<span class="px-1 text-slate-500">…</span>@endif
                @endif
                @for($n = $debtStart; $n <= $debtEnd; $n++)
                    <button type="button" wire:click="gotoPage({{ $n }})" @if($n === $debtPage) aria-current="page" @endif class="min-h-9 min-w-9 rounded-lg border px-2 {{ $n === $debtPage ? 'border-indigo-700 bg-indigo-700 font-semibold text-white' : 'border-slate-300 text-slate-700 hover:bg-slate-50' }}">{{ $n }}</button>
                @endfor
                @if($debtEnd < $debtLast)
                    @if($debtEnd < $debtLast - 1)<span class="px-1 text-slate-500">…</span>@endif
                    <button type="button" wire:click="gotoPage({{ $debtLast }})" class="min-h-9 rounded-lg border border-slate-300 px-3">{{ $debtLast }}</button>
                @endif
                <button type="button" wire:click="nextPage" @disabled($debtPage === $debtLast) class="min-h-9 rounded-lg border border-slate-300 px-3 disabled:opacity-40" aria-label="Trang sau">›</button>
                <button type="button" wire:click="gotoPage({{ $debtLast }})" @disabled($debtPage === $debtLast) class="min-h-9 rounded-lg border border-slate-300 px-3 disabled:opacity-40">Cuối</button>
            </div>
        </nav>
    @endif
        </section>
    @endif
    @if ($paymentReview)
        <div class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/50 p-3 sm:p-6" role="presentation" wire:key="finance-payment-review-modal">
            <section role="dialog" aria-modal="true" aria-label="Kiểm tra thanh toán công nợ" class="max-h-[92vh] w-full max-w-2xl space-y-4 overflow-y-auto rounded-2xl bg-white p-5 shadow-2xl sm:p-6">
                <div class="flex items-start justify-between gap-3 border-b border-slate-200 pb-3">
                    <div><p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Finance / Thanh toán</p><h3 class="mt-1 text-lg font-semibold text-slate-900">Kiểm tra trước khi thanh toán</h3></div>
                    <button type="button" wire:click="closePaymentReview" class="min-h-9 whitespace-nowrap rounded-lg border border-slate-300 px-3 text-sm text-slate-700 hover:bg-slate-50">Đóng ✕</button>
                </div>
                <dl class="grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-slate-500">Chứng từ</dt><dd class="mt-1 font-semibold">{{ $paymentReview['reference'] }}</dd></div>
                    <div><dt class="text-slate-500">Loại</dt><dd class="mt-1 font-semibold">{{ $paymentReview['direction'] === 'receivable' ? 'Phải thu · Phiếu thu' : 'Phải trả · Phiếu chi' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-slate-500">Đối tác</dt><dd class="mt-1 font-semibold">{{ $paymentReview['counterparty'] }}</dd></div>
                    <div><dt class="text-slate-500">Giá trị sau điều chỉnh</dt><dd class="mt-1 font-semibold tabular-nums">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd($paymentReview['principal']) }}</dd></div>
                    <div><dt class="text-slate-500">Còn phải thanh toán</dt><dd class="mt-1 font-semibold tabular-nums">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd($paymentReview['remaining']) }}</dd></div>
                    <div><dt class="text-slate-500">Chứng từ nguồn Finance</dt><dd class="mt-1 font-medium">{{ $paymentReview['source_linked'] ? 'Đã tìm thấy liên kết (chưa xác minh thanh toán)' : 'Chưa có liên kết' }}</dd></div>
                </dl>
                <div class="rounded-xl border {{ $paymentReview['eligible'] ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-amber-200 bg-amber-50 text-amber-950' }} p-4 text-sm" role="status">
                    <p class="font-semibold">{{ $paymentReview['eligible'] ? 'Có thể xác nhận thanh toán' : 'Chưa thể xác nhận đã thanh toán' }}</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($paymentReview['reasons'] as $reason)<li>{{ $reason }}</li>@endforeach
                    </ul>
                </div>
                <div class="space-y-3 rounded-xl border border-slate-200 p-4">
                    <div><h4 class="text-sm font-semibold text-slate-900">Chuẩn bị chứng từ thanh toán</h4><p class="mt-1 text-xs text-slate-500">Kiểm tra thông tin trước khi ghi sổ. Chỉ ghi nhận khi bạn bấm Xác nhận thanh toán.</p></div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="block text-sm"><span class="mb-1 block font-medium text-slate-700">Số tiền dự kiến ({{ $paymentReview['currency'] }})</span><input type="text" inputmode="numeric" wire:model.live.debounce.300ms="paymentAmount" x-on:input="$el.value = $el.value.replace(/[^0-9]/g, '').slice(0, 15).replace(/\B(?=(\d{3})+(?!\d))/g, '.')" placeholder="Ví dụ: 540.000.000" class="min-h-10 w-full rounded-lg border border-slate-300 px-3"><span class="mt-1 block text-xs text-amber-700">Điền sẵn theo số dư chưa phân bổ; hệ thống kiểm tra lại khi ghi sổ.</span></label>
                        <label class="block text-sm"><span class="mb-1 block font-medium text-slate-700">Ngày thanh toán</span><input type="date" wire:model.live="paymentDate" class="min-h-10 w-full rounded-lg border border-slate-300 px-3"></label>
                        <label class="block text-sm"><span class="mb-1 block font-medium text-slate-700">Tài khoản thu/chi</span><select wire:model.live="paymentAccountId" class="min-h-10 w-full rounded-lg border border-slate-300 px-3"><option value="">Chọn tài khoản</option>@foreach($paymentReview['accounts'] as $account)<option value="{{ $account['id'] }}">{{ $account['code'] }} · {{ $account['name'] }}</option>@endforeach</select></label>
                        <label class="block text-sm"><span class="mb-1 block font-medium text-slate-700">Số chứng từ / tham chiếu</span><input type="text" wire:model.live.debounce.400ms="paymentReference" maxlength="100" placeholder="Mã giao dịch ngân hàng..." class="min-h-10 w-full rounded-lg border border-slate-300 px-3"></label>
                    </div>
                    @foreach (['paymentAmount', 'paymentDate', 'paymentAccountId', 'paymentReference', 'payment'] as $field)
                        @error($field)<p class="text-xs text-rose-700">{{ $message }}</p>@enderror
                    @endforeach
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" wire:click="previewPayment" class="min-h-10 rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">Kiểm tra thông tin</button>
                        @if($paymentPreviewed)<span class="text-sm font-medium text-emerald-700">Thông tin nhập hợp lệ. Sẵn sàng kiểm tra điều kiện ghi sổ.</span>@endif
                    </div>
                </div>
                <section class="space-y-2 rounded-xl border border-slate-200 bg-white p-4" aria-label="Đối chiếu lịch sử thanh toán">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h4 class="text-sm font-semibold text-slate-900">Đối chiếu lịch sử thanh toán</h4>
                        <span class="rounded-full bg-amber-50 px-2 py-1 text-xs font-medium text-amber-800">{{ $paymentEvidence['payment_status'] }}</span>
                    </div>
                    <p class="text-xs text-amber-800">{{ $paymentEvidence['warning'] }}</p>
                    @if ($paymentEvidence['rows'] !== [])
                        <div class="overflow-x-auto rounded-lg border border-slate-200">
                            <table class="w-full min-w-[520px] text-left text-xs">
                                <thead class="bg-slate-50 text-slate-600"><tr><th class="px-3 py-2">Phiếu</th><th class="px-3 py-2">Ngày</th><th class="px-3 py-2">Loại ánh xạ</th><th class="px-3 py-2 text-right">Số tiền</th><th class="px-3 py-2">Trạng thái phiếu</th></tr></thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($paymentEvidence['rows'] as $row)
                                        <tr><td class="px-3 py-2">{{ $row['voucher_number'] ?: 'Không tìm thấy' }}</td><td class="px-3 py-2">{{ $row['voucher_date'] }}</td><td class="px-3 py-2">{{ $row['operation'] === 'reverse' ? 'Hoàn tác' : 'Ghi nhận' }}</td><td class="px-3 py-2 text-right tabular-nums">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd($row['amount']) }}</td><td class="px-3 py-2">{{ $row['voucher_status'] }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">Chưa có ánh xạ thanh toán nào để đối chiếu.</p>
                    @endif
                </section>
                <p class="text-xs text-slate-500">Phiếu thanh toán chỉ được tạo khi xác nhận; mỗi giao dịch có chứng từ và lịch sử kiểm toán.</p>
                <div class="flex justify-end gap-2 border-t border-slate-200 pt-3">
                    <button type="button" wire:click="closePaymentReview" class="min-h-10 rounded-lg border border-slate-300 px-4 text-sm font-medium hover:bg-slate-50">Quay lại</button>
                    @if($paymentReview['eligible'] && $canManage)
                        <button type="button" wire:click="confirmPayment" wire:loading.attr="disabled" wire:target="confirmPayment" @disabled(! $paymentPreviewed) class="min-h-10 rounded-lg bg-indigo-700 px-4 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-40">Xác nhận thanh toán</button>
                    @else
                        <button type="button" disabled class="min-h-10 cursor-not-allowed rounded-lg bg-slate-200 px-4 text-sm font-semibold text-slate-500" aria-label="Xác nhận thanh toán chưa khả dụng">Xác nhận thanh toán</button>
                    @endif
                </div>
            </section>
        </div>
    @endif
    @if ($selected)
        <div class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-950/50 p-3 sm:p-6" role="presentation" wire:key="finance-detail-modal">
            <section role="dialog" aria-modal="true" aria-label="Chi tiết công nợ" class="max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-2xl bg-white p-5 shadow-2xl sm:p-6 space-y-4">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 pb-3"><p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Finance / Công nợ</p><button type="button" wire:click="closeDebt" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" aria-label="Đóng chi tiết công nợ">Đóng ✕</button></div>
            <h3 class="font-semibold">Chi tiết: {{ $selected->reference_number }}</h3>
            <p class="text-sm">Đối tác: {{ $selected->counterparty_name }} · Trạng thái: {{ $selected->status }} · Số tiền gốc: {{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $selected->original_amount) }}</p>
            <div class="grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 sm:grid-cols-3" aria-label="Tổng hợp công nợ">
                <div><p class="text-xs text-slate-600">Công nợ gốc</p><p class="mt-1 font-semibold tabular-nums text-slate-900">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $selected->original_amount) }}</p></div>
                <div><p class="text-xs text-slate-600">Tổng điều chỉnh</p><p class="mt-1 font-semibold tabular-nums text-slate-900">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd($adjustmentTotal) }}</p></div>
                <div><p class="text-xs text-slate-600">Số dư sau điều chỉnh</p><p class="mt-1 font-semibold tabular-nums text-indigo-800">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd($currentBalance) }}</p></div>
            </div>
            @if($selectedPaymentEvidence && $selectedPaymentEvidence['verified_paid'] !== null)
            <div class="grid gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3 sm:grid-cols-3" aria-label="Tình trạng thanh toán">
                <div><p class="text-xs text-slate-600">Đã thanh toán</p><p class="mt-1 font-semibold tabular-nums">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd($selectedPaymentEvidence['verified_paid']) }}</p></div>
                <div><p class="text-xs text-slate-600">Còn phải thanh toán</p><p class="mt-1 font-semibold tabular-nums">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd($selectedPaymentEvidence['verified_outstanding']) }}</p></div>
                <div><p class="text-xs text-slate-600">Trạng thái</p><p class="mt-1 font-semibold">{{ $selectedPaymentEvidence['payment_status'] }}</p></div>
            </div>
            @else
            <p class="text-xs text-amber-700">Số dư sau điều chỉnh chưa trừ thanh toán chưa xác minh.</p>
            @endif
            @if($selectedPaymentEvidence && $selectedPaymentEvidence['rows'] !== [])
            <section class="space-y-2" aria-label="Lịch sử thanh toán công nợ">
                <h4 class="text-sm font-semibold">Lịch sử phiếu thanh toán</h4>
                @if($canManage)
                <label class="block text-xs font-medium">Lý do hoàn tác (ít nhất 10 ký tự)
                    <textarea wire:model="reversalReason" maxlength="500" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 p-2 text-sm" placeholder="Nhập lý do trước khi hoàn tác một phiếu đã ghi sổ"></textarea>
                </label>
                @error('reversalReason') <p class="text-xs text-red-700">{{ $message }}</p> @enderror
                @error('reversal') <p class="text-xs text-red-700">{{ $message }}</p> @enderror
                @endif
                <div class="overflow-x-auto rounded-lg border border-slate-200">
                    <table class="w-full min-w-[500px] text-left text-xs">
                        <thead class="bg-slate-50"><tr><th class="p-2">Số phiếu</th><th class="p-2">Ngày</th><th class="p-2">Nghiệp vụ</th><th class="p-2 text-right">Số tiền</th><th class="p-2">Trạng thái</th><th class="p-2">Thao tác</th></tr></thead>
                        <tbody>@foreach($selectedPaymentEvidence['rows'] as $row)
                        <tr class="border-t border-slate-100"><td class="p-2">{{ $row['voucher_number'] }}</td><td class="p-2">{{ $row['voucher_date'] }}</td><td class="p-2">{{ $row['operation'] === 'post' ? 'Thanh toán' : 'Hoàn tác' }}</td><td class="p-2 text-right">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd($row['amount']) }}</td><td class="p-2">{{ $row['voucher_status'] }}@if($row['operation'] === 'reverse')<div class="mt-1 text-xs text-slate-600">Lý do: {{ $row['audit_reason'] }}</div><div class="text-xs text-slate-500">Người thực hiện #{{ $row['audit_actor'] }} · {{ $row['audit_time'] }}</div>@endif</td><td class="p-2">
                            @if($canManage && $row['operation'] === 'post' && $row['voucher_status'] === 'posted')
                            <button type="button" wire:click="reversePayment({{ $row['voucher_id'] }})" wire:confirm="Hoàn tác phiếu {{ $row['voucher_number'] }}? Giao dịch sẽ ghi bút toán đảo và khôi phục công nợ. Hãy nhập lý do trước." wire:loading.attr="disabled" class="rounded-lg border border-red-300 px-2 py-1 font-semibold text-red-700">Hoàn tác</button>
                            @else <span class="text-slate-400">—</span> @endif
                        </td></tr>
                        @endforeach</tbody>
                    </table>
                </div>
            </section>
            @endif
            @if ($canManage && $selected->status === 'draft' && $selected->source_module === null && $selected->source_type === null && $selected->source_document_id === null)
                <button wire:click="confirm" wire:confirm="Xác nhận công nợ thủ công này? Không thể xác nhận lại." wire:loading.attr="disabled" class="rounded-xl bg-indigo-700 px-4 py-2 text-sm font-semibold text-white">Xác nhận công nợ</button>
            @endif
            <h4 class="font-medium">Lịch sử điều chỉnh</h4>
            <ul class="space-y-2 text-sm">@forelse ($events as $event)<li class="rounded-lg bg-slate-50 p-2">{{ $event->effective_on }} · {{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $event->signed_amount) }} · {{ $event->reason }}</li>@empty<li class="text-slate-500">Chưa có điều chỉnh.</li>@endforelse</ul>
            @if ($canManage && $selected->status === 'confirmed' && $selected->source_module === null && $selected->source_type === null && $selected->source_document_id === null)
                <form wire:submit="adjust" class="grid gap-3 border-t border-slate-200 pt-3 sm:grid-cols-3">
                    <label class="text-sm">Điều chỉnh (+/-)<input x-data="{ display: '' }" x-bind:value="display" x-on:input="let raw = $event.target.value; let sign = raw.trim().startsWith('-') ? '-' : ''; let digits = raw.replace(/[^0-9]/g, ''); display = sign + digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.'); $event.target.value = display; $wire.set('adjustment', sign + digits)" inputmode="numeric" placeholder="-500.000" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3" required></label>
                    <label class="text-sm">Ngày hiệu lực<input wire:model="adjustmentDate" type="date" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3" required></label>
                    <label class="text-sm">Lý do<input wire:model="adjustmentReason" maxlength="500" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3" required></label>
                    @if ($errors->any()) <p role="alert" class="text-sm text-red-700 sm:col-span-3">{{ $errors->first() }}</p> @endif
                    <button wire:loading.attr="disabled" wire:target="adjust" type="submit" class="rounded-xl bg-indigo-700 px-4 py-2 text-sm font-semibold text-white">Ghi nhận điều chỉnh</button>
                </form>
            @endif
            </section>
        </div>
    @endif
</div>
