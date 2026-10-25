<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 11G read-only reconciliation snapshot. Never authorizes settlement.
 * Amounts are DECIMAL strings; SQL performs the monetary arithmetic.
 */
final class FinanceDebtReconciliation11G
{
    public function snapshot(string $direction = '', string $search = ''): array
    {
        $required = ['finance_debt_obligations', 'finance_debt_events', 'finance_debt_settlement_mappings',
            'finance_debt_allocation_ownerships', 'finance_vouchers', 'finance_allocations',
            'finance_ledger_entries'];
        foreach ($required as $table) {
            if (! Schema::hasTable($table)) {
                return ['ready' => false, 'missing' => $table, 'rows' => [], 'totals' => null];
            }
        }

        $query = DB::table('finance_debt_obligations as d')
            ->where('d.status', 'confirmed')
            ->select('d.id', 'd.reference_number', 'd.counterparty_name', 'd.direction', 'd.original_amount')
            ->selectRaw('(SELECT COALESCE(SUM(e.signed_amount),0) FROM finance_debt_events e WHERE e.obligation_id=d.id) AS adjustments')
            ->selectRaw('(d.original_amount + (SELECT COALESCE(SUM(e.signed_amount),0) FROM finance_debt_events e WHERE e.obligation_id=d.id)) AS principal')
            ->selectRaw("(SELECT COALESCE(SUM(CASE WHEN m.operation='post' THEN m.amount WHEN m.operation='reverse' THEN -m.amount ELSE 0 END),0) FROM finance_debt_settlement_mappings m WHERE m.obligation_id=d.id) AS net_mapped")
            ->selectRaw("(SELECT COUNT(*) FROM finance_debt_settlement_mappings m LEFT JOIN finance_vouchers v ON v.id=m.voucher_id LEFT JOIN finance_debt_allocation_ownerships o ON o.mapping_id=m.id WHERE m.obligation_id=d.id AND (v.id IS NULL OR v.status NOT IN ('posted','reversed') OR (m.operation='post' AND o.id IS NULL) OR (m.operation='reverse' AND (m.reverses_mapping_id IS NULL OR v.status <> 'reversed')))) AS suspect_mappings")
            ->selectRaw("(SELECT COUNT(*) FROM finance_debt_settlement_mappings m JOIN finance_vouchers v ON v.id=m.voucher_id WHERE m.obligation_id=d.id AND m.operation='post' AND v.status='reversed' AND NOT EXISTS (SELECT 1 FROM finance_debt_settlement_mappings r WHERE r.reverses_mapping_id=m.id AND r.operation='reverse')) AS missing_reversals")
            ->selectRaw("(SELECT COUNT(*) FROM finance_debt_settlement_mappings m JOIN finance_vouchers v ON v.id=m.voucher_id WHERE m.obligation_id=d.id AND m.operation='reverse' AND NOT EXISTS (SELECT 1 FROM finance_ledger_entries l WHERE l.voucher_id=v.id AND l.entry_type='reversal')) AS missing_reversal_ledgers")
            ->when(in_array($direction, ['receivable', 'payable'], true), fn ($q) => $q->where('d.direction', $direction))
            ->when(trim($search) !== '', function ($q) use ($search): void {
                $term = '%'.addcslashes(trim($search), '%_\\').'%';
                $q->where(fn ($nested) => $nested->where('d.reference_number', 'like', $term)
                    ->orWhere('d.counterparty_name', 'like', $term));
            });

        $data = DB::query()->fromSub($query, 'x')
            ->select('x.*')
            ->selectRaw('(principal - net_mapped) AS outstanding')
            ->selectRaw("(CASE WHEN suspect_mappings > 0 OR missing_reversals > 0 OR missing_reversal_ledgers > 0 OR net_mapped < 0 OR net_mapped > principal OR principal < 0 THEN 'review' ELSE 'matched' END) AS reconciliation_status");

        $totals = DB::query()->fromSub(clone $data, 'r')
            ->selectRaw('COUNT(*) AS debt_count')
            ->selectRaw("COALESCE(SUM(CASE WHEN direction='receivable' THEN principal ELSE 0 END),0) AS receivable_principal")
            ->selectRaw("COALESCE(SUM(CASE WHEN direction='payable' THEN principal ELSE 0 END),0) AS payable_principal")
            ->selectRaw("COALESCE(SUM(CASE WHEN reconciliation_status='matched' AND direction='receivable' THEN outstanding ELSE 0 END),0) AS receivable_outstanding_verified")
            ->selectRaw("COALESCE(SUM(CASE WHEN reconciliation_status='matched' AND direction='payable' THEN outstanding ELSE 0 END),0) AS payable_outstanding_verified")
            ->selectRaw("COALESCE(SUM(CASE WHEN reconciliation_status='matched' THEN net_mapped ELSE 0 END),0) AS verified_net_payments")
            ->selectRaw("COALESCE(SUM(CASE WHEN reconciliation_status='review' THEN 1 ELSE 0 END),0) AS review_count")
            ->first();

        return ['ready' => true, 'missing' => null, 'rows' => $data->orderByDesc('id')->paginate(15),
            'totals' => $totals];
    }
}
