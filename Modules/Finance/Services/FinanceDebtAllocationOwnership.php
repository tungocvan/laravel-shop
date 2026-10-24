<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CP33 exact allocation ownership preflight.
 * Read-only; no authorization to settle, reserve tokens, or mutate records.
 */
final class FinanceDebtAllocationOwnership
{
    public function inspect(int $debtId, int $voucherId, int $allocationId): array
    {
        if ($debtId <= 0 || $voucherId <= 0 || $allocationId <= 0) {
            throw ValidationException::withMessages(['allocation' => 'Positive debt, voucher and allocation IDs required.']);
        }
        $debt = DB::table('finance_debt_obligations')->where('id', $debtId)->first();
        $voucher = DB::table('finance_vouchers')->where('id', $voucherId)->first();
        $allocation = DB::table('finance_allocations')->where('id', $allocationId)->first();
        if (! $debt || ! $voucher || ! $allocation) {
            throw ValidationException::withMessages(['allocation' => 'Debt, voucher or allocation is unavailable.']);
        }

        $reason = 'exact_allocation_mapping_not_persisted';
        $link = (new FinanceDebtSourceLinkReader)->forObligation($debtId);
        if ($link === null) {
            $reason = 'manual_debt_requires_explicit_payment_link';
        } elseif ((int) $allocation->voucher_id !== $voucherId
            || (int) $allocation->source_document_id !== $link['source_id']) {
            $reason = 'allocation_does_not_belong_to_debt_and_voucher';
        } elseif ($debt->status !== 'confirmed' || $voucher->status !== 'posted') {
            $reason = 'debt_or_voucher_not_posted';
        } elseif ($debt->currency !== $voucher->currency
            || ! in_array($debt->direction, ['receivable', 'payable'], true)
            || $voucher->direction !== ($debt->direction === 'receivable' ? 'receipt' : 'payment')) {
            $reason = 'financial_identity_mismatch';
        } elseif (! preg_match('/^\\d{1,18}(?:\\.\\d{1,2})?$/D', (string) $allocation->amount)
            || (float) $allocation->amount <= 0) {
            $reason = 'invalid_allocation_amount';
        }

        return [
            'debt_id' => $debtId,
            'voucher_id' => $voucherId,
            'allocation_id' => $allocationId,
            'eligible' => false,
            'authoritative' => false,
            'reason_code' => $reason,
        ];
    }

    public function assertCanSettle(int $debtId, int $voucherId, int $allocationId): void
    {
        $result = $this->inspect($debtId, $voucherId, $allocationId);
        throw ValidationException::withMessages([
            'settlement' => 'Allocation ownership cannot authorize settlement: '.$result['reason_code'],
        ]);
    }
}
