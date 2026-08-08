<?php

namespace Modules\Admin\Livewire\Products;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Website\Models\WpProduct;
use Modules\Website\Models\Category;
use Illuminate\Support\Str;

class ProductForm extends Component
{
    use WithFileUploads;

    public $productId = null;

    // --- Basic Info ---
    public $title, $slug, $short_description, $description;
    public $regular_price, $sale_price;
    public $is_active = true;
    public $category_ids = [];
    public $affiliate_commission_rate;
    // --- Images ---
    public $newImage;   // Ảnh đại diện mới
    public $oldImage;   // Ảnh đại diện cũ

    // --- BỔ SUNG: GALLERY & TAGS ---
    public $gallery = [];      // Mảng chứa đường dẫn ảnh cũ (từ DB)
    public $newGallery = [];   // Mảng chứa file ảnh mới upload (Livewire Upload)

    public $tags = [];         // Mảng chứa các tag đã add
    public $tagInput = '';     // Biến tạm để nhập tag

    // --- Load Data ---
    public function mount($id = null)
    {
        if ($id) {
            $product = WpProduct::with('categories')->findOrFail($id);
            $this->productId = $product->id;

            // Map dữ liệu cũ
            $this->title = $product->title;
            $this->slug = $product->slug;
            $this->regular_price = $product->regular_price;
            $this->sale_price = $product->sale_price;
            $this->short_description = $product->short_description;
            $this->description = $product->description;
            $this->is_active = (bool) $product->is_active;
            $this->oldImage = $product->image;
            $this->affiliate_commission_rate = $product->affiliate_commission_rate;
            // Danh mục
            $this->category_ids = $product->categories->pluck('id')->map(fn($i)=>(string)$i)->toArray();

            // Gallery & Tags (Decode JSON nếu cần, hoặc Laravel cast tự xử lý)
            $this->gallery = $product->gallery ?? [];
            $this->tags = $product->tags ?? [];
        }
    }

    public function getCategoriesProperty()
    {
        return \Modules\Website\Models\Category::where('type', 'product')
            ->whereNull('parent_id') // Chỉ lấy ông Tổ
            ->with(['children.children']) // Eager load con và cháu
            ->orderBy('sort_order')
            ->get();
    }

    // --- Logic TAGS ---
    public function addTag()
    {
        if (!empty($this->tagInput)) {
            // Thêm vào mảng nếu chưa tồn tại
            if (!in_array($this->tagInput, $this->tags)) {
                $this->tags[] = $this->tagInput;
            }
            $this->tagInput = ''; // Reset ô nhập
        }
    }

    public function removeTag($index)
    {
        unset($this->tags[$index]);
        $this->tags = array_values($this->tags); // Re-index array
    }

    // --- Logic GALLERY ---
    public function removeOldGallery($index)
    {
        // Xóa ảnh cũ khỏi danh sách hiển thị (Khi save sẽ cập nhật lại DB)
        unset($this->gallery[$index]);
        $this->gallery = array_values($this->gallery);
    }

    public function removeNewGallery($index)
    {
        // Xóa ảnh mới vừa upload (chưa lưu)
        unset($this->newGallery[$index]);
        $this->newGallery = array_values($this->newGallery);
    }

    protected function rules()
    {
        return [
            'title' => 'required|min:3',
            'slug' => 'required|unique:wp_products,slug,' . $this->productId,
            'regular_price' => 'required|numeric|min:0',
            'category_ids' => 'array',
            'newImage' => 'nullable|image|max:5120',
            'affiliate_commission_rate' => 'nullable|numeric|min:0|max:100',
            // Validate mảng ảnh mới
            'newGallery.*' => 'image|max:5120',
            'tags' => 'array',
        ];
    }

    public function save()
    {
        $this->validate();

        // 1. Xử lý Gallery: Gộp ảnh cũ + ảnh mới upload
        $finalGallery = $this->gallery; // Bắt đầu bằng ảnh cũ còn lại

        foreach ($this->newGallery as $file) {
            $finalGallery[] = $file->store('products/gallery', 'public');
        }

        $data = [
            'title' => $this->title,
            'slug' => $this->slug ?: Str::slug($this->title),
            'regular_price' => $this->regular_price,
            'sale_price' => $this->sale_price,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'tags' => $this->tags,        // Lưu mảng tags (Model tự cast JSON)
            'gallery' => $finalGallery,   // Lưu mảng gallery (Model tự cast JSON)
            'affiliate_commission_rate' => $this->affiliate_commission_rate ?: null,
        ];

        if ($this->newImage) {
            $data['image'] = $this->newImage->store('products', 'public');
        }

        if ($this->productId) {
            $product = WpProduct::find($this->productId);
            $product->update($data);
        } else {
            $product = WpProduct::create($data);
        }

        // Sync Categories
        $product->categories()->sync($this->category_ids);

        return redirect()->route('admin.products.index');
    }

    // Auto Slug
    public function updatedTitle($value)
    {
        $this->slug = Str::slug($value);
    }

    public function render()
    {
        return view('Admin::livewire.products.product-form');
    }
}
