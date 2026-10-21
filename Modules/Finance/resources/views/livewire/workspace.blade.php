<div class="space-y-6">
    @if(session()->has('finance_success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">{{ session('finance_success') }}</div>
    @endif
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-900">{{ $errors->first() }}</div>
    @endif

    <div class="grid gap-5 xl:grid-cols-3">
        <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 xl:col-span-2">
            <h2 class="text-lg font-bold text-slate-900">1. Chọn chứng từ đã ghi sổ</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="text-sm font-medium text-slate-700">Loại chứng từ
                    <select wire:model.live="kind" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 p-2">
                        <option value="inventory_receipt">Phiếu nhập → Chi</option>
                        <option value="inventory_issue">Phiếu xuất → Thu</option>
                    </select>
                </label>
                <label class="text-sm font-medium text-slate-700">Tìm theo mã phiếu
                    <input wire:model.live.debounce.400ms="search" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 p-2" placeholder="Nhập mã chứng từ">
                </label>
            </div>
            <div class="max-h-64 space-y-2 overflow-y-auto">
                @forelse($documents as $document)
                    <button type="button" wire:key="source-{{ $kind }}-{{ $document->id }}" wire:click="selectDocument('{{ $document->id }}')" class="flex min-h-11 w-full items-center justify-between rounded-xl border px-3 py-2 text-left text-sm {{ $documentId === (string) $document->id ? 'border-indigo-500 bg-indigo-50' : 'border-slate-200 hover:bg-slate-50' }}">
                        <span class="font-semibold text-slate-800">{{ $document->number }}</span>
                        <span class="text-indigo-700">Chọn →</span>
                    </button>
                @empty
                    <p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-600">Không có chứng từ phù hợp hoặc Pharma chưa bật.</p>
                @endforelse
            </div>
            @if($balance)
                <div class="grid gap-3 rounded-xl bg-slate-50 p-4 sm:grid-cols-3">
                    <div><p class="text-xs text-slate-500">Tổng giá trị</p><p class="font-bold">{{ $balance['total'] }} VND</p></div>
                    <div><p class="text-xs text-slate-500">Đã thu/chi</p><p class="font-bold">{{ $balance['paid'] }} VND</p></div>
                    <div><p class="text-xs text-slate-500">Còn lại</p><p class="font-bold text-indigo-700">{{ $balance['remaining'] }} VND</p></div>
                </div>
            @endif
        </section>

        <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4">
            <h2 class="text-lg font-bold text-slate-900">2. Lập phiếu nháp</h2>
            @can('finance.voucher.create')
                <form wire:submit="createDraft" class="space-y-3">
                    <label class="block text-sm font-medium text-slate-700">Tài khoản thu/chi
                        <select wire:model="accountId" required class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 p-2">
                            <option value="">Chọn tài khoản</option>
                            @foreach($accounts->where('is_active', true) as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-sm font-medium text-slate-700">Số tiền thanh toán (VND)
                        <input wire:model="amount" inputmode="decimal" required class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 p-2">
                    </label>
                    <button type="submit" wire:loading.attr="disabled" class="min-h-11 w-full rounded-xl bg-indigo-600 px-4 py-2 font-semibold text-white disabled:opacity-50">Tạo phiếu nháp</button>
                </form>
            @endcan
            @can('finance.account.manage')
                <details class="border-t border-slate-200 pt-4">
                    <summary class="cursor-pointer text-sm font-semibold text-indigo-700">+ Tạo tài khoản tiền mặt / ngân hàng</summary>
                    <form wire:submit="createAccount" class="mt-3 space-y-3">
                        <input wire:model="accountCode" required maxlength="40" placeholder="Mã tài khoản" class="min-h-11 w-full rounded-lg border border-slate-300 p-2">
                        <input wire:model="accountName" required maxlength="255" placeholder="Tên tài khoản" class="min-h-11 w-full rounded-lg border border-slate-300 p-2">
                        <select wire:model="accountKind" class="min-h-11 w-full rounded-lg border border-slate-300 p-2"><option value="cash">Tiền mặt</option><option value="bank">Ngân hàng</option></select>
                        <button type="submit" class="min-h-11 w-full rounded-xl border border-indigo-500 px-4 py-2 font-semibold text-indigo-700">Lưu tài khoản</button>
                    </form>
                </details>
            @endcan
        </section>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-4">
        <h2 class="mb-3 text-lg font-bold text-slate-900">3. Phiếu thu/chi gần đây</h2>
        <div class="space-y-3">
            @forelse($vouchers as $voucher)
                <div wire:key="voucher-{{ $voucher->id }}" class="flex flex-col gap-3 rounded-xl border border-slate-200 p-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm">
                        <p class="font-bold text-slate-900">{{ $voucher->number }}</p>
                        <p class="text-slate-600">{{ $voucher->direction === 'receipt' ? 'Thu' : 'Chi' }} · {{ $voucher->amount }} {{ $voucher->currency }} · {{ $voucher->status }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if($voucher->status === 'draft')
                            @can('finance.voucher.post')
                                <button type="button" wire:click="post({{ $voucher->id }})" wire:confirm="Xác nhận ghi sổ phiếu này?" class="min-h-11 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white">Ghi sổ</button>
                            @endcan
                        @elseif($voucher->status === 'posted')
                            @can('finance.voucher.reverse')
                                <button type="button" wire:click="reverse({{ $voucher->id }})" wire:confirm="Xác nhận hoàn tác phiếu này?" class="min-h-11 rounded-lg border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-700">Hoàn tác</button>
                            @endcan
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">Chưa có phiếu thu/chi.</p>
            @endforelse
        </div>
    </section>
</div>
