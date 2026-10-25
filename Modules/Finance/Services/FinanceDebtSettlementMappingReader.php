<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CP13 read-only mapping inspector. No mapping writer is authorized.
 */
final class FinanceDebtSettlementMappingReader
{
    public function inspect(int $debtId, int $voucherId): array
    {
        $debt = DB::table('finance_debt_obligations')->where('id', $debtId)->first();
        $voucher = DB::table('finance_vouchers')->where('id', $voucherId)->first();
        if ($debt === null || $voucher === null) {
            throw ValidationException::withMessages(['mapping' => 'Debt or voucher is unavailable.']);
        }

        $rows = DB::table('finance_debt_settlement_mappings')
            ->where('obligation_id', $debtId)->where('voucher_id', $voucherId)
            ->orderBy('id')->get();

        $status = 'no_authoritative_mapping';
        if ($rows->count() > 1) {
            $status = 'multiple_operations_require_review';
        } elseif ($rows->count() === 1) {
            $mapping = $rows->first();
            if (! in_array($mapping->operation, ['post', 'reverse'], true)) {
                $status = 'unknown_mapping_operation';
            } elseif ($mapping->operation === 'post' && $mapping->reverses_mapping_id !== null) {
                $status = 'invalid_post_reversal_reference';
            } elseif ($mapping->operation === 'reverse' && $mapping->reverses_mapping_id === null) {
                $status = 'missing_reversal_origin';
            } elseif ($mapping->operation === 'reverse') {
                $origin = DB::table('finance_debt_settlement_mappings')
                    ->where('id', $mapping->reverses_mapping_id)->first();
                $status = $origin !== null
                    && (int) $origin->obligation_id === $debtId
                    && $origin->operation === 'post'
                    && (string) $origin->amount === (string) $mapping->amount
                    ? 'reversal_link_structurally_valid'
                    : 'invalid_reversal_origin';
            } else {
                $status = 'post_mapping_requires_lifecycle_contract';
            }
        }

        return [
            'debt_id' => $debtId,
            'voucher_id' => $voucherId,
            'mapping_status' => $status,
            'mapping_count' => $rows->count(),
            'eligible' => false,
        ];
    }

    public function assertCanSettle(int $debtId, int $voucherId): void
    {
        $result = $this->inspect($debtId, $voucherId);
        throw ValidationException::withMessages([
            'mapping' => 'Settlement mapping execution disabled: '.$result['mapping_status'],
        ]);
    }
}
