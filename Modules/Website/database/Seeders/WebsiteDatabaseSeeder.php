<?php

namespace Modules\Website\database\Seeders;

use Illuminate\Database\Seeder;
// Import các Class Seeder

use Modules\Website\database\Seeders\CategorySeeder;
use Modules\Website\database\Seeders\ProductSeeder;
use Modules\Website\database\Seeders\OrderSeeder;


use Modules\Website\database\Seeders\SettingSeeder;
use Modules\Website\database\Seeders\HeaderSeeder;
use Modules\Website\database\Seeders\PostCategorySeeder;
use Modules\Website\database\Seeders\PostSeeder;
use Modules\Website\database\Seeders\CouponSeeder;
use Modules\Website\database\Seeders\FlashSaleSeeder;
use Modules\Website\database\Seeders\FooterSeeder;
use Modules\Website\database\Seeders\FooterPostSeeder;
use Modules\Website\database\Seeders\ReviewSeeder;
use Modules\Website\database\Seeders\AffiliateSeeder;
use Modules\Website\database\Seeders\AffiliateLevelSeeder;
use Modules\Website\database\Seeders\AffiliateSchemeSeeder;


class WebsiteDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Chạy lệnh: php artisan db:seed --class="Modules\Website\database\Seeders\WebsiteDatabaseSeeder"

        $this->call([
            //

            // 2. Tạo danh mục sản phẩm
            CategorySeeder::class,
            // 3. Tạo sản phẩm (gắn với danh mục)
            ProductSeeder::class,
            // Tạo danh mục bài viết
            PostCategorySeeder::class,
            // Tạo bài viết
            PostSeeder::class,
            CouponSeeder::class,
            // Tạo slides
           // ThemeSettingsSeeder::class,
            HeaderSeeder::class,
            SettingSeeder::class,
            // Tạo dữ liệu mẫu Footer
            FooterSeeder::class,
            FooterPostSeeder::class,
            // 4. Tạo đơn hàng (gắn với User và Sản phẩm)
            OrderSeeder::class,
            ReviewSeeder::class,
            // Tạo sản phẩm khuyến mãi
            FlashSaleSeeder::class,
            AffiliateSeeder::class,
            AffiliateLevelSeeder::class,
            AffiliateSchemeSeeder::class
        ]);
    }
}
