<?php

namespace Modules\Product\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Product\Models\Product;
use Modules\Pharma\Models\Medicine;

class PharmaProductsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private bool $template = false) {}

    public function collection()
    {
        if ($this->template) {
            return collect();
        }

        return Product::query()
            ->whereHas('pharmaLink')
            ->with(['pharmaLink.medicine.supplierTrackings', 'categories:id,name,slug,type'])
            ->orderBy('id')
            ->limit(5000)
            ->get();
    }

    public function headings(): array
    {
        return [
            'Product ID (chỉ đọc)', 'Medicine ID (chỉ đọc)', 'Tên sản phẩm (chỉ đọc)',
            'Slug (chỉ đọc)', 'Giá gốc', 'Giá sale', 'Mô tả ngắn',
            'Chi tiết', 'Ảnh đại diện', 'Album ảnh (JSON)', 'Tags (JSON)',
            'Danh mục (Slugs)', 'Trạng thái', 'Hoa hồng affiliate (%)',
            'Hoạt chất (Pharma - chỉ đọc)', 'Nhà cung cấp (Pharma - chỉ đọc)', 'Loại sản phẩm (Pharma - chỉ đọc)',
        ];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->pharmaLink?->medicine_id,
            $product->title,
            $product->slug,
            $product->regular_price,
            $product->sale_price,
            $product->short_description,
            $product->description,
            $product->image,
            json_encode($product->gallery, JSON_UNESCAPED_UNICODE),
            json_encode($product->tags, JSON_UNESCAPED_UNICODE),
            $product->categories->where('type', 'medicine')->pluck('slug')->implode(','),
            $product->is_active ? 1 : 0,
            $product->affiliate_commission_rate,
            $product->pharmaLink?->medicine?->active_ingredients,
            $product->pharmaLink?->medicine?->supplierTrackings->pluck('supplier_name')->filter()->unique()->implode('; '),
            Medicine::productTypeOptions()[$product->pharmaLink?->medicine?->product_type] ?? $product->pharmaLink?->medicine?->product_type,
        ];
    }
}
