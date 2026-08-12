<?php

namespace Modules\Website\database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\AdminMenu;

class WebsiteAdminMenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $website = $this->upsert('quan-ly-website', [
                'name' => 'Website & Nội dung', 'url' => null, 'icon' => 'globe-alt',
                'can' => 'website.view', 'sort_order' => 4,
            ]);

            $this->children($website, [
                ['website-dashboard', 'Tổng quan Website', '/admin/website', 'chart-bar', 'website.view'],
                ['cau-hinh-trang-chinh', 'Homepage Builder', '/admin/homepage-settings', 'squares-plus', 'website.home.manage'],
                ['cau-hinh-header', 'Menu & Header', '/admin/header-settings', 'bars-3', 'website.menu.manage'],
                ['quan-ly-banners-home', 'Banner', '/admin/banners', 'photo', 'website.banner.manage'],
                ['cau-hinh-footer', 'Footer', '/admin/footer-settings', 'rectangle-stack', 'website.footer.manage'],
                ['website-seo-settings', 'SEO, Giao diện & Cài đặt', '/admin/website/settings', 'cog-6-tooth', 'website.settings.manage'],
            ]);

            $marketing = $this->upsert('marketing-ban-hang', [
                'name' => 'Marketing & Bán hàng', 'url' => null, 'icon' => 'megaphone',
                'can' => 'marketing.coupon.view', 'sort_order' => 5,
            ]);
            $this->children($marketing, [
                ['quan-ly-ma-giam-gia', 'Mã giảm giá', '/admin/coupons', 'ticket', 'marketing.coupon.view'],
                ['tao-chien-dich-flash-sales', 'Flash Sale', '/admin/flash-sales', 'bolt', 'marketing.flash-sale.view'],
                ['quan-ly-affiliate', 'Affiliate', '/admin/affiliate', 'users', 'affiliate.view'],
            ]);
        });

        AdminMenu::clearMenuCache();
        $this->command?->info('Đã đồng bộ cấu trúc menu Website Admin chuyên nghiệp.');
    }

    private function children(AdminMenu $parent, array $items): void
    {
        foreach ($items as $sort => [$slug, $name, $url, $icon, $permission]) {
            $this->upsert($slug, [
                'parent_id' => $parent->id, 'name' => $name, 'url' => $url,
                'icon' => $icon, 'can' => $permission, 'sort_order' => $sort,
            ]);
        }
    }

    private function upsert(string $slug, array $data): AdminMenu
    {
        $menu = AdminMenu::withTrashed()->where('slug', $slug)->first();
        if ($menu) {
            $menu->restore();
            $menu->update($data + ['is_active' => true]);

            return $menu->refresh();
        }

        return AdminMenu::query()->create($data + ['slug' => $slug, 'is_active' => true]);
    }
}
