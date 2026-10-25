<?php

namespace Modules\Finance\Services;

use Illuminate\Validation\ValidationException;

/**
 * CP10 immutable transaction/idempotency protocol specification.
 * No database locks, token reservations, or settlement writes are performed.
 */
final class FinanceDebtSettlementTransactionProtocol
{
    /**
     * @return array{ready: false, reason: string, lock_order: array<int, string>, unique_scope: string, atomic_steps: array<int, string>}
     */
    public function plan(int $debtId, int $voucherId, string $key, string $operation = 'post'): array
    {
        if ($debtId <= 0 || $voucherId <= 0) {
            throw ValidationException::withMessages(['settlement' => 'Debt and voucher IDs must be positive.']);
        }
        if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9:_-]{7,127}$/D', $key)) {
            throw ValidationException::withMessages(['settlement' => 'Invalid idempotency key.']);
        }
        if (! in_array($operation, ['post', 'reverse'], true)) {
            throw ValidationException::withMessages(['settlement' => 'Unknown settlement operation.']);
        }

        return [
            'ready' => false,
            'reason' => 'authoritative_mapping_and_atomic_storage_not_implemented',
            'lock_order' => [
                'finance_debt_obligations',
                'finance_vouchers',
                'finance_source_documents',
                'finance_allocations',
            ],
            'unique_scope' => 'debt_id:operation:idempotency_key',
            'atomic_steps' => [
                'lock_rows_in_order',
                'reserve_unique_idempotency_key',
                'revalidate_source_and_debt_lifecycle',
                'verify_authoritative_mapping_and_remaining_principal',
                $operation === 'post' ? 'append_settlement_once' : 'append_linked_reversal_once',
                'commit_all_or_rollback_all',
            ],
        ];
    }

    public function assertCanExecute(int $debtId, int $voucherId, string $key, string $operation = 'post'): void
    {
        $plan = $this->plan($debtId, $voucherId, $key, $operation);
        throw ValidationException::withMessages(['settlement' => 'Execution disabled: '.$plan['reason']]);
    }
}
