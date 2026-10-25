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

            if (($voucher->origin ?? 'source') === 'manual') {
                if ($allocations->isNotEmpty()) {
                    throw ValidationException::withMessages(['allocations' => 'Manual vouchers cannot settle source documents.']);
                }
                if (trim((string) $voucher->manual_category) === ''
                    || trim((string) $voucher->counterparty) === ''
                    || trim((string) $voucher->description) === '') {
                    throw ValidationException::withMessages(['voucher' => 'Manual voucher details are required.']);
                }
            } elseif (($voucher->origin ?? 'source') !== 'source' || $allocations->isEmpty()) {
                throw ValidationException::withMessages(['allocations' => 'Source vouchers require allocations.']);
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

            if (($voucher->origin ?? 'source') === 'source' && $sum !== $amount) {
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

            // Debt-owned vouchers require an atomic debt reversal, not treasury-only reversal.
            if (\Illuminate\Support\Facades\Schema::hasTable('finance_debt_settlement_mappings')
                && DB::table('finance_debt_settlement_mappings')->where('voucher_id', $voucherId)->exists()) {
                throw ValidationException::withMessages([
                    'voucher' => 'Phiếu thanh toán công nợ phải hoàn tác từ chi tiết công nợ.',
                ]);
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

    /**
     * Reverse the posted voucher and create a distinct editable draft.
     * The original ledger and reversal remain immutable for audit.
     */
    public function reverseToDraft(int $voucherId, ?int $actorId): int
    {
        return DB::transaction(function () use ($voucherId, $actorId): int {
            $voucher = DB::table('finance_vouchers')->where('id', $voucherId)->lockForUpdate()->first();
            if (! $voucher || $voucher->status !== 'posted') {
                throw ValidationException::withMessages(['voucher' => 'Only posted vouchers can be returned to draft.']);
            }

            $allocations = DB::table('finance_allocations')
                ->where('voucher_id', $voucherId)->orderBy('source_document_id')->get();
            if ($voucher->origin === 'source' && $allocations->count() !== 1) {
                throw ValidationException::withMessages(['voucher' => 'Only single-source vouchers can be returned to draft.']);
            }
            if ($voucher->origin === 'manual' && $allocations->isNotEmpty()) {
                throw ValidationException::withMessages(['voucher' => 'Manual vouchers cannot have source allocations.']);
            }

            $this->reverse($voucherId, $actorId);

            $newId = DB::table('finance_vouchers')->insertGetId([
                'number' => 'FIN-R-'.now()->format('YmdHis').'-'.strtoupper(bin2hex(random_bytes(4))),
                'direction' => $voucher->direction,
                'status' => 'draft',
                'account_id' => $voucher->account_id,
                'currency' => $voucher->currency,
                'amount' => $voucher->amount,
                'voucher_date' => $voucher->voucher_date,
                'origin' => $voucher->origin,
                'manual_category' => $voucher->manual_category,
                'counterparty' => $voucher->counterparty,
                'description' => $voucher->description,
                'reference_number' => $voucher->reference_number,
                'created_by' => $actorId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($allocations as $allocation) {
                DB::table('finance_allocations')->insert([
                    'voucher_id' => $newId,
                    'source_document_id' => $allocation->source_document_id,
                    'amount' => $allocation->amount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $newId;
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
