<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * 11F: atomically reverse one owned, posted invoice debt payment.
 * The original voucher and ledger are immutable audit records.
 */
final class FinanceDebtPaymentReversalService
{
    public function reverse(int $debtId, int $voucherId, string $reason, int $actorId): array
    {
        $reason = trim($reason);
        if ($debtId <= 0 || $voucherId <= 0 || $actorId <= 0 || mb_strlen($reason) < 10 || mb_strlen($reason) > 500) {
            $this->deny('Lý do hoàn tác phải từ 10 đến 500 ký tự và người thực hiện phải hợp lệ.');
        }

        return DB::transaction(function () use ($debtId, $voucherId, $reason, $actorId): array {
            $debt = DB::table('finance_debt_obligations')->where('id', $debtId)->lockForUpdate()->first();
            if (! $debt || $debt->status !== 'confirmed' || $debt->source_module !== 'Invoices'
                || $debt->source_type !== 'invoice') {
                $this->deny('Chỉ hoàn tác thanh toán công nợ hóa đơn đã xác nhận.');
            }
            $voucher = DB::table('finance_vouchers')->where('id', $voucherId)->lockForUpdate()->first();
            if (! $voucher || $voucher->status !== 'posted' || $voucher->origin !== 'source'
                || $voucher->currency !== $debt->currency
                || $voucher->direction !== ($debt->direction === 'receivable' ? 'receipt' : 'payment')) {
                $this->deny('Phiếu không ở trạng thái đã ghi sổ hoặc không thuộc loại công nợ này.');
            }
            $mapping = DB::table('finance_debt_settlement_mappings')
                ->where('obligation_id', $debtId)->where('voucher_id', $voucherId)
                ->where('operation', 'post')->lockForUpdate()->first();
            if (! $mapping || $mapping->reverses_mapping_id !== null
                || DB::table('finance_debt_settlement_mappings')->where('reverses_mapping_id', $mapping->id)->exists()) {
                $this->deny('Phiếu chưa có phân bổ hợp lệ hoặc đã được hoàn tác.');
            }
            $ownership = DB::table('finance_debt_allocation_ownerships')
                ->where('obligation_id', $debtId)->where('mapping_id', $mapping->id)->first();
            if (! $ownership) {
                $this->deny('Không tìm thấy quyền sở hữu phân bổ thanh toán.');
            }
            $allocation = DB::table('finance_allocations')->where('id', $ownership->allocation_id)
                ->where('voucher_id', $voucherId)->first();
            $source = $allocation ? DB::table('finance_source_documents')
                ->where('id', $allocation->source_document_id)->first() : null;
            if (! $allocation || ! $source || $source->source_module !== $debt->source_module
                || $source->source_type !== $debt->source_type
                || (string) $source->source_document_id !== (string) $debt->source_document_id
                || (string) $allocation->amount != (string) $mapping->amount
                || (string) $voucher->amount != (string) $mapping->amount
                || DB::table('finance_allocations')->where('voucher_id', $voucherId)->count() !== 1
                || DB::table('finance_debt_settlement_mappings')->where('voucher_id', $voucherId)->count() !== 1) {
                $this->deny('Phiếu, nguồn và phân bổ không khớp; cần đối soát thủ công.');
            }
            $posting = DB::table('finance_ledger_entries')->where('voucher_id', $voucherId)
                ->where('entry_type', 'posting')->first();
            if (! $posting || DB::table('finance_ledger_entries')->where('voucher_id', $voucherId)->count() !== 1
                || $posting->account_id !== $voucher->account_id
                || $posting->currency !== $voucher->currency) {
                $this->deny('Bút toán gốc không hợp lệ hoặc đã có bút toán đảo.');
            }
            $balance = (new FinanceDebtSettlementBalanceInvariant)->inspect($debtId);
            if ($balance['reason_code'] !== 'mapping_projection_only_not_authorized') {
                $this->deny('Lịch sử công nợ không nhất quán.');
            }
            $key = 'reverse-'.$debtId.'-'.$voucherId;
            (new FinanceDebtIdempotencyRegistry)->reserve($debtId, 'reverse', $key);

            $signed = (string) $posting->signed_amount;
            $opposite = str_starts_with($signed, '-') ? substr($signed, 1) : '-'.$signed;
            DB::table('finance_ledger_entries')->insert([
                'voucher_id' => $voucherId, 'account_id' => $posting->account_id,
                'entry_type' => 'reversal', 'signed_amount' => $opposite,
                'currency' => $posting->currency, 'effective_at' => now(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('finance_vouchers')->where('id', $voucherId)->update([
                'status' => 'reversed', 'reversed_by' => $actorId,
                'reversed_at' => now(), 'updated_at' => now(),
            ]);
            $reversalId = DB::table('finance_debt_settlement_mappings')->insertGetId([
                'obligation_id' => $debtId, 'voucher_id' => $voucherId,
                'operation' => 'reverse', 'amount' => $mapping->amount,
                'reverses_mapping_id' => $mapping->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('finance_debt_settlement_audit_events')->insert([
                'obligation_id' => $debtId, 'mapping_id' => $reversalId,
                'voucher_id' => $voucherId, 'operation' => 'reverse',
                'origin_mapping_id' => $mapping->id, 'actor_id' => $actorId,
                'reason' => $reason, 'idempotency_key' => $key,
                'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $after = (new FinanceDebtSettlementBalanceInvariant)->inspect($debtId);
            if ($after['reason_code'] !== 'mapping_projection_only_not_authorized') {
                $this->deny('Không thể đối soát số dư sau hoàn tác.');
            }
            return ['voucher_id' => $voucherId, 'voucher_number' => $voucher->number,
                'remaining' => $after['remaining_diagnostic_amount']];
        }, 3);
    }

    private function deny(string $message): never
    {
        throw ValidationException::withMessages(['reversal' => $message]);
    }
}
