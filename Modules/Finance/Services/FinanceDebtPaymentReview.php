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
        if ($link === null) {
            $reasons[] = 'Chưa có liên kết chứng từ nguồn Finance được xác minh cho khoản công nợ này.';
        }
        // An exact source match alone is insufficient: current production
        // voucher posting has no approved debt allocation command.
        $reasons[] = 'Chưa kích hoạt quy trình ghi sổ phiếu thu/chi và phân bổ công nợ nguyên tử trên môi trường thực tế.';

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
            'eligible' => false,
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
