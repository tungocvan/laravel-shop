<?php

namespace Modules\Finance\Livewire;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Modules\Finance\Services\FinanceWorkflowService;
use Modules\Finance\Services\FinanceTreasuryService;
use Modules\Finance\Services\FinanceReportService;
use Modules\Finance\Services\ManualCategoryService;
use Modules\Finance\Services\VoucherPostingService;

final class Workspace extends Component
{
    public ?int $editingAccountId = null;
    public string $openingAmountDisplay = '0';
    public string $transferAmountDisplay = '';

    public function updatedOpeningAmountDisplay(string $value): void
    {
        $this->openingAmount = $this->normalizeVndInput($value);
        $this->openingAmountDisplay = $this->formatVnd($this->openingAmount);
    }

    public function updatedTransferAmountDisplay(string $value): void
    {
        $this->transferAmount = $this->normalizeVndInput($value);
        $this->transferAmountDisplay = $this->formatVnd($this->transferAmount);
    }

    private function normalizeVndInput(string $value): string
    {
        if (! preg_match('/^[0-9., ]*$/', trim($value))) {
            throw \Illuminate\Validation\ValidationException::withMessages(['amount' => 'Chỉ nhập số tiền VND và dấu phân cách hàng nghìn.']);
        }
        $digits = preg_replace('/[^0-9]/', '', $value);
        if (strlen($digits) > 15) {
            throw \Illuminate\Validation\ValidationException::withMessages(['amount' => 'Số tiền vượt giới hạn.']);
        }
        return ltrim($digits, '0') ?: '0';
    }

    public function editAccount(int $accountId): void
    {
        abort_unless(auth('admin')->user()?->can('finance.account.manage'), 403);
        $account = DB::table('finance_accounts')->where('id', $accountId)->first();
        abort_unless($account, 404);
        $this->editingAccountId = $accountId;
        $this->accountCode = $account->code;
        $this->accountName = $account->name;
        $this->accountKind = $account->kind;
        $this->bankName = $account->bank_name ?? '';
        $this->bankAccountNumber = $account->bank_account_number ?? '';
        $this->bankAccountHolder = $account->bank_account_holder ?? '';
        $this->cashCustodian = $account->cash_custodian ?? '';
        $this->cashLocation = $account->cash_location ?? '';
        $this->showAccounts = true;
        $this->showAccountForm = true;
    }

    public function openCreateAccount(): void
    {
        abort_unless(auth('admin')->user()?->can('finance.account.manage'), 403);
        $this->editingAccountId = null;
        $this->reset('accountCode', 'accountName', 'bankName', 'bankAccountNumber', 'bankAccountHolder', 'cashCustodian', 'cashLocation');
        $this->accountKind = 'bank';
        $this->showAccounts = true;
        $this->showAccountForm = true;
    }

    public string $transferFrom = '';
    public string $transferTo = '';
    public string $transferAmount = '';
    public string $transferDescription = '';
    public string $transferDate = '';
    public string $openingAccount = '';
    public string $openingAmount = '';

    public function saveOpeningBalance(): void
    {
        abort_unless(auth('admin')->user()?->can('finance.account.manage'), 403);
        $data = $this->validate([
            'openingAccount' => 'required|integer|min:1',
            'openingAmount' => 'required|regex:/^\d{1,15}(?:\.\d{1,2})?$/',
        ]);
        app(FinanceTreasuryService::class)->setOpeningBalance((int) $data['openingAccount'], $data['openingAmount']);
        $this->openingAmount = '0';
        $this->openingAmountDisplay = '0';
        $this->dispatch('finance-opening-saved');
        session()->flash('finance_success', 'Đã lưu số dư đầu kỳ.');
    }

