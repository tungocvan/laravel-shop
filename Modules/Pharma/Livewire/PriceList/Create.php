<?php

namespace Modules\Pharma\Livewire\PriceList;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Modules\Partner\Models\Partner;
use Modules\Pharma\Livewire\Concerns\AuthorizesPharmaActions;
use Modules\Pharma\Models\PriceList;
use Modules\Pharma\Models\PriceListItem;
use Modules\Pharma\Services\PriceListManager;
use Throwable;

class Create extends Component
{
    use AuthorizesPharmaActions;

    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

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

    protected PriceListManager $manager;

    protected $queryString = [
        'search' => ['except' => ''],
        'catalogStatus' => ['except' => 'active'],
        'specialControl' => ['except' => 'all'],
        'perPage' => ['except' => 10],
        'page' => ['except' => 1],
    ];

    public function boot(PriceListManager $manager): void
    {
        $this->manager = $manager;
    }

    public function mount(?int $priceListId = null): void
    {
        $this->priceListId = $priceListId;
        $priceListId ? $this->authorizePharmaEdit() : $this->authorizePharmaCreate();

        if ($priceListId) {
            $list = PriceList::query()->with('items')->findOrFail($priceListId);
            abort_unless($list->isDraft(), 422, 'Chỉ bảng giá Draft mới được chỉnh trực tiếp.');
            $this->name = $list->name;
            $this->code = $list->code;
            $this->type = $list->type;
            $this->partnerId = $list->partner_id;
            $this->effectiveFrom = $list->effective_from?->toDateString() ?? '';
            $this->effectiveTo = $list->effective_to?->toDateString() ?? '';
            $this->currency = $list->currency;
            $this->priority = $list->priority;
            $this->notes = $list->notes ?? '';

            foreach ($list->items as $item) {
                $key = $this->rowKey($item->medicine_variant_id, $item->medicine_package_id);
                $this->selectedRows[] = $key;
                $this->prices[$key] = [
                    'company' => $item->company_sale_price ?? '',
                    'receivable' => $item->actual_receivable_price ?? '',
                    'invoice' => $item->invoice_price ?? '',
                ];
            }
        } else {
            $this->code = 'BG-'.now()->format('Ymd-His');
        }
    }

    public function updatedType(string $value): void
    {
        if ($value === PriceList::TYPE_GLOBAL) {
            $this->partnerId = null;
        }
    }

    public function updatedSearch(): void { $this->page = 1; $this->selectPage = false; }
    public function updatedCatalogStatus(): void { $this->page = 1; $this->selectPage = false; }
    public function updatedSpecialControl(): void { $this->page = 1; $this->selectPage = false; }
    public function updatedPerPage(mixed $value): void
    {
        $value = (int) $value;
        $this->perPage = in_array($value, self::PER_PAGE_OPTIONS, true) ? $value : 10;
        $this->page = 1;
        $this->selectPage = false;
    }

    public function updatedSelectPage(bool $selected): void
    {
        $keys = collect($this->productPaginator()->items())->pluck('key')->all();
        $this->selectedRows = $selected
            ? array_values(array_unique([...$this->selectedRows, ...$keys]))
            : array_values(array_diff($this->selectedRows, $keys));
    }

    public function updatedSelectedRows(): void
    {
        $this->selectPage = false;
    }

    public function gotoPage(mixed $page): void
    {
        $this->page = max(1, (int) $page);
        $this->selectPage = false;
    }

    public function clearSelection(): void
    {
        $this->selectedRows = [];
        $this->selectPage = false;
    }

    public function applyDiscount(): void
    {
        $discount = (float) $this->bulkDiscount;
        if ($discount < 0 || $discount > 100) {
            $this->addError('bulkDiscount', 'Tỷ lệ giảm phải từ 0 đến 100%.');
            return;
        }

        $declared = $this->declaredPricesForSelected();
        foreach ($this->selectedRows as $key) {
            if (($declared[$key] ?? null) !== null) {
                $this->prices[$key]['company'] = round((float) $declared[$key] * (100 - $discount) / 100, 2);
            }
        }
    }

    public function copyCompanyToReceivable(): void
    {
        foreach ($this->selectedRows as $key) {
            $this->prices[$key]['receivable'] = $this->prices[$key]['company'] ?? '';
        }
    }

    public function copyCompanyToInvoice(): void
    {
        foreach ($this->selectedRows as $key) {
            $this->prices[$key]['invoice'] = $this->prices[$key]['company'] ?? '';
        }
    }

