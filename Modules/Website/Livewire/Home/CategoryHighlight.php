<?php

namespace Modules\Website\Livewire\Home;

use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;
use Modules\Website\Services\HomepageContentService;

class CategoryHighlight extends Component
{
    public Collection $categories;

    public $categoryIds = []; // 2. Nhận từ cha

    public function mount(HomepageContentService $homepage, $categoryIds = [])
    {
        $this->categoryIds = $categoryIds;
        $this->categories = $homepage->highlightedCategories($this->categoryIds);
    }

    public function render()
    {
        return view('Website::livewire.home.category-highlight');
    }
}
