<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Muasamcong\Models\KqlcntAwardItem;
use Modules\Muasamcong\Services\KqlcntAwardReadService;
use Modules\Pharma\Models\DrugBidAward;

/**
 * Explicit, atomic bootstrap of a completely new Excel KQLCNT notice.
 * Existing Pharma awards and all allocations/policies remain untouched.
 */
final class KqlcntExcelAwardBootstrapService
{
    public function __construct(private readonly KqlcntAwardReadService $canonical) {}

    public function eligible(int $awardItemId): bool
    {
        $lines = $this->canonical->medicines($awardItemId);
        if ($lines->isEmpty()) {
            return false;
        }

        $first = $lines->first();
        $notice = trim((string) $first->notify_no);
        $contractor = trim((string) $first->contractor_code);
        if ($notice === '' || $contractor === '' || trim((string) $first->contractor_name) === '') {
            return false;
        }

        if (DrugBidAward::query()->where('bidding_notice_code', $notice)->exists()) {
            return false;
        }

        // Never bootstrap a subset of a notice that contains another contractor:
        // legacy allocation/policy groups all Pharma rows by TBMT alone.
        if (KqlcntAwardItem::query()->where('notify_no', $notice)
            ->where(function ($query) use ($contractor, $first): void {
                $query->where('contractor_code', '!=', $contractor)
                    ->orWhereNull('contractor_code')
                    ->orWhere('contractor_name', '!=', $first->contractor_name)
                    ->orWhereNull('contractor_name');
            })->exists()) {
            return false;
        }

        $keys = [];
        foreach ($lines as $line) {
            if ((string) $line->source !== 'excel'
                || trim((string) $line->notify_no) !== $notice
                || trim((string) $line->contractor_code) !== $contractor
                || trim((string) $line->contractor_name) !== trim((string) $first->contractor_name)
                || trim((string) $line->medicine_name) === ''
                || $line->quantity === null || $line->winning_price === null
                || trim((string) $line->identity_key) === '') {
                return false;
            }
            if (isset($keys[$line->identity_key])) {
                return false;
            }
            $keys[$line->identity_key] = true;
        }

        return true;
    }

    public function confirm(int $awardItemId, int $userId): int
    {
        return DB::transaction(function () use ($awardItemId, $userId): int {
            $lines = $this->canonical->medicines($awardItemId);
            if ($lines->isEmpty()) {
                $this->reject();
            }

            // Lock the canonical notice rows to serialize concurrent confirmations.
            $notice = trim((string) $lines->first()->notify_no);
            KqlcntAwardItem::query()->where('notify_no', $notice)
                ->orderBy('id')->lockForUpdate()->get(['id']);

            if (! $this->eligible($awardItemId)) {
                $this->reject();
            }

            $created = 0;
            foreach ($lines as $line) {
                // Defensive recheck: never overwrite or reuse an existing source.
                if (DrugBidAward::query()->where('source_type', DrugBidAward::SOURCE_MUASAMCONG)
                    ->where('source_id', $line->id)->exists()) {
                    $this->reject();
                }

                DrugBidAward::query()->create([
                    'canonical_identity_key' => $line->identity_key,
                    'medicine_code' => $line->medicine_code,
                    'medicine_match_status' => DrugBidAward::MATCH_UNRESOLVED,
                    'medicine_name' => $line->medicine_name,
                    'active_ingredient' => $line->active_ingredient,
                    'concentration' => $line->concentration,
                    'route' => $line->route,
                    'dosage_form' => $line->dosage_form,
                    'unit' => $line->unit,
                    'drug_group' => $line->drug_group,
                    'packaging_specification' => $line->packaging_spec,
                    'shelf_life_months' => $line->shelf_life_months,
                    'registration_or_import_license' => $line->registration_or_import_license,
                    'manufacturer' => $line->manufacturer,
                    'country' => $line->country,
                    'quantity' => $line->quantity,
                    'price_plan' => $line->price_plan,
                    'winning_price' => $line->winning_price,
                    'amount' => $line->amount,
                    'bidding_notice_code' => $line->notify_no,
                    'lot_no' => $line->lot_no,
                    'investor_code' => $line->investor_code,
                    'investor_name' => $line->investor_name,
                    'decision_number' => $line->decision_no,
                    'decision_date' => $line->decision_date,
                    'published_at' => $line->published_at,
                    'contract_no' => $line->contract_no,
                    'contract_period' => $line->contract_period,
                    'contract_period_unit' => $line->contract_period_unit,
                    'contract_period_text' => $line->contract_period_text,
                    'effect_frame_period' => $line->effect_frame_period,
                    'winning_company_name' => $line->contractor_name,
                    'contractor_code' => $line->contractor_code,
                    'is_active' => $line->is_active,
                    'source_type' => DrugBidAward::SOURCE_MUASAMCONG,
                    'source_id' => $line->id,
                    'source_synced_at' => now(),
                    'source_payload_hash' => $line->fingerprint,
                    'created_by' => $userId,
                    'manual_note' => 'Khởi tạo từ hồ sơ KQLCNT Excel #'.$line->id,
                ]);
                $created++;
            }

            return $created;
        }, 3);
    }

    private function reject(): never
    {
        throw ValidationException::withMessages([
            'mapping' => 'Không thể khởi tạo Pharma: TBMT đã tồn tại, nguồn không phải Excel hoặc phạm vi nhà thầu/sản phẩm không an toàn.',
        ]);
    }
}
