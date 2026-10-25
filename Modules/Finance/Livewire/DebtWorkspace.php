<?php

namespace Modules\Finance\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Modules\Finance\Services\FinanceDebtService;
use Modules\Finance\Services\FinanceDebtBalanceReader;
use Modules\Finance\Services\FinanceDebtPaymentReview;
use Modules\Finance\Services\FinanceDebtPaymentPostingService;
use Modules\Finance\Services\FinanceDebtPaymentEvidenceReader;
use Modules\Finance\Services\PartnerDebtDraftService;
use Modules\Partner\Models\Partner;

final class DebtWorkspace extends Component
{
    use WithPagination;

    public string $activeTab = 'invoices';
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
    public ?int $paymentReviewDebtId = null;
    public string $paymentAmount = '';
    public string $paymentDate = '';
    public string $paymentAccountId = '';
    public string $paymentReference = '';
    public bool $paymentPreviewed = false;
    public string $adjustment = '';
    public string $adjustmentReason = '';
    public string $adjustmentDate = '';

    public function mount(): void
    {
        $id = request()->query('debt');
        if (is_scalar($id) && ctype_digit((string) $id) && (int) $id > 0
            && DB::table('finance_debt_obligations')->where('id', (int) $id)->exists()) {
            $this->selectedDebtId = (int) $id;
            $this->activeTab = 'debts';
        }
    }

    public function setTab(string $tab): void
    {
        $this->authorizeView();
        abort_unless(in_array($tab, ['invoices', 'debts'], true), 422);
        $this->activeTab = $tab;
    }

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
        $this->authorizeMutation();
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

    public function openPaymentReview(int $id): void
    {
        $this->authorizeView();
        abort_unless(DB::table('finance_debt_obligations')->where('id', $id)->where('status', 'confirmed')->exists(), 404);
        $this->paymentReviewDebtId = $id;
        $this->reset('paymentAmount', 'paymentAccountId', 'paymentReference', 'paymentPreviewed');
        $this->paymentDate = now()->toDateString();
        $review = app(FinanceDebtPaymentReview::class)->inspect($id);
        // This is adjusted principal, NOT verified unpaid balance.
        $this->paymentAmount = $this->formatPaymentInput($review['remaining']);
    }

    private function formatPaymentInput(string $amount): string
    {
        $parts = explode('.', $amount, 2);
        $integer = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $parts[0]);
        $fraction = rtrim($parts[1] ?? '', '0');

