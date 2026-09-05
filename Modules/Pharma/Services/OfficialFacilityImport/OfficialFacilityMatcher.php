<?php

namespace Modules\Pharma\Services\OfficialFacilityImport;

use Modules\Partner\Models\Partner;
use Modules\Partner\Models\PartnerSourceReference;

class OfficialFacilityMatcher
{
    public function __construct(private readonly OfficialFacilityNormalizer $normalizer) {}

    public function match(array $row, string $source): array
    {
        if (! empty($row['validation_errors'])) {
            return ['classification' => 'INVALID', 'method' => 'validation'];
        }

        if ($row['external_id'] ?? null) {
            $reference = PartnerSourceReference::query()
                ->where('source', $source)
                ->where('external_id', $row['external_id'])
                ->first();

            if ($reference) {
                return ['classification' => 'EXACT', 'partner_id' => $reference->partner_id, 'method' => 'source_external_id'];
            }
        }

        if ($row['tax_code'] ?? null) {
            $partners = Partner::query()->where('tax_code', $row['tax_code'])->get();
            if ($partners->count() === 1) {
                $partner = $partners->first();
                if ($partner->legal_type !== 'hospital') {
                    return ['classification' => 'CONFLICT', 'partner_id' => $partner->id, 'method' => 'tax_code_non_hospital'];
                }

                return ['classification' => 'EXACT', 'partner_id' => $partner->id, 'method' => 'tax_code'];
            }
            if ($partners->count() > 1) {
                return ['classification' => 'CONFLICT', 'method' => 'tax_code'];
            }
        }

        if (($row['province_code'] ?? null) && ($row['normalized_name'] ?? null)) {
            $nameProvince = Partner::query()
                ->where('legal_type', 'hospital')
                ->where('province_code', $row['province_code'])
                ->get()
                ->filter(fn (Partner $partner) => $this->normalizer->identity($partner->name) === $row['normalized_name']);

            if ($nameProvince->count() === 1) {
                return ['classification' => 'LIKELY_MATCH', 'partner_id' => $nameProvince->first()->id, 'method' => 'name_province'];
            }
            if ($nameProvince->count() > 1) {
                return ['classification' => 'CONFLICT', 'method' => 'name_province'];
            }
        }

        if (($row['normalized_address'] ?? null) && ($row['normalized_name'] ?? null)) {
            $nameAddress = Partner::query()
                ->where('legal_type', 'hospital')
                ->get()
                ->filter(fn (Partner $partner) => $this->normalizer->identity($partner->name) === $row['normalized_name']
                    && $this->normalizer->identity($partner->address) === $row['normalized_address']);

            if ($nameAddress->count() === 1) {
                return ['classification' => 'LIKELY_MATCH', 'partner_id' => $nameAddress->first()->id, 'method' => 'name_address'];
            }
            if ($nameAddress->count() > 1) {
                return ['classification' => 'CONFLICT', 'method' => 'name_address'];
            }
        }

        return ['classification' => 'NEW', 'method' => 'none'];
    }
}
