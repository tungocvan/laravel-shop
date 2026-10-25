<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Read-only payment preparation. Never changes debt, vouchers or ledger.
 * A linked source is not proof that settlement can be posted.
 */
final class FinanceDebtPaymentReview
{
    public function inspect(int $debtId): array
    {
        $debt = DB::table('finance_debt_obligations')->where('id', $debtId)->first();
        if (! $debt) {
            throw ValidationException::withMessages(['payment' => 'Không tìm thấy công nợ.']);
        }

        $principal = (new FinanceDebtBalanceReader)->forObligation($debtId)['adjusted_principal'];
        $link = null;
        if (Schema::hasTable('finance_source_documents')) {
            $link = (new FinanceDebtSourceLinkReader)->forObligation($debtId);
        }

        $reasons = [];
        if ($debt->status !== 'confirmed') {
            $reasons[] = 'Công nợ chưa được xác nhận.';
        }
        $invoiceEligible = $debt->source_module === 'Invoices' && $debt->source_type === 'invoice'
            && ctype_digit((string) $debt->source_document_id) && Schema::hasTable('invoices');
        if (! $invoiceEligible) {
            $reasons[] = '11E hiện hỗ trợ công nợ có hóa đơn nguồn Invoices; công nợ thủ công chưa được kích hoạt.';
        } else {
            $invoice = DB::table('invoices')->where('id', (int) $debt->source_document_id)->first();
            if (! $invoice || ($invoice->invoice_type === 'sold' ? 'receivable' : 'payable') !== $debt->direction
                || (string) $invoice->total_amount !== (string) $debt->original_amount) {
                $reasons[] = 'Hóa đơn nguồn không khớp công nợ.';
            }
        }
        if ($link !== null) {
            $source = DB::table('finance_source_documents')->where('id', $link['source_id'])->first();
            if (! $source || $source->direction !== $debt->direction || $source->currency !== $debt->currency
                || (string) $source->original_amount !== (string) $debt->original_amount) {
                $reasons[] = 'Chứng từ Finance nguồn không khớp.';
            }
        }
        $remaining = $principal;
        if (Schema::hasTable('finance_debt_settlement_mappings')) {
            $balance = (new FinanceDebtSettlementBalanceInvariant)->inspect($debtId);
            if ($balance['reason_code'] !== 'mapping_projection_only_not_authorized') {
                $reasons[] = 'Lịch sử thanh toán không nhất quán.';
            } else {
                $remaining = (string) $balance['remaining_diagnostic_amount'];
            }
        } else {
            $reasons[] = 'Chưa có cấu trúc phân bổ công nợ.';
        }
        if ((float) $remaining <= 0) {
            $reasons[] = 'Công nợ đã được thanh toán đủ.';
        }
        if (! Schema::hasTable('finance_debt_allocation_ownerships')
            || ! Schema::hasTable('finance_debt_settlement_audit_events')
            || ! Schema::hasTable('finance_debt_idempotency_keys')) {
            $reasons[] = 'Chưa có cấu trúc kiểm soát thanh toán.';
        }

        $accounts = Schema::hasTable('finance_accounts')
            ? DB::table('finance_accounts')->where('is_active', true)
                ->where('currency', $debt->currency ?: 'VND')
                ->orderBy('name')->get(['id', 'name', 'code', 'kind'])->map(fn ($a) => [
                    'id' => (int) $a->id,
                    'name' => (string) $a->name,
                    'code' => (string) $a->code,
                    'kind' => (string) $a->kind,
                ])->all()
            : [];

        return [
            'debt_id' => $debtId,
            'reference' => (string) $debt->reference_number,
            'counterparty' => (string) $debt->counterparty_name,
            'direction' => (string) $debt->direction,
            'currency' => (string) ($debt->currency ?: 'VND'),
            'principal' => $principal,
            'source_linked' => $link !== null,
            'remaining' => $remaining,
            'eligible' => $reasons === [],
            'reasons' => $reasons,
            'accounts' => $accounts,
        ];
    }

    /**
     * Explicit fail-closed command boundary: review must not mutate balances.
     */
    public function assertCanPost(int $debtId): never
    {
        $review = $this->inspect($debtId);
        throw ValidationException::withMessages(['payment' => implode(' ', $review['reasons'])]);
    }
}
