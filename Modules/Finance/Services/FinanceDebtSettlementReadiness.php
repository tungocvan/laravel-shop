<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Fail-closed settlement readiness check.
 *
 * Debt obligations have no authoritative allocation link to vouchers yet.
 * This class never posts vouchers or claims a debt has been paid.
 */
final class FinanceDebtSettlementReadiness
{
    /**
     * @return array{eligible: false, reason: string, adjusted_principal: string}
     */
    public function inspect(int $debtId): array
    {
        $debt = DB::table('finance_debt_obligations')->where('id', $debtId)->first();
        if ($debt === null) {
            throw ValidationException::withMessages(['debt' => 'Debt obligation is unavailable.']);
        }

        $principal = (new FinanceDebtBalanceReader)->forObligation($debtId)['adjusted_principal'];

        if ($debt->status !== 'confirmed') {
            $reason = 'Debt must be confirmed before settlement.';
        } elseif ($debt->source_module !== null || $debt->source_type !== null || $debt->source_document_id !== null) {
            $reason = 'Source lifecycle eligibility and debt-to-voucher mapping are not established.';
        } else {
            $reason = 'Manual debt-to-voucher allocation and reversal mapping are not implemented.';
        }

        return [
            'eligible' => false,
            'reason' => $reason,
            'adjusted_principal' => $principal,
        ];
    }

    /**
     * Guard for future settlement commands: never infer eligibility from
     * a principal balance or from unrelated finance_source_documents.
     */
    public function assertCanSettle(int $debtId): void
    {
        (new FinanceDebtSettlementContract)->assertCanSettle($debtId);
    }
}
