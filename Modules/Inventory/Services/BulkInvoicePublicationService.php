<?php

namespace Modules\Inventory\Services;

use DomainException;
use Illuminate\Support\Collection;
use Modules\Inventory\Models\InvoiceInbox;
use Modules\Inventory\Models\Receipt;
use Modules\Invoices\Integrations\Inventory\InvoiceForInventoryV1Factory;
use Modules\Invoices\Models\InvoiceInventorySnapshot;

final class BulkInvoicePublicationService
{
    public function __construct(
        private readonly InvoiceForInventoryV1Factory $factory,
        private readonly InventoryInvoiceIntegrationService $integration,
        private readonly InvoiceReceiptProposalService $receipts,
    ) {}

    /** @return Collection<int, InvoiceInbox> */
    public function publishReady(array $snapshotIds): Collection
    {
        $snapshots = InvoiceInventorySnapshot::query()
            ->with('invoice')
            ->whereIn('id', array_values(array_unique(array_map('intval', $snapshotIds))))
            ->where('status', 'NORMALIZED')
            ->orderBy('id')
            ->get();

        return $snapshots->map(function (InvoiceInventorySnapshot $snapshot): InvoiceInbox {
            if ($snapshot->invoice === null) {
                throw new DomainException('Snapshot không còn hóa đơn nguồn.');
            }

            return $this->integration->ingest($this->factory->buildFromSnapshot($snapshot));
        });
    }

    /** @return Collection<int, Receipt> */
    public function createDraftReceipts(array $snapshotIds, int $warehouseId, int $actorId): Collection
    {
        return $this->publishReady($snapshotIds)->map(function (InvoiceInbox $inbox) use ($warehouseId, $actorId) {
            if ($inbox->processing_status !== 'READY' && $inbox->processing_status !== 'RECEIPT_CREATED') {
                throw new DomainException("Hóa đơn {$inbox->invoice_number_snapshot} còn dòng cần review; chưa thể tạo phiếu nhập.");
            }

            return $this->receipts->createOrRefresh($inbox->id, $warehouseId, $actorId);
        });
    }
}
