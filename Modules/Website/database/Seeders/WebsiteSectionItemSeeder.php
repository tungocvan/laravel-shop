<?php

namespace Modules\Website\database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Website\Models\WebsiteSection;

class WebsiteSectionItemSeeder extends Seeder
{
    public function run(): void
    {
        $this->references('categories', 'category', Category::query()->where('type', 'product')->limit(8)->pluck('id')->all());
        $this->references('featured', 'product', Product::query()->where('is_active', true)->where('is_featured', true)->limit(10)->pluck('id')->all());

        $badges = [
            ['title' => 'Miễn phí vận chuyển', 'sub_title' => 'Cho đơn từ 500.000đ', 'icon' => 'truck'],
            ['title' => 'Thanh toán an toàn', 'sub_title' => 'Bảo mật giao dịch', 'icon' => 'shield'],
            ['title' => 'Đổi trả dễ dàng', 'sub_title' => 'Trong vòng 30 ngày', 'icon' => 'refresh'],
            ['title' => 'Hỗ trợ 24/7', 'sub_title' => 'Luôn sẵn sàng hỗ trợ', 'icon' => 'support'],
        ];
        $section = WebsiteSection::query()->where('key', 'trust_badges')->firstOrFail();
        foreach ($badges as $position => $config) {
            $section->items()->create(['position' => $position + 1, 'is_enabled' => true, 'config' => $config]);
        }
    }

    private function references(string $key, string $type, array $ids): void
    {
        $section = WebsiteSection::query()->where('key', $key)->firstOrFail();
        foreach ($ids as $position => $id) {
            $section->items()->create(['position' => $position + 1, 'reference_type' => $type, 'reference_id' => $id, 'is_enabled' => true]);
        }
    }
}
