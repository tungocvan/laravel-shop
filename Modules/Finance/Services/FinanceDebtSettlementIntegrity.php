<?php

namespace Modules\Finance\Services;

use Illuminate\Validation\ValidationException;

/**
 * CP9 pure preflight for proposed settlement amounts and idempotency tokens.
 * No persistent token registry, locking, settlement ownership or write path.
 */
final class FinanceDebtSettlementIntegrity
{
    /**
     * @return array{valid: bool, reason: string}
     */
    public function inspect(string $principal, string $alreadySettled, string $proposed, string $idempotencyKey, bool $keyAlreadyUsed = false): array
    {
        if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9:_-]{7,127}$/D', $idempotencyKey)) {
            return ['valid' => false, 'reason' => 'invalid_idempotency_key'];
        }
        if ($keyAlreadyUsed) {
            return ['valid' => false, 'reason' => 'duplicate_idempotency_key'];
        }

        $amounts = [];
        foreach ([$principal, $alreadySettled, $proposed] as $value) {
            $cents = $this->cents($value);
            if ($cents === null) {
                return ['valid' => false, 'reason' => 'invalid_amount'];
            }
            $amounts[] = $cents;
        }
        [$total, $settled, $requested] = $amounts;
        if ($total <= 0 || $settled < 0 || $requested <= 0) {
            return ['valid' => false, 'reason' => 'non_positive_principal_or_payment'];
        }
        if ($settled > $total) {
            return ['valid' => false, 'reason' => 'existing_settlement_exceeds_principal'];
        }
        if ($requested > $total - $settled) {
            return ['valid' => false, 'reason' => 'payment_exceeds_remaining_principal'];
        }

        return ['valid' => true, 'reason' => 'requires_atomic_idempotency_and_authoritative_settlement_mapping'];
    }

    public function assertCanExecute(string $principal, string $alreadySettled, string $proposed, string $idempotencyKey, bool $keyAlreadyUsed = false): void
    {
        $result = $this->inspect($principal, $alreadySettled, $proposed, $idempotencyKey, $keyAlreadyUsed);
        throw ValidationException::withMessages([
            'settlement' => 'Settlement execution disabled: '.$result['reason'],
        ]);
    }

    private function cents(string $value): ?int
    {
        if (! preg_match('/^\d{1,15}(?:\.\d{1,2})?$/D', $value)) {
            return null;
        }
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
}
