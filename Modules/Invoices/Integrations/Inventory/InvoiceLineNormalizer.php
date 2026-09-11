<?php

namespace Modules\Invoices\Integrations\Inventory;

use Carbon\CarbonImmutable;
use Modules\Invoices\Support\GdtInvoiceLineMetadata;

final class InvoiceLineNormalizer
{
    public const VERSION = 'deterministic-v4';

    public function version(): string
    {
        return self::VERSION;
    }

    public function normalize(array $line): array
    {
        $raw = (string) ($line['ten'] ?? '');
        $working = preg_replace('/\s+/u', ' ', trim($raw)) ?: trim($raw);

        $textLot = $this->match($working, '/(?:S[ỐO]\s*L[ÔO]|L[ÔO]|LOT)\s*[:\-]?\s*([A-Z0-9.\/-]+)/iu');
        $textExpiry = $this->dateMatch($working, '/(?:HSD|HẠN\s*DÙNG|HAN\s*DUNG|HD|EXP)\s*[:\-]?\s*(\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4}|\d{1,2}[\/\-.]\d{4})/iu');
        $textManufacture = $this->dateMatch($working, '/(?:NGÀY\s*(?:SX|SẢN\s*XUẤT)|MFG|NSX)\s*[:\-]?\s*(\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4})/iu');
        $lot = GdtInvoiceLineMetadata::lotNumber($line) ?? $textLot;
        $expiry = GdtInvoiceLineMetadata::expiryDate($line) ?? $textExpiry;
        $manufacture = GdtInvoiceLineMetadata::manufactureDate($line) ?? $textManufacture;
        $strength = $this->match($working, '/\b(\d+(?:[.,]\d+)?\s*(?:MG|G|MCG|ML|IU|UI|%))\b/iu');
        $package = $this->packageSpec($working);
        $manufacturer = $this->manufacturer($working);

        $name = $working;
        $name = preg_replace('/(?:S[ỐO]\s*L[ÔO]|L[ÔO]|LOT)\s*[:\-]?\s*[A-Z0-9.\/-]+/iu', ' ', $name) ?: $name;
        $name = preg_replace('/(?:HSD|HẠN\s*DÙNG|HAN\s*DUNG|HD|EXP)\s*[:\-]?\s*\d{1,2}[\/\-.]\d{1,2}[\/\-.]\d{2,4}/iu', ' ', $name) ?: $name;
        $name = preg_replace('/(?:HSD|HẠN\s*DÙNG|HAN\s*DUNG|HD|EXP)\s*[:\-]?\s*\d{1,2}[\/\-.]\d{4}/iu', ' ', $name) ?: $name;
        $name = preg_replace('/(?:NGÀY\s*(?:SX|SẢN\s*XUẤT)|MFG|NSX)\s*[:\-]?\s*[^,;]+/iu', ' ', $name) ?: $name;
        $name = trim(preg_replace('/\s*[,;]\s*[,;]+/u', '; ', $name) ?: $name);
        $name = trim(preg_replace('/\s+/u', ' ', trim($name, " -;,")) ?: $name);

        return [
            'normalized_name' => $name !== '' ? $name : trim($raw),
            'strength' => $strength,
            'dosage_form' => $this->dosageForm($working),
            'package_spec' => $package,
            'manufacturer' => $manufacturer,
            'normalized_uom' => $this->normalizeUom($line['dvtinh'] ?? null),
            'lot_number' => $lot,
            'manufacture_date' => $manufacture,
            'expiry_date' => $expiry,
            'normalization_status' => 'NORMALIZED',
            'normalization_meta' => ['parser' => self::VERSION],
        ];
    }

    private function packageSpec(string $value): ?string
    {
        $patterns = [
            '/\(((?:HỘP|HOP|BOX)\s+\d+\s*(?:VỈ|VI|LỌ|LO|ỐNG|ONG|GÓI|GOI|VIÊN|VIEN)(?:\s*(?:X|×)\s*\d+\s*(?:VIÊN|VIEN|VỈ|VI|LỌ|LO|ỐNG|ONG|GÓI|GOI|ML))?)\)/iu',
            '/\b((?:HỘP|HOP|BOX)\s+\d+\s*(?:VỈ|VI|LỌ|LO|ỐNG|ONG|GÓI|GOI|VIÊN|VIEN)(?:\s*(?:X|×)\s*\d+\s*(?:VIÊN|VIEN|VỈ|VI|LỌ|LO|ỐNG|ONG|GÓI|GOI|ML))?)\b/iu',
            '/\b((?:CHAI|LỌ|LO|TÚI|TUI)\s+\d+(?:[.,]\d+)?\s*(?:ML|G|MG)?)\b/iu',
        ];

        foreach ($patterns as $pattern) {
            $match = $this->match($value, $pattern);
            if ($match !== null) {
                return $match;
            }
        }

        return null;
    }

    private function manufacturer(string $value): ?string
    {
        $candidate = $this->match($value, '/(?:NHÀ\s*SẢN\s*XUẤT|NSX)\s*[:\-]?\s*([^,;]+)/iu');
        if ($candidate === null || $this->dateValue($candidate) !== null) {
            return null;
        }

        return trim($candidate);
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
        foreach (['bột pha tiêm', 'dung dịch tiêm', 'dung dịch', 'hỗn dịch', 'viên nén', 'viên nang', 'viên bao phim', 'siro', 'kem', 'gel', 'thuốc mỡ'] as $form) {
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
