<?php

namespace Modules\Inventory\Livewire;

use DomainException;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\InvoiceInbox;
use Modules\Inventory\Models\InvoiceInboxLine;
use Modules\Inventory\Models\StockBalance;
use Modules\Inventory\Models\StockMovement;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\InventoryItemMatchingService;
use Modules\Inventory\Services\InvoiceReceiptProposalService;
use Modules\Inventory\Services\ReceiptPostingService;
use Modules\Invoices\Integrations\Inventory\InvoiceInventoryHandoffService;
use Modules\Invoices\Integrations\Inventory\PurchaseInvoiceInventoryQueryService;
use Throwable;

final class InvoiceInboxWorkspace extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = 'all';
    public int $perPage = 25;
    public ?int $selectedInboxId = null;
    public ?int $warehouseId = null;
    public bool $showSourcePicker = false;
    public string $sourceSearch = '';
    public array $selectedLineIds = [];
    public ?int $bulkItemId = null;
    public ?string $errorMessage = null;
    public ?string $successMessage = null;

    public bool $showCreateItem = false;
    public ?int $creatingFromLineId = null;
    public array $itemForm = [];
    public bool $showConfirmReceipt = false;

    private const PAGE_SIZES = [10, 25, 50, 100];

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatus(): void { $this->resetPage(); }

    public function updatedPerPage($value): void
    {
        $this->perPage = in_array((int) $value, self::PAGE_SIZES, true) ? (int) $value : 25;
        $this->resetPage();
    }

    public function openSourcePicker(): void
    {
        abort_unless($this->canManageReceipt(), 403);
        $this->sourceSearch = '';
        $this->showSourcePicker = true;
    }

    public function closeSourcePicker(): void { $this->showSourcePicker = false; }

    public function selectInbox(int $id): void
    {
        $inbox = InvoiceInbox::query()->with('receipt')->findOrFail($id);
        $this->selectedInboxId = $id;
        $this->warehouseId = $inbox->receipt?->warehouse_id;
        $this->selectedLineIds = [];
        $this->bulkItemId = null;
        $this->errorMessage = null;
        $this->successMessage = null;
        $this->showConfirmReceipt = false;
        $this->closeCreateItem();
    }

    public function selectAllUnresolved(): void
    {
        if ($this->selectedInboxId === null) {
            $this->selectedLineIds = [];
            return;
        }

        $this->selectedLineIds = InvoiceInboxLine::query()
            ->where('inbox_id', $this->selectedInboxId)
            ->where('classification', 'UNRESOLVED')
            ->pluck('id')->map(fn ($id) => (string) $id)->all();
    }

    public function clearSelectedLines(): void
    {
        $this->selectedLineIds = [];
        $this->bulkItemId = null;
    }

    public function syncSourceInvoice(int $invoiceId): void
    {
        abort_unless($this->canManageReceipt(), 403);
        $this->runAction(function () use ($invoiceId): string {
            $inbox = app(InvoiceInventoryHandoffService::class)->handoff($invoiceId);
            $this->selectedInboxId = $inbox->id;
            $this->warehouseId = $inbox->receipt?->warehouse_id;
            $this->selectedLineIds = [];
            $this->showSourcePicker = false;

            return 'Đã đưa hóa đơn đủ điều kiện vào Inbox. Tồn kho chưa thay đổi.';
        });
    }

    public function assignLine(int $lineId, int $itemId): void
    {
        abort_unless($this->canManageReceipt(), 403);
        $this->runAction(function () use ($lineId, $itemId): string {
            $line = $this->ownedLine($lineId);
            $item = InventoryItem::query()->where('is_active', true)->findOrFail($itemId);
            app(InventoryItemMatchingService::class)->assign($line, $item, (int) auth('admin')->id());
            return 'Đã xác nhận mapping mặt hàng và lưu alias theo nguồn.';
        });
    }

    public function bulkAssignSelected(): void
    {
        abort_unless($this->canManageReceipt(), 403);
        $this->runAction(function (): string {
            if ($this->bulkItemId === null) {
                throw new DomainException('Chọn InventoryItem áp dụng cho các dòng đã chọn.');
            }

            $item = InventoryItem::query()->where('is_active', true)->findOrFail($this->bulkItemId);
            $lines = $this->selectedOwnedLines();
            foreach ($lines as $line) {
                app(InventoryItemMatchingService::class)->assign($line, $item, (int) auth('admin')->id());
            }

            $count = $lines->count();
            $this->clearSelectedLines();
            return "Đã mapping {$count} dòng vào {$item->sku} — {$item->display_name}.";
        });
    }

    public function markNonStock(int $lineId): void
    {
        abort_unless($this->canManageReceipt(), 403);
        $this->runAction(function () use ($lineId): string {
            app(InventoryItemMatchingService::class)->markNonStock($this->ownedLine($lineId));
            return 'Đã đánh dấu dòng NON_STOCK.';
        });
    }

    public function bulkMarkNonStock(): void
    {
        abort_unless($this->canManageReceipt(), 403);
        $this->runAction(function (): string {
            $lines = $this->selectedOwnedLines();
            foreach ($lines as $line) {
                app(InventoryItemMatchingService::class)->markNonStock($line);
            }
            $count = $lines->count();
            $this->clearSelectedLines();
            return "Đã đánh dấu {$count} dòng là NON_STOCK.";
        });
    }

    public function beginCreateItem(int $lineId): void
    {
        abort_unless((bool) auth('admin')->user()?->can('inventory.item.manage'), 403);
        $line = $this->ownedLine($lineId);
        if ($line->inventory_item_id !== null) {
            throw new DomainException('Dòng này đã có InventoryItem.');
        }

        $this->creatingFromLineId = $line->id;
        $this->itemForm = [
            'sku' => 'INV-LINE-'.$line->id,
            'display_name' => $line->description_snapshot,
            'base_uom' => trim((string) $line->source_uom) ?: 'unit',
            'lot_tracking' => filled($line->lot_number),
            'expiry_tracking' => filled($line->expiry_date),
            'allow_fractional_quantity' => false,
        ];
        $this->showCreateItem = true;
    }

    public function closeCreateItem(): void
    {
        $this->showCreateItem = false;
        $this->creatingFromLineId = null;
        $this->itemForm = [];
        $this->resetValidation();
    }

    public function saveStandaloneItem(): void
    {
        abort_unless((bool) auth('admin')->user()?->can('inventory.item.manage'), 403);
        $this->runAction(function (): string {
            if ($this->creatingFromLineId === null) {
                throw new DomainException('Không xác định được dòng hóa đơn nguồn.');
            }

            $data = $this->validate([
                'itemForm.sku' => ['required', 'string', 'max:120', Rule::unique('inventory_items', 'sku')],
                'itemForm.display_name' => ['required', 'string', 'max:255'],
                'itemForm.base_uom' => ['required', 'string', 'max:80'],
                'itemForm.lot_tracking' => ['boolean'],
                'itemForm.expiry_tracking' => ['boolean'],
                'itemForm.allow_fractional_quantity' => ['boolean'],
            ])['itemForm'];

            $line = $this->ownedLine($this->creatingFromLineId);
            $item = InventoryItem::query()->create([
                'sku' => trim($data['sku']),
                'display_name' => trim($data['display_name']),
                'base_uom' => trim($data['base_uom']),
                'lot_tracking' => (bool) $data['lot_tracking'],
                'expiry_tracking' => (bool) $data['expiry_tracking'],
                'allow_fractional_quantity' => (bool) $data['allow_fractional_quantity'],
                'is_active' => true,
                'metadata' => [
                    'created_from_invoice_inbox_line_id' => $line->id,
                    'source_invoice_identity' => $line->inbox->source_invoice_identity,
                    'creation_mode' => 'explicit_admin_review',
                ],
            ]);

            app(InventoryItemMatchingService::class)->assign($line, $item, (int) auth('admin')->id());
            $this->closeCreateItem();
            return 'Đã tạo InventoryItem sau khi review và mapping dòng hóa đơn.';
        });
    }

    public function createDraftReceipt(): void
    {
        abort_unless($this->canManageReceipt(), 403);
        $this->runAction(function (): string {
            if ($this->selectedInboxId === null || $this->warehouseId === null) {
                throw new DomainException('Hãy chọn hóa đơn Inbox và kho nhận.');
            }

            $receipt = app(InvoiceReceiptProposalService::class)->createOrRefresh(
                $this->selectedInboxId,
                $this->warehouseId,
                (int) auth('admin')->id(),
            );

            return 'Đã tạo/cập nhật phiếu nhập DRAFT '.$receipt->number.'. Hãy review quantity/UOM/lô/HSD trước khi xác nhận.';
        });
    }

    public function askConfirmReceipt(): void
    {
        abort_unless($this->canConfirmReceipt(), 403);
        $selected = $this->selectedInbox();
        if ($selected->receipt === null || $selected->receipt->status !== 'DRAFT') {
            throw new DomainException('Chưa có phiếu nhập DRAFT để xác nhận.');
        }
        $this->showConfirmReceipt = true;
    }

    public function closeConfirmReceipt(): void { $this->showConfirmReceipt = false; }

    public function confirmReceipt(): void
    {
        abort_unless($this->canConfirmReceipt(), 403);
        $this->runAction(function (): string {
            $selected = $this->selectedInbox();
            if ($selected->receipt === null) {
                throw new DomainException('Chưa có phiếu nhập để xác nhận.');
            }
            $actor = auth('admin')->user();
            abort_unless($actor !== null, 403);

            $receipt = app(ReceiptPostingService::class)->confirm($selected->receipt->id, $actor);
            $this->showConfirmReceipt = false;
            return 'Đã xác nhận phiếu '.$receipt->number.'. Stock Movement và Stock Balance đã được cập nhật qua canonical posting service.';
        });
    }

    public function render()
    {
        $base = InvoiceInbox::query();
        $stats = [
            'pending' => (clone $base)->whereIn('processing_status', ['RECEIVED', 'MATCHING'])->count(),
            'review' => (clone $base)->where('processing_status', 'REVIEW_REQUIRED')->count(),
            'ready' => (clone $base)->where('processing_status', 'READY')->count(),
            'created' => (clone $base)->where('processing_status', 'RECEIPT_CREATED')->count(),
        ];

        $rows = InvoiceInbox::query()
            ->withCount(['lines as unresolved_lines_count' => fn ($query) => $query->where('classification', 'UNRESOLVED')])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($nested): void {
                    $nested->where('invoice_number_snapshot', 'like', '%'.$this->search.'%')
                        ->orWhere('invoice_symbol_snapshot', 'like', '%'.$this->search.'%')
                        ->orWhere('seller_name_snapshot', 'like', '%'.$this->search.'%')
                        ->orWhere('seller_tax_code_snapshot', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->status !== 'all', fn ($query) => $query->where('processing_status', $this->status))
            ->latest('last_seen_at')->paginate($this->perPage);

        $selected = $this->selectedInboxId !== null
            ? InvoiceInbox::query()->with(['lines.item', 'receipt.warehouse', 'receipt.lines.item'])->find($this->selectedInboxId)
            : null;

        $movements = collect();
        $balances = collect();
        if ($selected?->receipt?->status === 'CONFIRMED') {
            $movements = StockMovement::query()
                ->where('document_type', 'receipt')
                ->where('document_id', $selected->receipt->id)
                ->orderBy('id')->get();
            $balances = StockBalance::query()
                ->whereIn('dimension_key', $movements->pluck('dimension_key')->unique()->values())
                ->get()->keyBy('dimension_key');
        }

        $sourceCandidates = collect();
        if ($this->showSourcePicker && class_exists(PurchaseInvoiceInventoryQueryService::class)) {
            $sourceCandidates = app(PurchaseInvoiceInventoryQueryService::class)->recent($this->sourceSearch, 30);
        }

        return view('Inventory::livewire.invoice-inbox-workspace', [
            'rows' => $rows,
            'selected' => $selected,
            'stats' => $stats,
            'sourceCandidates' => $sourceCandidates,
            'warehouses' => Warehouse::query()->where('is_active', true)->orderBy('name')->limit(100)->get(['id', 'code', 'name']),
            'items' => InventoryItem::query()->where('is_active', true)->orderBy('display_name')->limit(100)->get(['id', 'sku', 'display_name', 'base_uom']),
            'movements' => $movements,
            'balances' => $balances,
            'canManageReceipt' => $this->canManageReceipt(),
            'canConfirmReceipt' => $this->canConfirmReceipt(),
            'canManageItem' => (bool) auth('admin')->user()?->can('inventory.item.manage'),
        ]);
    }

    private function selectedInbox(): InvoiceInbox
    {
        if ($this->selectedInboxId === null) {
            throw new DomainException('Chọn hóa đơn Inbox trước.');
        }
        return InvoiceInbox::query()->with(['receipt.lines.item', 'lines.item'])->findOrFail($this->selectedInboxId);
    }

    private function ownedLine(int $lineId): InvoiceInboxLine
    {
        if ($this->selectedInboxId === null) { abort(404); }
        return InvoiceInboxLine::query()->with('inbox')->where('inbox_id', $this->selectedInboxId)->findOrFail($lineId);
    }

    private function selectedOwnedLines()
    {
        if ($this->selectedInboxId === null) {
            throw new DomainException('Chọn hóa đơn Inbox trước.');
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $this->selectedLineIds))));
        if ($ids === []) {
            throw new DomainException('Chọn ít nhất một dòng cần xử lý.');
        }
        $lines = InvoiceInboxLine::query()->with('inbox')->where('inbox_id', $this->selectedInboxId)->whereIn('id', $ids)->get();
        if ($lines->count() !== count($ids)) {
            throw new DomainException('Có dòng đã chọn không thuộc hóa đơn hiện tại.');
        }
        return $lines;
    }

    private function canManageReceipt(): bool
    {
        return (bool) auth('admin')->user()?->can('inventory.receipt.manage');
    }

    private function canConfirmReceipt(): bool
    {
        return (bool) auth('admin')->user()?->can('inventory.receipt.confirm');
    }

    private function runAction(callable $action): void
    {
        $this->errorMessage = null;
        $this->successMessage = null;
        try {
            $this->successMessage = $action();
        } catch (Throwable $exception) {
            report($exception);
            $this->errorMessage = $exception->getMessage();
        }
    }
}
