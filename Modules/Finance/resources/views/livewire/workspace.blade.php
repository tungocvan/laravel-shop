<div class="space-y-5">
    @if(session()->has('finance_success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">{{ session('finance_success') }}</div>
    @endif
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-900">{{ $errors->first() }}</div>
    @endif

    <nav aria-label="Không gian Finance" class="flex flex-wrap gap-2 border-b border-slate-200 pb-3">
        <button type="button" wire:click="$set('workspaceTab', 'compose')" class="min-h-11 rounded-xl px-4 text-sm font-semibold {{ $workspaceTab === 'compose' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700' }}">Lập phiếu</button>
        <button type="button" wire:click="$set('workspaceTab', 'ledger')" class="min-h-11 rounded-xl px-4 text-sm font-semibold {{ $workspaceTab === 'ledger' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700' }}">Sổ giao dịch</button>
    </nav>
    @if($workspaceTab === 'compose')
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
                <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Thông tin chứng từ nguồn</p>
                            <p class="mt-1 text-base font-bold text-slate-900">{{ $balance['document_code'] }}</p>
                            <p class="mt-1 text-sm text-slate-700">{{ $balance['direction'] === 'payable' ? 'Đơn vị / cá nhân nhận thanh toán' : 'Đơn vị / cá nhân thanh toán' }}</p>
                            <p class="font-semibold text-slate-900">{{ $balance['counterparty'] ?: 'Chưa có thông tin đối tượng trên chứng từ' }}</p>
                        </div>
                        <span class="rounded-lg bg-white px-3 py-1 text-xs font-semibold text-indigo-700">{{ $balance['direction'] === 'payable' ? 'PHIẾU CHI' : 'PHIẾU THU' }}</span>
                    </div>
                </div>
                <div class="grid gap-3 rounded-xl bg-slate-50 p-4 sm:grid-cols-3">
                    <div><p class="text-xs text-slate-500">Tổng giá trị</p><p class="font-bold">{{ number_format((float) $balance['total'], 0, ',', '.') }} ₫</p></div>
                    <div><p class="text-xs text-slate-500">Đã thu/chi</p><p class="font-bold">{{ number_format((float) $balance['paid'], 0, ',', '.') }} ₫</p></div>
                    <div><p class="text-xs text-slate-500">Công nợ còn lại</p><p class="font-bold text-indigo-700">{{ number_format((float) $balance['remaining'], 0, ',', '.') }} ₫</p></div>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
                    Phiếu nháp đang giữ: <strong>{{ number_format((float) $balance['reserved'], 0, ',', '.') }} ₫</strong>
                    · Có thể lập thêm: <strong>{{ number_format((float) $balance['available'], 0, ',', '.') }} ₫</strong>
                </div>
                @if($balance['available'] === '0.00')
                    <p role="status" class="rounded-lg bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800">Chứng từ đã thanh toán đủ hoặc số dư đã được giữ bởi phiếu nháp. Không thể lập thêm phiếu.</p>
                @endif
            @endif
        </section>

        <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4">
            <h2 class="text-lg font-bold text-slate-900">2. Lập phiếu nháp</h2>
            <div class="rounded-xl bg-slate-50 p-3 text-sm text-slate-700">
                <p class="text-xs font-medium text-slate-500">Ngày lập phiếu dự kiến</p>
                <p class="font-semibold">{{ now()->format('d/m/Y') }}</p>
                <p class="mt-1 text-xs text-slate-500">Ngày ghi nhận hiện theo ngày hệ thống; tùy chỉnh ngày hạch toán sẽ bổ sung ở batch 5E.</p>
            </div>
            @can('finance.voucher.create')
                <form wire:submit="createDraft" class="space-y-3">
                    <label class="block text-sm font-medium text-slate-700">Tài khoản thu/chi
                        <select wire:model.live="accountId" required class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 p-2">
                            <option value="">Chọn tài khoản</option>
                            @foreach($accounts->where('is_active', true) as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    @if($selectedAccount)
                        <div class="rounded-xl border border-slate-200 p-3 text-sm">
                            <p class="font-semibold text-slate-800">{{ $selectedAccount->kind === 'cash' ? 'Thanh toán tiền mặt' : 'Thanh toán qua ngân hàng' }}</p>
                            <p class="mt-1 text-slate-600">{{ $selectedAccount->name }} · {{ $selectedAccount->code }}</p>
                            @if($selectedAccount->kind === 'cash')
                                <p class="mt-1 text-xs text-slate-500">Thủ quỹ: {{ $selectedAccount->cash_custodian ?: "Chưa cập nhật" }} · Địa điểm: {{ $selectedAccount->cash_location ?: "Chưa cập nhật" }}</p>
                            @else
                                <p class="mt-1 text-xs text-slate-500">Ngân hàng: {{ $selectedAccount->bank_name ?: "Chưa cập nhật" }} · STK: {{ $selectedAccount->bank_account_number ?: "Chưa cập nhật" }} · Chủ TK: {{ $selectedAccount->bank_account_holder ?: "Chưa cập nhật" }}</p>
                            @endif
                        </div>
                    @endif
                    <label class="block text-sm font-medium text-slate-700">Số tiền thanh toán (VND)
                        <input wire:model.live.debounce.300ms="amountDisplay" inputmode="numeric" required aria-describedby="finance-amount-help" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 p-2 text-right font-semibold tabular-nums">
                        <span id="finance-amount-help" class="mt-1 block text-xs font-normal text-slate-500">Nhập số tiền VND, tự động phân cách hàng nghìn. Cho phép thanh toán một phần.</span>
                    </label>
                    @if($editingVoucherId)
                        <p class="rounded-lg bg-indigo-50 p-2 text-sm font-semibold text-indigo-800">Đang sửa phiếu nháp #{{ $editingVoucherId }}</p>
                        <button type="button" wire:click="saveDraft" class="min-h-11 w-full rounded-xl bg-indigo-600 px-4 py-2 font-semibold text-white">Lưu thay đổi phiếu nháp</button>
                    @endif
                    <button type="submit" @disabled($editingVoucherId !== null) wire:loading.attr="disabled" @disabled(!$balance || ($balance['available'] === '0.00' && !$editingVoucherId)) class="min-h-11 w-full rounded-xl bg-indigo-600 px-4 py-2 font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">Tạo phiếu nháp</button>
                </form>
            @endcan
            @can('finance.account.manage')
                <div class="border-t border-slate-200 pt-4">
                    <button type="button" wire:click="$toggle('showAccountForm')" class="min-h-11 w-full rounded-lg border border-indigo-200 px-3 text-left text-sm font-semibold text-indigo-700">+ Tạo tài khoản thu/chi {{ $showAccountForm ? '▴' : '▾' }}</button>
                    @if($showAccountForm)
                        <form wire:submit="createAccount" class="mt-3 space-y-3 rounded-xl bg-slate-50 p-3">
                            <p class="text-sm font-semibold text-slate-900">Hồ sơ tài khoản thu/chi</p>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" wire:click="$set('accountKind', 'bank')" class="min-h-11 rounded-lg border px-2 text-sm font-semibold {{ $accountKind === 'bank' ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-300 bg-white text-slate-700' }}">Ngân hàng</button>
                                <button type="button" wire:click="$set('accountKind', 'cash')" class="min-h-11 rounded-lg border px-2 text-sm font-semibold {{ $accountKind === 'cash' ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-300 bg-white text-slate-700' }}">Tiền mặt</button>
                            </div>
                            <label class="block text-xs font-semibold text-slate-600">Mã tài khoản
                                <input wire:model="accountCode" required maxlength="40" placeholder="VD: NH-TCB" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white p-2">
                            </label>
                            <label class="block text-xs font-semibold text-slate-600">Tên tài khoản
                                <input wire:model="accountName" required maxlength="255" placeholder="Tên tài khoản nội bộ" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white p-2">
                            </label>
                            @if($accountKind === 'bank')
                                <label class="block text-xs font-semibold text-slate-600">Ngân hàng
                                    <input wire:model="bankName" required maxlength="255" placeholder="VD: Techcombank" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white p-2">
                                </label>
                                <label class="block text-xs font-semibold text-slate-600">Số tài khoản ngân hàng
                                    <input wire:model="bankAccountNumber" required maxlength="100" placeholder="Số tài khoản" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white p-2">
                                </label>
                                <label class="block text-xs font-semibold text-slate-600">Chủ tài khoản
                                    <input wire:model="bankAccountHolder" required maxlength="255" placeholder="Tên cá nhân / tổ chức" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white p-2">
                                </label>
                            @else
                                <label class="block text-xs font-semibold text-slate-600">Người phụ trách quỹ
                                    <input wire:model="cashCustodian" required maxlength="255" placeholder="Họ tên thủ quỹ" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white p-2">
                                </label>
                                <label class="block text-xs font-semibold text-slate-600">Địa điểm quản lý quỹ
                                    <input wire:model="cashLocation" required maxlength="255" placeholder="VD: Văn phòng chính" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white p-2">
                                </label>
                            @endif
                            <button type="submit" wire:loading.attr="disabled" class="min-h-11 w-full rounded-xl bg-indigo-600 px-4 font-semibold text-white disabled:opacity-50">Lưu tài khoản</button>
                        </form>
                    @endif
                </div>
            @endcan
        </section>
    </div>

    @endif
    <section class="rounded-2xl border border-slate-200 bg-white p-4">
        <h2 class="mb-3 text-lg font-bold text-slate-900">{{ $workspaceTab === 'ledger' ? 'Sổ giao dịch gần đây' : '3. Phiếu thu/chi gần đây' }}</h2>
        <label class="mb-3 flex min-h-11 items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" wire:model.live="showHistory" class="h-4 w-4 rounded border-slate-300"> Hiển thị cả phiếu đã hoàn tác / hủy nháp
        </label>
        <div class="space-y-3">
            @forelse($vouchers as $voucher)
                <div wire:key="voucher-{{ $voucher->id }}" class="flex flex-col gap-3 rounded-xl border border-slate-200 p-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm">
                        <p class="font-bold text-slate-900">{{ $voucher->number }}</p>
                        <p class="mt-1 text-sm text-indigo-700">Chứng từ nguồn: <strong>{{ $voucher->source_document_code ?: 'Chưa xác định' }}</strong></p>
                        <p class="text-xs text-slate-500">{{ $voucher->counterparty ?: 'Chưa có đối tượng thanh toán' }}</p>
                        <p class="text-slate-600">{{ $voucher->direction === 'receipt' ? 'Thu' : 'Chi' }} · {{ number_format((float) $voucher->amount, 0, ',', '.') }} ₫ · {{ $voucher->status === 'draft' ? 'Nháp' : ($voucher->status === 'posted' ? 'Đã ghi sổ' : ($voucher->status === 'cancelled' ? 'Đã hủy nháp' : 'Đã hoàn tác')) }} · {{ \Illuminate\Support\Carbon::parse($voucher->voucher_date)->format('d/m/Y') }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if($voucher->status === 'draft')
                            @can('finance.voucher.create')
                                <button type="button" wire:click="editDraft({{ $voucher->id }})" class="min-h-11 rounded-lg border border-indigo-300 px-3 py-2 text-sm font-semibold text-indigo-700">Sửa nháp</button>
                                <button type="button" wire:click="cancelDraft({{ $voucher->id }})" wire:confirm="Hủy phiếu nháp này? Hạn mức sẽ được giải phóng." class="min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700">Hủy nháp</button>
                            @endcan
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
