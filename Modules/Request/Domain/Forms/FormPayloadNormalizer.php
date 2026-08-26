<?php

namespace Modules\Request\Domain\Forms;

use DateTimeImmutable;
use DateTimeZone;

final class FormPayloadNormalizer
{
    public function normalizeValue(string $type, mixed $value, array $field): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'text', 'textarea', 'select' => is_string($value) ? trim($value) : $value,
            'integer' => $this->integer($value, $field),
            'decimal' => is_int($value) || is_string($value) ? $this->decimal((string) $value) : $value,
            'currency' => $this->currency($value),
            'date' => is_string($value) ? trim($value) : $value,
            'datetime' => $this->dateTime($value),
            'boolean' => is_bool($value) ? $value : $value,
            'multiselect', 'attachment' => is_array($value) ? array_values(array_unique($value, SORT_REGULAR)) : $value,
            'user', 'role' => filter_var($value, FILTER_VALIDATE_INT) !== false ? (int) $value : $value,
            default => $value,
        };
    }

    private function integer(mixed $value, array $field): mixed
    {
        if (($field['display_format'] ?? null) === 'grouped_integer' && is_string($value)) {
            $value = trim($value);
            if (preg_match('/^-?\d{1,3}(?:\.\d{3})+$/', $value) === 1) {
                $value = str_replace('.', '', $value);
            }
        }

        return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int) $value : $value;
    }

    private function decimal(string $value): string
    {
        $value = trim($value);
        if (! preg_match('/^-?\d+(?:\.\d+)?$/', $value)) {
            return $value;
        }

        $negative = str_starts_with($value, '-');
        $unsigned = ltrim($value, '-');
        [$whole, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');
        $whole = ltrim($whole, '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = rtrim($fraction, '0');

        return ($negative && ($whole !== '0' || $fraction !== '') ? '-' : '').$whole.($fraction !== '' ? '.'.$fraction : '');
    }

    private function currency(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        return [
            'amount' => $this->decimal((string) ($value['amount'] ?? '')),
            'currency' => strtoupper(trim((string) ($value['currency'] ?? ''))),
        ];
    }

    private function dateTime(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        try {
            return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
        } catch (\Throwable) {
            return $value;
        }
    }
}
