<?php

namespace Modules\Finance\Livewire;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Modules\Finance\Services\FinanceWorkflowService;
use Modules\Finance\Services\VoucherPostingService;

final class Workspace extends Component
{
    public string $kind = 'inventory_receipt';
    public string $composeMode = 'source';
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
    public string $workspaceTab = 'compose';
    public bool $showHistory = false;
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

    public function updatedAmountDisplay(string $value): void
    {
        $value = trim($value);
        if ($value !== '' && ! preg_match('/^\\d[\\d., ]*$/', $value)) {
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
            'manualCategory' => 'required|in:deposit,advance,advance_refund,hospitality,other_receipt,other_payment',
            'manualCounterparty' => 'required|string|max:255',
            'manualDescription' => 'required|string|max:5000',
            'manualReference' => 'nullable|string|max:100',
            'manualDate' => 'required|date_format:Y-m-d',
            'accountId' => 'required|integer|min:1',
            'amount' => 'required|regex:/^\\d{1,15}(?:\\.\\d{1,2})?$/',
        ];
    }

    private function validateManualCategory(): void
    {
        $allowed = $this->manualDirection === 'receipt'
            ? ['deposit', 'advance_refund', 'other_receipt']
            : ['hospitality', 'advance', 'other_payment'];
        if (! in_array($this->manualCategory, $allowed, true)) {
            throw ValidationException::withMessages(['manualCategory' => 'Loại nghiệp vụ không phù hợp phiếu thu/chi.']);
        }
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

    public function render()
    {
        abort_unless(auth('admin')->user()?->can('finance.view'), 403);

        $accounts = DB::table('finance_accounts')->orderBy('code')->get();
        $vouchers = DB::table('finance_vouchers as v')
            ->leftJoin('finance_allocations as a', 'a.voucher_id', '=', 'v.id')
            ->leftJoin('finance_source_documents as s', 's.id', '=', 'a.source_document_id')
            ->when(! $this->showHistory, fn ($query) => $query->whereNotIn('v.status', ['reversed', 'cancelled']))
            ->select('v.*', 's.source_document_code', 's.counterparty as source_counterparty')
            ->orderByDesc('v.id')->limit(30)->get();
        $selectedAccount = $accounts->firstWhere('id', (int) $this->accountId);

        $documents = collect();
        if ((bool) config('modules.registry.Pharma.enabled', false)
            && in_array($this->kind, ['inventory_receipt', 'inventory_issue'], true)) {
            $table = $this->kind === 'inventory_receipt'
                ? 'pharma_inventory_receipts' : 'pharma_inventory_issues';
            $documents = DB::table($table)->where('status', 'posted')
                ->when(trim($this->search) !== '', fn ($query) => $query->where('number', 'like', '%'.trim($this->search).'%'))
                ->orderByDesc('id')->limit(30)->get(['id', 'number']);
        }

        return view('Finance::livewire.workspace', compact('accounts', 'vouchers', 'documents', 'selectedAccount'));
    }
}
