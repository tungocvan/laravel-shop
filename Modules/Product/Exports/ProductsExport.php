<?php

namespace Modules\Product\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Product\Services\ProductService;

class ProductsExport implements FromCollection, WithHeadings, WithMapping
{
    protected ?array $ids;

    public function __construct(?array $ids = null)
    {
        $this->ids = $ids;
    }

    public function collection()
    {
        return app(ProductService::class)->exportRows($this->ids);
    }

    public function headings(): array
    {
        // Export FULL cột để Import lại được
        return [
            'ID', 'Tên sản phẩm', 'Slug',
            'Giá gốc', 'Giá sale',
            'Mô tả ngắn', 'Chi tiết',
            'Ảnh đại diện', 'Album ảnh (JSON)',
            'Tags (JSON)', 'Danh mục (IDs)',
            'Trạng thái', 'Ngày tạo'
        ];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->title,
            $product->slug,
            $product->regular_price,
            $product->sale_price,
            $product->short_description,
            $product->description,
            $product->image,
            json_encode($product->gallery, JSON_UNESCAPED_UNICODE),
            json_encode($product->tags, JSON_UNESCAPED_UNICODE),
            $product->categories->pluck('id')->implode(','),
            $product->is_active ? 1 : 0,
            $product->created_at,
        ];
    }
}
