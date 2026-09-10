<?php

namespace Modules\Invoices\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Invoices\Integrations\Inventory\InvoiceInventoryStagingService;
use Modules\Invoices\Models\InvoiceInventorySnapshot;
use Modules\Invoices\Models\Invoices;
use Throwable;

final class StageInvoiceForInventory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly int $invoiceId, public readonly bool $refresh = false) {}

    public function handle(InvoiceInventoryStagingService $staging): void
    {
        $invoice = Invoices::query()->where('invoice_type', 'purchase')->findOrFail($this->invoiceId);

        try {
            $staging->stage($invoice, $this->refresh);
        } catch (Throwable $exception) {
            InvoiceInventorySnapshot::query()->where('invoice_id', $invoice->id)->update([
                'status' => 'ERROR',
                'last_error' => mb_substr($exception->getMessage(), 0, 4000),
            ]);
            throw $exception;
        }
    }
}
