<?php

namespace Modules\Invoices\Integrations\Inventory;

use DomainException;
use Modules\Inventory\Models\InvoiceInbox;
use Modules\Inventory\Services\InventoryInvoiceIntegrationService;
use Modules\Invoices\Models\Invoices;

final class InvoiceInventoryHandoffService
{
    public function __construct(private readonly InvoiceForInventoryV1Factory $factory) {}

    public function handoff(int $invoiceId): InvoiceInbox
    {
        $invoice = Invoices::query()->findOrFail($invoiceId);
        if ($invoice->invoice_type !== 'purchase') {
            throw new DomainException('Chỉ hóa đơn mua vào mới được đồng bộ sang Inventory.');
        }

        if (! class_exists(InventoryInvoiceIntegrationService::class)) {
            throw new DomainException('Inventory integration hiện không khả dụng.');
        }

        return app(InventoryInvoiceIntegrationService::class)->ingest($this->factory->build($invoice));
    }
}
