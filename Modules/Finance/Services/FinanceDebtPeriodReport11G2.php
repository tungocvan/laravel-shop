<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Read-only 11G.2 operational period movement projection.
 * All money arithmetic stays in SQL DECIMAL; never a statutory ledger.
 */
final class FinanceDebtPeriodReport11G2
{
    public function snapshot(string $from, string $to, string $direction = '', string $search = ''): array
    {
        foreach ([$from, $to] as $date) {
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)
                || ! checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4))) {
                throw ValidationException::withMessages(['period' => 'Ngày báo cáo không hợp lệ.']);
            }
        }
        if ($from > $to) {
            throw ValidationException::withMessages(['period' => 'Ngày bắt đầu phải trước hoặc bằng ngày kết thúc.']);
        }
        foreach (['finance_debt_obligations', 'finance_debt_events', 'finance_debt_settlement_mappings',
            'finance_debt_allocation_ownerships', 'finance_vouchers', 'finance_ledger_entries'] as $table) {
            if (! Schema::hasTable($table)) {
                return ['ready' => false, 'missing' => $table, 'rows' => [], 'totals' => null];
            }
        }

        // A post uses the posting ledger's effective date; a reversal uses its
        // reversal ledger's effective date. The original voucher date is not a
        // reliable reversal date. Missing or ambiguous evidence is flagged.
        $base = DB::table('finance_debt_obligations as d')
            ->where('d.status', 'confirmed')
            ->whereDate('d.recognized_on', '<=', $to)
            ->when(in_array($direction, ['receivable', 'payable'], true), fn ($q) => $q->where('d.direction', $direction))
            ->when(trim($search) !== '', function ($q) use ($search): void {
                $term = '%'.addcslashes(mb_substr(trim($search), 0, 100), '%_\\').'%';
                $q->where(fn ($w) => $w->where('d.reference_number', 'like', $term)
                    ->orWhere('d.counterparty_name', 'like', $term));
            })
            ->select('d.id', 'd.reference_number', 'd.counterparty_name', 'd.direction', 'd.recognized_on')
            ->selectRaw("CASE WHEN d.recognized_on < ? THEN d.original_amount ELSE 0 END AS opening_original", [$from])
            ->selectRaw("CASE WHEN d.recognized_on BETWEEN ? AND ? THEN d.original_amount ELSE 0 END AS new_principal", [$from, $to])
            ->selectRaw("(SELECT COALESCE(SUM(e.signed_amount),0) FROM finance_debt_events e WHERE e.obligation_id=d.id AND e.effective_on < ?) AS opening_adjustments", [$from])
            ->selectRaw("(SELECT COALESCE(SUM(e.signed_amount),0) FROM finance_debt_events e WHERE e.obligation_id=d.id AND e.effective_on BETWEEN ? AND ?) AS period_adjustments", [$from, $to]);

        // Correlated aggregates prevent multiplying debt rows by event/mapping joins.
        // Only owned mappings with exactly one matching ledger entry are included.
        $mapped = function (string $op, string $dateOperator, string $date): array {
            $entry = $op === 'post' ? 'posting' : 'reversal';
            $sql = "(SELECT COALESCE(SUM(m.amount),0)
                FROM finance_debt_settlement_mappings m
                JOIN finance_debt_allocation_ownerships o ON o.mapping_id =
                    CASE WHEN m.operation='reverse' THEN m.reverses_mapping_id ELSE m.id END
                JOIN finance_ledger_entries l ON l.voucher_id=m.voucher_id AND l.entry_type=?
                WHERE m.obligation_id=d.id AND m.operation=?
                AND DATE(l.effective_at) $dateOperator ?
                AND (SELECT COUNT(*) FROM finance_ledger_entries lx WHERE lx.voucher_id=m.voucher_id AND lx.entry_type=?)=1
                AND (SELECT COUNT(*) FROM finance_debt_allocation_ownerships ox WHERE ox.mapping_id=o.mapping_id)=1
                AND (m.operation='post' OR EXISTS
                    (SELECT 1 FROM finance_debt_settlement_mappings p WHERE p.id=m.reverses_mapping_id
                     AND p.obligation_id=m.obligation_id AND p.voucher_id=m.voucher_id AND p.amount=m.amount)))";
            return [$sql, [$entry, $op, $date, $entry]];
        };
        [$beforePost, $bp] = $mapped('post', '<', $from);
        [$beforeReverse, $br] = $mapped('reverse', '<', $from);
        [$throughPost, $tp] = $mapped('post', '<=', $to);
        [$throughReverse, $tr] = $mapped('reverse', '<=', $to);

        $base->selectRaw("($beforePost - $beforeReverse) AS opening_net_paid", [...$bp, ...$br])
            ->selectRaw("($throughPost - $throughReverse) AS closing_net_paid", [...$tp, ...$tr])
            ->selectRaw("(SELECT COUNT(*) FROM finance_debt_settlement_mappings m
                LEFT JOIN finance_debt_allocation_ownerships o ON o.mapping_id =
                    CASE WHEN m.operation='reverse' THEN m.reverses_mapping_id ELSE m.id END
                LEFT JOIN finance_vouchers v ON v.id=m.voucher_id
                WHERE m.obligation_id=d.id AND (o.id IS NULL OR v.id IS NULL
                    OR v.status NOT IN ('posted','reversed')
                    OR (SELECT COUNT(*) FROM finance_debt_allocation_ownerships ox WHERE ox.mapping_id=o.mapping_id)<>1
                    OR (m.operation='reverse' AND NOT EXISTS
                        (SELECT 1 FROM finance_debt_settlement_mappings p WHERE p.id=m.reverses_mapping_id
                        AND p.obligation_id=m.obligation_id AND p.voucher_id=m.voucher_id AND p.amount=m.amount))
                    OR NOT EXISTS
                    (SELECT 1 FROM finance_ledger_entries l WHERE l.voucher_id=m.voucher_id
                    AND l.entry_type=CASE WHEN m.operation='post' THEN 'posting' ELSE 'reversal' END))) AS evidence_gaps");

        $period = DB::query()->fromSub($base, 'x')
            ->select('x.*')
            ->selectRaw('(opening_original + opening_adjustments - opening_net_paid) AS opening_balance')
            ->selectRaw('(new_principal + period_adjustments) AS principal_movement')
            ->selectRaw('(closing_net_paid - opening_net_paid) AS payment_movement')
            ->selectRaw('(opening_original + opening_adjustments + new_principal + period_adjustments - closing_net_paid) AS closing_balance')
            ->selectRaw("CASE WHEN evidence_gaps > 0 OR closing_net_paid < 0
                OR closing_net_paid > opening_original + opening_adjustments + new_principal + period_adjustments
                THEN 'review' ELSE 'provisional' END AS report_status");

        $totals = DB::query()->fromSub(clone $period, 'r')
            ->selectRaw('COUNT(*) AS debt_count')
            ->selectRaw("COALESCE(SUM(CASE WHEN report_status='provisional' THEN opening_balance ELSE 0 END),0) AS opening_balance")
            ->selectRaw("COALESCE(SUM(CASE WHEN report_status='provisional' THEN principal_movement ELSE 0 END),0) AS principal_movement")
            ->selectRaw("COALESCE(SUM(CASE WHEN report_status='provisional' THEN payment_movement ELSE 0 END),0) AS payment_movement")
            ->selectRaw("COALESCE(SUM(CASE WHEN report_status='provisional' THEN closing_balance ELSE 0 END),0) AS closing_balance")
            ->selectRaw("COALESCE(SUM(CASE WHEN report_status='review' THEN 1 ELSE 0 END),0) AS review_count")
            ->first();

        return ['ready' => true, 'missing' => null, 'rows' => $period->orderByDesc('id')->paginate(15, ['*'], 'periodPage'),
            'totals' => $totals];
    }
}
