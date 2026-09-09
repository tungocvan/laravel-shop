<?php

namespace Modules\Inventory\Support;

use InvalidArgumentException;

final class DecimalQuantity
{
    public const SCALE = 6;

    public static function normalize(string|int $value): string
    {
        [$negative, $digits] = self::toScaledDigits((string) $value);

        return self::fromScaledDigits($negative, $digits);
    }

    public static function add(string|int $left, string|int $right): string
    {
        [$leftNegative, $leftDigits] = self::toScaledDigits((string) $left);
        [$rightNegative, $rightDigits] = self::toScaledDigits((string) $right);

        if ($leftNegative === $rightNegative) {
            return self::fromScaledDigits($leftNegative, self::addDigits($leftDigits, $rightDigits));
        }

        $comparison = self::compareDigits($leftDigits, $rightDigits);
        if ($comparison === 0) {
            return '0.000000';
        }

        if ($comparison > 0) {
            return self::fromScaledDigits($leftNegative, self::subtractDigits($leftDigits, $rightDigits));
        }

        return self::fromScaledDigits($rightNegative, self::subtractDigits($rightDigits, $leftDigits));
    }

    public static function negate(string|int $value): string
    {
        [$negative, $digits] = self::toScaledDigits((string) $value);

        if (self::isZeroDigits($digits)) {
            return '0.000000';
        }

        return self::fromScaledDigits(! $negative, $digits);
    }

    public static function isNegative(string|int $value): bool
    {
        [$negative, $digits] = self::toScaledDigits((string) $value);

        return $negative && ! self::isZeroDigits($digits);
    }

    public static function isPositive(string|int $value): bool
    {
        [$negative, $digits] = self::toScaledDigits((string) $value);

        return ! $negative && ! self::isZeroDigits($digits);
    }

    public static function isZero(string|int $value): bool
    {
        [, $digits] = self::toScaledDigits((string) $value);

        return self::isZeroDigits($digits);
    }

    public static function hasFractionalPart(string|int $value): bool
    {
        $normalized = self::normalize($value);

        return substr($normalized, -(self::SCALE)) !== str_repeat('0', self::SCALE);
    }

    /** @return array{0: bool, 1: string} */
    private static function toScaledDigits(string $value): array
    {
        $value = trim($value);

        if (! preg_match('/^([+-]?)(\d+)(?:\.(\d{1,6}))?$/', $value, $matches)) {
            throw new InvalidArgumentException('Inventory quantity must be a decimal with at most 6 fractional digits.');
        }

        $negative = ($matches[1] ?? '') === '-';
        $whole = ltrim($matches[2], '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = str_pad($matches[3] ?? '', self::SCALE, '0');
        $digits = ltrim($whole.$fraction, '0');
        $digits = $digits === '' ? '0' : $digits;

        if (strlen($digits) > 20) {
            throw new InvalidArgumentException('Inventory quantity exceeds decimal(20,6) precision.');
        }

        return [$negative, $digits];
    }

    private static function fromScaledDigits(bool $negative, string $digits): string
    {
        $digits = ltrim($digits, '0');
        $digits = $digits === '' ? '0' : $digits;
        $digits = str_pad($digits, self::SCALE + 1, '0', STR_PAD_LEFT);
        $whole = substr($digits, 0, -self::SCALE);
        $fraction = substr($digits, -self::SCALE);
        $sign = $negative && ! self::isZeroDigits($digits) ? '-' : '';

        return $sign.$whole.'.'.$fraction;
    }

    private static function compareDigits(string $left, string $right): int
    {
        $left = ltrim($left, '0') ?: '0';
        $right = ltrim($right, '0') ?: '0';

        if (strlen($left) !== strlen($right)) {
            return strlen($left) <=> strlen($right);
        }

        return strcmp($left, $right) <=> 0;
    }

    private static function addDigits(string $left, string $right): string
    {
        $left = strrev($left);
        $right = strrev($right);
        $length = max(strlen($left), strlen($right));
        $carry = 0;
        $result = '';

        for ($index = 0; $index < $length; $index++) {
            $sum = (int) ($left[$index] ?? '0') + (int) ($right[$index] ?? '0') + $carry;
            $result .= (string) ($sum % 10);
            $carry = intdiv($sum, 10);
        }

        if ($carry > 0) {
            $result .= (string) $carry;
        }

        $digits = strrev($result);
        if (strlen($digits) > 20) {
            throw new InvalidArgumentException('Inventory quantity arithmetic exceeds decimal(20,6) precision.');
        }

        return $digits;
    }

    private static function subtractDigits(string $larger, string $smaller): string
    {
        $larger = strrev($larger);
        $smaller = strrev($smaller);
        $borrow = 0;
        $result = '';

        for ($index = 0; $index < strlen($larger); $index++) {
            $digit = (int) $larger[$index] - $borrow - (int) ($smaller[$index] ?? '0');
            if ($digit < 0) {
                $digit += 10;
                $borrow = 1;
            } else {
                $borrow = 0;
            }
            $result .= (string) $digit;
        }

        $digits = ltrim(strrev($result), '0');

        return $digits === '' ? '0' : $digits;
    }

    private static function isZeroDigits(string $digits): bool
    {
        return trim($digits, '0') === '';
    }
}
