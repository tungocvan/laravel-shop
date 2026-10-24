<?php

namespace Modules\Finance\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CP11 persistent reservation primitive only.
 * A reservation is NOT a payment, posting, or settlement authorization.
 * Caller must place reservation and future authoritative writes in ONE transaction.
 */
final class FinanceDebtIdempotencyRegistry
{
    public function reserve(int $debtId, string $operation, string $key): int
    {
        if ($debtId <= 0 || ! in_array($operation, ['post', 'reverse'], true)
            || ! preg_match('/^[A-Za-z0-9][A-Za-z0-9:_-]{7,127}$/D', $key)) {
            throw ValidationException::withMessages(['idempotency' => 'Invalid debt, operation or key.']);
        }

        return DB::transaction(function () use ($debtId, $operation, $key): int {
            // Unique index is the authoritative protection against concurrent inserts.
            // This pre-check only provides a clear error for already committed keys.
            if (DB::table('finance_debt_idempotency_keys')
                ->where('obligation_id', $debtId)
                ->where('operation', $operation)
                ->where('idempotency_key', $key)
                ->exists()) {
                throw ValidationException::withMessages(['idempotency' => 'Duplicate idempotency key.']);
            }

            try {
                return DB::table('finance_debt_idempotency_keys')->insertGetId([
                    'obligation_id' => $debtId,
                    'operation' => $operation,
                    'idempotency_key' => $key,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (QueryException $exception) {
                // Preserve FK and infrastructure errors. Only map an actual
                // duplicate scope to a business validation error.
                if (DB::table('finance_debt_idempotency_keys')
                    ->where('obligation_id', $debtId)
                    ->where('operation', $operation)
                    ->where('idempotency_key', $key)
                    ->exists()) {
                    throw ValidationException::withMessages(['idempotency' => 'Duplicate idempotency key.']);
                }
                throw $exception;
            }
        });
    }
}
