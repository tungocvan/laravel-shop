<?php

namespace Modules\Finance\Data;

use InvalidArgumentException;

final readonly class FinancialDocumentSnapshot
{
    public function __construct(
        public string $module,
        public string $type,
        public string $documentId,
        public string $documentCode,
        public string $direction,
        public string $currency,
        public string $amount,
        public ?string $counterparty = null,
    ) {
        if ($module === '' || $type === '' || $documentId === '' || $documentCode === '') {
            throw new InvalidArgumentException('Document identity is required.');
        }

        if (! in_array($direction, ['receivable', 'payable'], true)) {
            throw new InvalidArgumentException('Invalid direction.');
        }

        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new InvalidArgumentException('Invalid currency.');
        }

        if (! preg_match('/^\d{1,16}(?:\.\d{1,2})?$/', $amount) || (float) $amount <= 0) {
            throw new InvalidArgumentException('Invalid monetary amount.');
        }
    }

    public function key(): string
    {
        return $this->module.':'.$this->type.':'.$this->documentId;
    }
}
