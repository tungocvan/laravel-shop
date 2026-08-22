<?php

namespace Modules\Website\database\Seeders;

use Database\Seeders\CategoryTypeSeeder;
use Illuminate\Database\Seeder;

class WebsiteDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn('Đang reset và tạo lại toàn bộ dữ liệu demo Website...');

        $this->call([
            WebsiteDemoResetSeeder::class,
            WebsiteDemoUserSeeder::class,
            CategoryTypeSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            PostCategorySeeder::class,
            PostSeeder::class,
            FooterPostSeeder::class,
            CouponSeeder::class,
            HeaderSeeder::class,
            SettingSeeder::class,
            FooterSeeder::class,
            BannerSeeder::class,
            NewsletterSeeder::class,
            AffiliateLevelSeeder::class,
            OrderSeeder::class,
            ReviewSeeder::class,
            FlashSaleSeeder::class,
            AffiliateSeeder::class,
            AffiliateSchemeSeeder::class,
            CartSeeder::class,
            WishlistSeeder::class,
            WebsitePageSeeder::class,
            WebsiteSectionSeeder::class,
            WebsiteSectionItemSeeder::class,
            HomepageSeeder::class,
            WebsiteDesignThemeSeeder::class,
            WebsiteAdminMenuSeeder::class,
        ]);

        $this->command?->newLine();
        $this->command?->info('Website demo đã sẵn sàng.');
        $this->command?->line('Đăng nhập: demo@website.test / Demo@123456');
    }
}
