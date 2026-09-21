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
use Modules\Pharma\Services\DrugBidAwardDistributionScopeService;
use Rap2hpoutre\FastExcel\FastExcel;
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
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);
        $service->save($this->awardId, $this->editingAllocationId, [
            'partner_id' => $data['partnerId'],
            'allocated_quantity' => $data['allocatedQuantity'],
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

    public function toggleAllocationPause(int $id, bool $paused, DrugBidAwardAllocationService $service): void
    {
        $allocation = DrugBidAwardAllocation::query()->where('drug_bid_award_id', $this->awardId)->findOrFail($id);

        if ($paused) {
            $this->authorizePermission('cancel_pharma_allocations');
            $service->cancel($this->awardId, $allocation->id, 'Tạm ngưng từ giao diện phân bổ', auth('admin')->id());
            session()->flash('success', 'Đã tạm ngưng phân bổ.');

            return;
        }

        $this->authorizePermission('manage_pharma_allocations');
        $service->save($this->awardId, $allocation->id, [
            'partner_id' => $allocation->partner_id,
            'allocated_quantity' => $allocation->allocated_quantity,
            'effective_from' => $allocation->effective_from?->format('Y-m-d'),
            'effective_until' => $allocation->effective_until?->format('Y-m-d'),
            'notes' => $allocation->notes,
        ], auth('admin')->id());
        session()->flash('success', 'Đã tiếp tục phân bổ.');
    }

    public function openContractForm(int $allocationId): void
    {
        $this->authorizePermission('manage_pharma_contracts');
        DrugBidAwardAllocation::query()->where('drug_bid_award_id', $this->awardId)->findOrFail($allocationId);

        if ($this->contractAllocationId === $allocationId && $this->editingContractId === null) {
            $this->resetContractForm();

            return;
        }

        $this->resetContractForm();
        $this->contractAllocationId = $allocationId;
    }

    public function closeContractForm(): void
    {
        $this->resetContractForm();
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

    public function exportAllocations(): StreamedResponse
    {
        $this->authorizePermission('view_pharma_allocations');
        $award = DrugBidAward::query()->findOrFail($this->awardId);
        $rows = $this->exportAllocationQuery()->with(['partner', 'contracts'])->get();
        $distributionScope = app(DrugBidAwardDistributionScopeService::class)->findForAward($award);

        return (new FastExcel($rows))->download("pharma-award-{$this->awardId}-allocations.xlsx", function (DrugBidAwardAllocation $row) use ($award, $distributionScope) {
            $committed = $row->contracts
                ->whereIn('status', DrugBidAwardContract::COMMITTED_STATUSES)
                ->sum(fn ($contract) => (float) $contract->contract_quantity);

            return [
                'Mã TBMT' => $award->bidding_notice_code,
                'Số quyết định' => $award->decision_number,
                'Ngày quyết định' => $award->decision_date?->format('d/m/Y'),
                'Số lô' => $award->lot_no,
                'Tên lô' => $award->lot_name,
                'Tên thuốc' => $award->medicine_name,
                'Hoạt chất' => $award->active_ingredient,
                'Nồng độ/Hàm lượng' => $award->concentration,
                'Dạng bào chế' => $award->dosage_form,
                'Đường dùng' => $award->route,
                'Đơn vị tính' => $award->unit,
                'Số lượng trúng' => (float) $award->quantity,
                'Đơn giá trúng' => (float) ($award->winning_price ?? $award->unit_price ?? 0),
                'Nhà thầu trúng' => $award->winning_company_name,
                'Mã nhà thầu' => $award->contractor_code,
                'Chủ đầu tư TBMT' => $award->investor_name,
                'Mã chủ đầu tư' => $award->investor_code,
                'Tỉnh/Thành trúng thầu' => $distributionScope?->province_code,
                'Cơ sở KCB nhận phân bổ' => $row->partner?->name,
                'MST cơ sở KCB' => $row->partner?->tax_code,
                'Địa chỉ cơ sở KCB' => $row->partner?->address,
                'Số lượng phân bổ' => (float) $row->allocated_quantity,
                'Đã cam kết hợp đồng' => $committed,
                'Còn lại chưa cam kết' => (float) $row->allocated_quantity - $committed,
                'Hiệu lực từ' => $row->effective_from?->format('d/m/Y'),
                'Hiệu lực đến' => $row->effective_until?->format('d/m/Y'),
                'Trạng thái phân bổ' => $row->status,
                'Ghi chú phân bổ' => $row->notes,
            ];
        });
    }

    public function exportContracts(): StreamedResponse
    {
        $this->authorizePermission('view_pharma_contracts');
        $award = DrugBidAward::query()->findOrFail($this->awardId);
        $allocationIds = $this->exportAllocationQuery()->pluck('id');
        $distributionScope = app(DrugBidAwardDistributionScopeService::class)->findForAward($award);
        $rows = DrugBidAwardContract::query()
            ->with('allocation.partner')
            ->whereIn('drug_bid_award_allocation_id', $allocationIds)
            ->orderBy('id')
            ->get();

        return (new FastExcel($rows))->download("pharma-award-{$this->awardId}-contracts.xlsx", function (DrugBidAwardContract $row) use ($award, $distributionScope) {
            $allocation = $row->allocation;

            return [
                'Mã TBMT' => $award->bidding_notice_code,
                'Số quyết định' => $award->decision_number,
                'Ngày quyết định' => $award->decision_date?->format('d/m/Y'),
                'Số lô' => $award->lot_no,
                'Tên lô' => $award->lot_name,
                'Tên thuốc' => $award->medicine_name,
                'Hoạt chất' => $award->active_ingredient,
                'Nồng độ/Hàm lượng' => $award->concentration,
                'Dạng bào chế' => $award->dosage_form,
                'Đường dùng' => $award->route,
                'Đơn vị tính' => $award->unit,
                'Số lượng trúng' => (float) $award->quantity,
                'Đơn giá trúng' => (float) ($award->winning_price ?? $award->unit_price ?? 0),
                'Nhà thầu trúng' => $award->winning_company_name,
                'Mã nhà thầu' => $award->contractor_code,
                'Chủ đầu tư TBMT' => $award->investor_name,
                'Tỉnh/Thành trúng thầu' => $distributionScope?->province_code,
                'Cơ sở KCB' => $allocation?->partner?->name,
                'MST cơ sở KCB' => $allocation?->partner?->tax_code,
                'Địa chỉ cơ sở KCB' => $allocation?->partner?->address,
                'Số lượng phân bổ' => (float) ($allocation?->allocated_quantity ?? 0),
                'Hiệu lực phân bổ từ' => $allocation?->effective_from?->format('d/m/Y'),
                'Hiệu lực phân bổ đến' => $allocation?->effective_until?->format('d/m/Y'),
                'Số hợp đồng' => $row->contract_number,
                'Ngày ký hợp đồng' => $row->contract_date?->format('d/m/Y'),
                'Số lượng hợp đồng' => (float) $row->contract_quantity,
                'Giá trị hợp đồng' => $row->contract_value === null ? null : (float) $row->contract_value,
                'Hợp đồng từ ngày' => $row->start_date?->format('d/m/Y'),
                'Hợp đồng đến ngày' => $row->end_date?->format('d/m/Y'),
                'Trạng thái hợp đồng' => $row->status,
                'Ghi chú hợp đồng' => $row->notes,
            ];
        });
    }

    public function render(DrugBidAwardAllocationSummaryService $summaryService)
    {
        $award = DrugBidAward::query()->findOrFail($this->awardId);

        $distributionScope = app(DrugBidAwardDistributionScopeService::class)->findForAward($award);
        $allowedPartnerIds = $distributionScope?->partners->pluck('id')->all() ?? [];

        return view('Pharma::livewire.drug-bid-award.allocation-workspace', [
            'award' => $award,
            'allocations' => $this->filteredQuery()->with(['partner', 'contracts'])->paginate($this->perPage, ['*'], 'page', $this->page),
            'summary' => $summaryService->forAward($award),
            'partners' => Partner::query()
                ->where('legal_type', 'hospital')
                ->where('status', 'active')
                ->whereIn('id', $allowedPartnerIds)
                ->orderBy('name')
                ->limit(500)
                ->get(['id', 'name', 'tax_code']),
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'distributionScope' => $distributionScope,
        ]);
    }

    private function filteredQuery()
    {
        return DrugBidAwardAllocation::query()->where('drug_bid_award_id', $this->awardId)
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
        $this->reset(['editingAllocationId', 'partnerId', 'allocatedQuantity', 'notes']);
        $this->dispatch('filters-reset');
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
