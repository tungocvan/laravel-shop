<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Read-only reconciliation diagnostics. Treasury source allocations are
 * deliberately not treated as authoritative debt settlement/outstanding.
 */
final class FinanceDebtReconciliationReader
{
    /**
     * @return array{debt_id: int, status: string, adjusted_principal: string, treasury_posted_amount: string|null, treasury_source_id: int|null, reconciliation_status: string, debt_settlement_confirmed: false}
     */
    public function forObligation(int $debtId): array
    {
        $debt = DB::table('finance_debt_obligations')->where('id', $debtId)->first();
        if ($debt === null) {
            throw ValidationException::withMessages(['debt' => 'Debt obligation is unavailable.']);
        }

        $principal = (new FinanceDebtBalanceReader)->forObligation($debtId);
        $source = (new FinanceDebtSourceAllocationReader)->forObligation($debtId);

        if ($debt->status !== 'confirmed') {
            $status = 'unconfirmed_debt';
        } elseif ($source === null) {
            $status = 'no_treasury_source_link';
        } elseif ($this->cents($source['posted_amount']) > $this->cents($principal['adjusted_principal'])) {
            $status = 'treasury_exceeds_adjusted_principal';
        } else {
            $status = 'requires_settlement_contract';
        }

        return [
            'debt_id' => (int) $debt->id,
            'status' => (string) $debt->status,
            'adjusted_principal' => $principal['adjusted_principal'],
            'treasury_posted_amount' => $source['posted_amount'] ?? null,
            'treasury_source_id' => $source['source_id'] ?? null,
            'reconciliation_status' => $status,
            'debt_settlement_confirmed' => false,
        ];
    }

    private function cents(string $amount): int
    {
        if (! preg_match('/^-?\d{1,16}(?:\.\d{1,2})?$/', $amount)) {
            throw ValidationException::withMessages(['amount' => 'Invalid debt amount.']);
        }
        $negative = str_starts_with($amount, '-');
        [$whole, $fraction] = array_pad(explode('.', ltrim($amount, '-'), 2), 2, '');
        $cents = ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
        return $negative ? -$cents : $cents;
    }
}
