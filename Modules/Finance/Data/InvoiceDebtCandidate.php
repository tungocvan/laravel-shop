<?php

namespace Modules\Finance\Data;

use InvalidArgumentException;

/**
 * Read-only invoice candidate. This is NOT a recognized debt or payment authorization.
 */
final readonly class InvoiceDebtCandidate
{
    public function __construct(
        public string $documentId,
        public string $documentCode,
        public string $direction,
        public string $amount,
        public string $issuedOn,
        public string $counterparty,
        public ?string $taxCode,
    ) {
        if (! ctype_digit($documentId) || $documentId === '0' || trim($documentCode) === ''
            || ! in_array($direction, ['receivable', 'payable'], true)
            || ! preg_match('/^\\d{1,16}(?:\\.\\d{1,2})?$/', $amount)
            || ! preg_match('/[1-9]/', $amount)
            || ! preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $issuedOn)
            || trim($counterparty) === '') {
            throw new InvalidArgumentException('Invalid invoice debt candidate.');
        }
    }

    public function sourceKey(): string
    {
        return 'Invoices:invoice:'.$this->documentId;
    }
}
