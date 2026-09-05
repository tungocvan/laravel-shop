<?php

namespace Modules\Pharma\Livewire\DrugBidAward;

use Livewire\Component;
use Modules\Partner\Models\Partner;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\DrugBidAwardAllocation;
use Modules\Pharma\Models\DrugBidAwardContract;
use Modules\Pharma\Services\DrugBidAwardAllocationService;
use Modules\Pharma\Services\DrugBidAwardAllocationSummaryService;
use Modules\Pharma\Services\DrugBidAwardContractService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AllocationWorkspace extends Component
{
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public int $awardId;

    public string $search = '';

    public string $filterStatus = '';

    public int $perPage = 10;

    public int $page = 1;

    public array $selectedIds = [];

    public bool $selectPage = false;

    public ?int $editingAllocationId = null;

    public string $partnerId = '';

    public string $allocatedQuantity = '';

    public string $effectiveFrom = '';

    public string $effectiveUntil = '';

    public string $notes = '';

    public string $allocationCancelReason = '';

    public ?int $contractAllocationId = null;

    public ?int $editingContractId = null;

    public string $contractNumber = '';

    public string $contractDate = '';

    public string $contractQuantity = '';

    public string $contractValue = '';

    public string $contractStartDate = '';

    public string $contractEndDate = '';

    public string $contractStatus = DrugBidAwardContract::STATUS_DRAFT;

    public string $contractNotes = '';

    public string $contractCancelReason = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'perPage' => ['except' => 10],
        'page' => ['except' => 1],
    ];

    public function mount(int $awardId): void
    {
        $this->authorizePermission('view_pharma_allocations');
        DrugBidAward::query()->findOrFail($awardId);
        $this->awardId = $awardId;
        $this->perPage = $this->normalizePerPage($this->perPage);
    }

    public function updatedSearch(): void
    {
        $this->resetPageState();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPageState();
    }

    public function updatedPerPage(mixed $value): void
    {
        $this->perPage = $this->normalizePerPage($value);
        $this->resetPageState();
    }

    public function gotoPage(int $page): void
    {
        $this->page = max(1, $page);
        $this->clearSelection();
    }

    public function updatedSelectPage(bool $value): void
    {
        $this->selectedIds = $value ? $this->currentPageIds() : [];
    }

    public function updatedSelectedIds(): void
    {
        $pageIds = $this->currentPageIds();
        $this->selectedIds = array_values(array_intersect(array_map('strval', $this->selectedIds), $pageIds));
        $this->selectPage = $pageIds !== [] && count($this->selectedIds) === count($pageIds);
    }

    public function saveAllocation(DrugBidAwardAllocationService $service): void
    {
        $this->authorizePermission('manage_pharma_allocations');
        $data = $this->validate([
            'partnerId' => ['required', 'integer'],
            'allocatedQuantity' => ['required', 'numeric', 'gt:0'],
            'effectiveFrom' => ['nullable', 'date'],
            'effectiveUntil' => ['nullable', 'date', 'after_or_equal:effectiveFrom'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $service->save($this->awardId, $this->editingAllocationId, [
            'partner_id' => $data['partnerId'],
            'allocated_quantity' => $data['allocatedQuantity'],
            'effective_from' => $data['effectiveFrom'] ?: null,
            'effective_until' => $data['effectiveUntil'] ?: null,
            'notes' => $data['notes'] ?: null,
        ], auth('admin')->id());

        $this->resetAllocationForm();
        session()->flash('success', 'Đã lưu phân bổ bệnh viện.');
    }

    public function editAllocation(int $id): void
    {
        $this->authorizePermission('manage_pharma_allocations');
        $allocation = DrugBidAwardAllocation::query()->where('drug_bid_award_id', $this->awardId)->findOrFail($id);
        $this->editingAllocationId = $allocation->id;
        $this->partnerId = (string) $allocation->partner_id;
        $this->allocatedQuantity = (string) $allocation->allocated_quantity;
        $this->effectiveFrom = $allocation->effective_from?->format('Y-m-d') ?? '';
        $this->effectiveUntil = $allocation->effective_until?->format('Y-m-d') ?? '';
        $this->notes = (string) ($allocation->notes ?? '');
    }

    public function cancelAllocation(int $id, DrugBidAwardAllocationService $service): void
    {
        $this->authorizePermission('cancel_pharma_allocations');
        $this->validate(['allocationCancelReason' => ['required', 'string', 'min:3', 'max:1000']]);
        $service->cancel($this->awardId, $id, $this->allocationCancelReason, auth('admin')->id());
        $this->allocationCancelReason = '';
        session()->flash('success', 'Đã hủy phân bổ.');
    }

    public function openContractForm(int $allocationId): void
    {
        $this->authorizePermission('manage_pharma_contracts');
        DrugBidAwardAllocation::query()->where('drug_bid_award_id', $this->awardId)->findOrFail($allocationId);
        $this->resetContractForm();
        $this->contractAllocationId = $allocationId;
    }

    public function editContract(int $allocationId, int $contractId): void
    {
        $this->authorizePermission('manage_pharma_contracts');
        $contract = DrugBidAwardContract::query()->where('drug_bid_award_allocation_id', $allocationId)->findOrFail($contractId);
        $this->contractAllocationId = $allocationId;
        $this->editingContractId = $contract->id;
        $this->contractNumber = $contract->contract_number;
        $this->contractDate = $contract->contract_date?->format('Y-m-d') ?? '';
        $this->contractQuantity = (string) $contract->contract_quantity;
        $this->contractValue = (string) ($contract->contract_value ?? '');
        $this->contractStartDate = $contract->start_date?->format('Y-m-d') ?? '';
        $this->contractEndDate = $contract->end_date?->format('Y-m-d') ?? '';
        $this->contractStatus = $contract->status;
        $this->contractNotes = (string) ($contract->notes ?? '');
    }

    public function saveContract(DrugBidAwardContractService $service): void
    {
        $this->authorizePermission('manage_pharma_contracts');
        $data = $this->validate([
            'contractAllocationId' => ['required', 'integer'],
            'contractNumber' => ['required', 'string', 'max:255'],
            'contractDate' => ['nullable', 'date'],
            'contractQuantity' => ['required', 'numeric', 'gt:0'],
            'contractValue' => ['nullable', 'numeric', 'gte:0'],
            'contractStartDate' => ['nullable', 'date'],
            'contractEndDate' => ['nullable', 'date', 'after_or_equal:contractStartDate'],
            'contractStatus' => ['required', 'in:draft,signed,in_progress,completed'],
            'contractNotes' => ['nullable', 'string', 'max:3000'],
        ]);

        $service->save((int) $data['contractAllocationId'], $this->editingContractId, [
            'contract_number' => $data['contractNumber'],
            'contract_date' => $data['contractDate'] ?: null,
            'contract_quantity' => $data['contractQuantity'],
            'contract_value' => $data['contractValue'] === '' ? null : $data['contractValue'],
            'start_date' => $data['contractStartDate'] ?: null,
            'end_date' => $data['contractEndDate'] ?: null,
            'status' => $data['contractStatus'],
            'contract_notes' => $data['contractNotes'] ?: null,
        ], auth('admin')->id());

        $this->resetContractForm();
        session()->flash('success', 'Đã lưu hợp đồng bệnh viện.');
    }

    public function cancelContract(int $allocationId, int $contractId, DrugBidAwardContractService $service): void
    {
        $this->authorizePermission('cancel_pharma_contracts');
        $this->validate(['contractCancelReason' => ['required', 'string', 'min:3', 'max:1000']]);
        $service->cancel($allocationId, $contractId, $this->contractCancelReason, auth('admin')->id());
        $this->contractCancelReason = '';
        session()->flash('success', 'Đã hủy hợp đồng.');
    }

    public function exportAllocations(): StreamedResponse
    {
        $this->authorizePermission('view_pharma_allocations');
        $rows = $this->exportAllocationQuery()->with('partner')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['TBMT', 'Lo', 'Thuoc', 'Chu dau tu TBMT', 'Benh vien', 'So luong phan bo', 'Trang thai', 'Tu ngay', 'Den ngay']);
            $award = DrugBidAward::query()->findOrFail($this->awardId);
            foreach ($rows as $row) {
                fputcsv($out, [$award->bidding_notice_code, $award->lot_no, $award->medicine_name, $award->investor_name, $row->partner?->name, $row->allocated_quantity, $row->status, $row->effective_from?->format('Y-m-d'), $row->effective_until?->format('Y-m-d')]);
            }
            fclose($out);
        }, "pharma-award-{$this->awardId}-allocations.csv");
    }

    public function exportContracts(): StreamedResponse
    {
        $this->authorizePermission('view_pharma_contracts');
        $allocationIds = $this->exportAllocationQuery()->pluck('id');
        $rows = DrugBidAwardContract::query()
            ->with('allocation.partner')
            ->whereIn('drug_bid_award_allocation_id', $allocationIds)
            ->orderBy('id')
            ->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Benh vien', 'So hop dong', 'Ngay hop dong', 'So luong', 'Gia tri', 'Tu ngay', 'Den ngay', 'Trang thai']);
            foreach ($rows as $row) {
                fputcsv($out, [$row->allocation?->partner?->name, $row->contract_number, $row->contract_date?->format('Y-m-d'), $row->contract_quantity, $row->contract_value, $row->start_date?->format('Y-m-d'), $row->end_date?->format('Y-m-d'), $row->status]);
            }
            fclose($out);
        }, "pharma-award-{$this->awardId}-contracts.csv");
    }

    public function render(DrugBidAwardAllocationSummaryService $summaryService)
    {
        $award = DrugBidAward::query()->findOrFail($this->awardId);
        $allocations = $this->filteredQuery()
            ->with(['partner', 'contracts'])
            ->paginate($this->perPage, ['*'], 'page', $this->page);
        $summary = $summaryService->forAward($award);

        return view('Pharma::livewire.drug-bid-award.allocation-workspace', [
            'award' => $award,
            'allocations' => $allocations,
            'summary' => $summary,
            'partners' => Partner::query()
                ->where('legal_type', 'hospital')
                ->where('status', 'active')
                ->orderBy('name')
                ->limit(500)
                ->get(['id', 'name', 'tax_code']),
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ]);
    }

    private function filteredQuery()
    {
        return DrugBidAwardAllocation::query()
            ->where('drug_bid_award_id', $this->awardId)
            ->when($this->filterStatus !== '', fn ($query) => $query->where('status', $this->filterStatus))
            ->when(
                $this->search !== '',
                fn ($query) => $query->whereHas(
                    'partner',
                    fn ($partnerQuery) => $partnerQuery
                        ->where('name', 'like', '%'.trim($this->search).'%')
                        ->orWhere('tax_code', 'like', '%'.trim($this->search).'%')
                )
            )
            ->orderByDesc('id');
    }

    private function exportAllocationQuery()
    {
        $query = $this->filteredQuery();
        $this->updatedSelectedIds();
        if ($this->selectedIds !== []) {
            $query->whereIn('id', array_map('intval', $this->selectedIds));
        }

        return $query;
    }

    private function currentPageIds(): array
    {
        return $this->filteredQuery()
            ->paginate($this->perPage, ['id'], 'page', $this->page)
            ->getCollection()
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    private function resetAllocationForm(): void
    {
        $this->reset(['editingAllocationId', 'partnerId', 'allocatedQuantity', 'effectiveFrom', 'effectiveUntil', 'notes']);
        $this->resetValidation();
    }

    private function resetContractForm(): void
    {
        $this->reset(['contractAllocationId', 'editingContractId', 'contractNumber', 'contractDate', 'contractQuantity', 'contractValue', 'contractStartDate', 'contractEndDate', 'contractNotes']);
        $this->contractStatus = DrugBidAwardContract::STATUS_DRAFT;
        $this->resetValidation();
    }

    private function resetPageState(): void
    {
        $this->page = 1;
        $this->clearSelection();
    }

    private function clearSelection(): void
    {
        $this->selectedIds = [];
        $this->selectPage = false;
    }

    private function normalizePerPage(mixed $value): int
    {
        $value = (int) $value;

        return in_array($value, self::PER_PAGE_OPTIONS, true) ? $value : 10;
    }

    private function authorizePermission(string $permission): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can($permission), 403);
    }
}
