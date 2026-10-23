<?php

namespace Modules\Finance\Contracts;

use Modules\Finance\Data\FinancialDocumentSnapshot;

interface FinancialDocumentProvider
{
    public function module(): string;

    public function documentType(): string;

    public function resolveForPosting(string $documentId): FinancialDocumentSnapshot;
}
