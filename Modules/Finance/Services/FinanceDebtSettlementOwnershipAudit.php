<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CP36 read-only reconciliation of durable exact allocation ownership.
 * This is a diagnostic, never an authorization to settle or reverse.
 */
final class FinanceDebtSettlementOwnershipAudit
{
    public function inspect(int $debtId): array
    {
        if ($debtId <= 0 || ! DB::table('finance_debt_obligations')->where('id', $debtId)->exists()) {
            throw ValidationException::withMessages(['audit' => 'Debt obligation unavailable.']);
        }
        if (! DB::getSchemaBuilder()->hasTable('finance_debt_allocation_ownerships')) {
            return $this->result($debtId, 'ownership_schema_missing', 0, 0);
        }
        $mappings = DB::table('finance_debt_settlement_mappings')
            ->where('obligation_id', $debtId)->orderBy('id')->get();
        $owners = DB::table('finance_debt_allocation_ownerships')
            ->where('obligation_id', $debtId)->get()->keyBy('mapping_id');
        $source = (new FinanceDebtSourceLinkReader)->forObligation($debtId);
        if ($source === null) {
            return $this->result($debtId, 'manual_debt_link_unavailable', $mappings->count(), $owners->count());
        }
        $postIds = [];
        $reversed = [];
        foreach ($mappings as $mapping) {
            if ($mapping->operation === 'post') {
                $postIds[(int) $mapping->id] = true;
                $owner = $owners->get($mapping->id);
                if (! $owner || (int) $owner->obligation_id !== $debtId
                    || (int) $owner->authorized_by <= 0 || trim((string) $owner->authorization_reason) === '') {
                    return $this->result($debtId, 'post_ownership_or_audit_missing', $mappings->count(), $owners->count());
                }
                $allocation = DB::table('finance_allocations')->where('id', $owner->allocation_id)->first();
                if (! $allocation || (int) $allocation->voucher_id !== (int) $mapping->voucher_id
                    || (int) $allocation->source_document_id !== (int) $source['source_id']
                    || $this->cents((string) $allocation->amount) !== $this->cents((string) $mapping->amount)) {
                    return $this->result($debtId, 'allocation_ownership_mismatch', $mappings->count(), $owners->count());
                }
            } elseif ($mapping->operation === 'reverse') {
                if ($mapping->reverses_mapping_id === null || isset($reversed[(int) $mapping->reverses_mapping_id])) {
                    return $this->result($debtId, 'invalid_reversal_origin', $mappings->count(), $owners->count());
                }
                $reversed[(int) $mapping->reverses_mapping_id] = true;
                if ($owners->has($mapping->id)) {
                    return $this->result($debtId, 'reversal_cannot_own_allocation', $mappings->count(), $owners->count());
                }
                $voucher = DB::table('finance_vouchers')->where('id', $mapping->voucher_id)->first();
                if (! $voucher || $voucher->status !== 'reversed') {
                    return $this->result($debtId, 'reversal_voucher_lifecycle_mismatch', $mappings->count(), $owners->count());
                }
            } else {
                return $this->result($debtId, 'unknown_mapping_operation', $mappings->count(), $owners->count());
            }
        }
        foreach ($owners as $owner) {
            if (! isset($postIds[(int) $owner->mapping_id])) {
                return $this->result($debtId, 'orphan_allocation_owner', $mappings->count(), $owners->count());
            }
        }
        $balance = (new FinanceDebtSettlementBalanceInvariant)->inspect($debtId);
        return $this->result(
            $debtId,
            $balance['reason_code'] === 'mapping_projection_only_not_authorized'
                ? 'ownership_reconciled_diagnostic_only' : $balance['reason_code'],
            $mappings->count(),
            $owners->count()
        );
    }

    private function cents(string $amount): ?int
    {
        if (! preg_match('/^\d{1,15}(?:\.\d{1,2})?$/D', $amount)) {
            return null;
        }
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function result(int $debtId, string $reason, int $mappings, int $owners): array
    {
        return [
            'debt_id' => $debtId,
            'authoritative' => false,
            'eligible' => false,
            'reason_code' => $reason,
            'mapping_count' => $mappings,
            'ownership_count' => $owners,
        ];
    }
}
