<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Finance-owned debt drafts. No invoice recognition, voucher posting or ledger writes.
 */
final class FinanceDebtService
{
    public function createManualDraft(
        string $reference,
        string $direction,
        string $counterparty,
        string $amount,
        ?string $dueOn = null,
        ?int $actorId = null,
    ): int {
        $reference = trim($reference);
        $counterparty = trim($counterparty);
        if ($reference === '' || mb_strlen($reference) > 100
            || $counterparty === '' || mb_strlen($counterparty) > 255
            || ! in_array($direction, ['receivable', 'payable'], true)) {
            throw ValidationException::withMessages(['debt' => 'Invalid debt draft details.']);
        }

        $amount = $this->normalizeAmount($amount);
        if ($dueOn !== null && ! $this->validDate($dueOn)) {
            throw ValidationException::withMessages(['due_on' => 'Invalid due date.']);
        }

        return DB::transaction(fn (): int => DB::table('finance_debt_obligations')->insertGetId([
            'reference_number' => $reference,
            'direction' => $direction,
            'status' => 'draft',
            'counterparty_name' => $counterparty,
            'original_amount' => $amount,
            'due_on' => $dueOn,
            'currency' => 'VND',
            'created_by' => $actorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]), 3);
    }

    /**
     * Only manual drafts can be confirmed in this checkpoint. Invoice-backed
     * debts remain blocked until source lifecycle eligibility is implemented.
     */
    public function confirmManualDraft(int $debtId, int $actorId, string $recognizedOn): void
    {
        if ($actorId <= 0 || ! $this->validDate($recognizedOn)) {
            throw ValidationException::withMessages(['debt' => 'Actor and recognition date are required.']);
        }

        DB::transaction(function () use ($debtId, $actorId, $recognizedOn): void {
            $debt = DB::table('finance_debt_obligations')->where('id', $debtId)->lockForUpdate()->first();
            if (! $debt || $debt->status !== 'draft'
                || $debt->source_module !== null
                || $debt->source_type !== null
                || $debt->source_document_id !== null) {
                throw ValidationException::withMessages(['debt' => 'Only manual draft debts may be confirmed.']);
            }
            DB::table('finance_debt_obligations')->where('id', $debtId)->update([
                'status' => 'confirmed',
                'recognized_on' => $recognizedOn,
                'confirmed_by' => $actorId,
                'confirmed_at' => now(),
                'updated_at' => now(),
            ]);
        }, 3);
    }

    /**
     * A signed adjustment is recorded as an append-only event.
     * Settlement/voucher allocation is deliberately not supported here.
     */
    public function adjustConfirmedDebt(int $debtId, string $signedAmount, string $effectiveOn, string $reason, int $actorId): int
    {
        $reason = trim($reason);
        if ($actorId <= 0 || $reason === '' || mb_strlen($reason) > 500 || ! $this->validDate($effectiveOn)) {
            throw ValidationException::withMessages(['adjustment' => 'Invalid adjustment details.']);
        }
        $amount = $this->normalizeAmount($signedAmount, true);

        return DB::transaction(function () use ($debtId, $amount, $effectiveOn, $reason, $actorId): int {
            $debt = DB::table('finance_debt_obligations')->where('id', $debtId)->lockForUpdate()->first();
            if (! $debt || $debt->status !== 'confirmed' || $debt->source_module !== null
                || $debt->source_type !== null || $debt->source_document_id !== null) {
                throw ValidationException::withMessages(['debt' => 'Only confirmed manual debts may be adjusted.']);
            }
            if ($effectiveOn < $debt->recognized_on) {
                throw ValidationException::withMessages(['effective_on' => 'Adjustment predates recognition.']);
            }

            $total = $this->cents((string) $debt->original_amount);
            $events = DB::table('finance_debt_events')->where('obligation_id', $debtId)->get(['signed_amount']);
            foreach ($events as $event) {
                $total += $this->cents((string) $event->signed_amount);
            }
            if ($total + $this->cents($amount) < 0) {
                throw ValidationException::withMessages(['signed_amount' => 'Adjustment would make debt negative.']);
            }

            return DB::table('finance_debt_events')->insertGetId([
                'obligation_id' => $debtId,
                'event_type' => 'adjustment',
                'signed_amount' => $amount,
                'effective_on' => $effectiveOn,
                'reason' => $reason,
                'created_by' => $actorId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }, 3);
    }

    private function normalizeAmount(string $amount, bool $signed = false): string
    {
        $amount = trim($amount);
        $pattern = $signed ? '/^-?\\d{1,18}(?:\\.\\d{1,2})?$/' : '/^\\d{1,18}(?:\\.\\d{1,2})?$/';
        if (! preg_match($pattern, $amount) || $this->cents($amount) === 0) {
            throw ValidationException::withMessages(['amount' => 'Amount must be a nonzero decimal with up to two places.']);
        }
        $negative = str_starts_with($amount, '-');
        $parts = explode('.', ltrim($amount, '-'), 2);

        return ($negative ? '-' : '').$parts[0].'.'.str_pad($parts[1] ?? '', 2, '0');
    }

    private function cents(string $amount): int
    {
        $negative = str_starts_with($amount, '-');
        $parts = explode('.', ltrim($amount, '-'), 2);
        return ($negative ? -1 : 1) * ((int) $parts[0] * 100 + (int) str_pad($parts[1] ?? '', 2, '0'));
    }

    private function validDate(string $date): bool
    {
        if (! preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $date)) {
            return false;
        }
        [$year, $month, $day] = array_map('intval', explode('-', $date));
        return checkdate($month, $day, $year);
    }
}
