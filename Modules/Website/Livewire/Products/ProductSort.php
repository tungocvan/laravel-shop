<?php

namespace Modules\Website\Livewire\Products;

use Livewire\Component;

class ProductSort extends Component
{
    public $sort = 'latest'; // Mặc định là mới nhất

    public function updatedSort()
    {
        // Gửi sự kiện lên cha
        $this->dispatch('sort-updated', sort: $this->sort);
    }

    public function render()
    {
        return view('Website::livewire.products.product-sort');
    }
}
