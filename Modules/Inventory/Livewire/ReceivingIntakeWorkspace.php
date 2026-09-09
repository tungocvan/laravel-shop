<?php

namespace Modules\Inventory\Livewire;

use Carbon\CarbonImmutable;
use Livewire\Component;
use Livewire\WithPagination;
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

    public ?string $message = null;

    public ?string $error = null;

    public function mount(): void
    {
        $this->toDate = now()->toDateString();
    }

    public function dispatchIntake(): void
    {
        abort_unless((bool) auth('admin')->user()?->can('inventory.receipt.manage'), 403);
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

        return view('Inventory::livewire.receiving-intake-workspace', compact('stats', 'lines'));
    }
}