    public function saveInternalTransfer(): void
    {
        abort_unless(auth('admin')->user()?->can('finance.voucher.post'), 403);
        $data = $this->validate([
            'transferFrom' => 'required|integer|min:1',
            'transferTo' => 'required|integer|min:1|different:transferFrom',
            'transferAmount' => 'required|regex:/^\d{1,15}(?:\.\d{1,2})?$/',
            'transferDate' => 'required|date_format:Y-m-d',
            'transferDescription' => 'required|string|max:255',
        ]);
        app(FinanceTreasuryService::class)->transfer(
            (int) $data['transferFrom'], (int) $data['transferTo'],
            $data['transferAmount'], $data['transferDate'], $data['transferDescription'], auth('admin')->id()
        );
        $this->transferAmount = '';
        $this->transferAmountDisplay = '';
        $this->dispatch('finance-transfer-saved');
        $this->transferDescription = '';
        session()->flash('finance_success', 'Đã ghi nhận chuyển tiền nội bộ (không tính vào thu/chi kinh doanh).');
    }

    public string $kind = 'inventory_receipt';
    public string $composeMode = 'home';
    public bool $showAccounts = false;
    public bool $showCategoryForm = false;
    public string $categoryName = '';

    public function openManual(string $direction): void
    {
        abort_unless(auth('admin')->user()?->can('finance.voucher.create'), 403);
        if (! in_array($direction, ['receipt', 'payment'], true)) {
            abort(422);
        }
        $this->editingManualVoucherId = null;
        $this->editingVoucherId = null;
        $this->manualDirection = $direction;
        $this->showCategoryForm = false;
        $this->manualCategory = $direction === 'receipt' ? 'deposit' : 'hospitality';
        $this->composeMode = 'manual';
        $this->workspaceTab = 'compose';
        $this->amount = '';
        $this->amountDisplay = '';
    }

    public function openSource(string $module): void
    {
        abort_unless(auth('admin')->user()?->can('finance.view'), 403);
        if ($module !== 'Pharma') {
            abort(422);
        }
        $this->editingManualVoucherId = null;
        $this->editingVoucherId = null;
        $this->composeMode = 'source';
        $this->workspaceTab = 'compose';
    }

    public function addManualCategory(): void
    {
        abort_unless(auth('admin')->user()?->can('finance.voucher.create'), 403);
        $this->validate(['categoryName' => 'required|string|max:120']);
        $this->manualCategory = app(ManualCategoryService::class)->create($this->manualDirection, $this->categoryName);
        $this->categoryName = '';
        $this->showCategoryForm = false;
        session()->flash('finance_success', 'Đã thêm nghiệp vụ thu/chi.');
    }
    public string $manualDirection = 'receipt';
    public string $manualCategory = 'deposit';
    public string $manualCounterparty = '';
    public string $manualDescription = '';
    public string $manualReference = '';
    public string $manualDate = '';
    public ?int $editingManualVoucherId = null;

    public function mount(): void
    {
        $this->manualDate = now()->toDateString();
        $this->transferDate = now()->toDateString();
    }

    public function updatedComposeMode(): void
    {
        $this->editingVoucherId = null;
        $this->editingManualVoucherId = null;
        $this->amount = '';
        $this->amountDisplay = '';
    }

    public function updatedManualDirection(): void
    {
        $this->manualCategory = $this->manualDirection === 'receipt' ? 'deposit' : 'hospitality';
    }
    public string $search = '';
    public string $documentId = '';
    public string $accountId = '';
    public string $amount = '';
    public string $accountCode = '';
    public string $accountName = '';
    public string $accountKind = 'bank';
    public string $bankName = '';
    public string $bankAccountNumber = '';
    public string $bankAccountHolder = '';
    public string $cashCustodian = '';
    public string $cashLocation = '';
    public bool $showAccountForm = false;
    public string $amountDisplay = '';
    public ?array $balance = null;
    public string $workspaceTab = 'overview';
    public bool $showVoucherOperations = false;
    public bool $showHistory = false;
    public string $ledgerDirection = '';
    public string $ledgerDateFrom = '';
    public string $ledgerDateTo = '';
    public string $ledgerCounterparty = '';
    public string $ledgerCategory = '';
    public string $ledgerStatus = '';