        return $integer.($fraction !== '' ? ','.$fraction : '');
    }

    private function normalizePaymentInput(string $amount): string
    {
        $amount = trim($amount);
        if (! preg_match('/^(?:\d{1,3}(?:\.\d{3})+|\d{1,15})(?:,\d{1,2})?$/D', $amount)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'paymentAmount' => 'Chỉ nhập chữ số, ví dụ 40.000.000.',
            ]);
        }

        // Normalize Vietnamese grouping and optional decimal comma.
        return str_replace(',', '.', str_replace('.', '', $amount));
    }

    public function updatedPaymentAmount(): void
    {
        $this->paymentPreviewed = false;
        $this->resetValidation('paymentAmount');
        try {
            $this->paymentAmount = $this->formatPaymentInput($this->normalizePaymentInput($this->paymentAmount));
        } catch (\Illuminate\Validation\ValidationException $exception) {
            // Keep malformed user input visible for correction; preview validates it.
        }
    }
    public function updatedPaymentDate(): void { $this->paymentPreviewed = false; }
    public function updatedPaymentAccountId(): void { $this->paymentPreviewed = false; }
    public function updatedPaymentReference(): void { $this->paymentPreviewed = false; }

    public function previewPayment(): void
    {
        $this->authorizeView();
        abort_unless($this->paymentReviewDebtId !== null, 404);
        $review = app(FinanceDebtPaymentReview::class)->inspect($this->paymentReviewDebtId);
        $this->validate([
            'paymentAmount' => ['required', 'string', 'max:30'],
            'paymentDate' => ['required', 'date_format:Y-m-d'],
            'paymentAccountId' => ['required', 'integer', 'min:1'],
            'paymentReference' => ['required', 'string', 'max:100'],
        ]);
        abort_unless(collect($review['accounts'])->contains(fn ($a) => (string) $a['id'] === $this->paymentAccountId), 422);
        $normalizedAmount = $this->normalizePaymentInput($this->paymentAmount);
        $amount = $this->toPaymentCents($normalizedAmount);
        $principal = $this->toPaymentCents($review['remaining']);
        if ($amount <= 0 || $amount > $principal) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'paymentAmount' => 'Số tiền phải lớn hơn 0 và không vượt giá trị sau điều chỉnh.',
            ]);
        }
        // Preview is never proof of an unpaid balance or authorization to post.
        $this->paymentAmount = $this->formatPaymentInput($normalizedAmount);
        $this->paymentPreviewed = true;
    }

    public function confirmPayment(FinanceDebtPaymentPostingService $service): void
    {
        $actor = $this->authorizeMutation();
        abort_unless($this->paymentReviewDebtId !== null && $this->paymentPreviewed, 422);
        // Re-run input checks and server-side debt review immediately before posting.
        $this->previewPayment();
        $review = app(FinanceDebtPaymentReview::class)->inspect($this->paymentReviewDebtId);
        if (! $review['eligible']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'payment' => implode(' ', $review['reasons']),
            ]);
        }
        $result = $service->post(
            $this->paymentReviewDebtId, (int) $this->paymentAccountId,
            $this->normalizePaymentInput($this->paymentAmount), $this->paymentDate,
            $this->paymentReference, $actor
        );
        $this->paymentPreviewed = false;
        $this->paymentAmount = $this->formatPaymentInput($result['remaining']);
        session()->flash('debt_notice', 'Đã ghi nhận thanh toán: '.$result['number']
            .' · Còn nợ: '.self::formatVnd($result['remaining']));
        $this->closePaymentReview();
    }

    private function toPaymentCents(string $value): int
    {
        if (! preg_match('/^\d{1,15}(?:\.\d{1,2})?$/D', $value)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['paymentAmount' => 'Số tiền không hợp lệ.']);
        }
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    public function closePaymentReview(): void
    {
        $this->authorizeView();
        $this->paymentReviewDebtId = null;
        $this->reset('paymentAmount', 'paymentDate', 'paymentAccountId', 'paymentReference', 'paymentPreviewed');
    }

    public function closeDebt(): void
    {
        $this->authorizeView();
        $this->selectedDebtId = null;
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
        // Correlated aggregate is computed by SQL for the paginated rows only.
        // No per-row queries and no float conversion of persisted DECIMAL values.
        $query->select('finance_debt_obligations.*')
            ->selectRaw('(finance_debt_obligations.original_amount + (SELECT COALESCE(SUM(signed_amount), 0) FROM finance_debt_events WHERE obligation_id = finance_debt_obligations.id)) AS adjusted_balance');
        if (\Illuminate\Support\Facades\Schema::hasTable('finance_debt_settlement_mappings') && \Illuminate\Support\Facades\Schema::hasTable('finance_debt_allocation_ownerships')) {
            $query->selectRaw("(SELECT COALESCE(SUM(m.amount), 0) FROM finance_debt_settlement_mappings m JOIN finance_debt_allocation_ownerships o ON o.mapping_id = m.id JOIN finance_vouchers v ON v.id = m.voucher_id WHERE m.obligation_id = finance_debt_obligations.id AND m.operation = 'post' AND v.status = 'posted') AS paid_amount");
        }
        $selected = $this->selectedDebtId === null ? null : DB::table('finance_debt_obligations')->find($this->selectedDebtId);
        $events = $selected ? DB::table('finance_debt_events')->where('obligation_id', $selected->id)->orderByDesc('id')->limit(100)->get() : collect();
        // A single read-only projection owns the exact-decimal principal calculation.
        // It deliberately does not subtract payments or claim settlement.
        $principal = $selected ? app(FinanceDebtBalanceReader::class)->forObligation((int) $selected->id) : null;
        $adjustmentTotal = $principal['adjustment_total'] ?? '0';
        $currentBalance = $principal['adjusted_principal'] ?? '0';

        $paymentReview = $this->paymentReviewDebtId === null ? null
            : app(FinanceDebtPaymentReview::class)->inspect($this->paymentReviewDebtId);

        return view('Finance::livewire.debt-workspace', [
            'paymentEvidence' => $paymentReview === null ? null : app(FinanceDebtPaymentEvidenceReader::class)->forObligation($this->paymentReviewDebtId),
            'paymentReview' => $paymentReview,
            'debts' => $query->paginate(10),
            'selected' => $selected,
            'events' => $events,
            'adjustmentTotal' => $adjustmentTotal,
            'currentBalance' => $currentBalance,
            'canManage' => (bool) auth('admin')->user()?->can('finance.debt.manage'),
        ]);
    }
}
