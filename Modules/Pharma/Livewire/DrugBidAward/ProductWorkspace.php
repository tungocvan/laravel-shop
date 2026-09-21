<?php

namespace Modules\Pharma\Livewire\DrugBidAward;

use Livewire\Component;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\OfficialSourceFacility;
use Modules\Pharma\Services\DrugBidAwardDistributionScopeService;

class ProductWorkspace extends Component
{
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public int $awardId;
    public string $search = '';
    public int $perPage = 10;
    public int $page = 1;
    public string $provinceCode = '';
    public array $selectedFacilityIds = [];
    public string $facilitySearch = '';
    public string $effectiveFrom = '';
    public string $effectiveUntil = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
        'page' => ['except' => 1],
    ];

    public function mount(int $awardId): void
    {
        abort_unless(auth('admin')->user()?->can('view_pharma_allocations'), 403);
        DrugBidAward::query()->findOrFail($awardId);
        $this->awardId = $awardId;
        $this->perPage = $this->normalizePerPage($this->perPage);
        $this->loadDistributionScope();
    }

    public function updatedSearch(): void { $this->page = 1; }

    public function updatedProvinceCode(): void
    {
        $this->selectedFacilityIds = [];
        $this->dispatch('filters-reset');
    }

    public function saveDistributionScope(DrugBidAwardDistributionScopeService $service): void
    {
        abort_unless(auth('admin')->user()?->can('manage_pharma_allocations'), 403);
        $data = $this->validate([
            'provinceCode' => ['required', 'string', 'max:50'],
            'selectedFacilityIds' => ['required', 'array', 'min:1'],
            'selectedFacilityIds.*' => ['integer'],
            'effectiveFrom' => ['required', 'date'],
            'effectiveUntil' => ['required', 'date', 'after_or_equal:effectiveFrom'],
        ]);

        $award = DrugBidAward::query()->findOrFail($this->awardId);
        $service->save($award, [
            'province_code' => $data['provinceCode'],
            'facility_ids' => $data['selectedFacilityIds'],
            'effective_from' => $data['effectiveFrom'],
            'effective_until' => $data['effectiveUntil'],
        ], auth('admin')->id());

        session()->flash('success', 'Đã lưu phạm vi và hiệu lực phân bổ cho kết quả trúng thầu.');
        $this->loadDistributionScope();
    }
    public function updatedPerPage(mixed $value): void
    {
        $this->perPage = $this->normalizePerPage($value);
        $this->page = 1;
    }
    public function gotoPage(int $page): void { $this->page = max(1, $page); }

    public function render()
    {
        $result = DrugBidAward::query()->findOrFail($this->awardId);
        $query = DrugBidAward::query()
            ->with(['medicine', 'allocations' => fn ($query) => $query->where('status', 'active')])
            ->when($result->bidding_notice_code, fn ($query, $code) => $query->where('bidding_notice_code', $code), fn ($query) => $query->whereKey($result->id))
            ->when($this->search, fn ($query, $search) => $query->where(fn ($nested) => $nested
                ->where('medicine_name', 'like', "%{$search}%")
                ->orWhere('active_ingredient', 'like', "%{$search}%")
                ->orWhere('lot_name', 'like', "%{$search}%")
                ->orWhere('registration_or_import_license', 'like', "%{$search}%")))
            ->orderByRaw("CASE WHEN lot_no IS NULL OR lot_no = '' THEN 1 ELSE 0 END")
            ->orderBy('lot_no')
            ->orderBy('id');

        $products = $query->paginate($this->perPage, ['*'], 'page', max(1, $this->page));

        $provinceOptions = OfficialSourceFacility::query()
            ->where('is_active', true)
            ->whereNotNull('province_name')->where('province_name', '!=', '')
            ->distinct()->orderBy('province_name')->pluck('province_name');

        $facilities = collect();
        if ($this->provinceCode !== '') {
            $facilities = OfficialSourceFacility::query()
                ->where('is_active', true)
                ->where('province_name', $this->provinceCode)
                ->when(trim($this->facilitySearch) !== '', function ($query) {
                    $like = '%'.trim($this->facilitySearch).'%';
                    $query->where(fn ($nested) => $nested->where('facility_name', 'like', $like)->orWhere('external_id', 'like', $like));
                })
                ->orderBy('facility_name')
                ->limit(300)
                ->get(['id', 'external_id', 'facility_name', 'district_name', 'province_name']);
        }

        return view('Pharma::livewire.drug-bid-award.product-workspace', [
            'result' => $result,
            'products' => $products,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'provinceOptions' => $provinceOptions,
            'facilities' => $facilities,
        ]);
    }

    private function loadDistributionScope(): void
    {
        $award = DrugBidAward::query()->findOrFail($this->awardId);
        $scope = app(DrugBidAwardDistributionScopeService::class)->findForAward($award);
        if (! $scope) {
            return;
        }

        $this->provinceCode = (string) ($scope->province_code ?? '');
        $externalIds = $scope->partners->flatMap(fn ($partner) => $partner->sourceReferences
            ->where('source', 'official_source_facility')->pluck('external_id'))->filter()->all();
        $this->selectedFacilityIds = OfficialSourceFacility::query()
            ->whereIn('external_id', $externalIds)->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->effectiveFrom = $scope->effective_from?->format('Y-m-d') ?? '';
        $this->effectiveUntil = $scope->effective_until?->format('Y-m-d') ?? '';
    }

    private function normalizePerPage(mixed $value): int
    {
        $value = (int) $value;

        return in_array($value, self::PER_PAGE_OPTIONS, true) ? $value : 10;
    }
}
