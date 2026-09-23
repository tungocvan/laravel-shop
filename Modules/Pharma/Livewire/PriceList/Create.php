<?php

namespace Modules\Pharma\Livewire\PriceList;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Modules\Partner\Models\Partner;
use Modules\Pharma\Livewire\Concerns\AuthorizesPharmaActions;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\OfficialSourceFacility;
use Modules\Pharma\Models\PriceList;
use Modules\Pharma\Models\PriceListPurpose;
use Modules\Pharma\Services\BidPriceIntelligenceService;
use Modules\Pharma\Services\PriceBidEvidenceService;
use Modules\Pharma\Services\PriceListManager;
use Throwable;

class Create extends Component
{
    use AuthorizesPharmaActions;

    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public int $step = 1;
    public ?int $priceListId = null;
    public string $name = '';
    public string $code = '';
    public string $type = PriceList::TYPE_GLOBAL;
    public string $customerSource = PriceList::CUSTOMER_SOURCE_PARTNER;
    public ?int $partnerId = null;
    public ?int $officialFacilityId = null;
    public ?int $managerUserId = null;
    public ?int $purposeId = null;
    public bool $purposeModalOpen = false;
    public ?int $editingPurposeId = null;
    public string $newPurposeName = '';
    public string $newPurposeDescription = '';
    public string $effectiveFrom = '';
    public string $effectiveTo = '';
    public string $currency = 'VND';
    public int $priority = 0;
    public string $notes = '';
    public ?int $sourceGlobalPriceListId = null;
    public string $search = '';
    public string $catalogStatus = 'active';
    public string $specialControl = 'all';
    public int $perPage = 10;
    public int $page = 1;
    public bool $selectPage = false;
    public array $selectedRows = [];
    public array $includedRows = [];
    public bool $includeAll = true;
    public array $prices = [];
    public array $bidIntelligence = [];
    public array $selectedBidAwardIds = [];
    public array $bidEvidenceSnapshots = [];
    public ?string $bidHistoryKey = null;
    public string $bulkDiscount = '';
    public ?string $successMessage = null;
    public ?string $errorMessage = null;
    public bool $savedModal = false;
    public ?string $savedName = null;

    protected PriceListManager $manager;
    protected BidPriceIntelligenceService $bidService;
    protected PriceBidEvidenceService $evidenceService;
    protected $queryString = ['search' => ['except' => ''], 'catalogStatus' => ['except' => 'active'], 'specialControl' => ['except' => 'all'], 'perPage' => ['except' => 10], 'page' => ['except' => 1]];

    public function boot(PriceListManager $manager, BidPriceIntelligenceService $bidService, PriceBidEvidenceService $evidenceService): void
    {
        $this->manager = $manager;
        $this->bidService = $bidService;
        $this->evidenceService = $evidenceService;
    }

    public function mount(?int $priceListId = null): void
    {
        $this->priceListId = $priceListId;
        $priceListId ? $this->authorizePharmaEdit() : $this->authorizePharmaCreate();
        if (! $priceListId) {
            $this->code = 'BG-'.now()->format('Ymd-His');
            $this->effectiveFrom = now()->toDateString();
            $this->effectiveTo = now()->addMonth()->toDateString();
            $this->managerUserId = auth('admin')->id();
            return;
        }

        $list = PriceList::query()->with(['items.bidEvidence'])->findOrFail($priceListId);
        abort_unless($list->isDirectlyEditable(), 422, 'Chỉ bảng giá Draft hoặc Inactive mới được chỉnh trực tiếp.');
        $this->name = $list->name;
        $this->code = $list->code;
        $this->type = $list->type;
        $this->customerSource = $list->customer_source ?: PriceList::CUSTOMER_SOURCE_PARTNER;
        $this->partnerId = $list->partner_id;
        $this->officialFacilityId = $list->official_facility_id;
        $this->managerUserId = $list->manager_user_id ?: auth('admin')->id();
        $this->purposeId = $list->purpose_id;
        $this->effectiveFrom = $list->effective_from?->toDateString() ?? '';
        $this->effectiveTo = $list->effective_to?->toDateString() ?? '';
        $this->currency = $list->currency;
        $this->priority = $list->priority;
        $this->notes = $list->notes ?? '';

        foreach ($list->items as $item) {
            $key = $this->rowKey($item->medicine_variant_id, $item->medicine_package_id);
            $this->selectedRows[] = $key;
            $this->prices[$key] = ['company' => $item->company_sale_price ?? '', 'receivable' => $item->actual_receivable_price ?? '', 'invoice' => $item->invoice_price ?? ''];
            if ($item->bidEvidence) {
                $this->bidEvidenceSnapshots[$key] = $item->bidEvidence->getAttributes();
                if ($item->bidEvidence->drug_bid_award_id) {
                    $this->selectedBidAwardIds[$key] = (int) $item->bidEvidence->drug_bid_award_id;
                }
            }
        }
        $this->includedRows = $this->selectedRows;
        $this->refreshBidIntelligence();
    }

