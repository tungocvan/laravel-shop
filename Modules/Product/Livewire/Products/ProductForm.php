<?php

namespace Modules\Product\Livewire\Products;

use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Product\Services\ProductService;

class ProductForm extends Component
{
    use WithFileUploads;

    public $productId = null;

    public $title;
    public $slug;
    public $short_description;
    public $description;
    public $regular_price;
    public $sale_price;
    public $is_active = true;
    public $category_ids = [];
    public $affiliate_commission_rate;

    public $newImage;
    public $oldImage;
    public $gallery = [];
    public $newGallery = [];

    public $tags = [];
    public $tagInput = '';

    protected ProductService $products;

    public function boot(ProductService $products): void
    {
        $this->products = $products;
    }

    public function mount($id = null): void
    {
        if (! $id) {
            $this->authorizeAdmin('create_product');

            return;
        }

        $this->authorizeAdmin('edit_product');

        $product = $this->products->findForEdit((int) $id);
        $this->productId = $product->id;
        $this->title = $product->title;
        $this->slug = $product->slug;
        $this->regular_price = $product->regular_price;
        $this->sale_price = $product->sale_price;
        $this->short_description = $product->short_description;
        $this->description = $product->description;
        $this->is_active = (bool) $product->is_active;
        $this->oldImage = $product->image;
        $this->affiliate_commission_rate = $product->affiliate_commission_rate;
        $this->category_ids = $product->categories->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->gallery = $product->gallery ?? [];
        $this->tags = $product->tags ?? [];
    }

    public function getCategoriesProperty()
    {
        return $this->products->productCategoryTree();
    }

    public function addTag(): void
    {
        $tag = trim((string) $this->tagInput);

        if ($tag !== '' && ! in_array($tag, $this->tags, true)) {
            $this->tags[] = $tag;
        }

        $this->tagInput = '';
    }

    public function removeTag($index): void
    {
        unset($this->tags[$index]);
        $this->tags = array_values($this->tags);
    }

    public function removeOldGallery($index): void
    {
        unset($this->gallery[$index]);
        $this->gallery = array_values($this->gallery);
    }

    public function removeNewGallery($index): void
    {
        unset($this->newGallery[$index]);
        $this->newGallery = array_values($this->newGallery);
    }

    protected function rules(): array
    {
        return [
            'title' => 'required|string|min:3|max:255',
            'slug' => 'nullable|string|max:255',
            'regular_price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'category_ids' => 'array',
            'category_ids.*' => 'integer',
            'newImage' => 'nullable|image|max:5120',
            'newGallery.*' => 'image|max:5120',
            'affiliate_commission_rate' => 'nullable|numeric|min:0|max:100',
            'tags' => 'array',
            'tags.*' => 'string|max:100',
        ];
    }

    public function save()
    {
        $this->authorizeAdmin($this->productId ? 'edit_product' : 'create_product');
        $this->validate();

        $payload = [
            'title' => $this->title,
            'slug' => $this->slug ?: Str::slug((string) $this->title),
            'regular_price' => $this->regular_price,
            'sale_price' => $this->sale_price,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'tags' => $this->tags,
            'gallery' => $this->gallery,
            'newGallery' => $this->newGallery,
            'newImage' => $this->newImage,
            'affiliate_commission_rate' => $this->affiliate_commission_rate ?: null,
            'category_ids' => $this->category_ids,
        ];

        if ($this->productId) {
            $this->products->update((int) $this->productId, $payload);
        } else {
            $this->products->create($payload);
        }

        return redirect()->route('admin.products.index');
    }

    public function updatedTitle($value): void
    {
        $this->slug = Str::slug((string) $value);
    }

    public function render()
    {
        return view('product::livewire.products.product-form');
    }

    private function authorizeAdmin(string $permission): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can($permission), 403);
    }
}
