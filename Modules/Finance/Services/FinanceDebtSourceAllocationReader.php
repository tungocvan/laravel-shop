<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Read-only treasury allocation projection for an exactly linked source.
 * Not a debt settlement/outstanding figure: invoice eligibility, adjustments
 * and debt allocation ownership have not been approved.
 */
final class FinanceDebtSourceAllocationReader
{
    /**
     * @return array{source_id: int, posted_amount: string, posted_count: int, draft_count: int, reversed_count: int}|null
     */
    public function forObligation(int $debtId): ?array
    {
        $link = (new FinanceDebtSourceLinkReader)->forObligation($debtId);
        if ($link === null) {
            return null;
        }

        $debt = DB::table('finance_debt_obligations')->where('id', $debtId)->first();
        $source = DB::table('finance_source_documents')->where('id', $link['source_id'])->first();

        // Identity alone cannot authorize settlement: reject inconsistent
        // direction/currency and source principal before showing an amount.
        if (! $debt || ! $source
            || ! in_array($debt->direction, ['receivable', 'payable'], true)
            || $debt->direction !== $source->direction
            || $debt->currency !== $source->currency
            || $this->cents((string) $debt->original_amount) !== $this->cents((string) $source->original_amount)) {
            throw ValidationException::withMessages(['source' => 'Debt and treasury source financial identity differ.']);
        }

        $rows = DB::table('finance_allocations as a')
            ->join('finance_vouchers as v', 'v.id', '=', 'a.voucher_id')
            ->where('a.source_document_id', $link['source_id'])
            ->get(['a.amount', 'v.status', 'v.direction', 'v.currency']);

        $posted = 0;
        $counts = ['posted' => 0, 'draft' => 0, 'reversed' => 0];
        foreach ($rows as $row) {
            if (! array_key_exists($row->status, $counts)) {
                throw ValidationException::withMessages(['voucher' => 'Unknown voucher status.']);
            }
            if ($row->currency !== $source->currency
                || $row->direction !== ($source->direction === 'receivable' ? 'receipt' : 'payment')) {
                throw ValidationException::withMessages(['voucher' => 'Voucher direction or currency mismatch.']);
            }
            $amount = $this->cents((string) $row->amount);
            if ($amount <= 0) {
                throw ValidationException::withMessages(['allocation' => 'Allocation must be positive.']);
            }
            $counts[$row->status]++;
            if ($row->status === 'posted') {
                $posted += $amount;
            }
        }

        if ($posted > $this->cents((string) $source->original_amount)) {
            throw ValidationException::withMessages(['allocation' => 'Posted allocations exceed source amount.']);
        }

        return [
            'source_id' => $link['source_id'],
            'posted_amount' => $this->decimal($posted),
            'posted_count' => $counts['posted'],
            'draft_count' => $counts['draft'],
            'reversed_count' => $counts['reversed'],
        ];
    }

    private function cents(string $amount): int
    {
        if (! preg_match('/^\d{1,16}(?:\.\d{1,2})?$/', $amount)) {
            throw ValidationException::withMessages(['amount' => 'Invalid decimal amount.']);
        }
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function decimal(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
