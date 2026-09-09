<?php

namespace Modules\Invoices\Integrations\Inventory;

use Carbon\CarbonImmutable;

final class InvoiceLineNormalizer
{
    public function normalize(array $line): array
    {
        $raw = trim((string) ($line['ten'] ?? ''));
        $working = preg_replace('/\s+/u', ' ', $raw) ?: $raw;

        $lot = $this->match($working, '/(?:S[ỐO]\s*L[ÔO]|LOT)\s*[:\-]?\s*([A-Z0-9.\/-]+)/iu');
        $expiry = $this->dateMatch($working, '/(?:HSD|EXP)\s*[:\-]?\s*(\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4}|\d{1,2}[\/\-.]\d{4})/iu');
        $manufacture = $this->dateMatch($working, '/(?:NSX|MFG)\s*[:\-]?\s*(\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4})/iu');
        $strength = $this->match($working, '/\b(\d+(?:[.,]\d+)?\s*(?:MG|G|MCG|ML|IU|UI|%))\b/iu');
        $package = $this->match($working, '/\b((?:HỘP|HOP|BOX|CHAI|LỌ|LO|TÚI|TUI)\s+[^,;]*(?:X|×)\s*\d+\s*(?:VIÊN|VIEN|VỈ|VI|LỌ|LO|ỐNG|ONG|GÓI|GOI|ML)?)\b/iu');

        $name = $working;
        foreach ([$lot, $strength, $package] as $token) {
            if ($token !== null) {
                $name = str_ireplace($token, ' ', $name);
            }
        }
        $name = preg_replace('/(?:S[ỐO]\s*L[ÔO]|LOT|HSD|EXP|NSX|MFG)\s*[:\-]?\s*[^,;]+/iu', ' ', $name) ?: $name;
        $name = trim(preg_replace('/\s+/u', ' ', trim($name, ' -;,')) ?: $name);

        return [
            'normalized_name' => $name !== '' ? $name : $raw,
            'strength' => $strength,
            'dosage_form' => $this->dosageForm($working),
            'package_spec' => $package,
            'manufacturer' => null,
            'normalized_uom' => $this->normalizeUom($line['dvtinh'] ?? null),
            'lot_number' => $lot ?? $this->nullable($line['solo'] ?? $line['lot'] ?? null),
            'manufacture_date' => $manufacture ?? $this->dateValue($line['nsx'] ?? null),
            'expiry_date' => $expiry ?? $this->dateValue($line['hsd'] ?? $line['expiry_date'] ?? null),
            'normalization_status' => 'NORMALIZED',
            'normalization_meta' => ['parser' => 'deterministic-v1'],
        ];
    }

    private function match(string $value, string $pattern): ?string
    {
        return preg_match($pattern, $value, $matches) === 1 ? trim($matches[1]) : null;
    }

    private function dateMatch(string $value, string $pattern): ?string
    {
        return preg_match($pattern, $value, $matches) === 1 ? $this->dateValue($matches[1]) : null;
    }

    private function dateValue(mixed $value): ?string
    {
        $value = $this->nullable($value);
        if ($value === null) {
            return null;
        }

        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y', 'm/Y', 'm-Y', 'm.Y'] as $format) {
            try {
                $date = CarbonImmutable::createFromFormat('!'.$format, $value);
                if ($date !== false) {
                    return $date->toDateString();
                }
            } catch (\Throwable) {
            }
        }

        return null;
    }

    private function dosageForm(string $value): ?string
    {
        foreach (['viên nén', 'viên nang', 'viên', 'ống', 'lọ', 'chai', 'gói'] as $form) {
            if (mb_stripos($value, $form) !== false) {
                return $form;
            }
        }

        return null;
    }

    private function normalizeUom(mixed $value): ?string
    {
        $value = mb_strtolower((string) $value);
        $map = ['hop' => 'hộp', 'hộp' => 'hộp', 'chai' => 'chai', 'lo' => 'lọ', 'lọ' => 'lọ', 'vien' => 'viên', 'viên' => 'viên'];

        return $map[trim($value)] ?? $this->nullable($value);
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
