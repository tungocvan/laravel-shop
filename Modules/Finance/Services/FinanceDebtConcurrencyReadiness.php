<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CP21: explicit prerequisites for real two-connection contention tests.
 * This does not run concurrency tests or grant settlement authority.
 */
final class FinanceDebtConcurrencyReadiness
{
    public function inspect(): array
    {
        $driver = DB::connection()->getDriverName();
        return [
            'eligible' => false,
            'driver' => $driver,
            'reason_code' => in_array($driver, ['mysql', 'pgsql'], true)
                ? 'independent_connection_contention_test_required'
                : 'database_driver_not_suitable_for_row_lock_proof',
            'requires_independent_connections' => true,
            'requires_real_row_lock_contention' => true,
            'requires_duplicate_key_race' => true,
            'requires_reversal_ownership_race' => true,
            'requires_rollback_verification' => true,
        ];
    }

    public function assertReadyForSettlement(): void
    {
        throw ValidationException::withMessages([
            'settlement' => 'Concurrent settlement safety has not been demonstrated.',
        ]);
    }
}
