<?php

namespace Modules\Inventory\Services;

use DomainException;
use Illuminate\Support\Arr;

final class InvoiceReceivingEligibilityService
{
    /**
     * Inventory may only receive invoices that have explicit business evidence.
     * MIXED is allowed into line-level review, but never treated wholesale as GOODS.
     */
    public function assertEligible(array $contract): string
    {
        $classification = strtoupper(trim((string) Arr::get($contract, 'metadata.source_business_classification', 'UNCLASSIFIED')));

        if ($classification === 'GOODS') {
            return $classification;
        }

        if ($classification === 'MIXED') {
            return $classification;
        }

        if ($classification === 'SERVICE_EXPENSE') {
            throw new DomainException('Hóa đơn được phân loại SERVICE_EXPENSE nên không đủ điều kiện tạo nghiệp vụ nhập kho.');
        }

        throw new DomainException('Hóa đơn chưa được phân loại GOODS/MIXED tại Invoices Source Data. Hãy phân loại nguồn trước khi đưa vào Inventory.');
    }
}