    public function updatedType(string $value): void
    {
        if ($value === PriceList::TYPE_GLOBAL) {
            $this->partnerId = null;
            $this->officialFacilityId = null;
            $this->managerUserId = null;
            $this->purposeId = null;
            $this->sourceGlobalPriceListId = null;
            return;
        }
        $this->customerSource = $this->customerSource ?: PriceList::CUSTOMER_SOURCE_PARTNER;
        $this->managerUserId ??= auth('admin')->id();
    }

    public function setCustomerSource(string $value): void
    {
        if (! in_array($value, [PriceList::CUSTOMER_SOURCE_PARTNER, PriceList::CUSTOMER_SOURCE_OFFICIAL_FACILITY], true)) return;
        $this->customerSource = $value;
        $this->partnerId = null;
        $this->officialFacilityId = null;
        $this->resetValidation(['customerSource', 'partnerId', 'officialFacilityId']);
    }

    public function updatedCustomerSource(string $value): void
    {
        if ($value === PriceList::CUSTOMER_SOURCE_PARTNER) $this->officialFacilityId = null;
        if ($value === PriceList::CUSTOMER_SOURCE_OFFICIAL_FACILITY) $this->partnerId = null;
        $this->resetValidation(['partnerId', 'officialFacilityId']);
    }

    public function openPurposeModal(): void { $this->resetValidation(['newPurposeName', 'newPurposeDescription']); $this->editingPurposeId = null; $this->newPurposeName = ''; $this->newPurposeDescription = ''; $this->purposeModalOpen = true; }
    public function editPurpose(int $id): void { $purpose = PriceListPurpose::query()->whereKey($id)->where('is_active', true)->firstOrFail(); $this->editingPurposeId = $purpose->id; $this->newPurposeName = $purpose->name; $this->newPurposeDescription = $purpose->description ?? ''; $this->purposeModalOpen = true; }
    public function closePurposeModal(): void { $this->purposeModalOpen = false; $this->editingPurposeId = null; $this->resetValidation(['newPurposeName', 'newPurposeDescription']); }

    public function createPurpose(): void
    {
        $this->authorizePharmaCreate();
        $this->validate(['newPurposeName' => ['required', 'string', 'max:120'], 'newPurposeDescription' => ['nullable', 'string', 'max:500']]);
        if ($this->editingPurposeId) {
            $purpose = PriceListPurpose::query()->whereKey($this->editingPurposeId)->where('is_active', true)->firstOrFail();
            $purpose->update(['name' => trim($this->newPurposeName), 'description' => trim($this->newPurposeDescription) ?: null]);
            $this->purposeId = $purpose->id;
            $this->purposeModalOpen = false;
            $this->editingPurposeId = null;
            $this->successMessage = 'Đã cập nhật mục đích “'.$purpose->name.'”.';
            return;
        }

        $base = Str::slug($this->newPurposeName, '_') ?: 'purpose';
        $code = $base;
        $suffix = 2;
        while (PriceListPurpose::query()->where('code', $code)->exists()) $code = $base.'_'.$suffix++;
        $purpose = PriceListPurpose::query()->create(['code' => $code, 'name' => trim($this->newPurposeName), 'description' => trim($this->newPurposeDescription) ?: null, 'is_active' => true, 'sort_order' => ((int) PriceListPurpose::query()->max('sort_order')) + 10, 'created_by' => auth('admin')->id()]);
        $this->purposeId = $purpose->id;
        $this->purposeModalOpen = false;
        $this->successMessage = 'Đã thêm mục đích “'.$purpose->name.'” và chọn cho bảng giá này.';
    }

