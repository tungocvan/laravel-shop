<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Str;

class MedicineCatalogNormalizer
{
    public function text(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        if ($value === '') {
            return null;
        }

        return Str::lower(Str::ascii($value));
    }

    public function compact(?string $value): ?string
    {
        $normalized = $this->text($value);

        if ($normalized === null) {
            return null;
        }

        return preg_replace('/[^a-z0-9]+/', '', $normalized) ?: null;
    }

    public function registrationPrimary(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $parts = preg_split('/[\r\n;,]+/u', trim($value)) ?: [];

        foreach ($parts as $part) {
            $candidate = trim($part, " \t\n\r\0\x0B()[]");
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }

    public function registration(?string $value): ?string
    {
        return $this->compact($this->registrationPrimary($value));
    }

    public function identityPart(?string $value): string
    {
        return $this->compact($value) ?? '-';
    }
}
