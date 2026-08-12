<?php

namespace Modules\Website\database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Website\Models\Banner;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $slides = [
            ['title' => 'Đại tiệc công nghệ', 'sub_title' => 'Ưu đãi đến 40% cho sản phẩm nổi bật', 'image_desktop' => 'https://images.unsplash.com/photo-1468495244123-6c6c332eeece?w=1920&q=85', 'image_mobile' => 'https://images.unsplash.com/photo-1468495244123-6c6c332eeece?w=800&q=85', 'link' => '/shop', 'btn_text' => 'Mua ngay'],
            ['title' => 'Phong cách mới mỗi ngày', 'sub_title' => 'Bộ sưu tập thời trang được tuyển chọn', 'image_desktop' => 'https://images.unsplash.com/photo-1445205170230-053b83016050?w=1920&q=85', 'image_mobile' => 'https://images.unsplash.com/photo-1445205170230-053b83016050?w=800&q=85', 'link' => '/shop', 'btn_text' => 'Khám phá'],
            ['title' => 'Không gian sống hiện đại', 'sub_title' => 'Nâng cấp tổ ấm với giá tốt', 'image_desktop' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=1920&q=85', 'image_mobile' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=800&q=85', 'link' => '/shop', 'btn_text' => 'Xem bộ sưu tập'],
        ];

        foreach ($slides as $index => $slide) {
            Banner::query()->create($slide + ['position' => 'hero', 'order' => $index + 1, 'is_active' => true]);
        }
    }
}
