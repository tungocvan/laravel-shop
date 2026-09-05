<?php

namespace Modules\Pharma\Services\OfficialFacilityImport;

use Illuminate\Support\Str;

class OfficialFacilityNormalizer
{
    public function text(?string $value): ?string
    {
        if ($value === null) return null;
        $value = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);
        return $value === '' ? null : Str::limit($value, 1000, '');
    }

    public function identity(?string $value): ?string
    {
        $value = $this->text($value);
        if ($value === null) return null;
        return Str::lower(Str::ascii($value));
    }

    public function taxCode(?string $value): ?string
    {
        $value = preg_replace('/[^0-9-]/', '', (string) $value);
        return $value === '' ? null : $value;
    }
}
