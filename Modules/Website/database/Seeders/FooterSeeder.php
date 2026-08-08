<?php

namespace Modules\Website\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Modules\Website\Models\Setting;
use Modules\Website\Models\FooterColumn;
use Modules\Website\Models\FooterLink;
use Modules\Website\Models\SocialLink;
use Illuminate\Support\Str;
// php artisan db:seed --class="Modules\Website\database\Seeders\FooterSeeder"
class FooterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Dọn dẹp dữ liệu cũ (Tùy chọn, để tránh duplicate khi chạy lại)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        FooterColumn::truncate();
        FooterLink::truncate();
        SocialLink::truncate();
        // Không truncate wp_settings vì chứa nhiều config khác, chỉ update đè
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // ---------------------------------------------------------
        // PHẦN 1: FOOTER INFO & BRAND (Bảng wp_settings)
        // ---------------------------------------------------------
        $settings = [
            [
                'key' => 'footer.brand_description',
                'value' => 'Nền tảng thương mại điện tử hàng đầu, mang đến trải nghiệm mua sắm đẳng cấp với những sản phẩm được tuyển chọn kỹ lưỡng và dịch vụ khách hàng tận tâm.',
                'group_name' => 'footer',
                'type' => 'textarea'
            ],
            [
                'key' => 'footer.address',
                'value' => '36 Nguyễn Minh Hoàng, Phường Bảy Hiền, TP.HCM',
                'group_name' => 'footer',
                'type' => 'text'
            ],
            [
                'key' => 'footer.email',
                'value' => 'tungocvan@gmail.com',
                'group_name' => 'footer',
                'type' => 'text'
            ],
            [
                'key' => 'footer.phone',
                'value' => '0903 971 949',
                'group_name' => 'footer',
                'type' => 'text'
            ],
            [
                'key' => 'footer.appstore_url',
                'value' => 'https://apps.apple.com/app-id',
                'group_name' => 'footer',
                'type' => 'text'
            ],
            [
                'key' => 'footer.playstore_url',
                'value' => 'https://play.google.com/store/apps',
                'group_name' => 'footer',
                'type' => 'text'
            ],
            [
                'key' => 'footer.copyright',
                'value' => '© 2026 FlexBiz. All rights reserved.',
                'group_name' => 'footer',
                'type' => 'text'
            ],
        ];

        foreach ($settings as $item) {
            Setting::updateOrCreate(
                ['key' => $item['key']],
                $item
            );
        }

        // ---------------------------------------------------------
        // PHẦN 2: FOOTER COLUMNS & LINKS
        // ---------------------------------------------------------

        // Cột 1: Về FlexBiz
        $col1 = FooterColumn::create([
            'title' => 'Về FlexBiz',
            'slug' => 'about-flexbiz',
            'sort_order' => 1,
            'is_active' => true
        ]);

        $col1->links()->createMany([
            ['label' => 'Câu chuyện thương hiệu', 'url' => '/blog/cau-chuyen-thuong-hieu', 'sort_order' => 1, 'is_active' => true],
            ['label' => 'Tuyển dụng', 'url' => '/blog/tuyen-dung', 'sort_order' => 2, 'is_active' => true],
            ['label' => 'Tin tức & Sự kiện', 'url' => '/blog', 'sort_order' => 3, 'is_active' => true],
            ['label' => 'Liên hệ hợp tác', 'url' => '/blog/' . Str::slug('Liên hệ hợp tác'), 'sort_order' => 4, 'is_active' => true],
        ]);

        // Cột 2: Hỗ trợ khách hàng
        $col2 = FooterColumn::create([
            'title' => 'Hỗ Trợ Khách Hàng',
            'slug' => 'customer-support',
            'sort_order' => 2,
            'is_active' => true
        ]);
 
        $col2->links()->createMany([
            ['label' => 'Trung tâm trợ giúp', 'url' => '/blog/' . Str::slug('Trung tâm trợ giúp'), 'sort_order' => 1, 'is_active' => true],
            ['label' => 'Hướng dẫn mua hàng', 'url' => '/blog/' . Str::slug('Hướng dẫn mua hàng'), 'sort_order' => 2, 'is_active' => true],
            ['label' => 'Chính sách vận chuyển', 'url' => '/blog/' . Str::slug('Chính sách vận chuyển'), 'sort_order' => 3, 'is_active' => true],
            ['label' => 'Chính sách đổi trả', 'url' => '/blog/' . Str::slug('Chính sách đổi trả'), 'sort_order' => 4, 'is_active' => true],
            ['label' => 'Điều Khoản Dịch Vụ', 'url' => '/blog/' . Str::slug('Điều Khoản Dịch Vụ'), 'sort_order' => 5, 'is_active' => true],
        ]);

        // ---------------------------------------------------------
        // PHẦN 3: SOCIAL LINKS
        // ---------------------------------------------------------
        SocialLink::insert([
            [
                'platform' => 'Facebook',
                'name' => 'Facebook Fanpage',
                'url' => 'https://facebook.com',
                'icon_class' => 'fab fa-facebook', // Hoặc để trống nếu dùng SVG hardcode
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'platform' => 'Instagram',
                'name' => 'Instagram Official',
                'url' => 'https://instagram.com',
                'icon_class' => 'fab fa-instagram',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'platform' => 'YouTube',
                'name' => 'FlexBiz Channel',
                'url' => 'https://youtube.com',
                'icon_class' => 'fab fa-youtube',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => now(), 'updated_at' => now()
            ],
        ]);

        // ---------------------------------------------------------
        // QUAN TRỌNG: XÓA CACHE ĐỂ ADMIN HIỂN THỊ NGAY
        // ---------------------------------------------------------
        Cache::flush();

        $this->command->info('✅ Footer Data Seeded Successfully!');
    }
}
