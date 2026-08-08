<?php

namespace Modules\Website\Services;

use Modules\Website\Models\Setting;
use Carbon\Carbon;

class MarketingService
{
    /**
     * Lấy dữ liệu Hero Banner
     * Return structure: [['image' => '...', 'title' => '...', 'link' => '...']]
     */
    public function getHeroSlides(): array
    {
        // Lấy từ bảng wp_settings, key là 'home_hero_slides'
        // Dữ liệu giả lập nếu chưa cấu hình trong DB
        $default = [
            [
                'image' => 'images/banners/hero-1.jpg', // Placeholder
                'title' => 'Bộ sưu tập mùa hè',
                'sub_title' => 'Giảm giá tới 50%',
                'link' => '/shop',
                'btn_text' => 'Mua ngay'
            ]
        ];

        $data = Setting::getValue('home_hero_slides');

        return !empty($data) ? json_decode($data, true) : $default;
    }

    /**
     * Lấy dữ liệu Promo Banner (Banner ngang giữa trang)
     */
    public function getPromoBanner(): array
    {
        $default = [
            'image' => 'images/banners/promo-middle.jpg',
            'title' => 'Đại tiệc công nghệ',
            'link' => '/category/tech',
        ];

        $data = Setting::getValue('home_promo_banner');
        return !empty($data) ? json_decode($data, true) : $default;
    }

    /**
     * Cấu hình thời gian FlashSale
     */
    public function getFlashSaleConfig(): array
    {
        // Lấy timestamp kết thúc từ DB (Lưu dạng string '2023-10-25 00:00:00')
        $endTimeStr = Setting::getValue('flash_sale_end_time');

        if ($endTimeStr) {
            $endTime = Carbon::parse($endTimeStr);
        } else {
            // Mặc định: Sale đến hết ngày hôm nay
            $endTime = Carbon::tomorrow();
        }

        return [
            // Check xem còn hạn không
            'is_active' => $endTime->isFuture(),
            // Trả về Timestamp (miliseconds) cho JS
            'end_time_js' => $endTime->timestamp * 1000,
            'end_time_human' => $endTime->format('H:i d/m')
        ];
    }
}
