<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Pharma\Data\DrugAwardProjectionData;
use Modules\Pharma\Data\MedicineResolution;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\DrugBidAwardSource;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\MedicineSource;

class DrugAwardProjectionService
{
    public function __construct(private readonly MedicineIdentityResolver $medicineResolver) {}

    public function project(DrugAwardProjectionData $source): DrugBidAward
    {
        return DB::transaction(function () use ($source): DrugBidAward {
            $resolution = $this->medicineResolver->resolve($source);
            $medicine = $resolution->medicine ?? $this->createProvisionalMedicineWhenSafe($source);

            if ($medicine && $resolution->medicine === null) {
                $resolution = new MedicineResolution(
                    $medicine,
                    DrugBidAward::MATCH_PROVISIONAL,
                    'provisional_source_profile',
                    80,
                );
            }

            $sourceLink = DrugBidAwardSource::query()->where([
                'source_system' => $source->sourceSystem,
                'source_record_type' => $source->sourceRecordType,
                'source_record_key' => $source->sourceRecordKey,
            ])->first();

            $canonicalIdentity = $this->awardIdentity($source, $medicine);
            $award = $sourceLink?->award()
                ->first()
                ?? DrugBidAward::query()->where('canonical_identity_key', $canonicalIdentity)->first()
                ?? new DrugBidAward;

            $attributes = $this->awardAttributes($source, $medicine?->id, $resolution->status, $canonicalIdentity);
            $award->fill($this->withoutNullOverwrites($award, $attributes));
            $award->save();

            DrugBidAwardSource::query()->updateOrCreate([
                'source_system' => $source->sourceSystem,
                'source_record_type' => $source->sourceRecordType,
                'source_record_key' => $source->sourceRecordKey,
            ], [
                'drug_bid_award_id' => $award->id,
                'source_reference' => $source->sourceReference,
                'source_channel' => $source->sourceChannel,
                'sync_source' => $source->syncSource,
                'source_payload_hash' => $source->payloadHash,
                'source_observed_at' => $source->observedAt,
                'synced_at' => now(),
                'last_verified_at' => $source->lastVerifiedAt,
                'is_active' => $source->isActive,
                'metadata' => [
                    'medicine_match_status' => $resolution->status,
                    'medicine_match_method' => $resolution->matchMethod,
                    'medicine_match_confidence' => $resolution->confidence,
                ],
            ]);

            if ($medicine) {
                $this->syncMedicineLineage($medicine, $source, $resolution->matchMethod, $resolution->confidence);
            }

            return $award->refresh()->load(['medicine', 'sources']);
        });
    }

    private function createProvisionalMedicineWhenSafe(DrugAwardProjectionData $source): ?Medicine
    {
        $signals = array_filter([
            $source->medicineName,
            $source->activeIngredient,
            $source->concentration,
            $source->dosageForm,
            $source->manufacturer,
        ], fn ($value) => $value !== null && trim((string) $value) !== '');

        if (count($signals) < 4) {
            return null;
        }

        $attributes = [
            'name' => $source->medicineName,
            'active_ingredients' => $source->activeIngredient,
            'concentration' => $source->concentration,
            'dosage_form' => $source->dosageForm,
            'route_of_administration' => $source->route,
            'unit' => $source->unit,
            'packaging_specification' => $source->packagingSpec,
            'shelf_life_months' => $source->shelfLifeMonths,
            'manufacturing_company' => $source->manufacturer,
            'manufacturing_country' => $source->country,
            'identity_status' => Medicine::IDENTITY_PROVISIONAL,
            'profile_status' => Medicine::PROFILE_NEEDS_REVIEW,
        ];
        $attributes['canonical_identity_key'] = $this->medicineResolver->canonicalMedicineIdentity($attributes);

        if (!$attributes['canonical_identity_key']) {
            return null;
        }

        return Medicine::query()->firstOrCreate(
            ['canonical_identity_key' => $attributes['canonical_identity_key']],
            $attributes,
        );
    }

