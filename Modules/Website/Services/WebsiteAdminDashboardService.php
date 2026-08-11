<?php

namespace Modules\Website\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WebsiteAdminDashboardService
{
    public function summary(): array
    {
        return [
            'pages' => $this->count('website_pages'),
            'sections' => $this->count('website_sections'),
            'enabled_sections' => $this->count('website_sections', ['is_enabled' => true]),
            'menus' => $this->count('header_menus'),
            'menu_items' => $this->count('header_menu_items'),
            'banners' => $this->count('wp_banners'),
            'active_banners' => $this->count('wp_banners', ['is_active' => true]),
            'footer_columns' => $this->count('footer_columns'),
            'social_links' => $this->count('social_links'),
        ];
    }

    public function checks(): array
    {
        $summary = $this->summary();

        return [
            ['label' => 'Trang chủ đã xuất bản', 'passed' => $this->exists('website_pages', ['slug' => 'home', 'status' => 'published'])],
            ['label' => 'Có section đang hiển thị', 'passed' => $summary['enabled_sections'] > 0],
            ['label' => 'Có menu điều hướng', 'passed' => $summary['menu_items'] > 0],
            ['label' => 'Có banner đang hoạt động', 'passed' => $summary['active_banners'] > 0],
            ['label' => 'Footer đã cấu hình', 'passed' => $summary['footer_columns'] > 0],
        ];
    }

    private function count(string $table, array $where = []): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return DB::table($table)->where($where)->count();
    }

    private function exists(string $table, array $where): bool
    {
        return Schema::hasTable($table) && DB::table($table)->where($where)->exists();
    }
}
