<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CP15 source ownership preflight, strictly read-only and fail-closed.
 * This cannot authorize settlement or prove concurrent transaction safety.
 */
final class FinanceDebtSettlementSourceOwnership
{
    public function inspect(int $debtId, int $voucherId): array
    {
        $debt = DB::table('finance_debt_obligations')->where('id', $debtId)->first();
        $voucher = DB::table('finance_vouchers')->where('id', $voucherId)->first();
        if ($debt === null || $voucher === null) {
            throw ValidationException::withMessages(['ownership' => 'Debt or voucher is unavailable.']);
        }

        $reason = 'requires_authoritative_settlement_lifecycle';
        $sourceId = null;
        $allocatedAmount = null;
        $source = (new FinanceDebtSourceLinkReader)->forObligation($debtId);
        if ($source === null) {
            $reason = 'missing_exact_source_identity';
        } else {
            $sourceId = $source['source_id'];
            $sourceRow = DB::table('finance_source_documents')->where('id', $sourceId)->first();
            if ($sourceRow === null || $debt->currency !== $sourceRow->currency
                || $debt->direction !== $sourceRow->direction
                || $voucher->currency !== $debt->currency
                || $voucher->direction !== ($debt->direction === 'receivable' ? 'receipt' : 'payment')) {
                $reason = 'financial_identity_mismatch';
            } elseif ($debt->status !== 'confirmed') {
                $reason = 'debt_not_confirmed';
            } elseif ($voucher->status !== 'posted') {
                $reason = 'voucher_not_posted';
            } else {
                $allocations = DB::table('finance_allocations')
                    ->where('voucher_id', $voucherId)->get(['source_document_id', 'amount']);
                $matching = $allocations->filter(fn ($row) => (int) $row->source_document_id === $sourceId);
                if ($matching->count() !== 1) {
                    $reason = 'missing_or_ambiguous_source_allocation';
                } else {
                    // A multi-source voucher is valid treasury behavior; use only
                    // the allocation belonging to this exact source.
                    $rawAmount = (string) $matching->first()->amount;
                    $cents = $this->cents($rawAmount);
                    $voucherCents = $this->cents((string) $voucher->amount);
                    if ($cents === null || $voucherCents === null || $cents <= 0 || $cents > $voucherCents) {
                        $reason = 'invalid_allocation_amount';
                    } else {
                        $allocatedAmount = intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
                        (new FinanceDebtSourceAllocationReader)->forObligation($debtId);
                        $reason = $allocations->count() > 1
                            ? 'multi_source_allocation_only_not_settlement'
                            : 'single_source_allocation_only_not_settlement';
                    }
                }
            }
        }

        return [
            'debt_id' => $debtId,
            'voucher_id' => $voucherId,
            'source_id' => $sourceId,
            'allocated_amount' => $allocatedAmount,
            'reason_code' => $reason,
            'eligible' => false,
        ];
    }

    public function assertCanSettle(int $debtId, int $voucherId): void
    {
        $result = $this->inspect($debtId, $voucherId);
        throw ValidationException::withMessages([
            'ownership' => 'Settlement disabled: '.$result['reason_code'],
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
