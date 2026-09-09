<?php

namespace Modules\Inventory\Livewire;

use Carbon\CarbonImmutable;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\BulkInvoicePublicationService;
use Modules\Invoices\Integrations\Inventory\BulkInvoiceInventoryIntakeService;
use Modules\Invoices\Models\InvoiceInventorySnapshot;
use Modules\Invoices\Models\InvoiceInventoryStagingLine;
use Throwable;

final class ReceivingIntakeWorkspace extends Component
{
    use WithPagination;

    public string $fromDate = '2026-01-01';

    public string $toDate = '';

    public int $batchSize = 100;

    public string $lineStatus = 'all';

    public array $selectedSnapshots = [];

    public ?int $warehouseId = null;

    public ?string $message = null;

    public ?string $error = null;

    public function mount(): void
    {
        $this->toDate = now()->toDateString();
    }

    public function dispatchIntake(): void
    {
        $this->authorizeManage();
        $this->message = $this->error = null;
        try {
            $from = CarbonImmutable::parse($this->fromDate)->startOfDay();
            $to = CarbonImmutable::parse($this->toDate)->endOfDay();
            if ($from->greaterThan($to)) {
                throw new \DomainException('Từ ngày phải nhỏ hơn hoặc bằng đến ngày.');
            }
            $count = app(BulkInvoiceInventoryIntakeService::class)->dispatch($from, $to, $this->batchSize);
            $this->message = "Đã đưa {$count} hóa đơn mua vào vào hàng đợi staging. Có thể rời màn hình và quay lại theo dõi tiến độ.";
        } catch (Throwable $exception) {
            report($exception);
            $this->error = $exception->getMessage();
        }
    }

    public function publishSelected(): void
    {
        $this->authorizeManage();
        $this->message = $this->error = null;
        try {
            $inboxes = app(BulkInvoicePublicationService::class)->publishReady($this->selectedSnapshotIds());
            $this->message = 'Đã publish '.$inboxes->count().' hóa đơn sang Inventory Inbox. Các dòng chưa match sẽ vào Review ngoại lệ.';
            $this->selectedSnapshots = [];
        } catch (Throwable $exception) {
            report($exception);
            $this->error = $exception->getMessage();
        }
    }

    public function createDraftReceipts(): void
    {
        $this->authorizeManage();
        $this->message = $this->error = null;
        try {
            if (! $this->warehouseId) {
                throw new \DomainException('Chọn kho nhận trước khi tạo phiếu nhập.');
            }
            $receipts = app(BulkInvoicePublicationService::class)->createDraftReceipts($this->selectedSnapshotIds(), $this->warehouseId, (int) auth('admin')->id());
            $this->message = 'Đã tạo/cập nhật '.$receipts->count().' phiếu nhập DRAFT. Tồn kho chưa thay đổi.';
            $this->selectedSnapshots = [];
        } catch (Throwable $exception) {
            report($exception);
            $this->error = $exception->getMessage();
        }
    }

    private function selectedSnapshotIds(): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $this->selectedSnapshots))));
        if ($ids === []) {
            throw new \DomainException('Chọn ít nhất một hóa đơn đã chuẩn hóa.');
        }

        return $ids;
    }

    private function authorizeManage(): void
    {
        abort_unless((bool) auth('admin')->user()?->can('inventory.receipt.manage'), 403);
    }

    public function render()
    {
        $stats = [
            'snapshots' => InvoiceInventorySnapshot::query()->count(),
            'normalized' => InvoiceInventorySnapshot::query()->where('status', 'NORMALIZED')->count(),
            'errors' => InvoiceInventorySnapshot::query()->where('status', 'ERROR')->count(),
            'lines' => InvoiceInventoryStagingLine::query()->count(),
        ];

        $lines = InvoiceInventoryStagingLine::query()
            ->with('snapshot.invoice:id,invoice_number,symbol,issued_date,tax_code,name')
            ->when($this->lineStatus !== 'all', fn ($query) => $query->where('normalization_status', $this->lineStatus))
            ->latest('id')->paginate(25);

        $readySnapshots = InvoiceInventorySnapshot::query()
            ->with('invoice:id,invoice_number,symbol,issued_date,tax_code,name')
            ->where('status', 'NORMALIZED')->latest('id')->limit(100)->get();
        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);

        return view('Inventory::livewire.receiving-intake-workspace', compact('stats', 'lines', 'readySnapshots', 'warehouses'));
    }
}
