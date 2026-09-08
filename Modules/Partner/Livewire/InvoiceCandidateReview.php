<?php

namespace Modules\Partner\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Partner\Models\PartnerSyncCandidate;
use Modules\Partner\Services\PartnerCandidateReviewService;

class InvoiceCandidateReview extends Component
{
    use WithPagination;

    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public string $status = 'pending';

    public string $search = '';

    public int|string $perPage = 10;

    public ?int $selectedCandidateId = null;

    public array $selectedFields = [];

    public ?string $notice = null;

    public ?string $error = null;

    public function mount(): void
    {
        $this->authorizePermission('view_partner');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedPerPage(): void
    {
        $perPage = (int) $this->perPage;
        $this->perPage = in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;
        $this->resetPage();
    }

    public function selectCandidate(int $id): void
    {
        $this->authorizePermission('view_partner');
        $candidate = $this->candidateQuery()->findOrFail($id);
        $this->selectedCandidateId = $candidate->getKey();
        $this->selectedFields = $this->defaultSelectedFields($candidate);
        $this->notice = null;
        $this->error = null;
    }

    public function createPartner(PartnerCandidateReviewService $review): void
    {
        $this->authorizePermission('create_partner');
        $candidate = $this->selectedCandidate();

        try {
            $partner = $review->createPartner($candidate);
            $this->notice = "Đã tạo Partner #{$partner->id} từ MST {$candidate->tax_code}.";
            $this->clearSelection(keepMessage: true);
        } catch (\Throwable $exception) {
            report($exception);
            $this->error = $exception->getMessage();
        }
    }

    public function confirmExisting(PartnerCandidateReviewService $review): void
    {
        $this->authorizePermission('edit_partner');
        $candidate = $this->selectedCandidate();

        try {
            $partner = $review->confirmExisting($candidate, $this->selectedFields);
            $this->notice = "Đã xác nhận candidate với Partner #{$partner->id}.";
            $this->clearSelection(keepMessage: true);
        } catch (\Throwable $exception) {
            report($exception);
            $this->error = $exception->getMessage();
        }
    }

    public function ignore(PartnerCandidateReviewService $review): void
    {
        $this->authorizePermission('edit_partner');
        $candidate = $this->selectedCandidate();

        try {
            $review->ignore($candidate);
            $this->notice = "Đã bỏ qua candidate MST {$candidate->tax_code}.";
            $this->clearSelection(keepMessage: true);
        } catch (\Throwable $exception) {
            report($exception);
            $this->error = $exception->getMessage();
        }
    }

    public function clearFilters(): void
    {
        $this->status = 'pending';
        $this->search = '';
        $this->perPage = 10;
        $this->resetPage();
        $this->clearSelection();
    }

    public function render()
    {
        $candidates = $this->candidateQuery()
            ->latest('last_seen_at')
            ->latest('id')
            ->paginate($this->normalizedPerPage());

        $selectedCandidate = $this->selectedCandidateId
            ? PartnerSyncCandidate::query()->with('matchedPartner')->find($this->selectedCandidateId)
            : null;

        return view('partner::livewire.invoice-candidate-review', [
            'candidates' => $candidates,
            'selectedCandidate' => $selectedCandidate,
            'statusOptions' => PartnerSyncCandidate::STATUSES,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ]);
    }

    private function candidateQuery()
    {
        return PartnerSyncCandidate::query()
            ->with('matchedPartner')
            ->where('source', 'invoices')
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when(trim($this->search) !== '', function ($query): void {
                $search = '%'.trim($this->search).'%';
                $query->where(function ($nested) use ($search): void {
                    $nested->where('tax_code', 'like', $search)
                        ->orWhere('name', 'like', $search)
                        ->orWhere('address', 'like', $search);
                });
            });
    }

    private function selectedCandidate(): PartnerSyncCandidate
    {
        if (! $this->selectedCandidateId) {
            throw new \RuntimeException('Vui lòng chọn một candidate.');
        }

        return PartnerSyncCandidate::query()
            ->where('source', 'invoices')
            ->with('matchedPartner')
            ->findOrFail($this->selectedCandidateId);
    }

    private function defaultSelectedFields(PartnerSyncCandidate $candidate): array
    {
        $partner = $candidate->matchedPartner;
        if (! $partner) {
            return [];
        }

        $selected = [];
        foreach (['name', 'address', 'email', 'phone'] as $field) {
            if (blank($partner->{$field}) && filled($candidate->{$field})) {
                $selected[] = $field;
            }
        }

        $incomingTypes = collect($candidate->partner_types ?? []);
        $currentTypes = collect($partner->partner_types ?? []);
        if ($incomingTypes->diff($currentTypes)->isNotEmpty()) {
            $selected[] = 'partner_types';
        }

        return $selected;
    }

    private function normalizedPerPage(): int
    {
        $perPage = (int) $this->perPage;

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;
    }

    private function clearSelection(bool $keepMessage = false): void
    {
        $this->selectedCandidateId = null;
        $this->selectedFields = [];

        if (! $keepMessage) {
            $this->notice = null;
            $this->error = null;
        }
    }

    private function authorizePermission(string $permission): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can($permission), 403);
    }
}