    public function deletePurpose(int $id): void
    {
        $this->authorizePharmaEdit();
        $purpose = PriceListPurpose::query()->whereKey($id)->where('is_active', true)->firstOrFail();
        if ($purpose->priceLists()->exists()) {
            $this->addError('purposeId', 'Mục đích đã được dùng trong bảng giá nên không thể xóa.');
            return;
        }
        $purpose->delete();
        if ($this->purposeId === $id) $this->purposeId = null;
        $this->successMessage = 'Đã xóa mục đích “'.$purpose->name.'”.';
    }

    public function updatedSearch(): void { $this->resetProductPage(); }
    public function updatedCatalogStatus(): void { $this->resetProductPage(); }
    public function updatedSpecialControl(): void { $this->resetProductPage(); }
    public function updatedPerPage(mixed $value): void { $value = (int) $value; $this->perPage = in_array($value, self::PER_PAGE_OPTIONS, true) ? $value : 10; $this->resetProductPage(); }

    public function updatedSelectPage(bool $selected): void
    {
        $keys = $this->currentPageKeys();
        $this->selectedRows = $selected ? array_values(array_unique([...$this->selectedRows, ...$keys])) : array_values(array_diff($this->selectedRows, $keys));
        $this->includedRows = $this->selectedRows;
        $this->includeAll = true;
        $this->initializeSelectedPrices($keys);
        $this->refreshBidIntelligence();
    }

    public function updatedSelectedRows(): void
    {
        $this->selectedRows = array_values(array_unique($this->selectedRows));
        $this->includedRows = $this->selectedRows;
        $this->includeAll = true;
        $this->initializeSelectedPrices($this->selectedRows);
        $this->syncSelectPageState();
        $this->refreshBidIntelligence();
    }

    public function updatedIncludedRows(): void { $this->includedRows = array_values(array_unique(array_intersect($this->includedRows, $this->selectedRows))); $this->includeAll = $this->selectedRows !== [] && count($this->includedRows) === count($this->selectedRows); }
    public function updatedIncludeAll(bool $value): void { $this->includedRows = $value ? $this->selectedRows : []; }
    public function gotoPage(mixed $page): void { $this->page = max(1, (int) $page); $this->syncSelectPageState(); }

    public function goToStep(int $step): void
    {
        if ($step < 1 || $step > 4) return;
        if ($step >= 2 && ! $this->headerIsValid()) return;
        if ($step >= 3 && $this->selectedRows === []) { $this->addError('selectedRows', 'Vui lòng chọn ít nhất một SKU/quy cách trước khi thiết lập giá.'); $this->step = 2; return; }
        if ($step >= 4 && $this->includedRows === []) { $this->addError('includedRows', 'Vui lòng giữ lại ít nhất một SKU trong bảng giá.'); $this->step = 3; return; }
        $this->initializeSelectedPrices($this->selectedRows);
        if ($step >= 3) $this->refreshBidIntelligence();
        $this->step = $step;
    }

    public function nextStep(): void { $this->goToStep(min(4, $this->step + 1)); }
    public function previousStep(): void { $this->step = max(1, $this->step - 1); }
    public function clearSelection(): void { $this->selectedRows = []; $this->includedRows = []; $this->prices = []; $this->bidIntelligence = []; $this->selectedBidAwardIds = []; $this->bidEvidenceSnapshots = []; $this->selectPage = false; $this->includeAll = false; }
    public function resetProductFilters(): void { $this->search = ''; $this->catalogStatus = 'active'; $this->specialControl = 'all'; $this->perPage = 10; $this->resetProductPage(); }

    public function selectAllMatching(): void
    {
        $keys = $this->productQuery()->get()->map(fn ($row): string => $this->rowKey((int) $row->variant_id, $row->package_id ? (int) $row->package_id : null))->all();
        $this->selectedRows = array_values(array_unique([...$this->selectedRows, ...$keys]));
        $this->includedRows = $this->selectedRows;
        $this->includeAll = true;
        $this->initializeSelectedPrices($keys);
        $this->syncSelectPageState();
        $this->refreshBidIntelligence();
    }

