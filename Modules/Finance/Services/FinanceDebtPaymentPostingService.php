<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * 11E: invoice-backed settlement. One transaction owns voucher, allocation,
 * ledger, mapping, allocation ownership and audit. Manual/unknown sources
 * are deliberately excluded until their lifecycle contract is established.
 */
final class FinanceDebtPaymentPostingService
{
    public function post(int $debtId, int $accountId, string $amount, string $date, string $reference, int $actorId): array
    {
        if ($actorId <= 0 || ! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)
            || ! checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4))
            || trim($reference) === '' || mb_strlen($reference) > 100) {
            $this->deny('Thông tin ngày, tham chiếu hoặc người thực hiện không hợp lệ.');
        }
        $cents = $this->cents($amount);
        if ($cents <= 0) {
            $this->deny('Số tiền thanh toán phải lớn hơn 0.');
        }
        foreach (['invoices', 'finance_debt_settlement_mappings', 'finance_debt_allocation_ownerships',
            'finance_debt_settlement_audit_events', 'finance_debt_idempotency_keys'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->deny('Thiếu cấu trúc dữ liệu thanh toán: '.$table);
            }
        }

        return DB::transaction(function () use ($debtId, $accountId, $amount, $cents, $date, $reference, $actorId): array {
            $debt = DB::table('finance_debt_obligations')->where('id', $debtId)->lockForUpdate()->first();
            if (! $debt || $debt->status !== 'confirmed' || $debt->source_module !== 'Invoices'
                || $debt->source_type !== 'invoice' || ! ctype_digit((string) $debt->source_document_id)
                || ! in_array($debt->direction, ['receivable', 'payable'], true) || $debt->currency !== 'VND') {
                $this->deny('Chỉ hỗ trợ công nợ hóa đơn đã xác nhận, có nguồn Invoices hợp lệ.');
            }
            $invoice = DB::table('invoices')->where('id', (int) $debt->source_document_id)->first();
            if (! $invoice || ! in_array($invoice->invoice_type, ['sold', 'purchase'], true)
                || ($invoice->invoice_type === 'sold' ? 'receivable' : 'payable') !== $debt->direction
                || $this->cents((string) $invoice->total_amount) !== $this->cents((string) $debt->original_amount)) {
                $this->deny('Hóa đơn nguồn đã thay đổi hoặc không khớp công nợ.');
            }
            $account = DB::table('finance_accounts')->where('id', $accountId)->lockForUpdate()->first();
            if (! $account || ! $account->is_active || $account->currency !== 'VND') {
                $this->deny('Tài khoản thanh toán không khả dụng.');
            }
            $principal = $this->cents((new FinanceDebtBalanceReader)->forObligation($debtId)['adjusted_principal']);
            $balance = (new FinanceDebtSettlementBalanceInvariant)->inspect($debtId);
            if ($balance['reason_code'] !== 'mapping_projection_only_not_authorized') {
                $this->deny('Lịch sử phân bổ không nhất quán, cần đối chiếu trước.');
            }
            // Legacy mappings without exact ownership/posted voucher cannot become payment proof.
            $existing = DB::table('finance_debt_settlement_mappings as m')
                ->leftJoin('finance_debt_allocation_ownerships as o', 'o.mapping_id', '=', 'm.id')
                ->leftJoin('finance_vouchers as v', 'v.id', '=', 'm.voucher_id')
                ->where('m.obligation_id', $debtId)
                ->where(fn ($q) => $q->whereNull('v.id')
                    ->orWhereNotIn('v.status', ['posted', 'reversed'])
                    ->orWhere(fn ($owned) => $owned->where('m.operation', 'post')->whereNull('o.id'))
                    ->orWhere(fn ($reversal) => $reversal->where('m.operation', 'reverse')
                        ->whereNull('m.reverses_mapping_id')))
                ->exists();
            if ($existing) {
                $this->deny('Có lịch sử thanh toán chưa được xác minh.');
            }
            $remaining = $this->cents((string) $balance['remaining_diagnostic_amount']);
            if ($principal <= 0 || $cents > $remaining) {
                $this->deny('Số tiền vượt số dư còn phải thanh toán.');
            }
            // Reference is a unique business operation token per debt.
            $key = 'pay-'.$debtId.'-'.substr(hash('sha256', trim($reference)), 0, 40);
            (new FinanceDebtIdempotencyRegistry)->reserve($debtId, 'post', $key);
            $source = DB::table('finance_source_documents')
                ->where('source_module', 'Invoices')->where('source_type', 'invoice')
                ->where('source_document_id', (string) $invoice->id)->lockForUpdate()->first();
            if ($source && ($source->direction !== $debt->direction || $source->currency !== 'VND'
                || $this->cents((string) $source->original_amount) !== $this->cents((string) $debt->original_amount))) {
                $this->deny('Chứng từ Finance nguồn không khớp.');
            }
            if (! $source) {
                $sourceId = DB::table('finance_source_documents')->insertGetId([
                    'source_module' => 'Invoices', 'source_type' => 'invoice',
                    'source_document_id' => (string) $invoice->id,
                    'source_document_code' => (string) ($invoice->invoice_number ?: $debt->reference_number),
                    'direction' => $debt->direction, 'currency' => 'VND',
                    'original_amount' => $debt->original_amount,
                    'counterparty' => $debt->counterparty_name,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            } else {
                $sourceId = (int) $source->id;
            }
            // A source already allocated by the treasury outside this debt's
            // owned mappings cannot safely be allocated a second time.
            $unowned = DB::table('finance_allocations as a')
                ->join('finance_vouchers as v', 'v.id', '=', 'a.voucher_id')
                ->leftJoin('finance_debt_allocation_ownerships as o', 'o.allocation_id', '=', 'a.id')
                ->where('a.source_document_id', $sourceId)
                ->where('v.status', 'posted')
                ->whereNull('o.id')->exists();
            if ($unowned) {
                $this->deny('Hóa đơn đã có phân bổ kho quỹ chưa được liên kết công nợ; cần đối soát.');
            }
            $number = 'FIN-D-'.now()->format('YmdHis').'-'.strtoupper(bin2hex(random_bytes(4)));
            $direction = $debt->direction === 'receivable' ? 'receipt' : 'payment';
            $voucherId = DB::table('finance_vouchers')->insertGetId([
                'number' => $number, 'direction' => $direction, 'status' => 'draft',
                'account_id' => $accountId, 'currency' => 'VND', 'amount' => $amount,
                'voucher_date' => $date, 'origin' => 'source',
                'counterparty' => $debt->counterparty_name,
                'description' => 'Thanh toán công nợ '.$debt->reference_number,
                'reference_number' => trim($reference), 'idempotency_key' => $key,
                'created_by' => $actorId, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $allocationId = DB::table('finance_allocations')->insertGetId([
                'voucher_id' => $voucherId, 'source_document_id' => $sourceId,
                'amount' => $amount, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('finance_ledger_entries')->insert([
                'voucher_id' => $voucherId, 'account_id' => $accountId, 'entry_type' => 'posting',
                'signed_amount' => ($direction === 'payment' ? '-' : '').$amount,
                'currency' => 'VND', 'effective_at' => $date.' 00:00:00',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('finance_vouchers')->where('id', $voucherId)->update([
                'status' => 'posted', 'posted_by' => $actorId, 'posted_at' => now(), 'updated_at' => now(),
            ]);
            $mappingId = DB::table('finance_debt_settlement_mappings')->insertGetId([
                'obligation_id' => $debtId, 'voucher_id' => $voucherId,
                'operation' => 'post', 'amount' => $amount, 'reverses_mapping_id' => null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('finance_debt_allocation_ownerships')->insert([
                'obligation_id' => $debtId, 'allocation_id' => $allocationId, 'mapping_id' => $mappingId,
                'authorized_by' => $actorId, 'authorization_reason' => 'Xác nhận thanh toán công nợ',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('finance_debt_settlement_audit_events')->insert([
                'obligation_id' => $debtId, 'mapping_id' => $mappingId, 'voucher_id' => $voucherId,
                'operation' => 'post', 'origin_mapping_id' => null, 'actor_id' => $actorId,
                'reason' => 'Thanh toán tham chiếu '.$reference, 'idempotency_key' => $key,
                'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            return ['voucher_id' => $voucherId, 'number' => $number,
                'remaining' => $this->decimal($remaining - $cents),
                'status' => $remaining === $cents ? 'paid' : 'partial'];
        }, 3);
    }

    private function cents(string $amount): int
    {
        if (! preg_match('/^\d{1,15}(?:\.\d{1,2})?$/D', $amount)) {
            $this->deny('Số tiền không hợp lệ.');
        }
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        if ((int) $whole > intdiv(PHP_INT_MAX, 100)) {
            $this->deny('Số tiền vượt giới hạn.');
        }
        return (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
    }

    private function decimal(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    private function deny(string $message): never
    {
        throw ValidationException::withMessages(['payment' => $message]);
    }
}
