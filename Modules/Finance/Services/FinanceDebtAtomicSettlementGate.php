<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CP12 atomic settlement boundary, deliberately fail-closed.
 * No debt settlement ownership model exists yet: never reserve a key or post.
 */
final class FinanceDebtAtomicSettlementGate
{
    /**
     * @return never
     */
    public function execute(int $debtId, int $voucherId, string $key, string $operation = 'post'): void
    {
        // Validate outside transaction; the plan is not execution authority.
        (new FinanceDebtSettlementTransactionProtocol)->plan($debtId, $voucherId, $key, $operation);

        DB::transaction(function () use ($debtId, $voucherId, $operation, $key): void {
            // Deterministic lock order. A missing record fails closed.
            $debt = DB::table('finance_debt_obligations')->where('id', $debtId)->lockForUpdate()->first();
            if ($debt === null) {
                throw ValidationException::withMessages(['settlement' => 'Debt obligation is unavailable.']);
            }
            $voucher = DB::table('finance_vouchers')->where('id', $voucherId)->lockForUpdate()->first();
            if ($voucher === null) {
                throw ValidationException::withMessages(['settlement' => 'Voucher is unavailable.']);
            }

            $expectedStatus = $operation === 'post' ? 'posted' : 'reversed';
            if ($voucher->status !== $expectedStatus) {
                throw ValidationException::withMessages(['settlement' => 'Voucher lifecycle is incompatible with requested operation.']);
            }

            // CP16: read source ownership under the same transaction boundary.
            // Lock the exact linked source before reading its allocations.
            // A missing source is not authority to settle; legacy manual debts
            // still reach the unconditional fail-closed gate below.
            $link = (new FinanceDebtSourceLinkReader)->forObligation($debtId);
            if ($link !== null) {
                $source = DB::table('finance_source_documents')
                    ->where('id', $link['source_id'])->lockForUpdate()->first();
                if ($source === null) {
                    throw ValidationException::withMessages(['settlement' => 'Linked source is unavailable.']);
                }
                DB::table('finance_allocations')
                    ->where('source_document_id', $link['source_id'])
                    ->orderBy('id')->lockForUpdate()->get(['id']);
                (new FinanceDebtSettlementSourceOwnership)->inspect($debtId, $voucherId);
            }

            // CP17: under the debt lock, inspect the idempotency scope without
            // reserving it. The unique index remains the final duplicate guard
            // for any future approved writer.
            $duplicate = DB::table('finance_debt_idempotency_keys')
                ->where('obligation_id', $debtId)
                ->where('operation', $operation)
                ->where('idempotency_key', $key)
                ->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['settlement' => 'Duplicate settlement idempotency key.']);
            }

            // Lock existing mappings before reading any projected amount.
            // These rows do NOT constitute a reliable outstanding balance,
            // because absent/new mappings and reversal ownership are unresolved.
            if (DB::getSchemaBuilder()->hasTable('finance_debt_settlement_mappings')) {
                DB::table('finance_debt_settlement_mappings')
                    ->where('obligation_id', $debtId)
                    ->orderBy('id')->lockForUpdate()->get(['id']);
            }
            (new FinanceDebtBalanceReader)->forObligation($debtId);

            // Source identity and principal remain diagnostic only. Even if
            // inspection succeeds, the authoritative debt settlement mapping
            // and reversal ownership do not yet exist.
            (new FinanceDebtSettlementContract)->inspect($debtId);

            throw ValidationException::withMessages([
                'settlement' => 'Atomic settlement blocked: authoritative mapping and reversal ownership not implemented.',
            ]);
        }, 3);
    }
}
