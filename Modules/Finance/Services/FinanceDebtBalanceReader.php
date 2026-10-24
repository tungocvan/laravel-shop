<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Read-only debt principal projection. This is NOT settlement/outstanding:
 * finance_allocations currently reference finance_source_documents, not obligations.
 */
final class FinanceDebtBalanceReader
{
    /**
     * @return array{original_amount: string, adjustment_total: string, adjusted_principal: string}
     */
    public function forObligation(int $obligationId): array
    {
        if ($obligationId <= 0) {
            throw ValidationException::withMessages(['debt' => 'Debt obligation is unavailable.']);
        }

        $row = DB::table('finance_debt_obligations as d')
            ->where('d.id', $obligationId)
            ->select('d.original_amount')
            ->selectRaw('(SELECT COALESCE(SUM(e.signed_amount), 0) FROM finance_debt_events e WHERE e.obligation_id = d.id) AS adjustment_total')
            ->selectRaw('(d.original_amount + (SELECT COALESCE(SUM(e.signed_amount), 0) FROM finance_debt_events e WHERE e.obligation_id = d.id)) AS adjusted_principal')
            ->first();

        if ($row === null) {
            throw ValidationException::withMessages(['debt' => 'Debt obligation is unavailable.']);
        }

        return [
            'original_amount' => (string) $row->original_amount,
            'adjustment_total' => (string) $row->adjustment_total,
            'adjusted_principal' => (string) $row->adjusted_principal,
        ];
    }
}
