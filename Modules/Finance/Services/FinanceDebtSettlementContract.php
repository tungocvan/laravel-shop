<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Preflight diagnostics only. No settlement write path exists.
 * Matching a treasury source does not prove invoice lifecycle eligibility.
 */
final class FinanceDebtSettlementContract
{
    /**
     * @return array{eligible: false, reason_code: string, adjusted_principal: string, treasury_posted_amount: string|null, source_id: int|null}
     */
    public function inspect(int $debtId): array
    {
        $debt = DB::table('finance_debt_obligations')->where('id', $debtId)->first();
        if ($debt === null) {
            throw ValidationException::withMessages(['debt' => 'Debt obligation is unavailable.']);
        }

        $reconciliation = (new FinanceDebtReconciliationReader)->forObligation($debtId);
        $reason = match ($reconciliation['reconciliation_status']) {
            'unconfirmed_debt' => 'debt_not_confirmed',
            'no_treasury_source_link' => 'missing_authoritative_allocation_mapping',
            'treasury_exceeds_adjusted_principal' => 'treasury_exceeds_adjusted_principal',
            default => 'source_lifecycle_and_settlement_contract_missing',
        };

        // Even a zero-balance or exact-match source is not an approved debt settlement.
        return [
            'eligible' => false,
            'reason_code' => $reason,
            'adjusted_principal' => $reconciliation['adjusted_principal'],
            'treasury_posted_amount' => $reconciliation['treasury_posted_amount'],
            'source_id' => $reconciliation['treasury_source_id'],
        ];
    }

    public function assertCanSettle(int $debtId): void
    {
        $result = $this->inspect($debtId);
        throw ValidationException::withMessages([
            'settlement' => 'Debt settlement blocked: '.$result['reason_code'],
        ]);
    }
}
