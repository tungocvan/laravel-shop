<?php

namespace Modules\Inventory\Livewire;

use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\InvoiceInboxLine;
use Modules\Inventory\Models\Issue;
use Modules\Inventory\Models\IssueLine;
use Modules\Inventory\Models\Lot;
use Modules\Inventory\Models\Receipt;
use Modules\Inventory\Models\ReceiptLine;
use Modules\Inventory\Models\Stocktake;
use Modules\Inventory\Models\StocktakeLine;
use Modules\Inventory\Models\Transfer;
use Modules\Inventory\Models\TransferLine;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\IssuePostingService;
use Modules\Inventory\Services\ReceiptPostingService;
use Modules\Inventory\Services\StocktakePostingService;
use Modules\Inventory\Services\TransferPostingService;
use Throwable;

final class AdminWorkspace extends Component
{
    use WithPagination;

    public string $workspace = 'stock';

    public string $search = '';

    public string $status = 'all';

    public string $warehouseFilter = 'all';

    public int $perPage = 25;

    public bool $formOpen = false;

    public ?int $editingId = null;

    public ?int $confirmingId = null;

    public ?string $errorMessage = null;

    public array $form = [];

    public array $lines = [];

    public array $itemInventorySummary = [];

    private const PAGE_SIZES = [10, 25, 50, 100];

