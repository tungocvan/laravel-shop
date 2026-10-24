<?php

namespace Modules\Finance\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Modules\Finance\Services\FinanceDebtService;
use Modules\Finance\Services\PartnerDebtDraftService;
use Modules\Partner\Models\Partner;

final class DebtWorkspace extends Component
{
    use WithPagination;

    public string $search = '';
    public string $direction = '';
    public string $status = '';
    public bool $creating = false;
    public string $reference = '';
    public string $draftDirection = 'receivable';
    public string $partnerSearch = '';
    public array $partnerOptions = [];
    public string $partnerId = '';
    public string $amount = '';
    public string $dueOn = '';
    public ?int $selectedDebtId = null;
    public string $adjustment = '';
    public string $adjustmentReason = '';
    public string $adjustmentDate = '';

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedDirection(): void { $this->resetPage(); }
    public function updatedStatus(): void { $this->resetPage(); }

    private function authorizeView(): void
    {
        abort_unless(auth('admin')->user()?->can('finance.view'), 403);
    }

    private function authorizeMutation(): int
    {
        abort_unless(auth('admin')->user()?->can('finance.debt.manage'), 403);
        $id = auth('admin')->id();
        abort_unless($id !== null, 403);
        return (int) $id;
    }

    public function openCreate(): void
    {
        $this->authorizeMutation();
        $this->creating = true;
        $this->dueOn = now()->addDays(30)->toDateString();
        $this->loadPartnerOptions();
    }

    public function updatedPartnerSearch(): void
    {
        $this->authorizeMutation();
        $this->partnerSearch = mb_substr(trim($this->partnerSearch), 0, 100);
        $this->loadPartnerOptions();
    }

    public function updatedDraftDirection(): void
    {
        $this->partnerId = '';
        $this->partnerSearch = '';
        $this->loadPartnerOptions();
    }

    #[On('finance-debt-partner-search')]
    public function searchPartners(string $search = ''): void
    {
        $this->authorizeMutation();
        $this->partnerSearch = mb_substr(trim($search), 0, 100);
        $this->loadPartnerOptions();
    }

    private function loadPartnerOptions(): void
    {
        $this->partnerOptions = [];
        if (! config('modules.registry.Partner.enabled', false)) {
            return;
        }
        $role = $this->draftDirection === 'payable' ? 'supplier' : 'customer';
        $query = Partner::query()->where('status', 'active')->withPartnerType($role);
        if ($this->partnerSearch !== '') {
            $term = '%'.addcslashes($this->partnerSearch, '%_\\\\').'%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('tax_code', 'like', $term));
        }
        $this->partnerOptions = $query->orderBy('name')->limit(20)->get(['id', 'name', 'tax_code'])
            ->map(fn ($p) => ['id' => (string) $p->id, 'label' => $p->name.($p->tax_code ? ' — '.$p->tax_code : '')])->all();
    }

    public function choosePartner(int $id): void
    {
        $this->authorizeMutation();
        $role = $this->draftDirection === 'payable' ? 'supplier' : 'customer';
        abort_unless((bool) config('modules.registry.Partner.enabled', false), 422);
        $partner = Partner::query()->whereKey($id)->where('status', 'active')->first();
        abort_unless($partner && $partner->hasPartnerType($role), 422);
        $this->partnerId = (string) $id;
    }

    public function create(PartnerDebtDraftService $service): void
    {
        $actor = $this->authorizeMutation();
        $this->validate([
            'reference' => 'required|string|max:100',
            'draftDirection' => 'required|in:receivable,payable',
            'partnerId' => 'required|integer|min:1',
            'amount' => ['required', 'regex:/^\\d{1,16}(?:\\.\\d{1,2})?$/'],
            'dueOn' => 'nullable|date_format:Y-m-d',
        ]);
        $service->create($this->reference, $this->draftDirection, (int) $this->partnerId, $this->amount, $this->dueOn ?: null, $actor);
        $this->reset('reference', 'partnerId', 'partnerSearch', 'amount', 'dueOn', 'creating');
        $this->resetPage();
        session()->flash('debt_notice', 'Đã tạo công nợ nháp.');
    }

