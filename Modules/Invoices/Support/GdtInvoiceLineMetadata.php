<?php

namespace Modules\Invoices\Support;

use Carbon\CarbonImmutable;

final class GdtInvoiceLineMetadata
{
    public static function lotNumber(array $line): ?string
    {
        return self::structuredValue($line, ['LotNo', 'LotNumber'])
            ?? self::nullable($line['solo'] ?? $line['lot'] ?? null);
    }

    public static function expiryDate(array $line): ?string
    {
        return self::dateValue(
            self::structuredValue($line, ['ExpiryDate', 'ExpDate'])
                ?? ($line['hsd'] ?? $line['expiry_date'] ?? null),
        );
    }

    public static function manufactureDate(array $line): ?string
    {
        return self::dateValue(
            self::structuredValue($line, ['ManufactureDate', 'MfgDate', 'ProductionDate'])
                ?? ($line['nsx'] ?? $line['manufacture_date'] ?? null),
        );
    }

    public static function structuredValue(array $line, array $fieldNames): ?string
    {
        $metadata = $line['ttkhac'] ?? null;
        if (! is_array($metadata)) {
            return null;
        }

        $wanted = array_map(
            static fn (string $field): string => mb_strtolower(trim($field)),
            $fieldNames,
        );

        foreach ($metadata as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $field = mb_strtolower(trim((string) ($entry['ttruong'] ?? '')));
            if ($field === '' || ! in_array($field, $wanted, true)) {
                continue;
            }

            $value = self::nullable($entry['dlieu'] ?? null);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    public static function dateValue(mixed $value): ?string
    {
        $value = self::nullable($value);
        if ($value === null) {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'm/Y', 'm-Y', 'm.Y'] as $format) {
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

    private static function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
