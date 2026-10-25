<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Read-only integrity gate for a proposed debt/voucher association.
 * Does not authorize settlement or create an allocation.
 */
final class FinanceDebtVoucherMappingInspector
{
    /**
     * @return array{debt_id: int, voucher_id: int, source_id: int|null, voucher_status: string, mapping_status: string, eligible: false}
     */
    public function inspect(int $debtId, int $voucherId): array
    {
        $debt = DB::table('finance_debt_obligations')->where('id', $debtId)->first();
        $voucher = DB::table('finance_vouchers')->where('id', $voucherId)->first();
        if ($debt === null || $voucher === null) {
            throw ValidationException::withMessages(['mapping' => 'Debt or voucher is unavailable.']);
        }

        $source = (new FinanceDebtSourceLinkReader)->forObligation($debtId);
        if ($source === null) {
            $status = 'no_authoritative_source_mapping';
        } elseif ($debt->status !== 'confirmed') {
            $status = 'debt_not_confirmed';
        } elseif (! in_array($voucher->status, ['draft', 'posted', 'reversed'], true)) {
            $status = 'unknown_voucher_status';
        } elseif ($voucher->currency !== $debt->currency
            || $voucher->direction !== ($debt->direction === 'receivable' ? 'receipt' : 'payment')) {
            $status = 'voucher_financial_identity_mismatch';
        } else {
            $allocations = DB::table('finance_allocations')
                ->where('voucher_id', $voucherId)
                ->get(['source_document_id', 'amount']);

            if ($allocations->count() !== 1
                || (int) $allocations->first()->source_document_id !== $source['source_id']) {
                $status = 'missing_or_ambiguous_voucher_allocation';
            } else {
                // Validate the entire linked source allocation projection, not
                // just the candidate voucher, before reporting a mapping.
                (new FinanceDebtSourceAllocationReader)->forObligation($debtId);
                $status = match ($voucher->status) {
                    'draft' => 'draft_not_settled',
                    'reversed' => 'reversed_not_settled',
                    default => 'source_mapping_only_contract_required',
                };
            }
        }

        return [
            'debt_id' => (int) $debt->id,
            'voucher_id' => (int) $voucher->id,
            'source_id' => $source['source_id'] ?? null,
            'voucher_status' => (string) $voucher->status,
            'mapping_status' => $status,
            'eligible' => false,
        ];
    }

    public function assertCanMap(int $debtId, int $voucherId): void
    {
        $result = $this->inspect($debtId, $voucherId);
        throw ValidationException::withMessages([
            'mapping' => 'Debt voucher mapping is not authorized: '.$result['mapping_status'],
        ]);
    }
}