    public function mount(string $workspace): void
    {
        abort_unless(in_array($workspace, ['warehouses', 'items', 'receipts', 'issues', 'transfers', 'stocktakes', 'stock', 'lots', 'movements'], true), 404);
        $this->workspace = $workspace;
        $this->resetForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedWarehouseFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage($value): void
    {
        $this->perPage = in_array((int) $value, self::PAGE_SIZES, true) ? (int) $value : 25;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->status = 'all';
        $this->warehouseFilter = 'all';
        $this->resetPage();
    }

    public function create(): void
    {
        abort_unless($this->canManage(), 403);
        abort_if(in_array($this->workspace, ['stock', 'lots', 'movements'], true), 404);
        $this->editingId = null;
        $this->resetForm();
        $this->itemInventorySummary = [];
        $this->formOpen = true;
    }

    public function edit(int $id): void
    {
        abort_unless($this->canManage(), 403);
        $this->editingId = $id;
        $this->loadForm($id);
        $this->itemInventorySummary = $this->workspace === 'items'
            ? $this->buildItemInventorySummary($id)
            : [];
        $this->formOpen = true;
    }

    public function addLine(): void
    {
        $this->lines[] = ['inventory_item_id' => '', 'lot_id' => '', 'quantity' => '1', 'unit_cost' => '', 'lot_number' => '', 'expiry_date' => '', 'notes' => ''];
    }

    public function removeLine(int $index): void
    {
        if (count($this->lines) > 1) {
            unset($this->lines[$index]);
            $this->lines = array_values($this->lines);
        }
    }

    public function save(): void
    {
        abort_unless($this->canManage(), 403);
        $this->errorMessage = null;

        try {
            match ($this->workspace) {
                'warehouses' => $this->saveWarehouse(),
                'items' => $this->saveItem(),
                'receipts', 'issues', 'transfers', 'stocktakes' => $this->saveDocument(),
                default => throw new DomainException('Workspace này chỉ đọc.'),
            };
            $this->formOpen = false;
            $this->dispatch('inventory-saved');
        } catch (Throwable $exception) {
            report($exception);
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function askConfirm(int $id): void
    {
        abort_unless($this->canConfirm(), 403);
        $this->confirmingId = $id;
    }

    public function confirmDocument(): void
    {
        abort_unless($this->canConfirm(), 403);
        abort_if($this->confirmingId === null, 422);
        $actor = auth('admin')->user();
        abort_unless($actor !== null, 403);

        $id = $this->confirmingId;
        $this->errorMessage = null;

        try {
            match ($this->workspace) {
                'receipts' => app(ReceiptPostingService::class)->confirm($id, $actor),
                'issues' => app(IssuePostingService::class)->confirm($id, $actor),
                'transfers' => app(TransferPostingService::class)->confirm($id, $actor),
                'stocktakes' => app(StocktakePostingService::class)->confirm($id, $actor),
                default => throw new DomainException('Workspace không hỗ trợ xác nhận.'),
            };
            $this->confirmingId = null;
            $this->dispatch('inventory-confirmed');
        } catch (Throwable $exception) {
            report($exception);
            $this->errorMessage = $exception->getMessage();
        }
    }

    public function render()
    {
        $rows = $this->rows();

        $itemStockSummaries = $this->workspace === 'items'
            ? collect($rows->items())
                ->mapWithKeys(fn (InventoryItem $item) => [
                    $item->id => $this->buildItemInventorySummary($item->id),
                ])
                ->all()
            : [];

        return view('Inventory::livewire.admin-workspace', [
            'rows' => $rows,
            'itemStockSummaries' => $itemStockSummaries,
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'items' => InventoryItem::query()->where('is_active', true)->orderBy('display_name')->get(['id', 'sku', 'display_name', 'base_uom', 'lot_tracking', 'expiry_tracking']),
            'lots' => Lot::query()->orderBy('expiry_date')->get(['id', 'inventory_item_id', 'lot_number', 'expiry_date']),
            'title' => $this->title(),
            'canManage' => $this->canManage(),
            'canConfirm' => $this->canConfirm(),
        ]);
    }

    private function rows(): LengthAwarePaginator
    {
        return match ($this->workspace) {
            'warehouses' => $this->warehouseRows(),
            'items' => $this->itemRows(),
            'receipts' => $this->documentRows(Receipt::query()->with('warehouse')),
            'issues' => $this->documentRows(Issue::query()->with('warehouse')),
            'transfers' => $this->transferRows(),
            'stocktakes' => $this->documentRows(Stocktake::query()->with('warehouse')),
            'stock' => $this->stockRows(),
            'lots' => $this->lotRows(),
            'movements' => $this->movementRows(),
        };
    }

    private function warehouseRows(): LengthAwarePaginator
    {
        return Warehouse::query()
            ->when($this->search !== '', fn (Builder $q) => $q->where(fn (Builder $x) => $x->where('code', 'like', '%'.$this->search.'%')->orWhere('name', 'like', '%'.$this->search.'%')->orWhere('address', 'like', '%'.$this->search.'%')))
            ->when($this->status !== 'all', fn (Builder $q) => $q->where('is_active', $this->status === 'active'))
            ->orderBy('code')->paginate($this->perPage);
    }

    private function itemRows(): LengthAwarePaginator
    {
        return InventoryItem::query()
            ->when($this->search !== '', fn (Builder $q) => $q->where(fn (Builder $x) => $x->where('sku', 'like', '%'.$this->search.'%')->orWhere('display_name', 'like', '%'.$this->search.'%')))
            ->when($this->status !== 'all', fn (Builder $q) => $q->where('is_active', $this->status === 'active'))
            ->orderBy('sku')->paginate($this->perPage);
    }

    private function documentRows(Builder $query): LengthAwarePaginator
    {
        return $query
            ->when($this->search !== '', fn (Builder $q) => $q->where('number', 'like', '%'.$this->search.'%'))
            ->when($this->status !== 'all', fn (Builder $q) => $q->where('status', $this->status))
            ->when($this->warehouseFilter !== 'all', fn (Builder $q) => $q->where('warehouse_id', (int) $this->warehouseFilter))
            ->latest('id')->paginate($this->perPage);
    }

    private function transferRows(): LengthAwarePaginator
    {
        return Transfer::query()->with(['sourceWarehouse', 'destinationWarehouse'])
            ->when($this->search !== '', fn (Builder $q) => $q->where('number', 'like', '%'.$this->search.'%'))
            ->when($this->status !== 'all', fn (Builder $q) => $q->where('status', $this->status))
            ->when($this->warehouseFilter !== 'all', fn (Builder $q) => $q->where(fn (Builder $x) => $x->where('source_warehouse_id', (int) $this->warehouseFilter)->orWhere('destination_warehouse_id', (int) $this->warehouseFilter)))
            ->latest('id')->paginate($this->perPage);
    }

    private function stockRows(): LengthAwarePaginator
    {
        $query = DB::table('inventory_balances as b')
            ->join('inventory_items as i', 'i.id', '=', 'b.inventory_item_id')
            ->join('inventory_warehouses as w', 'w.id', '=', 'b.warehouse_id')
            ->leftJoin('inventory_lots as l', 'l.id', '=', 'b.lot_id')
            ->select(['b.*', 'i.sku', 'i.display_name', 'i.reorder_level', 'w.code as warehouse_code', 'w.name as warehouse_name', 'l.lot_number', 'l.expiry_date'])
            ->when($this->search !== '', fn ($q) => $q->where(fn ($x) => $x->where('i.sku', 'like', '%'.$this->search.'%')->orWhere('i.display_name', 'like', '%'.$this->search.'%')->orWhere('l.lot_number', 'like', '%'.$this->search.'%')))
            ->when($this->warehouseFilter !== 'all', fn ($q) => $q->where('b.warehouse_id', (int) $this->warehouseFilter));

        if ($this->status === 'low') {
            $query->whereNotNull('i.reorder_level')->whereColumn('b.quantity_on_hand', '<=', 'i.reorder_level');
        } elseif ($this->status === 'positive') {
            $query->where('b.quantity_on_hand', '>', 0);
        } elseif ($this->status === 'zero') {
            $query->where('b.quantity_on_hand', '=', 0);
        }

        return $query->orderBy('i.sku')->paginate($this->perPage);
    }

    private function lotRows(): LengthAwarePaginator
    {
        $query = DB::table('inventory_lots as l')
            ->join('inventory_items as i', 'i.id', '=', 'l.inventory_item_id')
            ->select(['l.*', 'i.sku', 'i.display_name'])
            ->when($this->search !== '', fn ($q) => $q->where(fn ($x) => $x->where('i.sku', 'like', '%'.$this->search.'%')->orWhere('i.display_name', 'like', '%'.$this->search.'%')->orWhere('l.lot_number', 'like', '%'.$this->search.'%')));

        if ($this->status === 'expired') {
            $query->whereDate('l.expiry_date', '<', today());
        } elseif ($this->status === 'expiring') {
            $query->whereBetween('l.expiry_date', [today(), now()->addDays(90)]);
        } elseif ($this->status === 'valid') {
            $query->where(fn ($q) => $q->whereNull('l.expiry_date')->orWhereDate('l.expiry_date', '>=', today()));
        }

        return $query->orderByRaw('l.expiry_date is null, l.expiry_date asc')->paginate($this->perPage);
    }

    private function movementRows(): LengthAwarePaginator
    {
        return DB::table('inventory_movements as m')
            ->join('inventory_items as i', 'i.id', '=', 'm.inventory_item_id')
            ->join('inventory_warehouses as w', 'w.id', '=', 'm.warehouse_id')
            ->leftJoin('inventory_lots as l', 'l.id', '=', 'm.lot_id')
            ->select(['m.*', 'i.sku', 'i.display_name', 'w.code as warehouse_code', 'w.name as warehouse_name', 'l.lot_number'])
            ->when($this->search !== '', fn ($q) => $q->where(fn ($x) => $x->where('i.sku', 'like', '%'.$this->search.'%')->orWhere('i.display_name', 'like', '%'.$this->search.'%')->orWhere('m.document_type', 'like', '%'.$this->search.'%')->orWhere('m.document_id', $this->numericSearch())))
            ->when($this->status !== 'all', fn ($q) => $q->where('m.movement_type', $this->status))
            ->when($this->warehouseFilter !== 'all', fn ($q) => $q->where('m.warehouse_id', (int) $this->warehouseFilter))
            ->latest('m.occurred_at')->paginate($this->perPage);
    }

    private function buildItemInventorySummary(int $itemId): array
    {
        $item = InventoryItem::query()->find($itemId);

        if ($item === null) {
            return [
                'total_quantity' => '0',
                'base_uom' => '',
                'dimensions' => [],
                'packaging' => null,
                'lifecycle' => 'catalog',
                'lifecycle_label' => 'Danh mục',
                'source' => null,
            ];
        }

        $allDimensions = DB::table('inventory_balances as b')
            ->join('inventory_warehouses as w', 'w.id', '=', 'b.warehouse_id')
            ->leftJoin('inventory_lots as l', 'l.id', '=', 'b.lot_id')
            ->where('b.inventory_item_id', $itemId)
            ->select([
                'b.quantity_on_hand',
                'w.code as warehouse_code',
                'w.name as warehouse_name',
                'l.lot_number',
                'l.manufacture_date',
                'l.expiry_date',
            ])
            ->orderBy('w.code')
            ->orderByRaw('l.expiry_date is null, l.expiry_date asc')
            ->get();

        $total = $allDimensions->sum(fn ($row) => (float) $row->quantity_on_hand);
        $dimensions = $allDimensions
            ->filter(fn ($row) => abs((float) $row->quantity_on_hand) > 0.00000001)
            ->values();

        $sourceLineId = (int) data_get($item->metadata, 'created_from_invoice_inbox_line_id', 0);
        $sourceLine = $sourceLineId > 0
            ? InvoiceInboxLine::query()->with(['inbox.receipt'])->find($sourceLineId)
            : null;
        $sourceInbox = $sourceLine?->inbox;
        $sourceReceipt = $sourceInbox?->receipt;
        $hasMovement = DB::table('inventory_movements')
            ->where('inventory_item_id', $itemId)
            ->exists();

        if (! $item->is_active) {
            $lifecycle = 'inactive';
            $lifecycleLabel = 'Ngừng hoạt động';
        } elseif ($sourceLineId > 0 && $sourceReceipt?->status !== 'CONFIRMED' && ! $hasMovement) {
            $lifecycle = 'pending_receipt';
            $lifecycleLabel = 'Chờ xác nhận nhập kho';
        } elseif ($total > 0.00000001) {
            $lifecycle = 'in_stock';
            $lifecycleLabel = 'Đang tồn kho';
        } elseif ($hasMovement || $sourceReceipt?->status === 'CONFIRMED') {
            $lifecycle = 'zero_stock';
            $lifecycleLabel = 'Đã xác nhận · Hết tồn';
        } else {
            $lifecycle = 'catalog';
            $lifecycleLabel = 'Danh mục · Chưa có tồn';
        }

        $packaging = data_get($item->metadata, 'packaging');

        return [
            'total_quantity' => $this->displayQuantity($total),
            'base_uom' => (string) $item->base_uom,
            'dimensions' => $dimensions->map(fn ($row) => [
                'quantity' => $this->displayQuantity((float) $row->quantity_on_hand),
                'warehouse_code' => (string) $row->warehouse_code,
                'warehouse_name' => (string) $row->warehouse_name,
                'lot_number' => $row->lot_number,
                'manufacture_date' => $row->manufacture_date,
                'expiry_date' => $row->expiry_date,
            ])->all(),
            'packaging' => is_array($packaging) ? $packaging : null,
            'lifecycle' => $lifecycle,
            'lifecycle_label' => $lifecycleLabel,
            'source' => $sourceInbox ? [
                'inbox_id' => $sourceInbox->id,
                'invoice_number' => (string) ($sourceInbox->invoice_number_snapshot ?? ''),
                'seller_name' => (string) ($sourceInbox->seller_name_snapshot ?? ''),
                'receipt_number' => $sourceReceipt?->number,
                'receipt_status' => $sourceReceipt?->status,
            ] : null,
        ];
    }

    private function displayQuantity(float $value): string
    {
        return rtrim(
            rtrim(number_format($value, 6, '.', ''), '0'),
            '.'
        );
    }

    private function numericSearch(): int
    {
        return ctype_digit($this->search) ? (int) $this->search : -1;
    }

    private function saveWarehouse(): void
    {
        $data = $this->validate([
            'form.code' => ['required', 'string', 'max:80', Rule::unique('inventory_warehouses', 'code')->ignore($this->editingId)],
            'form.name' => ['required', 'string', 'max:255'],
            'form.address' => ['nullable', 'string'],
            'form.province_code' => ['nullable', 'string', 'max:30'],
            'form.is_active' => ['boolean'],
        ])['form'];

        $actorId = auth('admin')->id();
        Warehouse::query()->updateOrCreate(['id' => $this->editingId], $data + ['updated_by' => $actorId, 'created_by' => $this->editingId ? Warehouse::query()->whereKey($this->editingId)->value('created_by') : $actorId]);
    }

    private function saveItem(): void
    {
        $data = $this->validate([
            'form.sku' => ['required', 'string', 'max:120', Rule::unique('inventory_items', 'sku')->ignore($this->editingId)],
            'form.display_name' => ['required', 'string', 'max:255'],
            'form.base_uom' => ['required', 'string', 'max:80'],
            'form.reorder_level' => ['nullable', 'numeric', 'min:0'],
            'form.lot_tracking' => ['boolean'],
            'form.expiry_tracking' => ['boolean'],
            'form.allow_fractional_quantity' => ['boolean'],
            'form.is_active' => ['boolean'],
        ])['form'];

        if ($data['expiry_tracking'] && ! $data['lot_tracking']) {
            throw new DomainException('Theo dõi HSD yêu cầu bật theo dõi lô.');
        }

        InventoryItem::query()->updateOrCreate(['id' => $this->editingId], $data);
    }

    private function saveDocument(): void
    {
        $this->validateDocument();
        DB::transaction(function (): void {
            match ($this->workspace) {
                'receipts' => $this->persistReceipt(),
                'issues' => $this->persistIssue(),
                'transfers' => $this->persistTransfer(),
                'stocktakes' => $this->persistStocktake(),
            };
        });
    }

    private function validateDocument(): void
    {
        $rules = [
            'form.number' => ['required', 'string', 'max:80'],
            'form.document_date' => ['nullable', 'date'],
            'form.notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.inventory_item_id' => ['required', 'integer', 'exists:inventory_items,id'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.lot_id' => ['nullable', 'integer', 'exists:inventory_lots,id'],
        ];
        if ($this->workspace === 'transfers') {
            $rules['form.source_warehouse_id'] = ['required', 'different:form.destination_warehouse_id', 'exists:inventory_warehouses,id'];
            $rules['form.destination_warehouse_id'] = ['required', 'exists:inventory_warehouses,id'];
        } else {
            $rules['form.warehouse_id'] = ['required', 'exists:inventory_warehouses,id'];
        }
        if ($this->workspace === 'receipts') {
            $rules['lines.*.unit_cost'] = ['nullable', 'numeric', 'min:0'];
            $rules['lines.*.expiry_date'] = ['nullable', 'date'];
        }
        if ($this->workspace === 'stocktakes') {
            $rules['lines.*.quantity'] = ['required', 'numeric', 'min:0'];
        }
        $this->validate($rules);
    }

    private function persistReceipt(): void
    {
        $actorId = auth('admin')->id();
        $receipt = Receipt::query()->updateOrCreate(['id' => $this->editingId], [
            'number' => $this->form['number'], 'warehouse_id' => $this->form['warehouse_id'], 'status' => 'DRAFT',
            'source_type' => 'manual', 'document_date' => $this->form['document_date'] ?: null, 'notes' => $this->form['notes'] ?: null,
            'created_by' => $actorId, 'updated_by' => $actorId,
        ]);
        $receipt->lines()->delete();
        foreach ($this->lines as $index => $line) {
            $item = InventoryItem::query()->findOrFail($line['inventory_item_id']);
            ReceiptLine::query()->create([
                'receipt_id' => $receipt->id, 'line_number' => $index + 1, 'inventory_item_id' => $item->id,
                'classification' => 'STOCK', 'source_quantity' => $line['quantity'], 'source_uom' => $item->base_uom,
                'conversion_factor' => 1, 'base_quantity' => $line['quantity'], 'base_uom' => $item->base_uom,
                'unit_cost' => $line['unit_cost'] ?: null, 'lot_number' => $line['lot_number'] ?: null,
                'expiry_date' => $line['expiry_date'] ?: null, 'manufacture_date' => null, 'lot_id' => $line['lot_id'] ?: null,
                'description_snapshot' => $item->display_name,
            ]);
        }
    }

    private function persistIssue(): void
    {
        $actorId = auth('admin')->id();
        $issue = Issue::query()->updateOrCreate(['id' => $this->editingId], [
            'number' => $this->form['number'], 'warehouse_id' => $this->form['warehouse_id'], 'status' => 'DRAFT',
            'document_date' => $this->form['document_date'] ?: null, 'notes' => $this->form['notes'] ?: null,
            'created_by' => $actorId, 'updated_by' => $actorId,
        ]);
        $issue->lines()->delete();
        foreach ($this->lines as $index => $line) {
            $item = InventoryItem::query()->findOrFail($line['inventory_item_id']);
            IssueLine::query()->create(['issue_id' => $issue->id, 'line_number' => $index + 1, 'inventory_item_id' => $item->id, 'lot_id' => $line['lot_id'] ?: null, 'base_quantity' => $line['quantity'], 'base_uom' => $item->base_uom, 'notes' => $line['notes'] ?: null]);
        }
    }

    private function persistTransfer(): void
    {
        $actorId = auth('admin')->id();
        $transfer = Transfer::query()->updateOrCreate(['id' => $this->editingId], [
            'number' => $this->form['number'], 'source_warehouse_id' => $this->form['source_warehouse_id'], 'destination_warehouse_id' => $this->form['destination_warehouse_id'],
            'status' => 'DRAFT', 'document_date' => $this->form['document_date'] ?: null, 'notes' => $this->form['notes'] ?: null,
            'created_by' => $actorId, 'updated_by' => $actorId,
        ]);
        $transfer->lines()->delete();
        foreach ($this->lines as $index => $line) {
            $item = InventoryItem::query()->findOrFail($line['inventory_item_id']);
            TransferLine::query()->create(['transfer_id' => $transfer->id, 'line_number' => $index + 1, 'inventory_item_id' => $item->id, 'lot_id' => $line['lot_id'] ?: null, 'base_quantity' => $line['quantity'], 'base_uom' => $item->base_uom]);
        }
    }

    private function persistStocktake(): void
    {
        $actorId = auth('admin')->id();
        $stocktake = Stocktake::query()->updateOrCreate(['id' => $this->editingId], [
            'number' => $this->form['number'], 'warehouse_id' => $this->form['warehouse_id'], 'status' => 'DRAFT',
            'notes' => $this->form['notes'] ?: null, 'created_by' => $actorId, 'updated_by' => $actorId,
        ]);
        $stocktake->lines()->delete();
        foreach ($this->lines as $index => $line) {
            $item = InventoryItem::query()->findOrFail($line['inventory_item_id']);
            $dimension = DB::table('inventory_balances')->where('warehouse_id', $stocktake->warehouse_id)->where('inventory_item_id', $item->id)->where('lot_id', $line['lot_id'] ?: null)->first();
            StocktakeLine::query()->create(['stocktake_id' => $stocktake->id, 'line_number' => $index + 1, 'inventory_item_id' => $item->id, 'lot_id' => $line['lot_id'] ?: null, 'system_quantity_snapshot' => (string) ($dimension->quantity_on_hand ?? '0'), 'counted_quantity' => $line['quantity'], 'base_uom' => $item->base_uom, 'notes' => $line['notes'] ?: null]);
        }
    }

    private function loadForm(int $id): void
    {
        if ($this->workspace === 'warehouses') {
            $model = Warehouse::query()->findOrFail($id);
            $this->form = $model->only(['code', 'name', 'address', 'province_code', 'is_active']);

            return;
        }
        if ($this->workspace === 'items') {
            $model = InventoryItem::query()->findOrFail($id);
            $this->form = $model->only(['sku', 'display_name', 'base_uom', 'reorder_level', 'lot_tracking', 'expiry_tracking', 'allow_fractional_quantity', 'is_active']);

            return;
        }

        $model = match ($this->workspace) {
            'receipts' => Receipt::query()->with('lines')->findOrFail($id),
            'issues' => Issue::query()->with('lines')->findOrFail($id),
            'transfers' => Transfer::query()->with('lines')->findOrFail($id),
            'stocktakes' => Stocktake::query()->with('lines')->findOrFail($id),
        };
        abort_if($model->status !== 'DRAFT', 409);
        $this->form = $model->only(['number', 'warehouse_id', 'source_warehouse_id', 'destination_warehouse_id', 'document_date', 'notes']);
        $this->form['document_date'] = $model->document_date?->format('Y-m-d\TH:i');
        $this->lines = $model->lines->map(fn ($line) => ['inventory_item_id' => (string) $line->inventory_item_id, 'lot_id' => (string) ($line->lot_id ?? ''), 'quantity' => (string) ($line->base_quantity ?? $line->counted_quantity ?? '0'), 'unit_cost' => (string) ($line->unit_cost ?? ''), 'lot_number' => (string) ($line->lot_number ?? ''), 'expiry_date' => $line->expiry_date?->format('Y-m-d') ?? '', 'notes' => (string) ($line->notes ?? '')])->all();
    }

    private function resetForm(): void
    {
        $this->errorMessage = null;
        $this->form = match ($this->workspace) {
            'warehouses' => ['code' => '', 'name' => '', 'address' => '', 'province_code' => '', 'is_active' => true],
            'items' => ['sku' => '', 'display_name' => '', 'base_uom' => '', 'reorder_level' => '', 'lot_tracking' => false, 'expiry_tracking' => false, 'allow_fractional_quantity' => true, 'is_active' => true],
            'transfers' => ['number' => '', 'source_warehouse_id' => '', 'destination_warehouse_id' => '', 'document_date' => now()->format('Y-m-d\TH:i'), 'notes' => ''],
            default => ['number' => '', 'warehouse_id' => '', 'document_date' => now()->format('Y-m-d\TH:i'), 'notes' => ''],
        };
        $this->lines = [['inventory_item_id' => '', 'lot_id' => '', 'quantity' => '1', 'unit_cost' => '', 'lot_number' => '', 'expiry_date' => '', 'notes' => '']];
    }

    private function title(): string
    {
        return match ($this->workspace) {
            'warehouses' => 'Kho hàng', 'items' => 'Mặt hàng tồn kho', 'receipts' => 'Phiếu nhập kho', 'issues' => 'Phiếu xuất kho',
            'transfers' => 'Điều chuyển kho', 'stocktakes' => 'Kiểm kê', 'stock' => 'Tồn kho hiện tại', 'lots' => 'Lô / HSD', 'movements' => 'Sổ biến động kho',
        };
    }

    private function ability(string $suffix): string
    {
        return 'inventory.'.match ($this->workspace) {
            'warehouses' => 'warehouse', 'items' => 'item', 'receipts' => 'receipt', 'issues' => 'issue', 'transfers' => 'transfer', 'stocktakes' => 'stocktake',
            'stock', 'lots' => 'stock', 'movements' => 'movement',
        }.'.'.$suffix;
    }

    private function canManage(): bool
    {
        return ! in_array($this->workspace, ['stock', 'lots', 'movements'], true) && (bool) auth('admin')->user()?->can($this->ability('manage'));
    }

    private function canConfirm(): bool
    {
        return in_array($this->workspace, ['receipts', 'issues', 'transfers', 'stocktakes'], true) && (bool) auth('admin')->user()?->can($this->ability('confirm'));
    }
}