    public function saveDraft(): void
    {
        $this->priceListId ? $this->authorizePharmaEdit() : $this->authorizePharmaCreate();
        $this->resetValidation();
        $this->successMessage = null;
        $this->errorMessage = null;

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:80'],
            'type' => ['required', 'in:global,customer'],
            'partnerId' => ['nullable', 'integer'],
            'effectiveFrom' => ['nullable', 'date'],
            'effectiveTo' => ['nullable', 'date'],
            'currency' => ['required', 'string', 'size:3'],
            'priority' => ['integer'],
            'selectedRows' => ['array'],
        ]);

        try {
            DB::transaction(function (): void {
                $header = $this->manager->validateHeader([
                    'name' => $this->name,
                    'code' => $this->code,
                    'type' => $this->type,
                    'partner_id' => $this->partnerId,
                    'effective_from' => $this->effectiveFrom ?: null,
                    'effective_to' => $this->effectiveTo ?: null,
                    'currency' => strtoupper($this->currency),
                    'priority' => $this->priority,
                    'notes' => $this->notes ?: null,
                ]);

                $list = $this->priceListId
                    ? PriceList::query()->findOrFail($this->priceListId)
                    : new PriceList(['status' => PriceList::STATUS_DRAFT, 'created_by' => auth('admin')->id()]);
                abort_unless(! $list->exists || $list->isDraft(), 422, 'Chỉ bảng giá Draft mới được sửa.');
                $list->fill($header);
                $list->status = PriceList::STATUS_DRAFT;
                $list->save();

                $list->items()->delete();
                foreach ($this->selectedRows as $key) {
                    [$variantId, $packageId] = $this->parseRowKey($key);
                    $rowPrices = $this->prices[$key] ?? [];
                    $payload = $this->manager->validateItem([
                        'medicine_variant_id' => $variantId,
                        'medicine_package_id' => $packageId,
                        'company_sale_price' => $this->nullablePrice($rowPrices['company'] ?? null),
                        'actual_receivable_price' => $this->nullablePrice($rowPrices['receivable'] ?? null),
                        'invoice_price' => $this->nullablePrice($rowPrices['invoice'] ?? null),
                        'status' => 'active',
                    ]);
                    $list->items()->create($payload);
                }

                $this->priceListId = $list->id;
            });

            $this->successMessage = 'Đã lưu bảng giá Draft. Giá kê khai đã được snapshot từ Medicine Master.';
        } catch (Throwable $exception) {
            report($exception);
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function render()
    {
        $customers = Partner::query()->where('status', 'active')->orderBy('name')->get()
            ->filter(fn (Partner $partner) => in_array('customer', $partner->partner_types ?? [], true));
        $products = $this->productPaginator();
        $declared = collect($products->items())->mapWithKeys(fn ($row) => [$row->key => $row->declared_price]);
        $missingSale = collect($this->selectedRows)->filter(fn ($key) => ($this->prices[$key]['company'] ?? '') === '')->count();
        $overCeiling = collect($this->selectedRows)->filter(function ($key) use ($declared): bool {
            $company = $this->prices[$key]['company'] ?? null;
            $ceiling = $declared[$key] ?? null;
            return $company !== null && $company !== '' && $ceiling !== null && (float) $company > (float) $ceiling;
        })->count();

        return view('Pharma::livewire.price-list.create', [
            'customers' => $customers,
            'products' => $products,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'missingSaleCount' => $missingSale,
            'overCeilingCount' => $overCeiling,
        ]);
    }

    private function productPaginator(): LengthAwarePaginator
    {
        $query = DB::table('pharma_medicine_variants as v')
            ->join('pharma_medicines as m', 'm.id', '=', 'v.medicine_id')
            ->leftJoin('pharma_medicine_packages as p', 'p.medicine_variant_id', '=', 'v.id')
            ->select([
                'v.id as variant_id', 'v.sku', 'v.strength_text', 'v.presentation_text',
                'm.id as medicine_id', 'm.medicine_code', 'm.name', 'm.active_ingredients', 'm.concentration',
                'm.registration_number', 'm.packaging_specification', 'm.declared_price', 'm.catalog_status', 'm.is_special_control',
                'p.id as package_id', 'p.packaging_text', 'p.package_code',
            ])
            ->when($this->catalogStatus !== 'all', fn ($q) => $q->where('m.catalog_status', $this->catalogStatus))
            ->when($this->specialControl !== 'all', fn ($q) => $q->where('m.is_special_control', $this->specialControl === 'yes'))
            ->when(trim($this->search) !== '', function ($q): void {
                $search = '%'.trim($this->search).'%';
                $q->where(function ($inner) use ($search): void {
                    $inner->where('m.medicine_code', 'like', $search)
                        ->orWhere('m.name', 'like', $search)
                        ->orWhere('m.active_ingredients', 'like', $search)
                        ->orWhere('m.registration_number', 'like', $search)
                        ->orWhere('v.sku', 'like', $search)
                        ->orWhere('v.strength_text', 'like', $search)
                        ->orWhere('p.packaging_text', 'like', $search);
                });
            })
            ->orderBy('m.name')->orderBy('v.sku')->orderBy('p.id');

        $rows = $query->get()->map(function ($row) {
            $row->key = $this->rowKey((int) $row->variant_id, $row->package_id ? (int) $row->package_id : null);
            if (! isset($this->prices[$row->key])) {
                $this->prices[$row->key] = ['company' => '', 'receivable' => '', 'invoice' => ''];
            }
            return $row;
        });

        $total = $rows->count();
        $lastPage = max(1, (int) ceil($total / $this->perPage));
        $page = min(max(1, $this->page), $lastPage);

        return new LengthAwarePaginator(
            $rows->slice(($page - 1) * $this->perPage, $this->perPage)->values(),
            $total,
            $this->perPage,
            $page,
            ['path' => request()->url()]
        );
    }

    private function declaredPricesForSelected(): array
    {
        $result = [];
        foreach ($this->selectedRows as $key) {
            [$variantId] = $this->parseRowKey($key);
            $result[$key] = DB::table('pharma_medicine_variants as v')
                ->join('pharma_medicines as m', 'm.id', '=', 'v.medicine_id')
                ->where('v.id', $variantId)->value('m.declared_price');
        }
        return $result;
    }

    private function rowKey(int $variantId, ?int $packageId): string
    {
        return $variantId.'-'.($packageId ?? 0);
    }

    private function parseRowKey(string $key): array
    {
        [$variantId, $packageId] = array_map('intval', explode('-', $key, 2));
        return [$variantId, $packageId === 0 ? null : $packageId];
    }

    private function nullablePrice(mixed $value): ?float
    {
        return $value === '' || $value === null ? null : (float) $value;
    }
}
