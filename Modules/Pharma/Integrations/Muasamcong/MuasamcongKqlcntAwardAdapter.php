<?php

namespace Modules\Pharma\Integrations\Muasamcong;

use Modules\Muasamcong\Models\KqlcntAwardItem;
use Modules\Pharma\Data\DrugAwardProjectionData;

class MuasamcongKqlcntAwardAdapter
{
    public function fromModel(KqlcntAwardItem $item): DrugAwardProjectionData
    {
        return new DrugAwardProjectionData(
            sourceSystem: 'muasamcong',
            sourceRecordType: 'kqlcnt_award_item',
            sourceRecordKey: (string) ($item->identity_key ?: $item->getKey()),
            medicineName: (string) $item->medicine_name,
            notifyNo: $item->notify_no,
            lotNo: $item->lot_no,
            lotName: $item->lot_name,
            medicineCode: $item->medicine_code,
            activeIngredient: $item->active_ingredient,
            concentration: $item->concentration,
            route: $item->route,
            dosageForm: $item->dosage_form,
            unit: $item->unit,
            drugGroup: $item->drug_group,
            packagingSpec: $item->packaging_spec,
            shelfLifeMonths: $item->shelf_life_months,
            registrationOrImportLicense: $item->registration_or_import_license,
            manufacturer: $item->manufacturer,
            country: $item->country,
            quantity: $item->quantity,
            pricePlan: $item->price_plan,
            winningPrice: $item->winning_price,
            amount: $item->amount,
            contractorCode: $item->contractor_code,
            contractorName: $item->contractor_name,
            investorCode: $item->investor_code,
            investorName: $item->investor_name,
            decisionNo: $item->decision_no,
            decisionDate: $item->decision_date,
            publishedAt: $item->published_at,
            contractNo: $item->contract_no,
            contractPeriod: $item->contract_period,
            contractPeriodUnit: $item->contract_period_unit,
            contractPeriodText: $item->contract_period_text,
            effectFramePeriod: $item->effect_frame_period,
            sourceReference: 'muasamcong_kqlcnt_award_items:'.$item->getKey(),
            sourceChannel: $item->source,
            syncSource: $item->sync_source,
            payloadHash: $item->fingerprint,
            observedAt: $item->published_at,
            lastVerifiedAt: $item->last_verified_at,
            isActive: (bool) $item->is_active,
        );
    }
}