    public function loadFromGlobalPriceList(): void
    {
        $this->resetValidation('sourceGlobalPriceListId');
        if ($this->type !== PriceList::TYPE_CUSTOMER) { $this->addError('sourceGlobalPriceListId', 'Chỉ bảng giá khách hàng mới có thể khởi tạo từ bảng giá chung.'); return; }
        if (! $this->sourceGlobalPriceListId) { $this->addError('sourceGlobalPriceListId', 'Vui lòng chọn bảng giá chung ACTIVE làm nguồn.'); return; }
        $source = PriceList::query()->with('items')->whereKey($this->sourceGlobalPriceListId)->where('type', PriceList::TYPE_GLOBAL)->where('status', PriceList::STATUS_ACTIVE)->first();
        if (! $source) { $this->addError('sourceGlobalPriceListId', 'Bảng giá chung nguồn không còn ở trạng thái ACTIVE.'); return; }
        $this->selectedRows = [];
        $this->prices = [];
        $this->bidEvidenceSnapshots = [];
        foreach ($source->items as $item) {
            $key = $this->rowKey($item->medicine_variant_id, $item->medicine_package_id);
            $this->selectedRows[] = $key;
            $this->prices[$key] = ['company' => $item->company_sale_price ?? $item->declared_price_snapshot ?? '', 'receivable' => $item->actual_receivable_price ?? $item->company_sale_price ?? '', 'invoice' => $item->invoice_price ?? $item->company_sale_price ?? ''];
        }
        $this->includedRows = $this->selectedRows;
        $this->includeAll = true;
        $this->refreshBidIntelligence();
        $this->successMessage = 'Đã khởi tạo '.count($this->selectedRows).' SKU/quy cách từ '.$source->name.'. Bỏ chọn những SKU không áp dụng và điều chỉnh giá ngoại lệ.';
        $this->step = 3;
    }

    public function updatePrice(string $key, string $field, mixed $value): void
    {
        if (! in_array($key, $this->selectedRows, true) || ! in_array($field, ['company', 'receivable', 'invoice'], true)) return;
        if (is_string($value)) $value = preg_replace('/[^0-9]/', '', $value);
        $this->prices[$key][$field] = $this->normalizePriceInput($value);
    }

    public function showBidHistory(string $key): void { if (isset($this->bidIntelligence[$key])) $this->bidHistoryKey = $key; }
    public function closeBidHistory(): void { $this->bidHistoryKey = null; }

    public function selectBidAward(string $key, int $awardId): void
    {
        $history = collect($this->bidIntelligence[$key]['history'] ?? []);
        if (! $history->contains(fn (array $award): bool => (int) $award['id'] === $awardId)) return;
        $currentSnapshotAwardId = isset($this->bidEvidenceSnapshots[$key]['drug_bid_award_id']) ? (int) $this->bidEvidenceSnapshots[$key]['drug_bid_award_id'] : null;
        if ($currentSnapshotAwardId !== $awardId) {
            unset($this->bidEvidenceSnapshots[$key]);
        }
        $this->selectedBidAwardIds[$key] = $awardId;
        $this->bidHistoryKey = null;
    }

    public function applyDiscount(): void
    {
        $discount = (float) $this->bulkDiscount;
        if ($discount < 0 || $discount > 100) { $this->addError('bulkDiscount', 'Tỷ lệ giảm phải từ 0 đến 100%.'); return; }
        foreach ($this->declaredPricesForKeys($this->includedRows) as $key => $declared) if ($declared !== null) $this->prices[$key]['receivable'] = round((float) $declared * (100 - $discount) / 100, 2);
    }

    public function copyCompanyToReceivable(): void { foreach ($this->includedRows as $key) $this->prices[$key]['receivable'] = $this->prices[$key]['company'] ?? ''; }
    public function copyCompanyToInvoice(): void { foreach ($this->includedRows as $key) $this->prices[$key]['invoice'] = $this->prices[$key]['company'] ?? ''; }

