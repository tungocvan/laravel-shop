<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CP18 diagnostic-only mapping invariant. Never authorizes settlement.
 * Caller must lock the obligation before using this inside a future writer.
 */
final class FinanceDebtSettlementBalanceInvariant
{
    public function inspect(int $debtId): array
    {
        $debt = DB::table('finance_debt_obligations')->where('id', $debtId)->first();
        if ($debt === null) {
            throw ValidationException::withMessages(['settlement' => 'Debt obligation is unavailable.']);
        }

        $principal = $this->cents((new FinanceDebtBalanceReader)->forObligation($debtId)['adjusted_principal']);
        $rows = DB::table('finance_debt_settlement_mappings')
            ->where('obligation_id', $debtId)->orderBy('id')->get();
        $posts = [];
        $reversed = [];
        $net = 0;
        $reason = 'mapping_projection_only_not_authorized';

        foreach ($rows as $row) {
            $amount = $this->cents((string) $row->amount);
            if ($amount === null || $amount <= 0 || ! in_array($row->operation, ['post', 'reverse'], true)) {
                $reason = 'invalid_mapping_amount_or_operation';
                break;
            }
            if ($row->operation === 'post') {
                if ($row->reverses_mapping_id !== null) {
                    $reason = 'post_cannot_reverse';
                    break;
                }
                $posts[(int) $row->id] = $amount;
                $net += $amount;
            } else {
                $origin = (int) $row->reverses_mapping_id;
                if ($origin <= 0 || isset($reversed[$origin])) {
                    $reason = 'duplicate_or_missing_reversal_origin';
                    break;
                }
                $reversed[$origin] = $amount;
                $net -= $amount;
            }
        }

        if ($reason === 'mapping_projection_only_not_authorized') {
            foreach ($reversed as $origin => $amount) {
                if (! isset($posts[$origin]) || $posts[$origin] !== $amount) {
                    $reason = 'invalid_reversal_ownership';
                    break;
                }
            }
        }
        if ($reason === 'mapping_projection_only_not_authorized'
            && ($principal === null || $principal < 0 || $net < 0 || $net > $principal)) {
            $reason = 'negative_or_excessive_mapping_balance';
        }

        return [
            'debt_id' => $debtId,
            'eligible' => false,
            'reason_code' => $reason,
            'mapping_count' => $rows->count(),
            'net_mapped_amount' => $reason === 'mapping_projection_only_not_authorized'
                ? $this->decimal($net) : null,
            'remaining_diagnostic_amount' => $reason === 'mapping_projection_only_not_authorized'
                ? $this->decimal($principal - $net) : null,
        ];
    }

    public function assertCanSettle(int $debtId): void
    {
        $result = $this->inspect($debtId);
        throw ValidationException::withMessages([
            'settlement' => 'Mapping balance is diagnostic only: '.$result['reason_code'],
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

    private function decimal(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
