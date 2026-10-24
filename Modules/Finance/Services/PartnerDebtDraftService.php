<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Partner\Models\Partner;

final class PartnerDebtDraftService
{
    public function create(string $reference, string $direction, int $partnerId, string $amount, ?string $dueOn, int $actorId): int
    {
        if (! config('modules.registry.Partner.enabled', false)) {
            throw ValidationException::withMessages(['partnerId' => 'Partner module is unavailable.']);
        }

        return DB::transaction(function () use ($reference, $direction, $partnerId, $amount, $dueOn, $actorId): int {
            $partner = Partner::query()->whereKey($partnerId)->lockForUpdate()->first();
            $role = $direction === 'receivable' ? 'customer' : 'supplier';
            if (! in_array($direction, ['receivable', 'payable'], true)
                || ! $partner || $partner->status !== 'active' || ! $partner->hasPartnerType($role)) {
                throw ValidationException::withMessages(['partnerId' => 'Select an active customer or supplier.']);
            }

            $debtId = (new FinanceDebtService)->createManualDraft(
                $reference, $direction, (string) $partner->name, $amount, $dueOn, $actorId
            );
            DB::table('finance_debt_obligations')->where('id', $debtId)->update([
                'partner_id' => $partner->id,
                'counterparty_tax_code' => $partner->tax_code,
            ]);

            return $debtId;
        }, 3);
    }
}
