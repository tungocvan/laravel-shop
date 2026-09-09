<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class InvoiceLineStockClassifier
{
    public function classify(string $description, ?string $uom = null, ?array $metadata = null): array
    {
        $metadata ??= [];
        $raw = is_array($metadata['raw_gdt_line'] ?? null) ? $metadata['raw_gdt_line'] : [];

        $categoryCode = $this->firstString($raw, [
            'InventoryItemCategoryCode',
            'inventoryItemCategoryCode',
            'inventory_item_category_code',
            'mhdvu',
        ]);
        $categoryName = $this->firstString($raw, [
            'InventoryItemCategoryName',
            'inventoryItemCategoryName',
            'inventory_item_category_name',
            'thhdvu',
        ]);

        $normalizedDescription = $this->key($description);
        $normalizedCategory = $this->key((string) $categoryName);
        $normalizedCode = mb_strtoupper(trim((string) $categoryCode));
        $normalizedUom = $this->key((string) $uom);

        $nonStockEvidence = $this->nonStockEvidence($normalizedDescription, $normalizedCategory, $normalizedCode);
        if ($nonStockEvidence !== null) {
            return $this->result('NON_STOCK', $nonStockEvidence['reason'], $nonStockEvidence['evidence']);
        }

        if ($normalizedCode === 'HH' || in_array($normalizedCategory, ['hang hoa', 'goods'], true)) {
            return $this->result('STOCK', 'gdt_goods_category', array_filter([
                'category_code' => $categoryCode,
                'category_name' => $categoryName,
                'uom' => $uom,
            ], fn (mixed $value): bool => $value !== null && $value !== ''));
        }

        if ($normalizedUom !== '' && in_array($normalizedUom, [
            'hop', 'chai', 'lo', 'vien', 'goi', 'ong', 'tuyp', 'kg', 'g', 'mg', 'ml', 'lit', 'cai', 'bo', 'thung', 'kien',
        ], true)) {
            return $this->result('STOCK', 'physical_goods_uom', ['uom' => $uom]);
        }

        return $this->result('UNRESOLVED', 'insufficient_deterministic_evidence', array_filter([
            'category_code' => $categoryCode,
            'category_name' => $categoryName,
            'uom' => $uom,
        ], fn (mixed $value): bool => $value !== null && $value !== ''));
    }

    private function nonStockEvidence(string $description, string $category, string $categoryCode): ?array
    {
        $serviceCategory = in_array($categoryCode, ['DV', 'SERVICE'], true)
            || str_contains($category, 'dich vu')
            || str_contains($category, 'service');

        if ($serviceCategory) {
            return [
                'reason' => 'gdt_service_category',
                'evidence' => ['category_code' => $categoryCode, 'category_name' => $category],
            ];
        }

        $markers = [
            'thanh toan lai' => 'interest_payment',
            'tien lai' => 'interest_charge',
            'lai vay' => 'interest_charge',
            'lai suat' => 'interest_charge',
            'phi dich vu' => 'service_fee',
            'phi van chuyen' => 'freight_fee',
            'cuoc van chuyen' => 'freight_fee',
            'cuoc dich vu' => 'service_fee',
            'phi ngan hang' => 'bank_fee',
            'phi giao dich' => 'transaction_fee',
            'chi phi dich vu' => 'service_expense',
        ];

        foreach ($markers as $marker => $reason) {
            if (str_contains($description, $marker)) {
                return [
                    'reason' => $reason,
                    'evidence' => ['description_marker' => $marker],
                ];
            }
        }

        return null;
    }

    private function firstString(array $values, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = Arr::get($values, $key);
            if ($value === null) {
                continue;
            }

            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function result(string $classification, string $reason, array $evidence): array
    {
        return [
            'classification' => $classification,
            'reason' => $reason,
            'evidence' => $evidence,
            'classifier' => 'deterministic-v1',
        ];
    }

    private function key(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }
}
