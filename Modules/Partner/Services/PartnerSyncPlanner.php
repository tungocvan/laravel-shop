<?php

namespace Modules\Partner\Services;

use Modules\Partner\Data\ExternalPartnerData;
use Modules\Partner\Models\Partner;

class PartnerSyncPlanner
{
    public function plan(?Partner $partner, ExternalPartnerData $external): array
    {
        $fields = [
            'tax_code' => $external->taxCode,
            'name' => $external->name,
            'address' => $external->address,
        ];

        $plan = [];

        foreach ($fields as $field => $sourceValue) {
            $localValue = $partner?->{$field};
            $state = $this->state($partner, $localValue, $sourceValue);

            $plan[$field] = [
                'local' => $localValue,
                'source' => $sourceValue,
                'state' => $state,
                'selected' => in_array($state, ['new_value', 'missing_locally'], true),
            ];
        }

        return $plan;
    }

    private function state(?Partner $partner, mixed $local, mixed $source): string
    {
        if ($partner === null) {
            return $source === null || $source === '' ? 'source_missing' : 'new_value';
        }

        if ($source === null || $source === '') {
            return 'source_missing';
        }

        if ($local === null || $local === '') {
            return 'missing_locally';
        }

        if (trim((string) $local) === trim((string) $source)) {
            return 'same';
        }

        return 'local_differs';
    }
}
