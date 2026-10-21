<div class="space-y-5">
    @if(session()->has('finance_success'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">{{ session('finance_success') }}</div>
    @endif
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-900">{{ $errors->first() }}</div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-3 sm:p-4">
        <nav aria-label="Không gian Finance" class="flex flex-wrap gap-2">
            <button type="button" wire:click="$set('workspaceTab', 'compose')" class="min-h-10 rounded-lg px-4 text-sm font-semibold {{ $workspaceTab === 'compose' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700' }}">Lập phiếu</button>
            <button type="button" wire:click="$set('workspaceTab', 'ledger')" class="min-h-10 rounded-lg px-4 text-sm font-semibold {{ $workspaceTab === 'ledger' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700' }}">Sổ chứng từ</button>
        </nav>
        @can('finance.account.manage')
            <a href="{{ route('admin.finance.print.settings') }}" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700">⚙ Mẫu PDF</a>
            <button type="button" wire:click="$toggle('showAccounts')" class="min-h-10 rounded-lg border border-slate-300 px-4 text-sm font-semibold text-slate-700">⚙ Tài khoản thu/chi</button>
        @endcan
    </div>
    @can('finance.account.manage')
    @if($showAccounts)
        <section class="space-y-3 rounded-xl border border-slate-200 bg-white p-4">
            <div class="flex items-center justify-between gap-3"><h2 class="font-bold text-slate-900">Tài khoản thu/chi</h2><button type="button" wire:click="$set('showAccounts', false)" class="min-h-10 rounded-lg px-3 text-sm text-slate-600">Đóng</button></div>
            <div class="grid gap-2 sm:grid-cols-2">
                @forelse($accounts->sortBy(fn ($account) => $account->kind === 'bank' ? 0 : 1) as $account)
                    <div class="rounded-lg border border-slate-200 p-3 text-sm">
                        <p class="font-semibold">{{ $account->name }} <span class="text-xs text-slate-500">({{ $account->code }})</span></p>
                        <p class="mt-1 text-xs text-slate-500">{{ $account->kind === 'bank' ? 'Ngân hàng' : 'Tiền mặt' }} · {{ $account->is_active ? 'Đang sử dụng' : 'Ngừng sử dụng' }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Chưa có tài khoản.</p>
                @endforelse
            </div>
            @can('finance.account.manage')
                <div class="border-t border-slate-200 pt-4">
                    <button type="button" wire:click="$set('showAccountForm', true)" class="min-h-11 w-full rounded-lg border border-indigo-200 px-3 text-left text-sm font-semibold text-indigo-700">+ Thêm tài khoản</button>
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
    @endif
    @endcan
    @if($workspaceTab === 'compose')
    @if($composeMode === 'home')
        <section class="space-y-5 rounded-2xl border border-slate-200 bg-white p-4 sm:p-6">
            <div><h2 class="text-lg font-bold text-slate-900">Lập chứng từ thu / chi</h2><p class="mt-1 text-sm text-slate-500">Chọn nghiệp vụ cần thực hiện để bắt đầu.</p></div>
            @can('finance.voucher.create')
                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="openManual('receipt')" class="min-h-11 rounded-lg border border-emerald-300 bg-emerald-50 px-4 text-sm font-semibold text-emerald-800">+ Lập phiếu thu</button>
                    <button type="button" wire:click="openManual('payment')" class="min-h-11 rounded-lg border border-rose-300 bg-rose-50 px-4 text-sm font-semibold text-rose-800">+ Lập phiếu chi</button>
                </div>
            @endcan
            <div class="border-t border-slate-200 pt-4">
                <h3 class="font-semibold text-slate-900">Chứng từ tự động</h3>
                <p class="mt-1 text-xs text-slate-500">Nguồn chứng từ được tổ chức theo module để có thể mở rộng sau này.</p>
                <button type="button" wire:click="openSource('Pharma')" class="mt-3 flex min-h-12 w-full items-center justify-between rounded-xl border border-slate-200 px-4 text-left text-sm font-semibold text-slate-800 hover:bg-slate-50">Dược phẩm · Pharma <span class="text-indigo-700">Chọn chứng từ →</span></button>
            </div>
        </section>
    @else
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <button type="button" wire:click="$set('composeMode', 'home')" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm font-semibold text-slate-700">← Quay lại nghiệp vụ</button>
            @if($composeMode === 'source') <span class="text-xs font-semibold text-slate-500">Chứng từ tự động / Pharma</span> @endif
        </div>
    @if($composeMode === 'source')
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
        </section>
    </div>

    @else
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <header class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 bg-slate-50 px-4 py-5 sm:px-6">
            <div class="space-y-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-indigo-700">Chứng từ kế toán · Thu / chi thủ công</p>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">{{ $manualDirection === 'receipt' ? 'PHIẾU THU' : 'PHIẾU CHI' }}</h2>
                <p class="text-sm text-slate-600">Lập chứng từ, kiểm tra thông tin và lưu nháp trước khi ghi sổ.</p>
            </div>
            <div class="text-right">
                <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-800">BẢN NHÁP</span>
                <p class="mt-2 text-xs text-slate-500">{{ $editingManualVoucherId ? 'Đang sửa phiếu #'.$editingManualVoucherId : 'Số phiếu cấp tự động khi lưu' }}</p>
            </div>
        </header>
        @can('finance.voucher.create')
        <form wire:submit="{{ $editingManualVoucherId ? 'saveManualDraft' : 'createManualDraft' }}" class="space-y-6 p-4 sm:p-6">
            <div class="flex items-center gap-2 rounded-xl border px-4 py-3 text-sm font-semibold {{ $manualDirection === 'receipt' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' }}">
                <span aria-hidden="true">{{ $manualDirection === 'receipt' ? '↙' : '↗' }}</span>
                {{ $manualDirection === 'receipt' ? 'Nghiệp vụ thu tiền' : 'Nghiệp vụ chi tiền' }}
                <span class="ml-auto text-xs font-normal">Chọn loại phiếu khác tại Quay lại nghiệp vụ</span>
            </div>
            <fieldset class="space-y-4">
                <legend class="mb-3 text-base font-bold text-slate-900">01. Thông tin chứng từ</legend>
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block text-sm font-medium text-slate-700">Ngày chứng từ <span class="text-rose-600">*</span>
                        <input type="date" wire:model="manualDate" required class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                        @error('manualDate') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm font-medium text-slate-700">Nghiệp vụ <span class="text-rose-600">*</span>
                        <select wire:model="manualCategory" required class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-500">
                            @foreach($manualCategories as $category)
                                <option value="{{ $category->code }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('manualCategory') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                    </label>
                    @can('finance.voucher.create')
                        <div class="md:col-span-2">
                            <button type="button" wire:click="$toggle('showCategoryForm')" class="min-h-10 text-sm font-semibold text-indigo-700">+ Thêm nghiệp vụ {{ $manualDirection === 'receipt' ? 'thu' : 'chi' }}</button>
                            @if($showCategoryForm)
                                <div class="mt-2 flex flex-wrap gap-2 rounded-xl border border-indigo-100 bg-indigo-50 p-3">
                                    <label class="min-w-0 flex-1 text-sm font-medium">Tên nghiệp vụ mới
                                        <input wire:model="categoryName" maxlength="120" placeholder="Ví dụ: Thu phí dịch vụ" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3">
                                        @error('categoryName') <span class="block text-xs text-rose-600">{{ $message }}</span> @enderror
                                    </label>
                                    <button type="button" wire:click="addManualCategory" class="min-h-11 self-end rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white">Lưu nghiệp vụ</button>
                                </div>
                            @endif
                        </div>
                    @endcan
                    <label class="block text-sm font-medium text-slate-700 md:col-span-2">{{ $manualDirection === 'receipt' ? 'Người / đơn vị nộp tiền' : 'Người / đơn vị nhận tiền' }} <span class="text-rose-600">*</span>
                        <input wire:model="manualCounterparty" maxlength="255" required placeholder="Họ tên hoặc tên đơn vị" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                        @error('manualCounterparty') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm font-medium text-slate-700 md:col-span-2">Lý do {{ $manualDirection === 'receipt' ? 'thu' : 'chi' }} <span class="text-rose-600">*</span>
                        <textarea wire:model="manualDescription" required rows="3" maxlength="5000" placeholder="Nội dung chứng từ, mục đích thanh toán" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100"></textarea>
                        @error('manualDescription') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block text-sm font-medium text-slate-700 md:col-span-2">Số tham chiếu <span class="font-normal text-slate-400">(nếu có)</span>
                        <input wire:model="manualReference" maxlength="100" placeholder="Mã hồ sơ / đề nghị thanh toán" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                        @error('manualReference') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                    </label>
                </div>
            </fieldset>
            <div class="border-t border-slate-200"></div>
            <fieldset class="space-y-4">
                <legend class="mb-3 text-base font-bold text-slate-900">02. Thanh toán</legend>
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block text-sm font-medium text-slate-700">Tài khoản nhận / xuất tiền <span class="text-rose-600">*</span>
                        <select wire:model.live="accountId" required class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                            <option value="">Chọn tài khoản thanh toán</option>
                            @foreach($accounts->where('is_active', true)->sortBy(fn ($account) => $account->kind === 'bank' ? 0 : 1) as $account)
                                <option value="{{ $account->id }}">{{ $account->kind === 'bank' ? '🏦 Ngân hàng' : 'Tiền mặt' }} · {{ $account->code }} — {{ $account->name }}</option>
                            @endforeach
                        </select>
                        @error('accountId') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                        @if($selectedAccount)
                            <span class="mt-2 block rounded-lg bg-slate-50 p-3 text-xs text-slate-600">{{ $selectedAccount->kind === 'bank' ? 'Ngân hàng: '.($selectedAccount->bank_name ?: 'Chưa cập nhật') : 'Quỹ tiền mặt: '.($selectedAccount->cash_custodian ?: 'Chưa cập nhật') }}</span>
                        @endif
                    </label>
                    <label class="block text-sm font-medium text-slate-700">Số tiền {{ $manualDirection === 'receipt' ? 'thu' : 'chi' }} (VND) <span class="text-rose-600">*</span>
                        <input wire:model.live.debounce.300ms="amountDisplay" inputmode="numeric" required placeholder="0" class="mt-1 min-h-11 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100 text-right text-lg font-bold tabular-nums">
                        @error('amount') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                        @error('amountDisplay') <span class="mt-1 block text-xs text-rose-600">{{ $message }}</span> @enderror
                        <span class="mt-1 block text-xs text-slate-500">Đơn vị: đồng · Tự động phân cách hàng nghìn</span>
                    </label>
                </div>
                <p class="rounded-xl border border-amber-100 bg-amber-50 p-3 text-xs text-amber-900">Phiếu đặt cọc / tạm ứng ghi nhận dòng tiền, chưa tự động theo dõi tất toán hoặc cấn trừ.</p>
            </fieldset>
            <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-5">
                <p class="text-xs text-slate-500">Lưu nháp không phát sinh bút toán. Ghi sổ thực hiện tại Sổ giao dịch.</p>
                <button type="submit" wire:loading.attr="disabled" wire:target="createManualDraft,saveManualDraft" class="min-h-11 w-full rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50 sm:w-auto">{{ $editingManualVoucherId ? 'Lưu thay đổi phiếu nháp' : 'Lưu phiếu nháp' }}</button>
            </footer>
        </form>
        @endcan
    </section>
    @endif
    @endif
    @endif
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-4 sm:px-6">
            <div>
                <h2 class="text-lg font-bold text-slate-900">{{ $workspaceTab === 'ledger' ? 'Sổ chứng từ thu / chi' : 'Chứng từ gần đây' }}</h2>
                <p class="mt-1 text-xs text-slate-500">Theo dõi phiếu nháp, phiếu đã ghi sổ và lịch sử hoàn tác.</p>
            </div>
            <label class="flex min-h-11 items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" wire:model.live="showHistory" class="h-4 w-4 rounded border-slate-300">
                Bao gồm phiếu hoàn tác / đã hủy
            </label>
        </header>
        @if($workspaceTab === 'ledger')
        <div class="grid gap-3 border-b border-slate-200 bg-slate-50/70 p-4 sm:grid-cols-2 lg:grid-cols-3">
            <label class="block text-xs font-semibold text-slate-700">Loại phiếu
                <select wire:model.live="ledgerDirection" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                    <option value="">Tất cả thu / chi</option>
                    <option value="receipt">Phiếu thu</option>
                    <option value="payment">Phiếu chi</option>
                </select>
            </label>
            <label class="block text-xs font-semibold text-slate-700">Từ ngày
                <input type="date" wire:model.live="ledgerDateFrom" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
            </label>
            <label class="block text-xs font-semibold text-slate-700">Đến ngày
                <input type="date" wire:model.live="ledgerDateTo" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
            </label>
            <label class="block text-xs font-semibold text-slate-700">Đối tượng
                <input wire:model.live.debounce.400ms="ledgerCounterparty" placeholder="Tìm người nộp / nhận tiền" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
            </label>
            <label class="block text-xs font-semibold text-slate-700">Nghiệp vụ
                <select wire:model.live="ledgerCategory" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                    <option value="">Tất cả nghiệp vụ</option>
                    @foreach($ledgerCategories->filter(fn ($category) => $ledgerDirection === '' || $category->direction === $ledgerDirection) as $category)
                        <option value="{{ $category->code }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-xs font-semibold text-slate-700">Trạng thái
                <select wire:model.live="ledgerStatus" class="mt-1 min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm">
                    <option value="">Tất cả trạng thái đang hiển thị</option>
                    <option value="draft">Nháp</option>
                    <option value="posted">Đã ghi sổ</option>
                    @if($showHistory)
                        <option value="reversed">Đã hoàn tác</option>
                        <option value="cancelled">Đã hủy nháp</option>
                    @endif
                </select>
            </label>
            <div class="sm:col-span-2 lg:col-span-3">
                <button type="button" wire:click="resetLedgerFilters" class="min-h-10 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700">Xóa bộ lọc</button>
            </div>
        </div>
        @endif
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th scope="col" class="px-4 py-3">Số phiếu / Nguồn</th>
                        <th scope="col" class="px-4 py-3">Ngày</th>
                        <th scope="col" class="px-4 py-3">Đối tượng / Nội dung</th>
                        <th scope="col" class="px-4 py-3 text-right">Số tiền</th>
                        <th scope="col" class="px-4 py-3">Trạng thái</th>
                        <th scope="col" class="px-4 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($vouchers as $voucher)
                        <tr wire:key="voucher-{{ $voucher->id }}" class="align-top hover:bg-slate-50">
                            <td class="px-4 py-4">
                                <p class="font-semibold text-slate-900">{{ $voucher->number }}</p>
                                <p class="mt-1 text-xs text-indigo-700">{{ $voucher->origin === 'manual' ? 'Thủ công' : ($voucher->source_document_code ?: 'Chưa xác định nguồn') }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ \Illuminate\Support\Carbon::parse($voucher->voucher_date)->format('d/m/Y') }}</td>
                            <td class="max-w-[260px] px-4 py-4">
                                <p class="font-medium text-slate-800">{{ $voucher->origin === 'manual' ? ($voucher->counterparty ?: 'Chưa có đối tượng') : ($voucher->source_counterparty ?: 'Chưa có đối tượng thanh toán') }}</p>
                                @if($voucher->origin === 'manual')
                                    <p class="mt-1 break-words text-xs text-slate-500">{{ $voucher->manual_category_name ?: $voucher->manual_category }} · {{ $voucher->description }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-right font-bold tabular-nums {{ $voucher->direction === 'receipt' ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ $voucher->direction === 'receipt' ? '+' : '−' }}{{ number_format((float) $voucher->amount, 0, ',', '.') }} ₫
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold {{ $voucher->status === 'posted' ? 'bg-emerald-50 text-emerald-700' : ($voucher->status === 'draft' ? 'bg-amber-50 text-amber-800' : 'bg-slate-100 text-slate-600') }}">
                                    {{ $voucher->status === 'draft' ? 'Nháp' : ($voucher->status === 'posted' ? 'Đã ghi sổ' : ($voucher->status === 'cancelled' ? 'Đã hủy nháp' : 'Đã hoàn tác')) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <a href="{{ route('admin.finance.vouchers.print', ['voucher' => $voucher->id]) }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50" aria-label="In phiếu {{ $voucher->number }}">In A5</a>
                                    <a href="{{ route('admin.finance.vouchers.pdf', ['voucher' => $voucher->id]) }}" class="inline-flex min-h-10 items-center rounded-lg border border-indigo-200 px-3 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">PDF</a>
                                    @if($voucher->status === 'draft')
                                        @can('finance.voucher.create')
                                            <button type="button" wire:click="{{ $voucher->origin === 'manual' ? 'editManualDraft' : 'editDraft' }}({{ $voucher->id }})" class="min-h-10 rounded-lg border border-indigo-200 px-3 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Sửa</button>
                                            <button type="button" wire:click="cancelDraft({{ $voucher->id }})" wire:confirm="Hủy phiếu nháp này?" class="min-h-10 rounded-lg border border-slate-300 px-3 text-xs font-semibold text-slate-700">Hủy</button>
                                        @endcan
                                        @can('finance.voucher.post')
                                            <button type="button" wire:click="post({{ $voucher->id }})" wire:confirm="Xác nhận ghi sổ phiếu này?" class="min-h-10 rounded-lg bg-emerald-600 px-3 text-xs font-semibold text-white">Ghi sổ</button>
                                        @endcan
                                    @elseif($voucher->status === 'posted')
                                        @can('finance.voucher.reverse')
                                            <button type="button" wire:click="reverse({{ $voucher->id }})" wire:confirm="Xác nhận hoàn tác phiếu này?" class="min-h-10 rounded-lg border border-rose-300 px-3 text-xs font-semibold text-rose-700">Hoàn tác</button>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">Chưa có chứng từ thu / chi phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="border-t border-slate-100 px-4 py-3 text-xs text-slate-500">Hiển thị tối đa 30 chứng từ phù hợp gần nhất. Bộ lọc áp dụng trên toàn bộ dữ liệu trước khi giới hạn kết quả.</p>
    </section>
</div>
