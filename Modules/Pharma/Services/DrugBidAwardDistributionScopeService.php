<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Partner\Models\Partner;
use Modules\Partner\Models\PartnerSourceReference;
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
            $provinceCode = trim((string) $data['province_code']);

            $facilityIds = array_values(array_unique(array_map('intval', $data['facility_ids'] ?? [])));
            $facilities = OfficialSourceFacility::query()
                ->whereIn('id', $facilityIds)
                ->where('is_active', true)
                ->where('province_name', $provinceCode)
                ->get();

            if ($facilities->count() !== count($facilityIds)) {
                throw ValidationException::withMessages([
                    'selectedFacilityIds' => 'Chỉ được chọn cơ sở KCB đang hoạt động thuộc Tỉnh/Thành đã chọn.',
                ]);
            }

            $validPartners = $facilities->map(function (OfficialSourceFacility $facility): int {
                $reference = PartnerSourceReference::query()
                    ->where('source', 'official_source_facility')
                    ->where('external_id', (string) $facility->external_id)
                    ->first();

                if ($reference) {
                    return (int) $reference->partner_id;
                }

                $partner = Partner::query()->create([
                    'name' => $facility->facility_name,
                    'legal_type' => 'hospital',
                    'partner_types' => ['customer'],
                    'address' => $facility->address ?? null,
                    'province_code' => $facility->source_province_code ?: $facility->province_name,
                    'source' => 'system',
                    'status' => 'active',
                    'note' => 'Tạo tự động từ Kho dữ liệu cơ sở KCB nguồn khi thiết lập phạm vi trúng thầu.',
                ]);

                PartnerSourceReference::query()->create([
                    'partner_id' => $partner->id,
                    'source' => 'official_source_facility',
                    'external_id' => (string) $facility->external_id,
                    'source_province_code' => $facility->source_province_code,
                    'first_seen_at' => now(),
                    'last_seen_at' => now(),
                    'metadata' => ['official_source_facility_id' => $facility->id],
                ]);

                return (int) $partner->id;
            })->all();

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
