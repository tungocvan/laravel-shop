<?php

namespace Modules\Website\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Website\Models\Setting;

class ThemeSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Cấu hình Hero Banner (JSON Array)
        $heroSlides = [
            [
                'image' => 'images/banners/hero-1.jpg', // Đảm bảo file này có trong public/images/banners/
                'title' => 'Thời Trang Mùa Hè 2025',
                'sub_title' => 'Bộ sưu tập mới nhất với ưu đãi lên đến 50%',
                'link' => '/shop',
                'btn_text' => 'Mua Ngay'
            ],
            [
                'image' => 'images/banners/hero-2.jpg', // Bạn có thể copy hero-1.jpg và đổi tên thành hero-2.jpg để test slide chạy
                'title' => 'Phong Cách Công Sở',
                'sub_title' => 'Thanh lịch, hiện đại và chuyên nghiệp',
                'link' => '/category/office',
                'btn_text' => 'Khám Phá'
            ]
        ];

        // Lưu vào DB (Sử dụng hàm setValue trong Model Setting để tự động xóa Cache)
        Setting::setValue('home_hero_slides', json_encode($heroSlides), 'theme');
        $this->command->info('✅ Đã nạp dữ liệu mẫu: home_hero_slides');


        // 2. Cấu hình Promo Banner (JSON Object)
        $promoBanner = [
            'image' => 'images/banners/promo-middle.jpg',
            'title' => 'ĐẠI TIỆC CÔNG NGHỆ',
            'sub_title' => 'Giảm giá 40% cho các sản phẩm Apple', // Thêm dòng này nếu thiếu

            // Link 1: Dành cho Nút Mua Ngay (Trỏ về trang Shop)
            'link' => '/shop?categorySlug=cong-nghe',
            'btn_text' => 'Săn Deal Ngay',

            // Link 2: Dành cho dòng "Chi tiết chương trình" (Trỏ về Blog/Bài viết)
            'details_link' => '/blog/the-le-chuong-trinh-khuyen-mai-thang-10'
        ];
        Setting::setValue('home_promo_banner', json_encode($promoBanner), 'theme');
        $this->command->info('✅ Đã nạp dữ liệu mẫu: home_promo_banner');
    }
}
