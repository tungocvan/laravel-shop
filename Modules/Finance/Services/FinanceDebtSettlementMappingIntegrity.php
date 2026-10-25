<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CP14 read-only integrity audit. Never authorizes a mapping or settlement.
 * Monetary comparisons use integer cents; mapping rows are not settlement proof.
 */
final class FinanceDebtSettlementMappingIntegrity
{
    public function inspect(int $mappingId): array
    {
        $mapping = DB::table('finance_debt_settlement_mappings')->where('id', $mappingId)->first();
        if ($mapping === null) {
            throw ValidationException::withMessages(['mapping' => 'Settlement mapping is unavailable.']);
        }

        $debt = DB::table('finance_debt_obligations')->where('id', $mapping->obligation_id)->first();
        $voucher = DB::table('finance_vouchers')->where('id', $mapping->voucher_id)->first();
        $reason = 'requires_authoritative_source_and_lifecycle_contract';

        if ($debt === null || $voucher === null) {
            $reason = 'missing_debt_or_voucher';
        } elseif (! in_array($mapping->operation, ['post', 'reverse'], true)) {
            $reason = 'invalid_operation';
        } elseif ($debt->status !== 'confirmed') {
            $reason = 'debt_not_confirmed';
        } elseif ($voucher->currency !== $debt->currency
            || $voucher->direction !== ($debt->direction === 'receivable' ? 'receipt' : 'payment')) {
            $reason = 'voucher_financial_identity_mismatch';
        } elseif ($voucher->status !== ($mapping->operation === 'post' ? 'posted' : 'reversed')) {
            $reason = 'voucher_lifecycle_mismatch';
        } else {
            $amount = $this->cents((string) $mapping->amount);
            $voucherAmount = $this->cents((string) $voucher->amount);
            $principal = $this->cents((new FinanceDebtBalanceReader)->forObligation((int) $debt->id)['adjusted_principal']);

            if ($amount === null || $voucherAmount === null || $principal === null
                || $amount <= 0 || $principal <= 0 || $amount > $voucherAmount || $amount > $principal) {
                $reason = 'invalid_or_excessive_amount';
            } elseif ($mapping->operation === 'post' && $mapping->reverses_mapping_id !== null) {
                $reason = 'post_cannot_reverse';
            } elseif ($mapping->operation === 'reverse') {
                $origin = $mapping->reverses_mapping_id === null ? null
                    : DB::table('finance_debt_settlement_mappings')->where('id', $mapping->reverses_mapping_id)->first();
                if ($origin === null || $origin->operation !== 'post'
                    || (int) $origin->obligation_id !== (int) $mapping->obligation_id
                    || (int) $origin->voucher_id === (int) $mapping->voucher_id
                    || $this->cents((string) $origin->amount) !== $amount) {
                    $reason = 'invalid_reversal_ownership';
                } else {
                    $reason = 'reversal_structure_only_not_authorized';
                }
            } else {
                $net = 0;
                $rows = DB::table('finance_debt_settlement_mappings')
                    ->where('obligation_id', $debt->id)->get();
                foreach ($rows as $row) {
                    $cents = $this->cents((string) $row->amount);
                    if ($cents === null || $cents <= 0 || ! in_array($row->operation, ['post', 'reverse'], true)) {
                        $net = null;
                        break;
                    }
                    $net += $row->operation === 'post' ? $cents : -$cents;
                }
                $reason = $net === null || $net < 0 || $net > $principal
                    ? 'aggregate_mapping_amount_invalid'
                    : 'post_structure_only_not_authorized';
            }
        }

        return [
            'mapping_id' => $mappingId,
            'eligible' => false,
            'reason_code' => $reason,
        ];
    }

    public function assertCanExecute(int $mappingId): void
    {
        $result = $this->inspect($mappingId);
        throw ValidationException::withMessages([
            'mapping' => 'Mapping execution disabled: '.$result['reason_code'],
        ]);
    }

    private function cents(string $value): ?int
    {
        if (! preg_match('/^\\d{1,15}(?:\\.\\d{1,2})?$/D', $value)) {
            return null;
        }
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
}
