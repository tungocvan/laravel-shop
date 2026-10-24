<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CP34 internal transaction primitive. NOT exposed by a route/controller.
 * Authorization callback must be supplied by an approved application boundary.
 * Public atomic gate remains fail-closed; do not call in production.
 */
final class FinanceDebtInternalSettlementWriter
{
    public function post(
        int $debtId,
        int $voucherId,
        int $allocationId,
        string $key,
        int $actorId,
        string $reason,
        callable $authorize
    ): int {
        $this->checkContext($actorId, $reason, 'post', $authorize);
        (new FinanceDebtSettlementTransactionProtocol)->plan($debtId, $voucherId, $key, 'post');

        return DB::transaction(function () use ($debtId, $voucherId, $allocationId, $key, $actorId, $reason): int {
            $debt = DB::table('finance_debt_obligations')->where('id', $debtId)->lockForUpdate()->first();
            $voucher = DB::table('finance_vouchers')->where('id', $voucherId)->lockForUpdate()->first();
            $allocation = DB::table('finance_allocations')->where('id', $allocationId)->lockForUpdate()->first();
            if (! $debt || ! $voucher || ! $allocation) {
                $this->deny('Missing debt, voucher or allocation.');
            }
            if (! DB::getSchemaBuilder()->hasTable('finance_debt_allocation_ownerships')) {
                $this->deny('Allocation ownership schema is not installed.');
            }
            $link = (new FinanceDebtSourceLinkReader)->forObligation($debtId);
            if ($link === null || (int) $allocation->source_document_id !== $link['source_id']
                || (int) $allocation->voucher_id !== $voucherId) {
                $this->deny('Exact allocation ownership is missing or mismatched.');
            }
            if ($debt->status !== 'confirmed' || $voucher->status !== 'posted'
                || $debt->currency !== $voucher->currency
                || ! in_array($debt->direction, ['receivable', 'payable'], true)
                || $voucher->direction !== ($debt->direction === 'receivable' ? 'receipt' : 'payment')) {
                $this->deny('Invalid lifecycle, currency or direction.');
            }
            $source = DB::table('finance_source_documents')->where('id', $link['source_id'])->first();
            if (! $source || $source->currency !== $debt->currency
                || $source->direction !== $debt->direction
                || (string) $source->original_amount !== (string) $debt->original_amount) {
                $this->deny('Source financial identity mismatch.');
            }
            if (DB::table('finance_debt_allocation_ownerships')->where('allocation_id', $allocationId)->exists()) {
                $this->deny('Allocation already owned by another settlement.');
            }
            $balance = (new FinanceDebtSettlementBalanceInvariant)->inspect($debtId);
            if ($balance['reason_code'] !== 'mapping_projection_only_not_authorized') {
                $this->deny('Existing mapping balance is invalid.');
            }
            $amount = $this->cents((string) $allocation->amount);
            $remaining = $this->cents((string) $balance['remaining_diagnostic_amount']);
            $voucherAmount = $this->cents((string) $voucher->amount);
            if ($amount <= 0 || $amount > $remaining || $amount > $voucherAmount) {
                $this->deny('Allocation exceeds remaining debt or voucher amount.');
            }
            (new FinanceDebtIdempotencyRegistry)->reserve($debtId, 'post', $key);
            $mappingId = DB::table('finance_debt_settlement_mappings')->insertGetId([
                'obligation_id' => $debtId, 'voucher_id' => $voucherId,
                'operation' => 'post', 'amount' => $this->decimal($amount),
                'reverses_mapping_id' => null, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('finance_debt_allocation_ownerships')->insert([
                'obligation_id' => $debtId, 'allocation_id' => $allocationId,
                'mapping_id' => $mappingId, 'authorized_by' => $actorId,
                'authorization_reason' => $reason, 'created_at' => now(), 'updated_at' => now(),
            ]);
            return $mappingId;
        }, 3);
    }

    public function reverse(
        int $originMappingId,
        int $reversalVoucherId,
        string $key,
        int $actorId,
        string $reason,
        callable $authorize
    ): int {
        $this->checkContext($actorId, $reason, 'reverse', $authorize);
        $origin = DB::table('finance_debt_settlement_mappings')->where('id', $originMappingId)->first();
        if (! $origin || $origin->operation !== 'post') {
            $this->deny('Original posting does not exist.');
        }
        $debtId = (int) $origin->obligation_id;
        (new FinanceDebtSettlementTransactionProtocol)->plan($debtId, $reversalVoucherId, $key, 'reverse');

        return DB::transaction(function () use ($originMappingId, $reversalVoucherId, $debtId, $key): int {
            // Serialize with post() by locking the debt first. Keep ownership
            // immutable as historical evidence, including after reversal.
            $debt = DB::table('finance_debt_obligations')->where('id', $debtId)->lockForUpdate()->first();
            $voucher = DB::table('finance_vouchers')->where('id', $reversalVoucherId)->lockForUpdate()->first();
            $origin = DB::table('finance_debt_settlement_mappings')
                ->where('id', $originMappingId)->lockForUpdate()->first();
            if (! $debt || ! $voucher || ! $origin || $origin->operation !== 'post'
                || (int) $origin->obligation_id !== $debtId) {
                $this->deny('Original posting, debt or voucher is unavailable.');
            }
            // Existing treasury reversal changes the SAME voucher's lifecycle;
            // a second arbitrary voucher must never reverse someone else's post.
            if ((int) $origin->voucher_id !== $reversalVoucherId
                || $voucher->status !== 'reversed' || $debt->status !== 'confirmed'
                || $voucher->currency !== $debt->currency
                || $voucher->direction !== ($debt->direction === 'receivable' ? 'receipt' : 'payment')) {
                $this->deny('Reversal voucher or lifecycle does not match origin.');
            }
            $owner = DB::table('finance_debt_allocation_ownerships')
                ->where('mapping_id', $originMappingId)->first();
            if (! $owner || (int) $owner->obligation_id !== $debtId) {
                $this->deny('Missing exact allocation ownership.');
            }
            $allocation = DB::table('finance_allocations')
                ->where('id', $owner->allocation_id)->lockForUpdate()->first();
            if (! $allocation || (int) $allocation->voucher_id !== $reversalVoucherId
                || $this->cents((string) $allocation->amount) !== $this->cents((string) $origin->amount)) {
                $this->deny('Origin allocation identity or amount changed.');
            }
            $link = (new FinanceDebtSourceLinkReader)->forObligation($debtId);
            if (! $link || (int) $allocation->source_document_id !== $link['source_id']) {
                $this->deny('Origin allocation source does not match debt.');
            }
            if (DB::table('finance_debt_settlement_mappings')
                ->where('reverses_mapping_id', $originMappingId)->exists()) {
                $this->deny('Original posting was already reversed.');
            }
            if ((new FinanceDebtSettlementBalanceInvariant)->inspect($debtId)['reason_code']
                !== 'mapping_projection_only_not_authorized') {
                $this->deny('Existing mapping balance is invalid.');
            }
            (new FinanceDebtIdempotencyRegistry)->reserve($debtId, 'reverse', $key);
            return DB::table('finance_debt_settlement_mappings')->insertGetId([
                'obligation_id' => $debtId, 'voucher_id' => $reversalVoucherId,
                'operation' => 'reverse', 'amount' => $origin->amount,
                'reverses_mapping_id' => $originMappingId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }, 3);
    }

    private function checkContext(int $actorId, string $reason, string $operation, callable $authorize): void
    {
        if (! app()->environment('testing') || $actorId <= 0 || trim($reason) === '' || strlen($reason) > 500
            || $authorize($actorId, $operation) !== true) {
            $this->deny('Explicit actor authorization and reason required.');
        }
    }

    private function cents(string $value): int
    {
        if (! preg_match('/^\\d{1,15}(?:\\.\\d{1,2})?$/D', $value)) {
            $this->deny('Invalid monetary amount.');
        }
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function decimal(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    private function deny(string $message): never
    {
        throw ValidationException::withMessages(['settlement' => $message]);
    }
}
