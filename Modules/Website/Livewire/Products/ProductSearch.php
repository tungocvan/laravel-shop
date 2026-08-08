<?php

namespace Modules\Website\Livewire\Products;

use Livewire\Component;

class ProductSearch extends Component
{
    public $search = '';

    // Khi người dùng gõ, hàm này tự động chạy (nhờ wire:model.live)
    public function updatedSearch()
    {

        // Gửi sự kiện 'search-updated' kèm từ khóa
        $this->dispatch('search-updated', search: $this->search);
    }

    public function render()
    {
        return view('Website::livewire.products.product-search');
    }
}