    public function saveDraft(): void
    {
        $this->priceListId ? $this->authorizePharmaEdit() : $this->authorizePharmaCreate();
        $this->resetValidation();
        $this->successMessage = null;
        $this->errorMessage = null;
        if (! $this->headerIsValid()) { $this->step = 1; return; }
        if ($this->includedRows === []) { $this->addError('includedRows', 'Bảng giá phải có ít nhất một SKU/quy cách.'); $this->step = 3; return; }

        try {
            DB::transaction(function (): void {
                $header = $this->manager->validateHeader($this->headerPayload());
                $list = $this->priceListId ? PriceList::query()->findOrFail($this->priceListId) : new PriceList(['status' => PriceList::STATUS_DRAFT, 'created_by' => auth('admin')->id()]);
                abort_unless(! $list->exists || $list->isDirectlyEditable(), 422, 'Chỉ bảng giá Draft hoặc Inactive mới được sửa.');
                $list->fill($header);
                if (! $list->exists) $list->status = PriceList::STATUS_DRAFT;
                $list->save();
                $list->items()->delete();

                $restoredSnapshots = [];
                foreach ($this->includedRows as $key) {
                    [$variantId, $packageId] = $this->parseRowKey($key);
                    $rowPrices = $this->prices[$key] ?? [];
                    $item = $list->items()->create($this->manager->validateItem([
                        'medicine_variant_id' => $variantId,
                        'medicine_package_id' => $packageId,
                        'company_sale_price' => $this->nullablePrice($rowPrices['company'] ?? null),
                        'actual_receivable_price' => $this->nullablePrice($rowPrices['receivable'] ?? null),
                        'invoice_price' => $this->nullablePrice($rowPrices['invoice'] ?? null),
                        'status' => 'active',
                    ]));

                    if (isset($this->bidEvidenceSnapshots[$key])) {
                        $evidence = $this->evidenceService->restoreSnapshot($item, $this->bidEvidenceSnapshots[$key]);
                        $restoredSnapshots[$key] = $evidence->getAttributes();
                        continue;
                    }

                    $awardId = $this->selectedBidAwardIds[$key] ?? null;
                    if ($awardId) {
                        $award = DrugBidAward::query()->with(['canonicalMatch', 'sources'])->find($awardId);
                        if ($award) {
                            $evidence = $this->evidenceService->capture($item, $award, auth('admin')->id());
                            $restoredSnapshots[$key] = $evidence->getAttributes();
                        }
                    }
                }
                $this->bidEvidenceSnapshots = $restoredSnapshots;
                $this->priceListId = $list->id;
                $this->savedName = $list->name;
            });
            $this->savedModal = true;
            $this->step = 4;
        } catch (Throwable $exception) {
            report($exception);
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function returnToIndex() { return $this->redirectRoute('admin.pharma.price-lists.index', navigate: true); }

    public function render()
    {
        $customers = Partner::query()->where('status', 'active')->whereJsonContains('partner_types', 'customer')->orderBy('name')->limit(500)->get(['id', 'name', 'tax_code', 'address']);
        $officialFacilities = OfficialSourceFacility::query()->where('is_active', true)->orderBy('facility_name')->limit(500)->get(['id', 'facility_name', 'external_id', 'province_name']);
        $users = User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']);
        $purposes = PriceListPurpose::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'description']);
        $globalPriceLists = PriceList::query()->where('type', PriceList::TYPE_GLOBAL)->where('status', PriceList::STATUS_ACTIVE)->activeAt(today())->orderByDesc('priority')->orderByDesc('effective_from')->orderBy('name')->get(['id', 'code', 'name', 'effective_from', 'effective_to']);
        $products = $this->productPaginator();
        $selectedProducts = $this->selectedProductRows();
        $declared = $this->declaredPricesForKeys($this->includedRows);
        $missingSale = collect($this->includedRows)->filter(fn (string $key): bool => ($this->prices[$key]['company'] ?? '') === '')->count();
        $overCeiling = collect($this->includedRows)->filter(fn (string $key): bool => ($this->prices[$key]['company'] ?? '') !== '' && ($declared[$key] ?? null) !== null && (float) $this->prices[$key]['company'] > (float) $declared[$key])->count();
        return view('Pharma::livewire.price-list.create', compact('customers', 'officialFacilities', 'users', 'purposes', 'globalPriceLists', 'products', 'selectedProducts') + ['perPageOptions' => self::PER_PAGE_OPTIONS, 'missingSaleCount' => $missingSale, 'overCeilingCount' => $overCeiling]);
    }