    public function resetLedgerFilters(): void
    {
        $this->reset('ledgerDirection', 'ledgerDateFrom', 'ledgerDateTo', 'ledgerCounterparty', 'ledgerCategory', 'ledgerStatus');
    }

    public ?int $editingVoucherId = null;

    public function selectDocument(string $id): void
    {
        $this->editingVoucherId = null;
        $this->editingManualVoucherId = null;
        $this->documentId = $id;
        $this->loadBalance();
    }

    public function updatedKind(): void
    {
        $this->editingVoucherId = null;
        $this->documentId = '';
        $this->balance = null;
        $this->amount = '';
        $this->amountDisplay = '';
    }

    public function loadBalance(): void
    {
        $this->balance = null;
        if ($this->documentId === '') {
            return;
        }
        $outstanding = app(FinanceWorkflowService::class)
            ->outstanding('Pharma', $this->kind, $this->documentId);

        // Livewire public state must contain only serializable primitives.
        // FinancialDocumentSnapshot stays inside the service boundary.
        $this->balance = [
            'total' => $outstanding['total'],
            'paid' => $outstanding['paid'],
            'reserved' => $outstanding['reserved'],
            'available' => $outstanding['available'],
            'remaining' => $outstanding['remaining'],
            'document_code' => $outstanding['snapshot']->documentCode,
            'counterparty' => $outstanding['snapshot']->counterparty,
            'direction' => $outstanding['snapshot']->direction,
        ];
        $this->amount = $this->balance['available'] === '0.00' ? '' : $this->balance['available'];
        $this->amountDisplay = $this->formatVnd($this->amount);
    }

    public function createAccount(): void
    {
        if ($this->editingAccountId !== null) {
            $this->updateAccount();
            return;
        }
        abort_unless(auth('admin')->user()?->can('finance.account.manage'), 403);
        $this->validate([
            'accountCode' => 'required|string|max:40',
            'accountName' => 'required|string|max:255',
            'accountKind' => 'required|in:cash,bank',
        ]);
        $this->validate($this->accountKind === 'bank' ? [
            'bankName' => 'required|string|max:255',
            'bankAccountNumber' => 'required|string|max:100',
            'bankAccountHolder' => 'required|string|max:255',
        ] : [
            'cashCustodian' => 'required|string|max:255',
            'cashLocation' => 'required|string|max:255',
        ]);
        $id = app(FinanceWorkflowService::class)->createDetailedAccount(
            $this->accountCode, $this->accountName, $this->accountKind,
            [
                'bank_name' => $this->bankName,
                'bank_account_number' => $this->bankAccountNumber,
                'bank_account_holder' => $this->bankAccountHolder,
                'cash_custodian' => $this->cashCustodian,
                'cash_location' => $this->cashLocation,
            ]
        );
        $this->accountId = (string) $id;
        $this->reset('accountCode', 'accountName', 'bankName', 'bankAccountNumber', 'bankAccountHolder', 'cashCustodian', 'cashLocation');
        $this->showAccountForm = false;
        session()->flash('finance_success', 'Đã tạo tài khoản thu/chi.');
    }

    public function updateAccount(): void
    {
        abort_unless(auth('admin')->user()?->can('finance.account.manage'), 403);
        abort_unless($this->editingAccountId !== null, 422);
        $this->validate(['accountName' => 'required|string|max:255']);
        $this->validate($this->accountKind === 'bank' ? [
            'bankName' => 'required|string|max:255',
            'bankAccountNumber' => 'required|string|max:100',
            'bankAccountHolder' => 'required|string|max:255',
        ] : [
            'cashCustodian' => 'required|string|max:255',
            'cashLocation' => 'required|string|max:255',
        ]);
        app(FinanceWorkflowService::class)->updateDetailedAccount($this->editingAccountId, $this->accountName, [
            'bank_name' => $this->bankName,
            'bank_account_number' => $this->bankAccountNumber,
            'bank_account_holder' => $this->bankAccountHolder,
            'cash_custodian' => $this->cashCustodian,
            'cash_location' => $this->cashLocation,
        ]);
        $this->editingAccountId = null;
        $this->showAccountForm = false;
        $this->dispatch('finance-account-saved');
        session()->flash('finance_success', 'Đã cập nhật thông tin tài khoản.');
    }

