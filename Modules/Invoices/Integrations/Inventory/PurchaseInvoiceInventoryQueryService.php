<?php

namespace Modules\Invoices\Integrations\Inventory;

use Illuminate\Support\Collection;
use Modules\Invoices\Models\Invoices;

final class PurchaseInvoiceInventoryQueryService
{
    public function recent(string $search = '', int $limit = 50): Collection
    {
        $search = trim($search);
        $limit = max(1, min($limit, 100));

        return Invoices::query()
            ->where('invoice_type', 'purchase')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('invoice_number', 'like', '%'.$search.'%')
                        ->orWhere('symbol', 'like', '%'.$search.'%')
                        ->orWhere('tax_code', 'like', '%'.$search.'%')
                        ->orWhere('name', 'like', '%'.$search.'%');
                });
            })
            ->latest('issued_date')
            ->latest('id')
            ->limit($limit)
            ->get(['id', 'invoice_number', 'symbol', 'issued_date', 'tax_code', 'name', 'total_amount']);
    }
}
