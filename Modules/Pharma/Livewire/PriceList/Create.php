<?php

namespace Modules\Pharma\Livewire\PriceList;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Modules\Partner\Models\Partner;
use Modules\Pharma\Livewire\Concerns\AuthorizesPharmaActions;
use Modules\Pharma\Models\PriceList;
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
    public ?int $partnerId = null;
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
    public array $prices = [];
    public string $bulkDiscount = '';
    public ?string $successMessage = null;
    public ?string $errorMessage = null;
    public bool $savedModal = false;
    public ?string $savedName = null;

    protected PriceListManager $manager;

    protected $queryString = ['search' => ['except' => ''], 'catalogStatus' => ['except' => 'active'], 'specialControl' => ['except' => 'all'], 'perPage' => ['except' => 10], 'page' => ['except' => 1]];

    public function boot(PriceListManager $manager): void { $this->manager = $manager; }

    public function mount(?int $priceListId = null): void
    {
        $this->priceListId = $priceListId;
        $priceListId ? $this->authorizePharmaEdit() : $this->authorizePharmaCreate();
        if (! $priceListId) { $this->code = 'BG-'.now()->format('Ymd-His'); return; }

        $list = PriceList::query()->with('items')->findOrFail($priceListId);
        abort_unless($list->isDraft(), 422, 'Chỉ bảng giá Draft mới được chỉnh trực tiếp.');
        $this->name = $list->name; $this->code = $list->code; $this->type = $list->type; $this->partnerId = $list->partner_id;
        $this->effectiveFrom = $list->effective_from?->toDateString() ?? ''; $this->effectiveTo = $list->effective_to?->toDateString() ?? '';
        $this->currency = $list->currency; $this->priority = $list->priority; $this->notes = $list->notes ?? '';
        foreach ($list->items as $item) {
            $key = $this->rowKey($item->medicine_variant_id, $item->medicine_package_id); $this->selectedRows[] = $key;
            $this->prices[$key] = ['company' => $item->company_sale_price ?? '', 'receivable' => $item->actual_receivable_price ?? '', 'invoice' => $item->invoice_price ?? ''];
        }
    }

    public function updatedType(string $value): void { if ($value === PriceList::TYPE_GLOBAL) { $this->partnerId = null; $this->sourceGlobalPriceListId = null; } }
    public function updatedSearch(): void { $this->resetProductPage(); }
    public function updatedCatalogStatus(): void { $this->resetProductPage(); }
    public function updatedSpecialControl(): void { $this->resetProductPage(); }
    public function updatedPerPage(mixed $value): void { $value = (int) $value; $this->perPage = in_array($value, self::PER_PAGE_OPTIONS, true) ? $value : 10; $this->resetProductPage(); }

    public function updatedSelectPage(bool $selected): void
    {
        $keys = $this->currentPageKeys();
        $this->selectedRows = $selected ? array_values(array_unique([...$this->selectedRows, ...$keys])) : array_values(array_diff($this->selectedRows, $keys));
        $this->initializeSelectedPrices($keys);
    }

    public function updatedSelectedRows(): void
    {
        $this->selectedRows = array_values(array_unique($this->selectedRows));
        $this->initializeSelectedPrices($this->selectedRows);
        $this->syncSelectPageState();
    }

    public function gotoPage(mixed $page): void
    {
        $this->page = max(1, (int) $page);
        $this->syncSelectPageState();
    }

    public function goToStep(int $step): void
    {
        if ($step < 1 || $step > 4) return;
        if ($step >= 2 && ! $this->headerIsValid()) return;
        if ($step >= 3 && $this->selectedRows === []) { $this->addError('selectedRows', 'Vui lòng chọn ít nhất một SKU/quy cách trước khi thiết lập giá.'); $this->step = 2; return; }
        $this->initializeSelectedPrices($this->selectedRows); $this->step = $step;
    }

    public function nextStep(): void { $this->goToStep(min(4, $this->step + 1)); }
    public function previousStep(): void { $this->step = max(1, $this->step - 1); }
    public function clearSelection(): void { $this->selectedRows = []; $this->selectPage = false; }

    public function selectAllMatching(): void
    {
        $keys = $this->productQuery()->get()->map(fn ($row): string => $this->rowKey((int) $row->variant_id, $row->package_id ? (int) $row->package_id : null))->all();
        $this->selectedRows = array_values(array_unique([...$this->selectedRows, ...$keys])); $this->initializeSelectedPrices($keys); $this->syncSelectPageState();
    }

    public function loadFromGlobalPriceList(): void
    {
        $this->resetValidation('sourceGlobalPriceListId');
        if ($this->type !== PriceList::TYPE_CUSTOMER) { $this->addError('sourceGlobalPriceListId', 'Chỉ bảng giá khách hàng mới có thể khởi tạo từ bảng giá chung.'); return; }
        if (! $this->sourceGlobalPriceListId) { $this->addError('sourceGlobalPriceListId', 'Vui lòng chọn bảng giá chung ACTIVE làm nguồn.'); return; }
        $source = PriceList::query()->with('items')->whereKey($this->sourceGlobalPriceListId)->where('type', PriceList::TYPE_GLOBAL)->where('status', PriceList::STATUS_ACTIVE)->first();
        if (! $source) { $this->addError('sourceGlobalPriceListId', 'Bảng giá chung nguồn không còn ở trạng thái ACTIVE.'); return; }
        $this->selectedRows = []; $this->prices = [];
        foreach ($source->items as $item) {
            $key = $this->rowKey($item->medicine_variant_id, $item->medicine_package_id); $this->selectedRows[] = $key;
            $this->prices[$key] = ['company' => $item->company_sale_price ?? $item->declared_price_snapshot ?? '', 'receivable' => $item->actual_receivable_price ?? $item->company_sale_price ?? '', 'invoice' => $item->invoice_price ?? $item->company_sale_price ?? ''];
        }
        $this->successMessage = 'Đã khởi tạo '.count($this->selectedRows).' SKU/quy cách từ '.$source->name.'. Hãy điều chỉnh các giá ngoại lệ của khách hàng.'; $this->step = 3;
    }

    public function applyDiscount(): void
    {
        $discount = (float) $this->bulkDiscount;
        if ($discount < 0 || $discount > 100) { $this->addError('bulkDiscount', 'Tỷ lệ giảm phải từ 0 đến 100%.'); return; }
        foreach ($this->declaredPricesForSelected() as $key => $declared) if ($declared !== null) $this->prices[$key]['receivable'] = round((float) $declared * (100 - $discount) / 100, 2);
    }

    public function copyCompanyToReceivable(): void { foreach ($this->selectedRows as $key) $this->prices[$key]['receivable'] = $this->prices[$key]['company'] ?? ''; }
    public function copyCompanyToInvoice(): void { foreach ($this->selectedRows as $key) $this->prices[$key]['invoice'] = $this->prices[$key]['company'] ?? ''; }

    public function saveDraft(): void
    {
        $this->priceListId ? $this->authorizePharmaEdit() : $this->authorizePharmaCreate(); $this->resetValidation(); $this->successMessage = null; $this->errorMessage = null;
        if (! $this->headerIsValid()) { $this->step = 1; return; }
        if ($this->selectedRows === []) { $this->addError('selectedRows', 'Bảng giá phải có ít nhất một SKU/quy cách.'); $this->step = 2; return; }
        try {
            DB::transaction(function (): void {
                $header = $this->manager->validateHeader($this->headerPayload());
                $list = $this->priceListId ? PriceList::query()->findOrFail($this->priceListId) : new PriceList(['status' => PriceList::STATUS_DRAFT, 'created_by' => auth('admin')->id()]);
                abort_unless(! $list->exists || $list->isDraft(), 422, 'Chỉ bảng giá Draft mới được sửa.'); $list->fill($header); $list->status = PriceList::STATUS_DRAFT; $list->save(); $list->items()->delete();
                foreach ($this->selectedRows as $key) {
                    [$variantId, $packageId] = $this->parseRowKey($key); $rowPrices = $this->prices[$key] ?? [];
                    $list->items()->create($this->manager->validateItem(['medicine_variant_id' => $variantId, 'medicine_package_id' => $packageId, 'company_sale_price' => $this->nullablePrice($rowPrices['company'] ?? null), 'actual_receivable_price' => $this->nullablePrice($rowPrices['receivable'] ?? null), 'invoice_price' => $this->nullablePrice($rowPrices['invoice'] ?? null), 'status' => 'active']));
                }
                $this->priceListId = $list->id; $this->savedName = $list->name;
            });
            $this->savedModal = true; $this->step = 4;
        } catch (Throwable $exception) { report($exception); $this->errorMessage = $exception->getMessage(); }
    }

    public function returnToIndex() { return $this->redirectRoute('admin.pharma.price-lists.index', navigate: true); }

    public function render()
    {
        $customers = Partner::query()->where('status', 'active')->whereJsonContains('partner_types', 'customer')->orderBy('name')->get(['id', 'name', 'tax_code', 'address']);
        $globalPriceLists = PriceList::query()->where('type', PriceList::TYPE_GLOBAL)->where('status', PriceList::STATUS_ACTIVE)->activeAt(today())->orderByDesc('priority')->orderByDesc('effective_from')->orderBy('name')->get(['id', 'code', 'name', 'effective_from', 'effective_to']);
        $products = $this->productPaginator(); $selectedProducts = $this->selectedProductRows(); $declared = $this->declaredPricesForSelected();
        $missingSale = collect($this->selectedRows)->filter(fn (string $key): bool => ($this->prices[$key]['company'] ?? '') === '')->count();
        $overCeiling = collect($this->selectedRows)->filter(fn (string $key): bool => ($this->prices[$key]['company'] ?? '') !== '' && ($declared[$key] ?? null) !== null && (float) $this->prices[$key]['company'] > (float) $declared[$key])->count();
        return view('Pharma::livewire.price-list.create', compact('customers', 'globalPriceLists', 'products', 'selectedProducts') + ['perPageOptions' => self::PER_PAGE_OPTIONS, 'missingSaleCount' => $missingSale, 'overCeilingCount' => $overCeiling]);
    }

    private function headerIsValid(): bool
    {
        $this->resetValidation(['name', 'code', 'type', 'partnerId', 'effectiveFrom', 'effectiveTo', 'currency', 'priority']);
        $this->validate(['name' => ['required', 'string', 'max:255'], 'code' => ['required', 'string', 'max:80'], 'type' => ['required', 'in:global,customer'], 'partnerId' => [$this->type === PriceList::TYPE_CUSTOMER ? 'required' : 'nullable', 'integer'], 'effectiveFrom' => ['nullable', 'date'], 'effectiveTo' => ['nullable', 'date', 'after_or_equal:effectiveFrom'], 'currency' => ['required', 'string', 'size:3'], 'priority' => ['integer']]);
        try { $this->manager->validateHeader($this->headerPayload()); } catch (Throwable $exception) { $this->errorMessage = $exception->getMessage(); return false; }
        return true;
    }

    private function headerPayload(): array { return ['name' => $this->name, 'code' => $this->code, 'type' => $this->type, 'partner_id' => $this->partnerId, 'effective_from' => $this->effectiveFrom ?: null, 'effective_to' => $this->effectiveTo ?: null, 'currency' => strtoupper($this->currency), 'priority' => $this->priority, 'notes' => $this->notes ?: null]; }

    private function productPaginator(): LengthAwarePaginator
    {
        $rows = $this->productQuery()->get()->map(function ($row) { $row->key = $this->rowKey((int) $row->variant_id, $row->package_id ? (int) $row->package_id : null); return $row; });
        $total = $rows->count(); $lastPage = max(1, (int) ceil($total / $this->perPage)); $page = min(max(1, $this->page), $lastPage);
        return new LengthAwarePaginator($rows->slice(($page - 1) * $this->perPage, $this->perPage)->values(), $total, $this->perPage, $page, ['path' => request()->url()]);
    }

    private function productQuery(bool $applyFilters = true): Builder
    {
        return DB::table('pharma_medicine_variants as v')->join('pharma_medicines as m', 'm.id', '=', 'v.medicine_id')->leftJoin('pharma_medicine_packages as p', 'p.medicine_variant_id', '=', 'v.id')
            ->select(['v.id as variant_id', 'v.sku', 'v.strength_text', 'v.presentation_text', 'm.id as medicine_id', 'm.medicine_code', 'm.name', 'm.active_ingredients', 'm.concentration', 'm.registration_number', 'm.packaging_specification', 'm.declared_price', 'm.catalog_status', 'm.is_special_control', 'p.id as package_id', 'p.packaging_text', 'p.package_code'])
            ->when($applyFilters && $this->catalogStatus !== 'all', fn ($q) => $q->where('m.catalog_status', $this->catalogStatus))->when($applyFilters && $this->specialControl !== 'all', fn ($q) => $q->where('m.is_special_control', $this->specialControl === 'yes'))
            ->when($applyFilters && trim($this->search) !== '', function ($q): void { $search = '%'.trim($this->search).'%'; $q->where(function ($inner) use ($search): void { $inner->where('m.medicine_code', 'like', $search)->orWhere('m.name', 'like', $search)->orWhere('m.active_ingredients', 'like', $search)->orWhere('m.registration_number', 'like', $search)->orWhere('v.sku', 'like', $search)->orWhere('v.strength_text', 'like', $search)->orWhere('p.packaging_text', 'like', $search); }); })->orderBy('m.name')->orderBy('v.sku')->orderBy('p.id');
    }

    private function selectedProductRows(): Collection
    {
        if ($this->selectedRows === []) return collect(); $selected = array_flip($this->selectedRows);
        return $this->productQuery(false)->get()->map(function ($row) { $row->key = $this->rowKey((int) $row->variant_id, $row->package_id ? (int) $row->package_id : null); return $row; })->filter(fn ($row): bool => isset($selected[$row->key]))->values();
    }

    private function initializeSelectedPrices(array $keys): void
    {
        $declared = $this->declaredPricesForKeys($keys);
        foreach ($keys as $key) { if (! in_array($key, $this->selectedRows, true) || isset($this->prices[$key])) continue; $ceiling = $declared[$key] ?? null; $this->prices[$key] = ['company' => $ceiling ?? '', 'receivable' => $ceiling ?? '', 'invoice' => $ceiling ?? '']; }
    }

    private function declaredPricesForSelected(): array { return $this->declaredPricesForKeys($this->selectedRows); }
    private function declaredPricesForKeys(array $keys): array
    {
        if ($keys === []) return []; $wanted = array_flip($keys); $result = [];
        foreach ($this->productQuery(false)->get() as $row) { $key = $this->rowKey((int) $row->variant_id, $row->package_id ? (int) $row->package_id : null); if (isset($wanted[$key])) $result[$key] = $row->declared_price; }
        return $result;
    }

    private function currentPageKeys(): array
    {
        return collect($this->productPaginator()->items())->pluck('key')->all();
    }

    private function syncSelectPageState(): void
    {
        $keys = $this->currentPageKeys();
        $this->selectPage = $keys !== [] && count(array_intersect($keys, $this->selectedRows)) === count($keys);
    }

    private function resetProductPage(): void
    {
        $this->page = 1;
        $this->syncSelectPageState();
    }

    private function rowKey(int $variantId, ?int $packageId): string { return $variantId.'-'.($packageId ?? 0); }
    private function parseRowKey(string $key): array { [$variantId, $packageId] = array_map('intval', explode('-', $key, 2)); return [$variantId, $packageId === 0 ? null : $packageId]; }
    private function nullablePrice(mixed $value): ?float { return $value === '' || $value === null ? null : (float) $value; }
}
