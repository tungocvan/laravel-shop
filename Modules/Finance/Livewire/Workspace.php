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
    public string $search = '';
    public string $documentId = '';
    public string $accountId = '';
    public string $amount = '';
    public string $accountCode = '';
    public string $accountName = '';
    public string $accountKind = 'cash';
    public ?array $balance = null;

    public function selectDocument(string $id): void
    {
        $this->documentId = $id;
        $this->loadBalance();
    }

    public function updatedKind(): void
    {
        $this->documentId = '';
        $this->balance = null;
        $this->amount = '';
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
            'remaining' => $outstanding['remaining'],
        ];
        $this->amount = $this->balance['remaining'];
    }

    public function createAccount(): void
    {
        abort_unless(auth('admin')->user()?->can('finance.account.manage'), 403);
        $this->validate([
            'accountCode' => 'required|string|max:40',
            'accountName' => 'required|string|max:255',
            'accountKind' => 'required|in:cash,bank',
        ]);
        $id = app(FinanceWorkflowService::class)->createAccount(
            $this->accountCode, $this->accountName, $this->accountKind
        );
        $this->accountId = (string) $id;
        $this->reset('accountCode', 'accountName');
        session()->flash('finance_success', 'Đã tạo tài khoản thu/chi.');
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
        $number = 'FIN-'.now()->format('YmdHis').'-'.strtoupper(bin2hex(random_bytes(4)));
        app(FinanceWorkflowService::class)->createDraft(
            'Pharma', $this->kind, $this->documentId, (int) $this->accountId,
            $this->amount, $number, auth('admin')->id()
        );
        $this->loadBalance();
        session()->flash('finance_success', 'Đã tạo phiếu nháp. Kiểm tra và ghi sổ khi sẵn sàng.');
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
        $vouchers = DB::table('finance_vouchers')->orderByDesc('id')->limit(30)->get();

        $documents = collect();
        if ((bool) config('modules.registry.Pharma.enabled', false)
            && in_array($this->kind, ['inventory_receipt', 'inventory_issue'], true)) {
            $table = $this->kind === 'inventory_receipt'
                ? 'pharma_inventory_receipts' : 'pharma_inventory_issues';
            $documents = DB::table($table)->where('status', 'posted')
                ->when(trim($this->search) !== '', fn ($query) => $query->where('number', 'like', '%'.trim($this->search).'%'))
                ->orderByDesc('id')->limit(30)->get(['id', 'number']);
        }

        return view('Finance::livewire.workspace', compact('accounts', 'vouchers', 'documents'));
    }
}
