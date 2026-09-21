<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Partner\Models\Partner;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\DrugBidAwardDistributionScope;
use Modules\Pharma\Models\OfficialSourceFacility;

class DrugBidAwardDistributionScopeService
{
    public function resultKey(DrugBidAward $award): string
    {
        return $award->bidding_notice_code
            ? 'tbmt:'.trim($award->bidding_notice_code)
            : 'award:'.$award->id;
    }

    public function findForAward(DrugBidAward $award): ?DrugBidAwardDistributionScope
    {
        return DrugBidAwardDistributionScope::query()
            ->with('partners')
            ->where('result_key', $this->resultKey($award))
            ->first();
    }

    public function save(DrugBidAward $award, array $data, ?int $adminId): DrugBidAwardDistributionScope
    {
        return DB::transaction(function () use ($award, $data, $adminId) {
            $partnerIds = array_values(array_unique(array_map('intval', $data['partner_ids'] ?? [])));
            $provinceCode = trim((string) $data['province_code']);

            $officialFacilityIds = OfficialSourceFacility::query()
                ->where('is_active', true)
                ->where('province_name', $provinceCode)
                ->whereNotNull('external_id')->where('external_id', '!=', '')
                ->pluck('external_id');

            $validPartners = Partner::query()
                ->whereIn('id', $partnerIds)
                ->where('legal_type', 'hospital')
                ->where('status', 'active')
                ->whereHas('sourceReferences', fn ($query) => $query->whereIn('external_id', $officialFacilityIds))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (count($validPartners) !== count($partnerIds)) {
                throw ValidationException::withMessages([
                    'selectedPartnerIds' => 'Chỉ được chọn bệnh viện đang hoạt động đã liên kết với Kho dữ liệu cơ sở KCB nguồn thuộc Tỉnh/Thành đã chọn.',
                ]);
            }

            $scope = DrugBidAwardDistributionScope::query()->firstOrNew([
                'result_key' => $this->resultKey($award),
            ]);
            $scope->fill([
                'bidding_notice_code' => $award->bidding_notice_code,
                'province_code' => $provinceCode,
                'effective_from' => $data['effective_from'],
                'effective_until' => $data['effective_until'],
                'updated_by' => $adminId,
            ]);
            if (! $scope->exists) {
                $scope->created_by = $adminId;
            }
            $scope->save();
            $scope->partners()->sync($validPartners);

            return $scope->fresh('partners');
        }, 3);
    }
}
