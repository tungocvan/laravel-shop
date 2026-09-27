<?php

namespace Modules\Pharma\Livewire\DrugBidAward;

use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Modules\Partner\Models\Partner;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\DrugBidAwardAllocation;
use Modules\Pharma\Models\DrugBidAwardContract;
use Modules\Pharma\Services\DrugBidAwardAllocationService;
use Modules\Pharma\Services\DrugBidAwardAllocationSummaryService;
use Modules\Pharma\Services\DrugBidAwardContractService;
use Modules\Pharma\Services\DrugBidAwardDistributionScopeService;
use Modules\System\Services\Cloud\GoogleDriveConnectionService;
use Throwable;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AllocationWorkspace extends Component
{
    use WithFileUploads;
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public int $awardId;

    public string $search = '';

    public string $filterStatus = '';

    public int $perPage = 10;

    public int $page = 1;

    public array $selectedIds = [];

    public bool $selectPage = false;

    public ?int $editingAllocationId = null;

    public string $editingPartnerName = '';

    public string $partnerId = '';

    public string $allocatedQuantity = '';

    public string $effectiveFrom = '';

    public string $effectiveUntil = '';

    public string $notes = '';

    public ?int $contractAllocationId = null;

    public string $contractPartnerName = '';

    public ?int $editingContractId = null;

    public string $contractNumber = '';

    public string $contractDate = '';

    public $signedContractFile = null;

    public array $contractStorageTargets = [];

    public bool $googleDriveConnected = false;

    public string $contractValue = '';

    public ?int $returnToContractId = null;

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
        $this->googleDriveConnected = $this->driveConnected();
        $this->contractStorageTargets = [$this->googleDriveConnected ? 'google_drive' : 'local'];
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
        if ($this->editingAllocationId) {
            $existing = DrugBidAwardAllocation::query()->where('drug_bid_award_id', $this->awardId)->findOrFail($this->editingAllocationId);
            $this->partnerId = (string) $existing->partner_id;
            $this->allocatedQuantity = str_replace(['.', ','], ['', '.'], trim($this->allocatedQuantity));
        }
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
        $allocation = DrugBidAwardAllocation::query()->with('partner')->where('drug_bid_award_id', $this->awardId)->findOrFail($id);
        $this->editingAllocationId = $allocation->id;
        $this->partnerId = (string) $allocation->partner_id;
        $this->editingPartnerName = (string) ($allocation->partner?->name ?? '');
        $this->allocatedQuantity = $this->formatQuantityInput($allocation->allocated_quantity);
        $this->effectiveFrom = $allocation->effective_from?->format('Y-m-d') ?? '';
        $this->effectiveUntil = $allocation->effective_until?->format('Y-m-d') ?? '';
        $this->notes = (string) ($allocation->notes ?? '');
    }

    public function cancelAllocationEdit(): void
    {
        $this->resetAllocationForm();
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
        $allocation = DrugBidAwardAllocation::query()->with('partner')->where('drug_bid_award_id', $this->awardId)->findOrFail($allocationId);

        $this->resetContractForm();
        $this->contractAllocationId = $allocationId;
        $this->contractPartnerName = (string) ($allocation->partner?->name ?? '');
        $this->contractStorageTargets = [$this->googleDriveConnected ? 'google_drive' : 'local'];
    }

    public function closeContractForm(): void
    {
        $this->resetContractForm();
    }

    public function editContract(int $allocationId, int $contractId): void
    {
        $this->authorizePermission('manage_pharma_contracts');
        $contract = DrugBidAwardContract::query()->with('allocation.partner')->where('drug_bid_award_allocation_id', $allocationId)->findOrFail($contractId);
        $this->contractAllocationId = $allocationId;
        $this->contractPartnerName = (string) ($contract->allocation?->partner?->name ?? '');
        $this->editingContractId = $contract->id;
        $this->contractNumber = $contract->contract_number;
        $this->contractDate = $contract->contract_date?->format('Y-m-d') ?? '';
                $this->contractValue = $this->formatMoneyInput($contract->contract_value);
        $this->contractStartDate = $contract->start_date?->format('Y-m-d') ?? '';
        $this->contractEndDate = $contract->end_date?->format('Y-m-d') ?? '';
        $this->contractStatus = $contract->status;
        $this->contractNotes = (string) ($contract->notes ?? '');
        $this->contractStorageTargets = array_values(array_filter([
            $contract->signed_file_path ? 'local' : null,
            $contract->signed_file_remote_id ? 'google_drive' : null,
        ])) ?: [$this->googleDriveConnected ? 'google_drive' : 'local'];
    }

    public function createAnotherContract(): void
    {
        $allocationId = $this->contractAllocationId;
        $currentContractId = $this->editingContractId;
        if (! $allocationId) {
            return;
        }

        $this->openContractForm($allocationId);
        $this->returnToContractId = $currentContractId;
    }

    public function cancelCreateAnotherContract(): void
    {
        if ($this->contractAllocationId && $this->returnToContractId) {
            $contractId = $this->returnToContractId;
            $allocationId = $this->contractAllocationId;
            $this->returnToContractId = null;
            $this->editContract($allocationId, $contractId);

            return;
        }

        $this->closeContractForm();
    }

    public function saveContract(DrugBidAwardContractService $service): void
    {
        $this->authorizePermission('manage_pharma_contracts');
        $this->contractValue = $this->normalizeMoneyInput($this->contractValue);
        $data = $this->validate([
            'contractAllocationId' => ['required', 'integer'],
            'contractNumber' => ['required', 'string', 'max:255'],
            'contractDate' => ['nullable', 'date'],
            'signedContractFile' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:20480'],
            'contractStorageTargets' => ['required', 'array', 'min:1'],
            'contractStorageTargets.*' => ['in:local,google_drive'],
            'contractValue' => ['nullable', 'numeric', 'gte:0'],
            'contractStartDate' => ['nullable', 'date'],
            'contractEndDate' => ['nullable', 'date', 'after_or_equal:contractStartDate'],
            'contractStatus' => ['required', 'in:draft,signed,in_progress,completed'],
            'contractNotes' => ['nullable', 'string', 'max:3000'],
        ]);
        $contract = $service->save((int) $data['contractAllocationId'], $this->editingContractId, [
            'contract_number' => $data['contractNumber'],
            'contract_date' => $data['contractDate'] ?: null,
            'contract_quantity' => $this->contractCommittedQuantity((int) $data['contractAllocationId']),
            'contract_value' => $data['contractValue'] === '' ? null : $data['contractValue'],
            'start_date' => $data['contractStartDate'] ?: null,
            'end_date' => $data['contractEndDate'] ?: null,
            'status' => $data['contractStatus'],
            'contract_notes' => $data['contractNotes'] ?: null,
        ], auth('admin')->id());

        if (in_array('google_drive', $data['contractStorageTargets'], true) && ! $this->googleDriveConnected) {
            $this->addError('contractStorageTargets', 'Google Drive chưa được kết nối.');

            return;
        }

        if ($this->signedContractFile) {
            $this->storeSignedContractFile($contract, $data['contractStorageTargets']);
        }

        $this->editContract((int) $data['contractAllocationId'], $contract->id);
        session()->flash('success', 'Đã lưu hợp đồng bệnh viện. Bạn có thể tiếp tục chỉnh sửa hoặc thêm hợp đồng khác.');
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

    private function contractCommittedQuantity(int $allocationId): float
    {
        $allocation = DrugBidAwardAllocation::query()->findOrFail($allocationId);
        $otherCommitted = (float) DrugBidAwardContract::query()
            ->where('drug_bid_award_allocation_id', $allocationId)
            ->whereIn('status', DrugBidAwardContract::COMMITTED_STATUSES)
            ->when($this->editingContractId, fn ($query) => $query->where('id', '!=', $this->editingContractId))
            ->sum('contract_quantity');

        return max(0.0001, round((float) $allocation->allocated_quantity - $otherCommitted, 4));
    }

    private function storeSignedContractFile(DrugBidAwardContract $contract, array $targets): void
    {
        $award = DrugBidAward::query()->findOrFail($this->awardId);
        $safeTbmt = $this->safeStorageSegment((string) ($award->bidding_notice_code ?: 'TBMT-'.$award->id));
        $safeContract = $this->safeStorageSegment($contract->contract_number);
        $directory = 'Laravel-Backup/Pharma/DrugBidAwards/'.$safeTbmt.'/Contracts/'.$contract->drug_bid_award_allocation_id.'/'.$safeContract;
        $originalName = $this->signedContractFile->getClientOriginalName();
        $storedName = now()->format('YmdHis').'-'.$this->safeStorageSegment($originalName);
        $path = $this->signedContractFile->storeAs($directory, $storedName, 'local');

        $keepLocal = in_array('local', $targets, true);
        $useDrive = in_array('google_drive', $targets, true);
        $remoteId = null;

        if ($useDrive) {
            $uploaded = app(GoogleDriveConnectionService::class)->uploadApplicationFile(
                Storage::disk('local')->path($path),
                ['Pharma', 'DrugBidAwards', $safeTbmt, 'Contracts', (string) $contract->drug_bid_award_allocation_id, $safeContract],
                $storedName,
                $this->signedContractFile->getMimeType() ?: 'application/octet-stream',
            );
            $remoteId = $uploaded['id'];
        }

        $contract->update([
            'signed_file_disk' => $keepLocal ? 'local' : 'google_drive',
            'signed_file_path' => $keepLocal ? $path : null,
            'signed_file_name' => $originalName,
            'signed_file_mime' => $this->signedContractFile->getMimeType(),
            'signed_file_size' => $this->signedContractFile->getSize(),
            'signed_file_remote_id' => $remoteId,
        ]);

        if (! $keepLocal) {
            Storage::disk('local')->delete($path);
        }
    }

    public function backupSignedContractToDrive(int $contractId, GoogleDriveConnectionService $drive): void
    {
        $this->authorizePermission('manage_pharma_contracts');
        $contract = $this->signedContractForAward($contractId);
        abort_unless($contract->signed_file_path && Storage::disk($contract->signed_file_disk ?: 'local')->exists($contract->signed_file_path), 404);

        try {
            if ($contract->signed_file_remote_id) {
                $drive->deleteApplicationFile($contract->signed_file_remote_id, null);
            }

            $uploaded = $drive->uploadApplicationFile(
                Storage::disk($contract->signed_file_disk ?: 'local')->path($contract->signed_file_path),
                $this->contractDriveFolders($contract),
                basename($contract->signed_file_path),
                $contract->signed_file_mime ?: 'application/octet-stream',
            );

            $contract->update(['signed_file_remote_id' => $uploaded['id']]);
            session()->flash('success', 'Đã backup file hợp đồng lên Google Drive.');
        } catch (Throwable $e) {
            report($e);
            session()->flash('error', 'Backup Google Drive thất bại. Hãy kiểm tra kết nối Drive trong System.');
        }
    }

    public function restoreSignedContractFromDrive(int $contractId, GoogleDriveConnectionService $drive): void
    {
        $this->authorizePermission('manage_pharma_contracts');
        $contract = $this->signedContractForAward($contractId);
        abort_unless((string) $contract->signed_file_remote_id !== '', 404);

        try {
            $path = $contract->signed_file_path ?: $this->contractLocalPath($contract);
            $metadata = $drive->downloadApplicationFile(
                (string) $contract->signed_file_remote_id,
                Storage::disk('local')->path($path),
            );

            $contract->update([
                'signed_file_disk' => 'local',
                'signed_file_path' => $path,
                'signed_file_name' => $contract->signed_file_name ?: $metadata['name'],
                'signed_file_mime' => $metadata['mime_type'] ?: $contract->signed_file_mime,
                'signed_file_size' => $metadata['size'],
            ]);
            session()->flash('success', 'Đã khôi phục file hợp đồng từ Google Drive về local.');
        } catch (Throwable $e) {
            report($e);
            session()->flash('error', 'Khôi phục từ Google Drive thất bại. Hãy kiểm tra kết nối Drive trong System.');
        }
    }

    public function deleteSignedContractLocal(int $contractId): void
    {
        $this->authorizePermission('manage_pharma_contracts');
        $contract = $this->signedContractForAward($contractId);
        if ($contract->signed_file_path) {
            Storage::disk('local')->delete($contract->signed_file_path);
        }
        $contract->update(['signed_file_path' => null, 'signed_file_disk' => $contract->signed_file_remote_id ? 'google_drive' : null]);
        $this->editContract($contract->drug_bid_award_allocation_id, $contract->id);
        session()->flash('success', 'Đã xóa bản Local của file hợp đồng.');
    }

    public function deleteSignedContractDrive(int $contractId, GoogleDriveConnectionService $drive): void
    {
        $this->authorizePermission('manage_pharma_contracts');
        $contract = $this->signedContractForAward($contractId);
        if ($contract->signed_file_remote_id) {
            $drive->deleteApplicationFile($contract->signed_file_remote_id, null);
        }
        $contract->update(['signed_file_remote_id' => null, 'signed_file_disk' => $contract->signed_file_path ? 'local' : null]);
        $this->editContract($contract->drug_bid_award_allocation_id, $contract->id);
        session()->flash('success', 'Đã xóa bản Google Drive của file hợp đồng.');
    }

    public function downloadSignedContract(int $contractId)
    {
        $this->authorizePermission('view_pharma_contracts');
        $contract = DrugBidAwardContract::query()
            ->whereHas('allocation', fn ($query) => $query->where('drug_bid_award_id', $this->awardId))
            ->findOrFail($contractId);
        abort_unless($contract->signed_file_path && Storage::disk($contract->signed_file_disk ?: 'local')->exists($contract->signed_file_path), 404);

        return Storage::disk($contract->signed_file_disk ?: 'local')->download($contract->signed_file_path, $contract->signed_file_name ?: basename($contract->signed_file_path));
    }

    private function driveConnected(): bool
    {
        try {
            return (bool) (app(GoogleDriveConnectionService::class)->status()['connected'] ?? false);
        } catch (Throwable) {
            return false;
        }
    }

    private function signedContractForAward(int $contractId): DrugBidAwardContract
    {
        return DrugBidAwardContract::query()
            ->with('allocation.award')
            ->whereHas('allocation', fn ($query) => $query->where('drug_bid_award_id', $this->awardId))
            ->findOrFail($contractId);
    }

    private function contractDriveFolders(DrugBidAwardContract $contract): array
    {
        $award = $contract->allocation?->award ?: DrugBidAward::query()->findOrFail($this->awardId);

        return [
            'Pharma',
            'DrugBidAwards',
            $this->safeStorageSegment((string) ($award->bidding_notice_code ?: 'TBMT-'.$award->id)),
            'Contracts',
            (string) $contract->drug_bid_award_allocation_id,
            $this->safeStorageSegment($contract->contract_number),
        ];
    }

    private function contractLocalPath(DrugBidAwardContract $contract): string
    {
        $folders = $this->contractDriveFolders($contract);
        $fileName = $this->safeStorageSegment($contract->signed_file_name ?: 'signed-contract-'.$contract->id.'.pdf');

        return 'Laravel-Backup/'.implode('/', $folders).'/'.$fileName;
    }

    private function safeStorageSegment(string $value): string
    {
        return trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $value), '.-') ?: 'unknown';
    }

    private function formatMoneyInput(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((float) $value, 0, ',', '.');
    }

    private function normalizeMoneyInput(string $value): string
    {
        return str_replace(['.', ',', ' '], '', trim($value));
    }

    private function formatQuantityInput(mixed $value): string
    {
        $number = (float) $value;

        return fmod($number, 1.0) === 0.0
            ? number_format($number, 0, ',', '.')
            : rtrim(rtrim(number_format($number, 4, ',', '.'), '0'), ',');
    }

    private function resetAllocationForm(): void
    {
        $this->reset(['editingAllocationId', 'editingPartnerName', 'partnerId', 'allocatedQuantity', 'notes']);
        $this->dispatch('filters-reset');
        $this->resetValidation();
    }

    private function resetContractForm(): void
    {
        $this->reset(['contractAllocationId', 'contractPartnerName', 'editingContractId', 'contractNumber', 'contractDate', 'signedContractFile', 'contractValue', 'returnToContractId', 'contractStartDate', 'contractEndDate', 'contractNotes']);
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
