<?php

namespace Modules\Product\Imports;

use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Product\Services\ProductService;

class ProductsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return app(ProductService::class)->importRow([
            'title' => $row['ten_san_pham'] ?? null,
            'slug' => $row['slug'] ?? null,
            'regular_price' => $row['gia_goc'] ?? 0,
            'sale_price' => $row['gia_sale'] ?? null,
            'short_description' => $row['mo_ta_ngan'] ?? null,
            'description' => $row['chi_tiet'] ?? null,
            'image' => $row['anh_dai_dien'] ?? null,
            'gallery' => $this->jsonArray($row['album_anh_json'] ?? null, 'album_anh_json'),
            'tags' => $this->jsonArray($row['tags_json'] ?? null, 'tags_json'),
            'category_ids' => $row['danh_muc_ids'] ?? null,
            'is_active' => $this->booleanValue($row['trang_thai'] ?? true),
        ]);
    }

    private function jsonArray(mixed $value, string $field): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode((string) $value, true);

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                $field => 'Giá trị JSON không hợp lệ.',
            ]);
        }

        return $decoded;
    }

    private function booleanValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'active', 'published'], true);
    }
}
