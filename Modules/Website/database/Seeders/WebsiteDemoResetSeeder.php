<?php

namespace Modules\Website\database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WebsiteDemoResetSeeder extends Seeder
{
    public function run(): void
    {
        $tables = [
            'website_section_items', 'website_sections', 'website_pages',
            'wp_flash_sale_items', 'wp_flash_sales', 'reviews', 'wishlists',
            'cart_items', 'carts', 'order_histories', 'order_items', 'wp_orders',
            'wp_affiliate_schemes', 'affiliate_levels', 'newsletters', 'coupons',
            'footer_links', 'footer_columns', 'social_links',
            'header_menu_items', 'header_menus', 'wp_banners',
            'category_post', 'product_category', 'post_tag', 'wp_posts', 'tags',
            'wp_products', 'categories', 'wp_settings',
        ];

        Schema::disableForeignKeyConstraints();
        try {
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->command?->info('Đã làm sạch dữ liệu demo Website.');
    }
}
