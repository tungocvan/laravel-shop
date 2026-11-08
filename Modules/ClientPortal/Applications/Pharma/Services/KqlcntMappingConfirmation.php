<?php

namespace Modules\ClientPortal\Applications\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Muasamcong\Services\KqlcntAwardReadService;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Services\DrugBidAwardResultGroupService;

/**
 * Explicitly confirms a previously unlinked, uniquely verified historical
 * Pharma award against canonical KQLCNT. No automatic writes on GET.
 */
final class KqlcntMappingConfirmation
{
    public function __construct(
        private readonly KqlcntAwardReadService $canonical,
        private readonly KqlcntLegacyWorkflowBridge $bridge,
        private readonly DrugBidAwardResultGroupService $groups,
    ) {}

    public function confirm(int $awardItemId, int $userId): void
    {
        DB::transaction(function () use ($awardItemId, $userId): void {
            $lines = $this->canonical->medicines($awardItemId);
            $ids = $this->bridge->verifiedMappingCandidateIds($awardItemId);
            if ($lines->isEmpty() || count($ids) !== $lines->count()) {
                $this->reject();
            }

            $rows = DrugBidAward::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            if ($rows->count() !== $lines->count()) {
                $this->reject();
            }

            $first = $rows->first();
            $legacyIds = $this->groups->awardsQuery($first)->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
            $candidateIds = collect($ids)->sort()->values()->all();
            if ($legacyIds !== $candidateIds) {
                $this->reject();
            }

            $used = [];
            foreach ($lines as $line) {
                $matches = $rows->filter(fn (DrugBidAward $row): bool =>
                    $row->source_id === null
                    && in_array($row->source_type, [null, DrugBidAward::SOURCE_MANUAL], true)
                    && ! $row->sources()->exists()
                    && trim((string) $row->bidding_notice_code) === trim((string) $line->notify_no)
                    && trim((string) $row->contractor_code) === trim((string) $line->contractor_code)
                    && trim((string) $row->winning_company_name) === trim((string) $line->contractor_name)
                    && trim((string) $row->medicine_name) === trim((string) $line->medicine_name)
                    && $this->decimalEqual($row->quantity, $line->quantity)
                    && $this->decimalEqual($row->winning_price, $line->winning_price)
                    && ! in_array((int) $row->id, $used, true)
                );
                if ($matches->count() !== 1) {
                    $this->reject();
                }

                $row = $matches->first();
                if (DrugBidAward::query()
                    ->where('source_type', DrugBidAward::SOURCE_MUASAMCONG)
                    ->where('source_id', $line->id)
                    ->exists()) {
                    $this->reject();
                }
                $used[] = (int) $row->id;
                // Explicit provenance reassignment of a previously unlinked
                // manual award; never overwrite an existing source reference.
                $row->source_type = DrugBidAward::SOURCE_MUASAMCONG;
                $row->source_id = (int) $line->id;
                $row->source_synced_at = now();
                $row->manual_note = trim((string) $row->manual_note.' | Xác nhận liên kết KQLCNT #'.$line->id.' bởi user #'.$userId);
                $row->save();
            }
        }, 3);
    }

    private function decimalEqual(mixed $a, mixed $b): bool
    {
        return $a !== null && $b !== null
            && number_format((float) $a, 4, '.', '') === number_format((float) $b, 4, '.', '');
    }

    private function reject(): never
    {
        throw ValidationException::withMessages([
            'mapping' => 'Không thể xác nhận: định danh sản phẩm, nguồn dữ liệu hoặc phạm vi TBMT đã thay đổi. Vui lòng kiểm tra lại.',
        ]);
    }
}
