<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 11D: non-authoritative reconciliation evidence. Mapping rows and posted
 * treasury vouchers are not, on their own, proof of debt settlement.
 */
final class FinanceDebtPaymentEvidenceReader
{
    public function forObligation(int $debtId): array
    {
        $principal = (new FinanceDebtBalanceReader)->forObligation($debtId)['adjusted_principal'];
        $empty = [
            'principal' => $principal,
            'verified_paid' => null,
            'verified_outstanding' => null,
            'payment_status' => 'Chưa xác minh thanh toán',
            'rows' => [],
            'warning' => 'Chưa có cơ chế ghi sổ và đối soát thanh toán công nợ được kích hoạt.',
        ];
        if (! Schema::hasTable('finance_debt_settlement_mappings')
            || ! Schema::hasTable('finance_vouchers')
            || ! Schema::hasTable('finance_debt_allocation_ownerships')) {
            return $empty;
        }

        $mappings = DB::table('finance_debt_settlement_mappings as m')
            ->leftJoin('finance_vouchers as v', 'v.id', '=', 'm.voucher_id')
            ->leftJoin('finance_debt_settlement_audit_events as ae', 'ae.mapping_id', '=', 'm.id')
            ->where('m.obligation_id', $debtId)
            ->orderByDesc('m.id')
            ->limit(100)
            ->get(['m.id', 'm.operation', 'm.amount', 'm.created_at', 'm.voucher_id',
                'm.reverses_mapping_id', 'v.number as voucher_number',
                'v.status as voucher_status', 'v.voucher_date', 'ae.reason as audit_reason', 'ae.actor_id as audit_actor', 'ae.occurred_at as audit_time']);
        $empty['rows'] = $mappings->map(fn ($m) => [
            'id' => (int) $m->id,
            'operation' => (string) $m->operation,
            'amount' => (string) $m->amount,
            'voucher_number' => (string) ($m->voucher_number ?? ''),
            'voucher_status' => (string) ($m->voucher_status ?? 'missing'),
            'voucher_date' => (string) ($m->voucher_date ?? ''),
            'audit_reason' => (string) ($m->audit_reason ?? ''),
            'audit_actor' => $m->audit_actor === null ? null : (int) $m->audit_actor,
            'audit_time' => (string) ($m->audit_time ?? ''),
            'reverses_mapping_id' => $m->reverses_mapping_id === null ? null : (int) $m->reverses_mapping_id,
        ])->all();

        if ($mappings->isNotEmpty()) {
            $all = DB::table('finance_debt_settlement_mappings as m')
                ->leftJoin('finance_debt_allocation_ownerships as o', 'o.mapping_id', '=', 'm.id')
                ->leftJoin('finance_vouchers as v', 'v.id', '=', 'm.voucher_id')
                ->leftJoin('finance_ledger_entries as l', function ($join) { $join->on('l.voucher_id', '=', 'v.id')->where('l.entry_type', '=', 'posting'); })
                ->where('m.obligation_id', $debtId)
                ->get(['m.id', 'm.operation', 'm.amount', 'm.reverses_mapping_id',
                    'm.voucher_id', 'o.id as ownership_id', 'v.status', 'l.id as ledger_id']);
            $posts = $all->where('operation', 'post')->keyBy('id');
            $reversals = $all->where('operation', 'reverse')->keyBy('reverses_mapping_id');
            $verified = $all->every(function ($row) use ($posts, $reversals): bool {
                if ($row->operation === 'post') {
                    if ($row->ownership_id === null || $row->ledger_id === null) {
                        return false;
                    }
                    return $row->status === (isset($reversals[$row->id]) ? 'reversed' : 'posted');
                }
                return $row->operation === 'reverse' && $row->status === 'reversed'
                    && $row->reverses_mapping_id !== null
                    && isset($posts[$row->reverses_mapping_id])
                    && (string) $posts[$row->reverses_mapping_id]->amount === (string) $row->amount
                    && DB::table('finance_ledger_entries')->where('voucher_id', $row->voucher_id)
                        ->where('entry_type', 'reversal')->exists();
            });
            $balance = (new FinanceDebtSettlementBalanceInvariant)->inspect($debtId);
            if ($verified && $balance['reason_code'] === 'mapping_projection_only_not_authorized') {
                $empty['verified_paid'] = $balance['net_mapped_amount'];
                $empty['verified_outstanding'] = $balance['remaining_diagnostic_amount'];
                $remaining = $balance['remaining_diagnostic_amount'];
                $empty['payment_status'] = (float) $remaining <= 0
                    ? 'Đã thanh toán' : ((float) $balance['net_mapped_amount'] > 0 ? 'Thanh toán một phần' : 'Chưa thanh toán');
                $empty['warning'] = 'Số dư được tính từ các phiếu đã ghi sổ và ánh xạ có quyền sở hữu.';
                return $empty;
            }
        }
        if ($mappings->isNotEmpty()) {
            $empty['warning'] = 'Có '.count($empty['rows']).' dòng ánh xạ thanh toán để đối chiếu; chưa đủ căn cứ xác nhận số tiền đã trả hoặc số dư còn lại.';
        }
        return $empty;
    }
}
