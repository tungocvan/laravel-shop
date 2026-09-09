<?php

namespace Modules\Invoices\Integrations\Inventory;

use Carbon\CarbonInterface;
use Modules\Invoices\Jobs\StageInvoiceForInventory;
use Modules\Invoices\Models\Invoices;

final class BulkInvoiceInventoryIntakeService
{
    public function dispatch(CarbonInterface $from, CarbonInterface $to, int $batchSize = 100, bool $refresh = false): int
    {
        $batchSize = max(10, min($batchSize, 500));
        $count = 0;

        Invoices::query()
            ->where('invoice_type', 'purchase')
            ->whereBetween('issued_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('id')
            ->select('id')
            ->chunkById($batchSize, function ($invoices) use (&$count, $refresh): void {
                foreach ($invoices as $invoice) {
                    StageInvoiceForInventory::dispatch((int) $invoice->id, $refresh)->onQueue('default');
                    $count++;
                }
            });

        return $count;
    }
}
