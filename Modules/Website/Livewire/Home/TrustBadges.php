<?php

namespace Modules\Website\Livewire\Home;

use Livewire\Component;
use Modules\Admin\Models\Setting; // Đảm bảo import Model Setting

class TrustBadges extends Component
{
    public $badges = [];

    public function mount()
    {
        // 1. Lấy dữ liệu từ bảng Settings
        $settingData = Setting::where('key', 'home_trust_badges')->value('value');

        // 2. Decode JSON
        if ($settingData) {
            $this->badges = json_decode($settingData, true);
        }

        // 3. Fallback: Nếu không có dữ liệu, dùng dữ liệu mẫu (Hardcode)
        // Để giao diện luôn đẹp ngay cả khi chưa cấu hình
        if (empty($this->badges)) {
            $this->badges = [
                [
                    'icon' => '<svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>',
                    'title' => 'Miễn Phí Vận Chuyển',
                    'sub_title' => 'Cho đơn hàng từ 500k',
                ],
                [
                    'icon' => '<svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>',
                    'title' => 'Thanh Toán An Toàn',
                    'sub_title' => 'Bảo mật 100% chuẩn SSL',
                ],
                [
                    'icon' => '<svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>',
                    'title' => 'Đổi Trả Dễ Dàng',
                    'sub_title' => 'Trong vòng 30 ngày',
                ],
                [
                    'icon' => '<svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" /></svg>',
                    'title' => 'Hỗ Trợ 24/7',
                    'sub_title' => 'Hotline & Chat trực tuyến',
                ],
            ];
        }
    }

    public function render()
    {
        return view('Website::livewire.home.trust-badges');
    }
}
