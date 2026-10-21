<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Data\FinancialDocumentSnapshot;

final class VoucherPostingService
{
    public function __construct(private readonly FinancialDocumentRegistry $providers) {}

    /**
     * Post an existing draft voucher with its existing allocations.
     * Source documents are revalidated by their owning providers under transaction.
     */
    public function post(int $voucherId, ?int $actorId): void
    {
        DB::transaction(function () use ($voucherId, $actorId): void {
            $voucher = DB::table('finance_vouchers')->where('id', $voucherId)->lockForUpdate()->first();
            if (! $voucher || $voucher->status !== 'draft') {
                throw ValidationException::withMessages(['voucher' => 'Only a draft voucher may be posted.']);
            }

            $account = DB::table('finance_accounts')->where('id', $voucher->account_id)->lockForUpdate()->first();
            if (! $account || ! $account->is_active || $account->currency !== $voucher->currency) {
                throw ValidationException::withMessages(['account' => 'Account is inactive or currency does not match.']);
            }

            if (! in_array($voucher->direction, ['receipt', 'payment'], true)) {
                throw ValidationException::withMessages(['direction' => 'Invalid voucher direction.']);
            }

            $amount = $this->cents((string) $voucher->amount);
            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Voucher amount must be positive.']);
            }

            $allocations = DB::table('finance_allocations')
                ->where('voucher_id', $voucherId)->orderBy('source_document_id')->lockForUpdate()->get();

            if ($allocations->isEmpty()) {
                throw ValidationException::withMessages(['allocations' => 'At least one source allocation is required.']);
            }

            $sum = 0;
            foreach ($allocations as $allocation) {
                $source = DB::table('finance_source_documents')
                    ->where('id', $allocation->source_document_id)->lockForUpdate()->first();

                if (! $source) {
                    throw ValidationException::withMessages(['source' => 'Source document is unavailable.']);
                }

                $snapshot = $this->providers
                    ->resolve($source->source_module, $source->source_type)
                    ->resolveForPosting((string) $source->source_document_id);

                $this->assertSourceMatches($source, $snapshot, $voucher);
                $allocated = $this->cents((string) $allocation->amount);
                if ($allocated <= 0) {
                    throw ValidationException::withMessages(['allocations' => 'Allocation must be positive.']);
                }

                $already = DB::table('finance_allocations as a')
                    ->join('finance_vouchers as v', 'v.id', '=', 'a.voucher_id')
                    ->where('a.source_document_id', $source->id)
                    ->where('v.status', 'posted')
                    ->select('a.amount')->get()
                    ->sum(fn ($row): int => $this->cents((string) $row->amount));

                if ($already + $allocated > $this->cents((string) $snapshot->amount)) {
                    throw ValidationException::withMessages(['allocations' => 'Allocation exceeds source outstanding amount.']);
                }

                $sum += $allocated;
            }

            if ($sum !== $amount) {
                throw ValidationException::withMessages(['amount' => 'Voucher total must equal allocations.']);
            }

            $signed = ($voucher->direction === 'receipt' ? '' : '-').$this->decimal($amount);
            DB::table('finance_ledger_entries')->insert([
                'voucher_id' => $voucherId,
                'account_id' => $account->id,
                'entry_type' => 'posting',
                'signed_amount' => $signed,
                'currency' => $voucher->currency,
                'effective_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('finance_vouchers')->where('id', $voucherId)->update([
                'status' => 'posted', 'posted_by' => $actorId, 'posted_at' => now(), 'updated_at' => now(),
            ]);
        }, 3);
    }

    public function reverse(int $voucherId, ?int $actorId): void
    {
        DB::transaction(function () use ($voucherId, $actorId): void {
            $voucher = DB::table('finance_vouchers')->where('id', $voucherId)->lockForUpdate()->first();
            if (! $voucher || $voucher->status !== 'posted') {
                throw ValidationException::withMessages(['voucher' => 'Only a posted voucher may be reversed.']);
            }

            $posting = DB::table('finance_ledger_entries')
                ->where('voucher_id', $voucherId)->where('entry_type', 'posting')->first();
            if (! $posting) {
                throw ValidationException::withMessages(['ledger' => 'Posting ledger entry not found.']);
            }

            $signed = (string) $posting->signed_amount;
            $opposite = str_starts_with($signed, '-') ? substr($signed, 1) : '-'.$signed;
            DB::table('finance_ledger_entries')->insert([
                'voucher_id' => $voucherId,
                'account_id' => $posting->account_id,
                'entry_type' => 'reversal',
                'signed_amount' => $opposite,
                'currency' => $posting->currency,
                'effective_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('finance_vouchers')->where('id', $voucherId)->update([
                'status' => 'reversed', 'reversed_by' => $actorId, 'reversed_at' => now(), 'updated_at' => now(),
            ]);
        }, 3);
    }

    private function assertSourceMatches(object $source, FinancialDocumentSnapshot $snapshot, object $voucher): void
    {
        if ($snapshot->module !== $source->source_module
            || $snapshot->type !== $source->source_type
            || $snapshot->documentId !== (string) $source->source_document_id
            || $snapshot->documentCode !== $source->source_document_code
            || $snapshot->direction !== $source->direction
            || $snapshot->currency !== $source->currency
            || $snapshot->currency !== $voucher->currency
            || $snapshot->direction !== ($voucher->direction === 'receipt' ? 'receivable' : 'payable')
            || $this->cents($snapshot->amount) !== $this->cents((string) $source->original_amount)) {
            throw ValidationException::withMessages(['source' => 'Source financial snapshot has changed.']);
        }
    }

    private function cents(string $amount): int
    {
        if (! preg_match('/^\d{1,16}(?:\.\d{1,2})?$/', $amount)) {
            throw ValidationException::withMessages(['amount' => 'Invalid decimal money value.']);
        }
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function decimal(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
