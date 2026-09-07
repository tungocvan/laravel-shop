<?php

namespace Modules\Partner\Services;

use Illuminate\Support\Str;
use Modules\Partner\Data\ExternalPartnerData;
use Modules\Partner\Models\Partner;
use Modules\Partner\Models\PartnerSourceReference;

class PartnerMatcher
{
    public function match(ExternalPartnerData $external): array
    {
        $reference = PartnerSourceReference::query()
            ->where('source', $external->source)
            ->where('external_id', $external->externalId)
            ->first();

        if ($reference) {
            return ['partner' => $reference->partner, 'reason' => 'source_reference', 'score' => 100];
        }

        if ($external->taxCode) {
            $partner = Partner::where('tax_code', $external->taxCode)->first();
            if ($partner) {
                return ['partner' => $partner, 'reason' => 'tax_code', 'score' => 95];
            }
        }

        if ($external->name) {
            $normalized = $this->normalizeName($external->name);
            $partner = Partner::query()->get()->first(
                fn (Partner $partner) => $this->normalizeName($partner->name) === $normalized
            );

            if ($partner) {
                return ['partner' => $partner, 'reason' => 'normalized_name', 'score' => 60];
            }
        }

        return ['partner' => null, 'reason' => 'none', 'score' => 0];
    }

    private function normalizeName(string $value): string
    {
        return Str::of($value)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->value();
    }
}