    public function selectDebt(int $id): void
    {
        $this->authorizeView();
        abort_unless(DB::table('finance_debt_obligations')->where('id', $id)->exists(), 404);
        $this->selectedDebtId = $id;
        $this->reset('adjustment', 'adjustmentReason', 'adjustmentDate');
    }

    public function confirm(FinanceDebtService $service): void
    {
        $actor = $this->authorizeMutation();
        abort_unless($this->selectedDebtId !== null, 404);
        $service->confirmManualDraft($this->selectedDebtId, $actor, now()->toDateString());
        session()->flash('debt_notice', 'Đã xác nhận công nợ thủ công.');
    }

    public function adjust(FinanceDebtService $service): void
    {
        $actor = $this->authorizeMutation();
        abort_unless($this->selectedDebtId !== null, 404);
        $this->validate([
            'adjustment' => ['required', 'regex:/^-?\\d{1,16}(?:\\.\\d{1,2})?$/'],
            'adjustmentReason' => 'required|string|max:500',
            'adjustmentDate' => 'required|date_format:Y-m-d',
        ]);
        $service->adjustConfirmedDebt($this->selectedDebtId, $this->adjustment, $this->adjustmentDate, $this->adjustmentReason, $actor);
        $this->reset('adjustment', 'adjustmentReason', 'adjustmentDate');
        session()->flash('debt_notice', 'Đã ghi nhận điều chỉnh.');
    }

    /**
     * Display a persisted decimal without converting it to float.
     * Keep fractional digits if legacy records contain non-whole VND.
     */
    public static function formatVnd(string $amount): string
    {
        $negative = str_starts_with($amount, '-');
        $parts = explode('.', ltrim($amount, '-'), 2);
        $integer = preg_replace('/\\B(?=(\\d{3})+(?!\\d))/', '.', $parts[0]);
        $fraction = rtrim($parts[1] ?? '', '0');

        return ($negative ? '−' : '').$integer.($fraction !== '' ? ','.$fraction : '').' ₫';
    }

    public function render()
    {
        $this->authorizeView();
        $query = DB::table('finance_debt_obligations')->orderByDesc('id');
        if (trim($this->search) !== '') {
            $term = '%'.addcslashes(trim($this->search), '%_\\').'%';
            $query->where(fn ($q) => $q->where('reference_number', 'like', $term)->orWhere('counterparty_name', 'like', $term));
        }
        if (in_array($this->direction, ['receivable', 'payable'], true)) {
            $query->where('direction', $this->direction);
        }
        if (in_array($this->status, ['draft', 'confirmed'], true)) {
            $query->where('status', $this->status);
        }
        $selected = $this->selectedDebtId === null ? null : DB::table('finance_debt_obligations')->find($this->selectedDebtId);
        $events = $selected ? DB::table('finance_debt_events')->where('obligation_id', $selected->id)->orderByDesc('id')->limit(100)->get() : collect();
        // Aggregate ALL events, not only the 100 latest displayed in the history.
        // SQL DECIMAL arithmetic preserves exact monetary values without PHP float rounding.
        $adjustmentTotal = $selected
            ? (string) DB::table('finance_debt_events')->where('obligation_id', $selected->id)
                ->selectRaw('COALESCE(SUM(signed_amount), 0) AS total')->value('total')
            : '0';
        $currentBalance = $selected
            ? (string) DB::table('finance_debt_obligations')
                ->where('finance_debt_obligations.id', $selected->id)
                ->selectRaw('(finance_debt_obligations.original_amount + (SELECT COALESCE(SUM(signed_amount), 0) FROM finance_debt_events WHERE obligation_id = finance_debt_obligations.id)) AS balance')
                ->value('balance')
            : '0';

        return view('Finance::livewire.debt-workspace', [
            'debts' => $query->paginate(10),
            'selected' => $selected,
            'events' => $events,
            'adjustmentTotal' => $adjustmentTotal,
            'currentBalance' => $currentBalance,
            'canManage' => (bool) auth('admin')->user()?->can('finance.debt.manage'),
        ]);
    }
}
