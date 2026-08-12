<?php

namespace Modules\Website\Livewire\Home;

use Livewire\Component;
use Modules\Website\Services\HomepageContentService;

class HomeList extends Component
{
    // Mảng chứa toàn bộ cấu hình (Layout + Data IDs)
    public $settings = [];

    public array $sectionOrder = [];

    public array $sectionTypes = [];

    public array $categoryIds = [];

    // Inject Service vào mount để lấy dữ liệu chuẩn
    public function mount(HomepageContentService $service)
    {
        // 1. Lấy toàn bộ cấu hình từ Admin thông qua Service
        $this->settings = $service->visibility();
        $this->sectionOrder = $service->order();
        $this->sectionTypes = $service->sectionTypes();
        $this->categoryIds = $service->referenceIds('categories', 'category', 'home_category_ids');
    }

    /**
     * Helper chuyển đổi trạng thái config sang class Tailwind
     * Input: 'show_hero' -> Output: 'hidden md:block', 'block', ...
     */
    public function getVisibilityClass($key)
    {
        // Lấy giá trị từ settings, mặc định 'all' nếu key chưa tồn tại
        $state = $this->settings[$key] ?? 'all';

        return match ($state) {
            'desktop' => 'hidden md:block', // Ẩn mobile, hiện từ tablet/pc trở lên
            'mobile' => 'block md:hidden', // Hiện mobile, ẩn từ tablet/pc trở lên
            'none' => 'hidden',          // Ẩn hoàn toàn (dùng class hidden của Tailwind)
            'hidden' => 'hidden',          // Case dự phòng nếu lưu là 'hidden'
            default => 'block'            // Hiện tất cả ('all')
        };
    }

    public function render()
    {
        return view('Website::livewire.home.home-list');
    }
}
