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

        DB::transaction(function () use ($debtId, $voucherId, $operation): void {
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
