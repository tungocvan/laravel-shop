<?php

namespace Modules\Website\database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Website\Models\WebsitePage;

class WebsiteSectionSeeder extends Seeder
{
    public function run(): void
    {
        $page = WebsitePage::query()->where('slug', 'home')->firstOrFail();
        $sections = [
            'hero' => [], 'categories' => [], 'flash_sale' => [],
            'featured' => ['limit' => 10], 'new_arrivals' => ['limit' => 10],
            'best_sellers' => ['limit' => 8], 'blog_highlight' => ['limit' => 3],
            'promo_banner' => ['image' => 'https://images.unsplash.com/photo-1607082349566-187342175e2f?w=1600&q=85', 'title' => 'Ưu đãi cuối tuần', 'sub_title' => 'Giảm thêm cho đơn hàng online', 'link' => '/shop', 'btn_text' => 'Săn ưu đãi', 'show' => true],
            'trust_badges' => [], 'newsletter' => [],
        ];

        foreach ($sections as $key => $config) {
            $position = array_search($key, array_keys($sections), true) + 1;
            $page->sections()->create(['key' => $key, 'type' => $key, 'position' => $position, 'is_enabled' => true, 'config' => ['visibility' => 'all'] + $config]);
        }
    }
}
