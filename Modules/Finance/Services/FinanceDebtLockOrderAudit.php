<?php

namespace Modules\Finance\Services;

use Illuminate\Validation\ValidationException;

/**
 * CP19: diagnostic lock-order contract, not a locking primitive.
 * Never treats a declared sequence as evidence of DB deadlock safety.
 */
final class FinanceDebtLockOrderAudit
{
    public const SETTLEMENT = ['debt', 'voucher', 'source', 'allocation', 'mapping'];
    public const VOUCHER_POST = ['voucher', 'account', 'allocation', 'source'];
    public const VOUCHER_REVERSE = ['voucher', 'ledger'];

    public function inspect(array $sequence): array
    {
        $allowed = ['debt', 'voucher', 'account', 'source', 'allocation', 'mapping', 'ledger'];
        $seen = [];
        foreach ($sequence as $resource) {
            if (! is_string($resource) || ! in_array($resource, $allowed, true) || isset($seen[$resource])) {
                return ['eligible' => false, 'reason_code' => 'invalid_or_duplicate_lock_resource'];
            }
            $seen[$resource] = true;
        }
        $canonical = self::SETTLEMENT;
        $last = -1;
        foreach ($sequence as $resource) {
            $index = array_search($resource, $canonical, true);
            if ($index === false) {
                return ['eligible' => false, 'reason_code' => 'requires_cross_service_lock_review'];
            }
            if ($index <= $last) {
                return ['eligible' => false, 'reason_code' => 'inverted_lock_order'];
            }
            $last = $index;
        }

        return ['eligible' => false, 'reason_code' => 'sequence_only_not_concurrency_proof'];
    }

    public function crossServiceAudit(): array
    {
        // Source and allocation acquisition is reversed across these services.
        // This is a documented hazard, not an assertion that a deadlock occurred.
        return [
            'eligible' => false,
            'reason_code' => 'cross_service_source_allocation_order_conflict',
            'settlement_order' => self::SETTLEMENT,
            'voucher_post_order' => self::VOUCHER_POST,
            'voucher_reverse_order' => self::VOUCHER_REVERSE,
            'requires_real_database_contention_tests' => true,
        ];
    }

    public function assertSafeForSettlement(): void
    {
        throw ValidationException::withMessages([
            'settlement' => 'Cross-service lock safety is not proven; settlement remains disabled.',
        ]);
    }
}
