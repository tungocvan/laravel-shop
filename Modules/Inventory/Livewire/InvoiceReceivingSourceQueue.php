<?php

namespace Modules\Inventory\Livewire;

use Livewire\Component;
use Modules\Inventory\Models\InvoiceInbox;
use Modules\Invoices\Integrations\Inventory\InvoiceInventoryHandoffService;
use Modules\Invoices\Integrations\Inventory\PurchaseInvoiceInventoryQueryService;
use Throwable;

final class InvoiceReceivingSourceQueue extends Component
{
    public int $year;
    public int $month;
    public string $search = '';
    public string $queueStatus = 'all';
    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->year = (int) now()->year;
        $this->month = (int) now()->month;
    }

    public function updatedYear(): void { $this->errorMessage = null; }
    public function updatedMonth(): void { $this->errorMessage = null; }
    public function updatedSearch(): void { $this->errorMessage = null; }
    public function updatedQueueStatus(): void { $this->errorMessage = null; }

    public function startReceiving(int $invoiceId): void
    {
        abort_unless((bool) auth('admin')->user()?->can('inventory.receipt.manage'), 403);
        $this->errorMessage = null;

        try {
            $inbox = app(InvoiceInventoryHandoffService::class)->handoff($invoiceId);
            $this->redirectRoute('admin.inventory.invoice-inbox', ['inbox' => $inbox->id], navigate: true);
        } catch (Throwable $e) {
            report($e);
            $this->errorMessage = $e->getMessage();
        }
    }

    public function continueReceiving(int $inboxId): void
    {
        $inbox = InvoiceInbox::query()->findOrFail($inboxId);
        $this->redirectRoute('admin.inventory.invoice-inbox', ['inbox' => $inbox->id], navigate: true);
    }

    public function render()
    {
        $invoices = app(PurchaseInvoiceInventoryQueryService::class)
            ->forReceivingQueue($this->year, $this->month, $this->search, 100);

        $inboxByInvoice = InvoiceInbox::query()
            ->with('receipt')
            ->where('source_module', 'Invoices')
            ->whereIn('source_invoice_id', $invoices->pluck('id'))
            ->get()
            ->keyBy('source_invoice_id');

        $rows = $invoices->map(function ($invoice) use ($inboxByInvoice): array {
            $source = $invoice->sourceRecord;
            $inbox = $inboxByInvoice->get($invoice->id);
            $hasRaw = (bool) $source?->hasUsableDetail();
            $classification = (string) ($source?->business_classification ?? 'UNCLASSIFIED');

            $state = match (true) {
                $inbox?->receipt?->status === 'CONFIRMED' => 'confirmed',
                $inbox !== null => 'in_progress',
                ! $hasRaw => 'needs_raw',
                in_array($classification, ['GOODS', 'MIXED'], true) => 'ready',
                default => 'needs_review',
            };

            return compact('invoice', 'source', 'inbox', 'hasRaw', 'classification', 'state');
        })->when($this->queueStatus !== 'all', fn ($rows) => $rows->where('state', $this->queueStatus))->values();

        $counts = [
            'all' => $invoices->count(),
            'ready' => $rows->where('state', 'ready')->count(),
            'needs_raw' => $rows->where('state', 'needs_raw')->count(),
            'in_progress' => $rows->where('state', 'in_progress')->count(),
            'confirmed' => $rows->where('state', 'confirmed')->count(),
        ];

        return view('Inventory::livewire.invoice-receiving-source-queue', [
            'rows' => $rows,
            'counts' => $counts,
            'years' => range((int) now()->year, max(2020, (int) now()->year - 8)),
            'canManageReceipt' => (bool) auth('admin')->user()?->can('inventory.receipt.manage'),
        ]);
    }
}