    public function updatedAmountDisplay(string $value): void
    {
        $value = trim($value);
        if ($value !== '' && ! preg_match('/^\d[\d., ]*$/', $value)) {
            $this->addError('amountDisplay', 'Số tiền chỉ được chứa chữ số và dấu phân cách.');
            return;
        }
        // VND input: commas/spaces/dots are thousands separators; no fractional VND in this UI.
        $digits = preg_replace('/[^0-9]/', '', $value);
        if ($digits === '' || strlen($digits) > 15) {
            $this->amount = '';
            $this->amountDisplay = '';
            return;
        }
        $this->amount = ltrim($digits, '0') ?: '0';
        $this->amountDisplay = $this->formatVnd($this->amount);
    }

    private function formatVnd(string $amount): string
    {
        if ($amount === '') {
            return '';
        }
        $whole = explode('.', $amount, 2)[0];
        return number_format((int) $whole, 0, ',', '.');
    }

    private function manualRules(): array
    {
        return [
            'manualDirection' => 'required|in:receipt,payment',
            'manualCategory' => 'required|string|max:60',
            'manualCounterparty' => 'required|string|max:255',
            'manualDescription' => 'required|string|max:5000',
            'manualReference' => 'nullable|string|max:100',
            'manualDate' => 'required|date_format:Y-m-d',
            'accountId' => 'required|integer|min:1',
            'amount' => 'required|regex:/^\d{1,15}(?:\.\d{1,2})?$/',
        ];
    }

    private function validateManualCategory(): void
    {
        app(ManualCategoryService::class)->assertActive($this->manualDirection, $this->manualCategory);
    }

    public function createManualDraft(): void
    {
        abort_unless(auth('admin')->user()?->can('finance.voucher.create'), 403);
        $this->validate($this->manualRules());
        $this->validateManualCategory();
        if ($this->editingManualVoucherId !== null) {
            throw ValidationException::withMessages(['voucher' => 'Save or cancel the current edit first.']);
        }
        $number = 'FIN-M-'.now()->format('YmdHis').'-'.strtoupper(bin2hex(random_bytes(4)));
        app(FinanceWorkflowService::class)->createManualDraft(
            $this->manualDirection, $this->manualCategory, $this->manualCounterparty,
            $this->manualDescription, (int) $this->accountId, $this->amount,
            $number, $this->manualDate, auth('admin')->id(), $this->manualReference ?: null
        );
        $this->amount = '';
        $this->amountDisplay = '';
        session()->flash('finance_success', 'Đã tạo phiếu thủ công ở trạng thái nháp.');
    }

    public function saveManualDraft(): void
    {
        abort_unless(auth('admin')->user()?->can('finance.voucher.create'), 403);
        $this->validate($this->manualRules());
        $this->validateManualCategory();
        if ($this->editingManualVoucherId === null) {
            throw ValidationException::withMessages(['voucher' => 'No manual draft selected.']);
        }
        app(FinanceWorkflowService::class)->updateManualDraft(
            $this->editingManualVoucherId, (int) $this->accountId, $this->amount,
            $this->manualCategory, $this->manualCounterparty, $this->manualDescription,
            $this->manualDate, $this->manualReference ?: null
        );
        $this->editingManualVoucherId = null;
        session()->flash('finance_success', 'Đã lưu thay đổi phiếu thủ công.');
    }

