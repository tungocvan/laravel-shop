<?php

namespace Modules\Admin\Imports;

use Modules\Website\Models\WpProduct;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class ProductsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Xử lý dữ liệu thô từ Excel
        $gallery = !empty($row['album_anh_json']) ? json_decode($row['album_anh_json'], true) : [];
        $tags    = !empty($row['tags_json']) ? json_decode($row['tags_json'], true) : [];
        $catIds  = !empty($row['danh_muc_ids']) ? explode(',', $row['danh_muc_ids']) : [];

        $product = WpProduct::create([
            'title'             => $row['ten_san_pham'],
            'slug'              => $row['slug'] ?? Str::slug($row['ten_san_pham']),
            'regular_price'     => $row['gia_goc'] ?? 0,
            'sale_price'        => $row['gia_sale'],
            'short_description' => $row['mo_ta_ngan'],
            'description'       => $row['chi_tiet'],
            'image'             => $row['anh_dai_dien'],
            'gallery'           => $gallery,
            'tags'              => $tags,
            'is_active'         => $row['trang_thai'] ?? 1,
        ]);

        // Sync Categories
        if (!empty($catIds)) {
            $product->categories()->sync($catIds);
        }

        return $product;
    }
}