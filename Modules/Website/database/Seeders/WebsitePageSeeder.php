<?php

namespace Modules\Website\database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Website\Models\WebsitePage;

class WebsitePageSeeder extends Seeder
{
    public function run(): void
    {
        WebsitePage::query()->create([
            'slug' => 'home', 'title' => 'Trang chủ', 'status' => 'published', 'template' => 'default',
            'seo_title' => 'FlexBiz Demo Store', 'seo_description' => 'Website thương mại điện tử đầy đủ dữ liệu demo.', 'published_at' => now(),
        ]);
    }
}
