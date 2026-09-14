<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Str;

class MedicineSkuGenerator
{
    public function __construct(private ?MedicineCatalogNormalizer $normalizer = null)
    {
        $this->normalizer ??= new MedicineCatalogNormalizer;
    }

    public function generate(array $attributes): array
    {
        $basis = $this->basis($attributes);
        $basisHash = hash('sha256', $basis);

        $brand = $this->token($attributes['name'] ?? $attributes['brand_name'] ?? 'MED', 8);
        $strength = $this->token($attributes['strength_text'] ?? $attributes['concentration'] ?? 'NA', 14);
        $presentation = $this->token($attributes['presentation_text'] ?? $attributes['packaging_specification'] ?? 'STD', 14);
        $suffix = strtoupper(substr(base_convert(substr($basisHash, 0, 10), 16, 36), 0, 4));

        return [
            'sku' => implode('-', array_filter([$brand, $strength, $presentation, $suffix])),
            'basis_hash' => $basisHash,
        ];
    }

    public function variantIdentity(array $attributes): string
    {
        return hash('sha256', $this->basis($attributes));
    }

    public function packageIdentity(string $variantIdentityKey, ?string $packaging): string
    {
        return hash('sha256', implode('|', [
            'package',
            $variantIdentityKey,
            $this->normalizer->identityPart($packaging),
        ]));
    }

    private function basis(array $attributes): string
    {
        return implode('|', [
            'variant-v1',
            $this->normalizer->registration($attributes['registration_number'] ?? null) ?? '-',
            $this->normalizer->identityPart($attributes['name'] ?? $attributes['brand_name'] ?? null),
            $this->normalizer->identityPart($attributes['strength_text'] ?? $attributes['concentration'] ?? null),
            $this->normalizer->identityPart($attributes['dosage_form'] ?? null),
            $this->normalizer->identityPart($attributes['presentation_text'] ?? null),
        ]);
    }

    private function token(?string $value, int $limit): string
    {
        $ascii = Str::upper(Str::ascii((string) $value));
        $ascii = str_replace([',', '.'], ['P', 'P'], $ascii);
        $ascii = preg_replace('/[^A-Z0-9]+/', '', $ascii) ?: 'NA';

        return substr($ascii, 0, $limit);
    }
}
