<?php

namespace Modules\Invoices\Services;

/**
 * Read-only comparison of two GDT POS response shapes.
 *
 * Keeps invoice IDs in memory only. Never returns raw IDs, opaque state,
 * CAPTCHA, cookies, tokens or invoice payloads in diagnostic output.
 */
final class GdtPosResponseComparison
{
    public static function compare(array $first, array $second): array
    {
        $a = self::inspect($first);
        $b = self::inspect($second);
        $firstIds = $a['ids'];
        $secondIds = $b['ids'];

        return [
            'first' => self::metadata($a),
            'second' => self::metadata($b),
            'shared_id_count' => count(array_intersect($firstIds, $secondIds)),
            'new_id_count' => count(array_diff($secondIds, $firstIds)),
            'missing_id_count' => count(array_diff($firstIds, $secondIds)),
            'union_unique_id_count' => count(array_unique(array_merge($firstIds, $secondIds))),
            'totals_match' => $a['total'] === $b['total'],
            'complete_unique_set' => $a['total'] === $b['total']
                && count(array_unique(array_merge($firstIds, $secondIds))) === $a['total'],
        ];
    }

    private static function inspect(array $response): array
    {
        $items = $response['datas'] ?? null;
        $total = $response['total'] ?? null;
        if (! is_array($items) || ! array_is_list($items) || ! is_int($total) || $total < 0) {
            throw new \InvalidArgumentException('Invalid GDT POS response shape.');
        }

        $ids = [];
        foreach ($items as $item) {
            if (! is_array($item) || ! is_string($item['id'] ?? null) || trim($item['id']) === '') {
                throw new \InvalidArgumentException('GDT POS response contains missing invoice ID.');
            }
            $ids[] = $item['id'];
        }
        if (count($items) > $total) {
            throw new \InvalidArgumentException('GDT POS response exceeds total.');
        }

        return [
            'ids' => $ids,
            'total' => $total,
            'count' => count($items),
            'unique_count' => count(array_unique($ids)),
            'has_state' => is_string($response['state'] ?? null) && $response['state'] !== '',
        ];
    }

    private static function metadata(array $page): array
    {
        return [
            'datas_count' => $page['count'],
            'unique_id_count' => $page['unique_count'],
            'total' => $page['total'],
            'has_state' => $page['has_state'],
        ];
    }
}