    public function editManualDraft(int $voucherId): void
    {
        abort_unless(auth('admin')->user()?->can('finance.voucher.create'), 403);
        $voucher = DB::table('finance_vouchers')
            ->where('id', $voucherId)->where('origin', 'manual')->where('status', 'draft')->first();
        if (! $voucher) {
            throw ValidationException::withMessages(['voucher' => 'Manual draft not found.']);
        }
        $this->composeMode = 'manual';
        $this->workspaceTab = 'compose';
        $this->editingVoucherId = null;
        $this->editingManualVoucherId = $voucherId;
        $this->manualDirection = $voucher->direction;
        $this->manualCategory = (string) $voucher->manual_category;
        $this->manualCounterparty = (string) $voucher->counterparty;
        $this->manualDescription = (string) $voucher->description;
        $this->manualReference = (string) $voucher->reference_number;
        $this->manualDate = substr((string) $voucher->voucher_date, 0, 10);
        $this->accountId = (string) $voucher->account_id;
        $this->amount = (string) $voucher->amount;
        $this->amountDisplay = $this->formatVnd($this->amount);
    }

    public function createDraft(): void
    {
        abort_unless(auth('admin')->user()?->can('finance.voucher.create'), 403);
        $this->validate([
            'kind' => 'required|in:inventory_receipt,inventory_issue',
            'documentId' => 'required|integer|min:1',
            'accountId' => 'required|integer|min:1',
            'amount' => 'required|regex:/^\d{1,15}(?:\.\d{1,2})?$/',
        ]);
        $enteredAmount = $this->amount;
        $this->loadBalance();
        $this->amount = $enteredAmount;
        $this->amountDisplay = $this->formatVnd($enteredAmount);
        if (! $this->balance || $this->balance['available'] === '0.00') {
            throw ValidationException::withMessages(['amount' => 'Chứng từ đã thanh toán đủ.']);
        }
        $number = 'FIN-'.now()->format('YmdHis').'-'.strtoupper(bin2hex(random_bytes(4)));
        app(FinanceWorkflowService::class)->createDraft(
            'Pharma', $this->kind, $this->documentId, (int) $this->accountId,
            $this->amount, $number, auth('admin')->id()
        );
        $this->loadBalance();
        session()->flash('finance_success', 'Đã tạo phiếu nháp. Kiểm tra và ghi sổ khi sẵn sàng.');
    }

    public function editDraft(int $voucherId): void
    {
        abort_unless(auth('admin')->user()?->can('finance.voucher.create'), 403);
        $voucher = DB::table('finance_vouchers')->where('id', $voucherId)->where('status', 'draft')->first();
        $source = DB::table('finance_allocations as a')
            ->join('finance_source_documents as s', 's.id', '=', 'a.source_document_id')
            ->where('a.voucher_id', $voucherId)->first(['s.source_module', 's.source_type', 's.source_document_id']);
        if (! $voucher || ! $source || $source->source_module !== 'Pharma') {
            throw ValidationException::withMessages(['voucher' => 'Draft is not editable here.']);
        }
        $this->composeMode = 'source';
        $this->editingManualVoucherId = null;
        $this->kind = $source->source_type;
        $this->documentId = (string) $source->source_document_id;
        $this->accountId = (string) $voucher->account_id;
        $this->editingVoucherId = $voucherId;
        $this->workspaceTab = 'compose';
        $this->loadBalance();
        $this->amount = (string) $voucher->amount;
        $this->amountDisplay = $this->formatVnd($this->amount);
    }

    public function saveDraft(): void
    {
        abort_unless(auth('admin')->user()?->can('finance.voucher.create'), 403);
        if ($this->editingVoucherId === null) {
            throw ValidationException::withMessages(['voucher' => 'Select a draft to edit.']);
        }
        $this->validate(['accountId' => 'required|integer|min:1', 'amount' => 'required|numeric|gt:0']);
        app(FinanceWorkflowService::class)->updateDraft($this->editingVoucherId, (int) $this->accountId, $this->amount);
        $this->editingVoucherId = null;
        $this->loadBalance();
        session()->flash('finance_success', 'Đã cập nhật phiếu nháp.');
    }

