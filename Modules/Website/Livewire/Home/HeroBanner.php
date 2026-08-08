<?php

namespace Modules\Website\Livewire\Home;

use Livewire\Component;
use Modules\Admin\Models\Banner;

class HeroBanner extends Component
{
    public $slides = [];

    public function mount()
    {
        // 1. Query dữ liệu thật từ DB
        $dbSlides = Banner::where('position', 'hero')
            ->where('is_active', true)
            ->orderBy('order', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        // 2. Kiểm tra: Nếu DB có dữ liệu thì dùng, nếu không thì dùng mẫu
        if ($dbSlides->isNotEmpty()) {
            // Chuyển Collection thành Array để đồng bộ cấu trúc với dữ liệu mẫu
            $this->slides = $dbSlides->toArray();
        } else {
            // 3. Dữ liệu mẫu (Online Images)
            $this->slides = [
                [
                    'id' => 1,
                    // Ảnh ngang 16:9 cho Desktop
                    'image_desktop' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?q=80&w=1920&h=800&fit=crop',
                    // Ảnh dọc 4:5 cho Mobile
                    'image_mobile'  => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?q=80&w=800&h=1000&fit=crop',
                    'title'         => 'Thời trang Mùa Hè 2024',
                    'sub_title'     => 'Bộ sưu tập mới nhất',
                    'btn_text'      => 'Mua ngay',
                    'link'          => '#',
                ],
                [
                    'id' => 2,
                    'image_desktop' => 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?q=80&w=1920&h=800&fit=crop',
                    'image_mobile'  => 'https://images.unsplash.com/photo-1469334031218-e382a71b716b?q=80&w=800&h=1000&fit=crop',
                    'title'         => 'Siêu Sale Giữa Năm',
                    'sub_title'     => 'Giảm giá lên tới 50%',
                    'btn_text'      => 'Xem chi tiết',
                    'link'          => '#',
                ],
                 [
                    'id' => 3,
                    'image_desktop' => 'https://images.unsplash.com/photo-1445205170230-053b83016050?q=80&w=1920&h=800&fit=crop',
                    'image_mobile'  => 'https://images.unsplash.com/photo-1445205170230-053b83016050?q=80&w=800&h=1000&fit=crop',
                    'title'         => 'Phong cách Thu Đông',
                    'sub_title'     => 'Ấm áp & Sang trọng',
                    'btn_text'      => 'Khám phá',
                    'link'          => '#',
                ],
            ];
        }
    }

    public function render()
    {
        return view('Website::livewire.home.hero-banner');
    }
}
