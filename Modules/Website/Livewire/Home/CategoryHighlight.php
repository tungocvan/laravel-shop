<?php

namespace Modules\Website\Livewire\Home;

use Livewire\Component;
use Modules\Website\Models\Category; // 1. Import Model Category
use Illuminate\Database\Eloquent\Collection;

class CategoryHighlight extends Component
{
    public Collection $categories;
    public $categoryIds = []; // 2. Nhận từ cha

    public function mount($categoryIds = [])
    {
        $this->categoryIds = $categoryIds;
        $this->loadCategories();
    }

    public function loadCategories()
    {
        if (!empty($this->categoryIds)) {
            // 3. Query lấy danh mục theo ID admin đã chọn
            // Dùng whereIn và sắp xếp đúng thứ tự admin đã chọn (FIELD)
            $idsString = implode(',', $this->categoryIds);

            $this->categories = Category::whereIn('id', $this->categoryIds)
                ->where('is_active', true) // Chỉ lấy danh mục đang bật
                ->orderByRaw("FIELD(id, $idsString)")
                ->get();
        } else {
            // Fallback: Nếu Admin chưa chọn gì thì lấy tạm 8 danh mục đầu tiên (để giao diện ko bị trống)
     
            $this->categories = Category::where('is_active', true)
                ->limit(8)
                ->get();
        }
    }

    public function render()
    {
        return view('Website::livewire.home.category-highlight');
    }
}
