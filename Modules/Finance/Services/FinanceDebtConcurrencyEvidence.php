<?php

namespace Modules\Finance\Services;

use Illuminate\Validation\ValidationException;

/**
 * CP25: reporting only. An evidence report is not settlement authority.
 * No secrets, DSN, usernames or passwords are accepted in the report.
 */
final class FinanceDebtConcurrencyEvidence
{
    public function inspect(array $evidence): array
    {
        $driver = $evidence['driver'] ?? null;
        $database = $evidence['database'] ?? null;
        $checks = $evidence['checks'] ?? null;
        $required = ['row_lock', 'idempotency_unique', 'reversal_unique', 'rollback'];
        $valid = in_array($driver, ['mysql', 'pgsql'], true)
            && is_string($database)
            && (bool) preg_match('/^[a-z][a-z0-9_]*_test$/D', $database)
            && ($evidence['independent_connections'] ?? null) === true
            && is_array($checks);

        if ($valid) {
            foreach ($required as $check) {
                if (($checks[$check] ?? null) !== true) {
                    $valid = false;
                    break;
                }
            }
        }

        return [
            'status' => $valid ? 'REPORTED_PASS_UNVERIFIED' : 'NOT_RUN_OR_INCOMPLETE',
            'settlement_eligible' => false,
            'driver' => in_array($driver, ['mysql', 'pgsql'], true) ? $driver : null,
            'database' => is_string($database) && preg_match('/^[a-z][a-z0-9_]*_test$/D', $database)
                ? $database : null,
            'required_checks' => $required,
            'reason_code' => $valid
                ? 'self_reported_evidence_not_independent_authority'
                : 'real_database_evidence_missing',
        ];
    }

    public function assertCanSettle(array $evidence): void
    {
        throw ValidationException::withMessages([
            'settlement' => 'Concurrency evidence is diagnostic only; settlement remains disabled.',
        ]);
    }
}
