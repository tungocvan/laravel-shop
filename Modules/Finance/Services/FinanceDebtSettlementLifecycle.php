<?php

namespace Modules\Finance\Services;

use Illuminate\Validation\ValidationException;

/**
 * CP8 settlement lifecycle specification; pure validation, no persistence.
 * These transitions are NOT enabled as commands.
 */
final class FinanceDebtSettlementLifecycle
{
    public const DRAFT = 'draft';
    public const POSTED = 'posted';
    public const REVERSED = 'reversed';

    /**
     * @return array{allowed: bool, reason: string}
     */
    public function inspectTransition(string $from, string $to): array
    {
        if (! in_array($from, [self::DRAFT, self::POSTED, self::REVERSED], true)
            || ! in_array($to, [self::DRAFT, self::POSTED, self::REVERSED], true)) {
            return ['allowed' => false, 'reason' => 'unknown_lifecycle_status'];
        }

        if ($from === $to) {
            return ['allowed' => false, 'reason' => 'duplicate_transition'];
        }

        if ($from === self::DRAFT && $to === self::POSTED) {
            return ['allowed' => true, 'reason' => 'requires_atomic_posting_and_authoritative_mapping'];
        }

        if ($from === self::POSTED && $to === self::REVERSED) {
            return ['allowed' => true, 'reason' => 'requires_atomic_reversal_and_authoritative_mapping'];
        }

        return ['allowed' => false, 'reason' => 'invalid_lifecycle_transition'];
    }

    /**
     * Guard deliberately rejects even structurally valid transitions until
     * atomic posting, reversal, idempotency and ownership are implemented.
     */
    public function assertCanExecute(string $from, string $to): void
    {
        $result = $this->inspectTransition($from, $to);
        throw ValidationException::withMessages([
            'settlement' => 'Settlement execution disabled: '.$result['reason'],
        ]);
    }
}