    public function cancelDraft(int $voucherId): void
    {
        abort_unless(auth('admin')->user()?->can('finance.voucher.create'), 403);
        app(FinanceWorkflowService::class)->cancelDraft($voucherId);
        if ($this->editingManualVoucherId === $voucherId) {
            $this->editingManualVoucherId = null;
        }
        if ($this->editingVoucherId === $voucherId) {
            $this->editingVoucherId = null;
        }
        if ($this->documentId !== '') {
            $this->loadBalance();
        }
        session()->flash('finance_success', 'Đã hủy phiếu nháp, giải phóng hạn mức.');
    }

    public function permanentlyDeleteUnposted(int $voucherId): void
    {
        $user = auth('admin')->user();
        abort_unless($user && $user->hasAnyRole(['admin', 'Admin', 'Super Admin', 'super-admin']) && $user->can('finance.voucher.force-delete'), 403);
        app(FinanceWorkflowService::class)->permanentlyDeleteUnposted($voucherId);
        if ($this->documentId !== '') {
            $this->loadBalance();
        }
        session()->flash('finance_success', 'Đã xóa vĩnh viễn chứng từ chưa từng ghi sổ.');
    }

    public function permanentlyDeleteDemoVoucher(int $voucherId): void
    {
        $user = auth('admin')->user();
        abort_unless($user && $user->hasAnyRole(['admin', 'Admin', 'Super Admin', 'super-admin'])
            && $user->can('finance.voucher.force-delete'), 403);
        abort_if(app()->environment('production'), 403);
        app(FinanceWorkflowService::class)->permanentlyDeleteDemoVoucher($voucherId);
        if ($this->documentId !== '') {
            $this->loadBalance();
        }
        session()->flash('finance_success', 'Đã xóa vĩnh viễn chứng từ demo.');
    }

    public function post(int $voucherId): void
    {
        abort_unless(auth('admin')->user()?->can('finance.voucher.post'), 403);
        app(VoucherPostingService::class)->post($voucherId, auth('admin')->id());
        if ($this->documentId !== '') {
            $this->loadBalance();
        }
        session()->flash('finance_success', 'Đã ghi sổ phiếu.');
    }

    public function reverse(int $voucherId): void
    {
        abort_unless(auth('admin')->user()?->can('finance.voucher.reverse'), 403);
        app(VoucherPostingService::class)->reverse($voucherId, auth('admin')->id());
        if ($this->documentId !== '') {
            $this->loadBalance();
        }
        session()->flash('finance_success', 'Đã hoàn tác phiếu và lưu bút toán đảo.');
    }

    public function reverseToDraft(int $voucherId): void
    {
        abort_unless(auth('admin')->user()?->can('finance.voucher.reverse')
            && auth('admin')->user()?->can('finance.voucher.create'), 403);
        $newId = app(VoucherPostingService::class)->reverseToDraft($voucherId, auth('admin')->id());
        if ($this->documentId !== '') {
            $this->loadBalance();
        }
        $this->showHistory = false;
        session()->flash('finance_success', 'Đã hoàn tác phiếu cũ và tạo nháp mới #'.$newId.'. Hãy kiểm tra, sửa rồi ghi sổ lại.');
    }

