<?php

namespace Modules\Pharma\Services\OfficialFacilityImport;

use Modules\Partner\Models\Partner;
use Modules\Partner\Models\PartnerSourceReference;

class OfficialFacilityMatcher
{
    public function match(array $row, string $source): array
    {
        if (! empty($row['validation_errors'])) return ['classification' => 'INVALID'];

        if ($row['external_id'] ?? null) {
            $ref = PartnerSourceReference::query()->where('source', $source)->where('external_id', $row['external_id'])->first();
            if ($ref) return ['classification' => 'EXACT', 'partner_id' => $ref->partner_id, 'method' => 'source_external_id'];
        }

        if ($row['tax_code'] ?? null) {
            $partners = Partner::query()->where('tax_code', $row['tax_code'])->get();
            if ($partners->count() === 1) return ['classification' => 'EXACT', 'partner_id' => $partners->first()->id, 'method' => 'tax_code'];
            if ($partners->count() > 1) return ['classification' => 'CONFLICT', 'method' => 'tax_code'];
        }

        $nameProvince = Partner::query()->where('province_code', $row['province_code'])->get()->filter(fn (Partner $partner) => app(OfficialFacilityNormalizer::class)->identity($partner->name) === $row['normalized_name']);
        if ($nameProvince->count() === 1) return ['classification' => 'LIKELY_MATCH', 'partner_id' => $nameProvince->first()->id, 'method' => 'name_province'];
        if ($nameProvince->count() > 1) return ['classification' => 'CONFLICT', 'method' => 'name_province'];

        if ($row['normalized_address'] ?? null) {
            $nameAddress = Partner::query()->get()->filter(fn (Partner $partner) => app(OfficialFacilityNormalizer::class)->identity($partner->name) === $row['normalized_name'] && app(OfficialFacilityNormalizer::class)->identity($partner->address) === $row['normalized_address']);
            if ($nameAddress->count() === 1) return ['classification' => 'LIKELY_MATCH', 'partner_id' => $nameAddress->first()->id, 'method' => 'name_address'];
            if ($nameAddress->count() > 1) return ['classification' => 'CONFLICT', 'method' => 'name_address'];
        }

        return ['classification' => 'NEW', 'method' => 'none'];
    }
}