    private function syncMedicineLineage(
        Medicine $medicine,
        DrugAwardProjectionData $source,
        ?string $matchMethod,
        int $confidence,
    ): void {
        MedicineSource::query()->updateOrCreate([
            'source_system' => $source->sourceSystem,
            'source_record_type' => $source->sourceRecordType.'.medicine',
            'source_record_key' => $source->sourceRecordKey,
        ], [
            'medicine_id' => $medicine->id,
            'source_reference' => $source->sourceReference,
            'payload_hash' => $source->payloadHash,
            'observed_at' => $source->observedAt,
            'synced_at' => now(),
            'last_verified_at' => $source->lastVerifiedAt,
            'is_active' => $source->isActive,
            'match_method' => $matchMethod,
            'match_confidence' => $confidence,
            'metadata' => ['source_channel' => $source->sourceChannel],
        ]);
    }

    private function awardAttributes(
        DrugAwardProjectionData $source,
        ?int $medicineId,
        string $matchStatus,
        string $canonicalIdentity,
    ): array {
        $winningPrice = $source->winningPrice;
        $amount = $source->amount;

        if ($amount === null && is_numeric($source->quantity) && is_numeric($winningPrice)) {
            $amount = (string) ((float) $source->quantity * (float) $winningPrice);
        }

        return [
            'canonical_identity_key' => $canonicalIdentity,
            'medicine_id' => $medicineId,
            'medicine_code' => $source->medicineCode,
            'medicine_match_status' => $matchStatus,
            'medicine_name' => $source->medicineName,
            'active_ingredient' => $source->activeIngredient,
            'concentration' => $source->concentration,
            'route' => $source->route,
            'dosage_form' => $source->dosageForm,
            'unit' => $source->unit,
            'drug_group' => $source->drugGroup,
            'packaging_specification' => $source->packagingSpec,
            'shelf_life_months' => $source->shelfLifeMonths,
            'registration_or_import_license' => $source->registrationOrImportLicense,
            'manufacturer' => $source->manufacturer,
            'country' => $source->country,
            'quantity' => $source->quantity,
            'price_plan' => $source->pricePlan,
            'winning_price' => $winningPrice,
            'unit_price' => $winningPrice,
            'amount' => $amount,
            'bidding_notice_code' => $source->notifyNo,
            'lot_no' => $source->lotNo,
            'lot_name' => $source->lotName,
            'contractor_code' => $source->contractorCode,
            'winning_company_name' => $source->contractorName,
            'investor_code' => $source->investorCode,
            'investor_name' => $source->investorName,
            'decision_number' => $source->decisionNo,
            'decision_date' => $source->decisionDate,
            'published_at' => $source->publishedAt,
            'contract_no' => $source->contractNo,
            'contract_period' => $source->contractPeriod,
            'contract_period_unit' => $source->contractPeriodUnit,
            'contract_period_text' => $source->contractPeriodText,
            'effect_frame_period' => $source->effectFramePeriod,
            'contract_duration_months' => $source->contractPeriodUnit === 'M' ? $source->contractPeriod : null,
            'is_active' => $source->isActive,
            'source_type' => $source->sourceSystem,
            'source_synced_at' => now(),
            'source_payload_hash' => $source->payloadHash,
        ];
    }

    private function withoutNullOverwrites(DrugBidAward $award, array $attributes): array
    {
        return Arr::where($attributes, fn ($value, $key) => $value !== null || $award->getAttribute($key) === null);
    }

    private function awardIdentity(DrugAwardProjectionData $source, ?Medicine $medicine): string
    {
        $drugIdentity = $source->medicineCode
            ?? ($medicine?->canonical_identity_key ?: $this->normalize(implode('|', array_filter([
                $source->medicineName,
                $source->activeIngredient,
                $source->concentration,
                $source->manufacturer,
            ]))));

        return hash('sha256', implode('|', [
            $this->normalize($source->notifyNo),
            $this->normalize($source->contractorCode ?? $source->contractorName),
            $this->normalize($source->lotNo ?? $source->lotName),
            $drugIdentity ?: '-',
        ]));
    }

    private function normalize(?string $value): string
    {
        return Str::lower(preg_replace('/\s+/u', ' ', Str::ascii(trim((string) $value))));
    }
}
