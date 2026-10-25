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
            ->where('m.obligation_id', $debtId)
            ->orderByDesc('m.id')
            ->limit(100)
            ->get(['m.id', 'm.operation', 'm.amount', 'm.created_at', 'm.voucher_id',
                'm.reverses_mapping_id', 'v.number as voucher_number',
                'v.status as voucher_status', 'v.voucher_date']);
        $empty['rows'] = $mappings->map(fn ($m) => [
            'id' => (int) $m->id,
            'operation' => (string) $m->operation,
            'amount' => (string) $m->amount,
            'voucher_number' => (string) ($m->voucher_number ?? ''),
            'voucher_status' => (string) ($m->voucher_status ?? 'missing'),
            'voucher_date' => (string) ($m->voucher_date ?? ''),
            'reverses_mapping_id' => $m->reverses_mapping_id === null ? null : (int) $m->reverses_mapping_id,
        ])->all();

        if ($mappings->isNotEmpty()) {
            $all = DB::table('finance_debt_settlement_mappings as m')
                ->leftJoin('finance_debt_allocation_ownerships as o', 'o.mapping_id', '=', 'm.id')
                ->leftJoin('finance_vouchers as v', 'v.id', '=', 'm.voucher_id')
                ->where('m.obligation_id', $debtId)
                ->get(['m.id', 'm.operation', 'm.amount', 'm.reverses_mapping_id',
                    'm.voucher_id', 'o.id as ownership_id', 'v.status']);
            $verified = $all->every(function ($row) use ($debtId): bool {
                if (! $row->status || ! in_array($row->status, ['posted', 'reversed'], true)) {
                    return false;
                }
                if ($row->operation === 'post') {
                    return $row->ownership_id !== null && $row->status === 'posted';
                }
                return $row->operation === 'reverse' && $row->status === 'reversed'
                    && $row->reverses_mapping_id !== null;
            });
            $balance = (new FinanceDebtSettlementBalanceInvariant)->inspect($debtId);
            if ($verified && $balance['reason_code'] === 'mapping_projection_only_not_authorized') {
                $empty['verified_paid'] = $balance['net_mapped_amount'];
                $empty['verified_outstanding'] = $balance['remaining_diagnostic_amount'];
                $remaining = $balance['remaining_diagnostic_amount'];
                $empty['payment_status'] = (float) $remaining <= 0
                    ? 'Đã thanh toán' : 'Thanh toán một phần';
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
