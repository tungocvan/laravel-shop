<?php

namespace Modules\Finance\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
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
        abort_unless(auth('admin')->user()?->can('finance.voucher.create'), 403);
        $id = auth('admin')->id();
        abort_unless($id !== null, 403);
        return (int) $id;
    }

    public function openCreate(): void
    {
        $this->authorizeMutation();
        $this->creating = true;
    }

    public function updatedDraftDirection(): void
    {
        $this->partnerId = '';
        $this->partnerSearch = '';
    }

    public function choosePartner(int $id): void
    {
        $this->authorizeMutation();
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

        $partners = collect();
        if ($this->creating && (bool) config('modules.registry.Partner.enabled', false)) {
            $role = $this->draftDirection === 'payable' ? 'supplier' : 'customer';
            $partnerQuery = Partner::query()->where('status', 'active')->withPartnerType($role);
            if (trim($this->partnerSearch) !== '') {
                $term = '%'.addcslashes(trim($this->partnerSearch), '%_\\\\').'%';
                $partnerQuery->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('tax_code', 'like', $term));
            }
            $partners = $partnerQuery->orderBy('name')->limit(20)->get(['id', 'name', 'tax_code']);
        }

        return view('Finance::livewire.debt-workspace', [
            'partners' => $partners,
            'debts' => $query->paginate(10),
            'selected' => $selected,
            'events' => $events,
            'canManage' => (bool) auth('admin')->user()?->can('finance.voucher.create'),
        ]);
    }
}
