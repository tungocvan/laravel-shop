<?php

namespace Modules\Inventory\Livewire;

use DomainException;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\InvoiceInbox;
use Modules\Inventory\Models\InvoiceInboxLine;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\InventoryItemMatchingService;
use Modules\Inventory\Services\InvoiceReceiptProposalService;
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

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    private const PAGE_SIZES = [10, 25, 50, 100];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

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

    public function closeSourcePicker(): void
    {
        $this->showSourcePicker = false;
    }

    public function selectInbox(int $id): void
    {
        InvoiceInbox::query()->findOrFail($id);
        $this->selectedInboxId = $id;
        $this->errorMessage = null;
        $this->successMessage = null;
    }

    public function syncSourceInvoice(int $invoiceId): void
    {
        abort_unless($this->canManageReceipt(), 403);
        $this->runAction(function () use ($invoiceId): string {
            $inbox = app(InvoiceInventoryHandoffService::class)->handoff($invoiceId);
            $this->selectedInboxId = $inbox->id;
            $this->showSourcePicker = false;

            return 'Đã đưa hóa đơn vào Inbox. Tồn kho chưa thay đổi.';
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

    public function markNonStock(int $lineId): void
    {
        abort_unless($this->canManageReceipt(), 403);
        $this->runAction(function () use ($lineId): string {
            app(InventoryItemMatchingService::class)->markNonStock($this->ownedLine($lineId));

            return 'Đã đánh dấu dòng NON_STOCK.';
        });
    }

    public function createStandaloneItem(int $lineId): void
    {
        abort_unless((bool) auth('admin')->user()?->can('inventory.item.manage'), 403);
        $this->runAction(function () use ($lineId): string {
            $line = $this->ownedLine($lineId);
            if ($line->inventory_item_id !== null) {
                throw new DomainException('Dòng này đã có InventoryItem.');
            }

            $baseUom = trim((string) $line->source_uom) ?: 'unit';
            $item = InventoryItem::query()->firstOrCreate(['sku' => 'INV-LINE-'.$line->id], [
                'display_name' => $line->description_snapshot,
                'base_uom' => $baseUom,
                'lot_tracking' => false,
                'expiry_tracking' => false,
                'allow_fractional_quantity' => true,
                'is_active' => true,
                'metadata' => [
                    'created_from_invoice_inbox_line_id' => $line->id,
                    'source_invoice_identity' => $line->inbox->source_invoice_identity,
                ],
            ]);

            app(InventoryItemMatchingService::class)->assign($line, $item, (int) auth('admin')->id());

            return 'Đã tạo InventoryItem độc lập và mapping dòng hóa đơn.';
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

            return 'Đã tạo/cập nhật phiếu nhập DRAFT '.$receipt->number.'. Tồn kho chưa thay đổi cho đến khi xác nhận phiếu.';
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
            ? InvoiceInbox::query()->with(['lines.item', 'receipt'])->find($this->selectedInboxId)
            : null;

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
            'canManageReceipt' => $this->canManageReceipt(),
            'canManageItem' => (bool) auth('admin')->user()?->can('inventory.item.manage'),
        ]);
    }

    private function ownedLine(int $lineId): InvoiceInboxLine
    {
        if ($this->selectedInboxId === null) {
            abort(404);
        }

        return InvoiceInboxLine::query()->with('inbox')->where('inbox_id', $this->selectedInboxId)->findOrFail($lineId);
    }

    private function canManageReceipt(): bool
    {
        return (bool) auth('admin')->user()?->can('inventory.receipt.manage');
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
