<?php

namespace Modules\Website\Livewire\Home;

use Livewire\Component;
use Modules\Admin\Models\Setting;

class PromoBanner extends Component
{
    public array $banner = [];

    public function mount()
    {
        // 1. Lấy dữ liệu từ DB
        $settings = Setting::where('key', 'home_promo_banner')->value('value');

        // 2. Decode JSON
        $this->banner = $settings ? json_decode($settings, true) : [];
    }

    public function placeholder()
    {
        // Giữ nguyên Skeleton cũ rất đẹp của bạn
        return <<<'blade'
        <div class="mb-16 container mx-auto px-4">
            <div class="w-full aspect-[21/9] md:aspect-[3/1] bg-gray-200 rounded-2xl animate-pulse flex items-center justify-center">
                <svg class="w-12 h-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
        </div>
        blade;
    }

    public function render()
    {
        // Kiểm tra nếu tắt hiển thị hoặc không có ảnh thì không render gì cả
        if (empty($this->banner) || empty($this->banner['image']) || ($this->banner['show'] ?? true) === false) {
            return <<<'blade'
                <div></div>
            blade;
        }

        return view('Website::livewire.home.promo-banner');
    }
}
