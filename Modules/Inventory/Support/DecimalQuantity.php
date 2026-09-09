<?php

namespace Modules\Inventory\Support;

use InvalidArgumentException;

final class DecimalQuantity
{
    public const SCALE = 6;

    public static function normalize(string|int $value): string
    {
        return self::fromScaledInteger(self::toScaledInteger((string) $value));
    }

    public static function add(string|int $left, string|int $right): string
    {
        return self::fromScaledInteger(self::toScaledInteger((string) $left) + self::toScaledInteger((string) $right));
    }

    public static function negate(string|int $value): string
    {
        return self::fromScaledInteger(-self::toScaledInteger((string) $value));
    }

    public static function isNegative(string|int $value): bool
    {
        return self::toScaledInteger((string) $value) < 0;
    }

    public static function isPositive(string|int $value): bool
    {
        return self::toScaledInteger((string) $value) > 0;
    }

    public static function isZero(string|int $value): bool
    {
        return self::toScaledInteger((string) $value) === 0;
    }

    private static function toScaledInteger(string $value): int
    {
        $value = trim($value);

        if (! preg_match('/^([+-]?)(\d+)(?:\.(\d{1,6}))?$/', $value, $matches)) {
            throw new InvalidArgumentException('Inventory quantity must be a decimal with at most 6 fractional digits.');
        }

        $sign = ($matches[1] ?? '') === '-' ? -1 : 1;
        $whole = ltrim($matches[2], '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = str_pad($matches[3] ?? '', self::SCALE, '0');

        $maxWhole = intdiv(PHP_INT_MAX, 10 ** self::SCALE);
        if ((int) $whole > $maxWhole) {
            throw new InvalidArgumentException('Inventory quantity exceeds supported arithmetic range.');
        }

        return $sign * (((int) $whole * (10 ** self::SCALE)) + (int) $fraction);
    }

    private static function fromScaledInteger(int $value): string
    {
        $sign = $value < 0 ? '-' : '';
        $absolute = abs($value);
        $scale = 10 ** self::SCALE;
        $whole = intdiv($absolute, $scale);
        $fraction = str_pad((string) ($absolute % $scale), self::SCALE, '0', STR_PAD_LEFT);

        return $sign.$whole.'.'.$fraction;
    }
}
