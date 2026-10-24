<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** CP32 diagnostic-only mapping reconciliation; never grants settlement. */
final class FinanceDebtMappingReconciliation
{
    public function inspect(int $debtId): array
    {
        if ($debtId <= 0) {
            throw ValidationException::withMessages(['debt' => 'Debt obligation is unavailable.']);
        }
        $debt = DB::table('finance_debt_obligations')->where('id', $debtId)->first();
        if ($debt === null) {
            throw ValidationException::withMessages(['debt' => 'Debt obligation is unavailable.']);
        }
        $balance = (new FinanceDebtSettlementBalanceInvariant)->inspect($debtId);
        $consistent = $balance['reason_code'] === 'mapping_projection_only_not_authorized';
        return [
            'debt_id' => $debtId,
            'eligible' => false,
            'authoritative' => false,
            'reason_code' => ! $consistent ? $balance['reason_code'] : ($debt->status !== 'confirmed' ? 'debt_not_confirmed' : 'mapping_allocation_ownership_not_proven'),
            'mapping_count' => $balance['mapping_count'],
            'net_mapped_diagnostic' => $consistent ? $balance['net_mapped_amount'] : null,
            'remaining_diagnostic' => $consistent ? $balance['remaining_diagnostic_amount'] : null,
        ];
    }

    public function assertCanSettle(int $debtId): void
    {
        $result = $this->inspect($debtId);
        throw ValidationException::withMessages(['settlement' => 'Reconciliation cannot authorize settlement: '.$result['reason_code']]);
    }
}