    private function refreshBidIntelligence(): void
    {
        if ($this->selectedRows === []) { $this->bidIntelligence = []; return; }
        $items = collect($this->selectedRows)->map(function (string $key): array { [$variantId, $packageId] = $this->parseRowKey($key); return ['variant_id' => $variantId, 'package_id' => $packageId]; })->all();
        $intelligence = $this->bidService->forItems($items, 20);
        $result = [];
        foreach ($this->selectedRows as $key) {
            [$variantId, $packageId] = $this->parseRowKey($key);
            $serviceKey = 'variant:'.$variantId.':package:'.($packageId ?? 0);
            $summary = $intelligence[$serviceKey] ?? null;
            if (! $summary || $summary->count === 0) {
                $result[$key] = ['has_award' => false, 'history' => []];
                continue;
            }
            $history = $summary->recentAwards->map(fn ($award): array => ['id' => (int) $award->id, 'price' => $award->winning_price, 'quantity' => $award->quantity, 'decision_number' => $award->decision_number, 'award_date' => $award->decision_date?->toDateString() ?? $award->published_at?->toDateString(), 'contractor' => $award->winning_company_name, 'source' => $award->source_type === DrugBidAward::SOURCE_MANUAL ? 'Nhập thủ công' : 'Mua sắm công'])->values()->all();
            $selectedId = $this->selectedBidAwardIds[$key] ?? ($history[0]['id'] ?? null);
            if ($selectedId) $this->selectedBidAwardIds[$key] = $selectedId;
            $selected = collect($history)->firstWhere('id', $selectedId) ?? ($history[0] ?? null);
            $result[$key] = ['has_award' => true, 'selected' => $selected, 'history' => $history, 'count' => $summary->count];
        }
        $this->bidIntelligence = $result;
    }

    private function headerIsValid(): bool
    {
        $this->resetValidation(['name', 'code', 'type', 'customerSource', 'partnerId', 'officialFacilityId', 'managerUserId', 'purposeId', 'effectiveFrom', 'effectiveTo', 'currency', 'priority']);
        $this->validate(['name' => ['required', 'string', 'max:255'], 'code' => ['required', 'string', 'max:80'], 'type' => ['required', 'in:global,customer'], 'customerSource' => [$this->type === PriceList::TYPE_CUSTOMER ? 'required' : 'nullable', 'in:partner,official_facility'], 'partnerId' => ['nullable', 'integer', 'exists:partners,id'], 'officialFacilityId' => ['nullable', 'integer'], 'managerUserId' => [$this->type === PriceList::TYPE_CUSTOMER ? 'required' : 'nullable', 'integer'], 'purposeId' => [$this->type === PriceList::TYPE_CUSTOMER ? 'required' : 'nullable', 'integer'], 'effectiveFrom' => ['nullable', 'date'], 'effectiveTo' => ['nullable', 'date', 'after_or_equal:effectiveFrom'], 'currency' => ['required', 'string', 'size:3'], 'priority' => ['integer']]);
        try { $this->manager->validateHeader($this->headerPayload()); } catch (Throwable $exception) { $this->errorMessage = $exception->getMessage(); return false; }
        return true;
    }

    private function headerPayload(): array
    {
        return ['name' => $this->name, 'code' => $this->code, 'type' => $this->type, 'customer_source' => $this->type === PriceList::TYPE_CUSTOMER ? $this->customerSource : null, 'partner_id' => $this->partnerId, 'official_facility_id' => $this->officialFacilityId, 'manager_user_id' => $this->managerUserId, 'purpose_id' => $this->purposeId, 'effective_from' => $this->effectiveFrom ?: null, 'effective_to' => $this->effectiveTo ?: null, 'currency' => strtoupper($this->currency), 'priority' => $this->priority, 'notes' => $this->notes ?: null];
    }

    private function productPaginator(): LengthAwarePaginator
    {
        $rows = $this->productQuery()->get()->map(function ($row) { $row->key = $this->rowKey((int) $row->variant_id, $row->package_id ? (int) $row->package_id : null); return $row; });
        $total = $rows->count();
        $lastPage = max(1, (int) ceil($total / $this->perPage));
        $page = min(max(1, $this->page), $lastPage);
        return new LengthAwarePaginator($rows->slice(($page - 1) * $this->perPage, $this->perPage)->values(), $total, $this->perPage, $page, ['path' => request()->url()]);
    }

