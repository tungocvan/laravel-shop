<?php

namespace Modules\Finance\Contracts;

use Modules\Finance\Data\InvoiceDebtCandidate;

/**
 * Only discovers potential debt; it never confirms debt or posts a voucher.
 */
interface InvoiceDebtCandidateReader
{
    public function find(string $invoiceId): InvoiceDebtCandidate;
}
