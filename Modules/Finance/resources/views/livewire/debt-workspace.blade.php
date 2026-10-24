<div class="space-y-4">
    @if (session()->has('debt_notice')) <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">{{ session('debt_notice') }}</div> @endif
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div><h2 class="text-lg font-semibold text-slate-900">Danh sách công nợ</h2><p class="text-sm text-slate-600">Chỉ ghi nhận thủ công; chưa liên kết thanh toán hoặc hóa đơn.</p></div>
        @if ($canManage) <button type="button" wire:click="openCreate" class="rounded-xl bg-indigo-700 px-4 py-2 text-sm font-semibold text-white">+ Tạo công nợ</button> @endif
    </div>
    <div class="grid gap-3 sm:grid-cols-3">
        <label class="block text-sm font-medium">Tìm chứng từ / đối tác<input aria-label="Tìm công nợ" wire:model.live.debounce.350ms="search" type="search" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm"></label>
        <label class="block text-sm font-medium">Loại công nợ<select wire:model.live="direction" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm"><option value="">Tất cả</option><option value="receivable">Phải thu</option><option value="payable">Phải trả</option></select></label>
        <label class="block text-sm font-medium">Trạng thái<select wire:model.live="status" class="mt-1 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm"><option value="">Tất cả</option><option value="draft">Nháp</option><option value="confirmed">Đã xác nhận</option></select></label>
    </div>
    @if ($creating && $canManage)
        <form wire:submit="create" class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
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
    @endif
    <div class="overflow-x-auto rounded-xl border border-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50"><tr><th class="p-3">Chứng từ</th><th class="p-3">Đối tác</th><th class="p-3">Loại</th><th class="p-3">Số tiền gốc</th><th class="p-3">Hạn</th><th class="p-3">Trạng thái</th><th class="p-3">Chi tiết</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($debts as $debt)
                    <tr><td class="p-3">{{ $debt->reference_number }}</td><td class="p-3">{{ $debt->counterparty_name }}</td><td class="p-3">{{ $debt->direction === 'receivable' ? 'Phải thu' : 'Phải trả' }}</td><td class="p-3 whitespace-nowrap">{{ number_format((float) $debt->original_amount, 0, ',', '.') }} ₫</td><td class="p-3">{{ $debt->due_on ?: '—' }}</td><td class="p-3">{{ $debt->status === 'draft' ? 'Nháp' : ($debt->status === 'confirmed' ? 'Đã xác nhận' : $debt->status) }}</td><td class="p-3"><button wire:click="selectDebt({{ $debt->id }})" class="font-semibold text-indigo-700 underline">Xem</button></td></tr>
                @empty <tr><td colspan="7" class="p-6 text-center text-slate-500">Chưa có công nợ phù hợp.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $debts->links() }}
    @if ($selected)
        <section class="space-y-3 rounded-xl border border-slate-200 p-4" aria-label="Chi tiết công nợ">
            <h3 class="font-semibold">Chi tiết: {{ $selected->reference_number }}</h3>
            <p class="text-sm">Đối tác: {{ $selected->counterparty_name }} · Trạng thái: {{ $selected->status }} · Số tiền gốc: {{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $selected->original_amount) }}</p>
            <div class="grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 sm:grid-cols-3" aria-label="Tổng hợp công nợ">
                <div><p class="text-xs text-slate-600">Công nợ gốc</p><p class="mt-1 font-semibold tabular-nums text-slate-900">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd((string) $selected->original_amount) }}</p></div>
                <div><p class="text-xs text-slate-600">Tổng điều chỉnh</p><p class="mt-1 font-semibold tabular-nums text-slate-900">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd($adjustmentTotal) }}</p></div>
                <div><p class="text-xs text-slate-600">Số dư sau điều chỉnh</p><p class="mt-1 font-semibold tabular-nums text-indigo-800">{{ \Modules\Finance\Livewire\DebtWorkspace::formatVnd($currentBalance) }}</p></div>
            </div>
            <p class="text-xs text-slate-500">Số dư công nợ thủ công, chưa trừ thanh toán hoặc đối trừ.</p>
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
    @endif
</div>
