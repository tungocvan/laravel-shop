<?php

namespace Modules\Website\Livewire\Products;

use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Product\Models\Product;

class PharmaCatalog extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $category = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->category = '';
        $this->resetPage();
    }

    public function render()
    {
        $products = Product::query()
            ->with('categories')
            ->where('is_active', true)
            ->whereHas('pharmaLink')
            ->when(trim($this->search) !== '', fn ($query) => $query->where('title', 'like', '%'.trim($this->search).'%'))
            ->when($this->category !== '', fn ($query) => $query->whereHas('categories', fn ($categories) => $categories->where('categories.type', 'medicine')->where('categories.slug', $this->category)))
            ->latest()
            ->paginate(12);

        $categories = \Modules\Category\Models\Category::query()
            ->where('type', 'medicine')
            ->whereHas('products', fn ($products) => $products->where('is_active', true)->whereHas('pharmaLink'))
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return view('Website::livewire.products.pharma-catalog', compact('products', 'categories'));
    }
}
