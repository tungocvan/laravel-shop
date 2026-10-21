<?php

namespace Modules\Finance\Support;

final class VietnameseMoney
{
    public static function spell(string $amount): string
    {
        if (! preg_match('/^\\d{1,16}(?:\\.\\d{1,2})?$/', $amount)) {
            throw new \InvalidArgumentException('Invalid VND amount.');
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');
        $whole = ltrim($whole, '0') ?: '0';
        $fraction = (int) str_pad($fraction, 2, '0');

        $groups = ['','nghìn','triệu','tỷ','nghìn tỷ','triệu tỷ'];
        $parts = [];
        $chunks = [];
        while ($whole !== '') {
            array_unshift($chunks, (int) substr($whole, -3));
            $whole = substr($whole, 0, -3);
        }

        foreach ($chunks as $i => $chunk) {
            if ($chunk === 0) {
                continue;
            }
            $position = count($chunks) - $i - 1;
            $parts[] = self::triplet($chunk, $i > 0 && $chunk < 100);
            if ($groups[$position] !== '') {
                $parts[] = $groups[$position];
            }
        }

        $words = implode(' ', $parts) ?: 'không';
        if ($fraction > 0) {
            return ucfirst($words).' đồng và '.self::triplet($fraction, false).' xu.';
        }

        return ucfirst($words).' đồng chẵn.';
    }

    private static function triplet(int $number, bool $fullHundreds): string
    {
        $digits = ['không','một','hai','ba','bốn','năm','sáu','bảy','tám','chín'];
        $hundreds = intdiv($number, 100);
        $tens = intdiv($number % 100, 10);
        $ones = $number % 10;
        $parts = [];

        if ($hundreds > 0 || $fullHundreds) {
            $parts[] = $digits[$hundreds].' trăm';
        }
        if ($tens > 1) {
            $parts[] = $digits[$tens].' mươi';
            if ($ones === 1) {
                $parts[] = 'mốt';
            } elseif ($ones === 5) {
                $parts[] = 'lăm';
            } elseif ($ones > 0) {
                $parts[] = $digits[$ones];
            }
        } elseif ($tens === 1) {
            $parts[] = 'mười';
            if ($ones === 5) {
                $parts[] = 'lăm';
            } elseif ($ones > 0) {
                $parts[] = $digits[$ones];
            }
        } elseif ($ones > 0) {
            if ($hundreds > 0 || $fullHundreds) {
                $parts[] = 'lẻ';
            }
            $parts[] = $digits[$ones];
        }

        return implode(' ', $parts);
    }
}
