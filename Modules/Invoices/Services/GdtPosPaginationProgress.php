<?php

namespace Modules\Invoices\Services;

use InvalidArgumentException;

/**
 * Non-sensitive progress metadata for user-driven POS pagination.
 *
 * Never store GDT tokens, CAPTCHA answers, cookies, opaque pagination state,
 * or invoice payloads in this value object.
 */
final class GdtPosPaginationProgress
{
    public function __construct(
        public readonly int $received,
        public readonly int $total,
        public readonly int $pageSize = 15,
    ) {
        if ($received < 0 || $total < 0 || $received > $total || $pageSize !== 15) {
            throw new InvalidArgumentException('Invalid GDT POS pagination progress.');
        }
    }

    public function needsNextPage(): bool
    {
        return $this->received < $this->total;
    }

    public function nextPageNumber(): ?int
    {
        return $this->needsNextPage() ? intdiv($this->received, $this->pageSize) + 1 : null;
    }

    public function isComplete(): bool
    {
        return $this->received === $this->total;
    }
}