    private function productQuery(bool $applyFilters = true): Builder
    {
        return DB::table('pharma_medicine_variants as v')->join('pharma_medicines as m', 'm.id', '=', 'v.medicine_id')->leftJoin('pharma_medicine_packages as p', 'p.medicine_variant_id', '=', 'v.id')
            ->select(['v.id as variant_id', 'v.sku', 'v.strength_text', 'v.presentation_text', 'm.id as medicine_id', 'm.medicine_code', 'm.name', 'm.active_ingredients', 'm.concentration', 'm.registration_number', 'm.packaging_specification', 'm.declared_price', 'm.catalog_status', 'm.is_special_control', 'p.id as package_id', 'p.packaging_text', 'p.package_code'])
            ->when($applyFilters && $this->catalogStatus !== 'all', fn ($q) => $q->where('m.catalog_status', $this->catalogStatus))
            ->when($applyFilters && $this->specialControl !== 'all', fn ($q) => $q->where('m.is_special_control', $this->specialControl === 'yes'))
            ->when($applyFilters && trim($this->search) !== '', function ($q): void {
                $search = '%'.trim($this->search).'%';
                $q->where(function ($inner) use ($search): void {
                    $inner->where('m.medicine_code', 'like', $search)->orWhere('m.name', 'like', $search)->orWhere('m.active_ingredients', 'like', $search)->orWhere('m.registration_number', 'like', $search)->orWhere('v.sku', 'like', $search)->orWhere('v.strength_text', 'like', $search)->orWhere('p.packaging_text', 'like', $search);
                });
            })->orderBy('m.name')->orderBy('v.sku')->orderBy('p.id');
    }

    private function selectedProductRows(): Collection
    {
        if ($this->selectedRows === []) return collect();
        $selected = array_flip($this->selectedRows);
        return $this->productQuery(false)->get()->map(function ($row) { $row->key = $this->rowKey((int) $row->variant_id, $row->package_id ? (int) $row->package_id : null); return $row; })->filter(fn ($row): bool => isset($selected[$row->key]))->values();
    }

    private function initializeSelectedPrices(array $keys): void
    {
        $declared = $this->declaredPricesForKeys($keys);
        foreach ($keys as $key) {
            if (! in_array($key, $this->selectedRows, true) || isset($this->prices[$key])) continue;
            $ceiling = $declared[$key] ?? null;
            $this->prices[$key] = ['company' => $ceiling ?? '', 'receivable' => $ceiling ?? '', 'invoice' => $ceiling ?? ''];
        }
    }

    private function declaredPricesForKeys(array $keys): array
    {
        if ($keys === []) return [];
        $wanted = array_flip($keys);
        $result = [];
        foreach ($this->productQuery(false)->get() as $row) {
            $key = $this->rowKey((int) $row->variant_id, $row->package_id ? (int) $row->package_id : null);
            if (isset($wanted[$key])) $result[$key] = $row->declared_price;
        }
        return $result;
    }

    private function currentPageKeys(): array { return collect($this->productPaginator()->items())->pluck('key')->all(); }
    private function syncSelectPageState(): void { $keys = $this->currentPageKeys(); $this->selectPage = $keys !== [] && count(array_intersect($keys, $this->selectedRows)) === count($keys); }
    private function resetProductPage(): void { $this->page = 1; $this->syncSelectPageState(); }

    private function normalizePriceInput(mixed $value): float|string
    {
        if ($value === null || $value === '') return '';
        $normalized = trim(str_replace(['₫', ' ', '.'], '', (string) $value));
        $normalized = str_replace(',', '.', $normalized);
        return is_numeric($normalized) ? max(0, (float) $normalized) : '';
    }

    private function rowKey(int $variantId, ?int $packageId): string { return $variantId.'-'.($packageId ?? 0); }
    private function parseRowKey(string $key): array { [$variantId, $packageId] = array_map('intval', explode('-', $key, 2)); return [$variantId, $packageId === 0 ? null : $packageId]; }
    private function nullablePrice(mixed $value): ?float { return $value === '' || $value === null ? null : (float) $value; }
}