    public function render()
    {
        abort_unless(auth('admin')->user()?->can('finance.view'), 403);

        $accounts = DB::table('finance_accounts')->orderBy('code')->get();
        $treasuryBalances = app(FinanceTreasuryService::class)->balances();
        $internalTransfers = DB::table('finance_internal_transfers as t')
            ->join('finance_accounts as a', 'a.id', '=', 't.from_account_id')
            ->join('finance_accounts as b', 'b.id', '=', 't.to_account_id')
            ->select('t.*', 'a.name as from_name', 'b.name as to_name')
            ->orderByDesc('t.id')->limit(20)->get();
        $ledgerFilters = validator([
            'direction' => $this->ledgerDirection,
            'from' => $this->ledgerDateFrom,
            'to' => $this->ledgerDateTo,
            'status' => $this->ledgerStatus,
            'category' => $this->ledgerCategory,
        ], [
            'direction' => 'nullable|in:receipt,payment',
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d|after_or_equal:from',
            'status' => 'nullable|in:draft,posted,reversed,cancelled',
            'category' => 'nullable|string|max:60',
        ])->validate();
        $vouchers = DB::table('finance_vouchers as v')
            ->leftJoin('finance_allocations as a', 'a.voucher_id', '=', 'v.id')
            ->leftJoin('finance_source_documents as s', 's.id', '=', 'a.source_document_id')
            ->leftJoin('finance_manual_categories as c', function ($join) {
                $join->on('c.code', '=', 'v.manual_category')->on('c.direction', '=', 'v.direction');
            })
            ->when(! $this->showHistory, fn ($query) => $query->whereNotIn('v.status', ['reversed', 'cancelled']))
            ->when($ledgerFilters['direction'] !== '', fn ($query) => $query->where('v.direction', $ledgerFilters['direction']))
            ->when($ledgerFilters['from'] !== '', fn ($query) => $query->whereDate('v.voucher_date', '>=', $ledgerFilters['from']))
            ->when($ledgerFilters['to'] !== '', fn ($query) => $query->whereDate('v.voucher_date', '<=', $ledgerFilters['to']))
            ->when($ledgerFilters['status'] !== '', fn ($query) => $query->where('v.status', $ledgerFilters['status']))
            ->when($ledgerFilters['category'] !== '', fn ($query) => $query->where('v.manual_category', $ledgerFilters['category']))
            ->when(trim($this->ledgerCounterparty) !== '', function ($query) {
                $term = '%'.addcslashes(trim($this->ledgerCounterparty), '%_\\').'%';
                $query->where(function ($q) use ($term) {
                    $q->where('v.counterparty', 'like', $term)
                        ->orWhere('s.counterparty', 'like', $term);
                });
            })
            ->select('v.*', 's.source_document_code', 's.counterparty as source_counterparty', 'c.name as manual_category_name')
            ->orderByDesc('v.id')->limit(30)->get();
        $reportService = app(FinanceReportService::class);
        $reportFilters = [
            'from' => $this->ledgerDateFrom,
            'to' => $this->ledgerDateTo,
            'direction' => $this->ledgerDirection,
            'category' => $this->ledgerCategory,
            'counterparty' => $this->ledgerCounterparty,
        ];
        $reportTotals = $this->workspaceTab === 'overview' || $this->workspaceTab === 'ledger'
            ? $reportService->totals($reportFilters)
            : ['receipts' => '0', 'payments' => '0', 'net' => '0', 'voucher_count' => 0];
        $reportRows = $this->workspaceTab === 'ledger'
            ? $reportService->query($reportFilters)->orderByDesc('v.voucher_date')->orderByDesc('v.id')->limit(100)->get()
            : collect();
        $selectedAccount = $accounts->firstWhere('id', (int) $this->accountId);
        $manualCategories = app(ManualCategoryService::class)->active($this->manualDirection);
        $ledgerCategories = DB::table('finance_manual_categories')->orderBy('name')->get(['code', 'name', 'direction']);

        $documents = collect();
        if ((bool) config('modules.registry.Pharma.enabled', false)
            && in_array($this->kind, ['inventory_receipt', 'inventory_issue'], true)) {
            $table = $this->kind === 'inventory_receipt'
                ? 'pharma_inventory_receipts' : 'pharma_inventory_issues';
            $documents = DB::table($table)->where('status', 'posted')
                ->when(trim($this->search) !== '', fn ($query) => $query->where('number', 'like', '%'.trim($this->search).'%'))
                ->orderByDesc('id')->limit(30)->get(['id', 'number']);
        }

        return view('Finance::livewire.workspace', compact('accounts', 'vouchers', 'documents', 'selectedAccount', 'manualCategories', 'ledgerCategories', 'reportTotals', 'reportRows', 'reportFilters', 'treasuryBalances', 'internalTransfers'));
    }
}
