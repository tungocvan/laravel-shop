<?php

namespace Modules\Admin\Livewire\Categories;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Website\Models\Category;
use Illuminate\Support\Str;

class CategoryForm extends Component
{
    use WithFileUploads;

    public $categoryId = null;

    // Fields
    public $name, $slug;
    public $parent_id = null;
    public $sort_order = 0;
    public $is_active = true;
    public $type = 'product'; // Mặc định Product

    // Image
    public $newImage;
    public $oldImage;

    // Load danh sách danh mục cha theo TYPE
    public function getParentsProperty()
    {
        $query = Category::where('type', $this->type);

        // Nếu đang edit, loại bỏ chính nó
        if ($this->categoryId) {
            $query->where('id', '!=', $this->categoryId);
        }

        $categories = $query->orderBy('sort_order')->orderBy('name')->get();

        return $this->buildTreeOption($categories);
    }

    private function buildTreeOption($categories, $parentId = null, $prefix = '')
    {
        $result = [];
        foreach ($categories as $category) {
            if ($category->parent_id == $parentId) {
                // Tạo tên hiển thị dạng tree: "-- Name"
                $category->view_name = $prefix . $category->name;
                $result[] = $category;
                $children = $this->buildTreeOption($categories, $category->id, $prefix . '-- ');
                $result = array_merge($result, $children);
            }
        }
        return $result;
    }

    public function mount($id = null)
    {
        if ($id) {
            $category = Category::findOrFail($id);
            $this->categoryId = $category->id;
            $this->name = $category->name;
            $this->slug = $category->slug;
            $this->parent_id = $category->parent_id;
            $this->sort_order = $category->sort_order;
            $this->is_active = (bool) $category->is_active;
            $this->type = $category->type;
            $this->oldImage = $category->image;
        }
    }

    // Khi đổi Type -> Reset Parent ID (Vì Parent của Product ko thể là Parent của Post)
    public function updatedType()
    {
        $this->parent_id = null;
    }

    public function updatedName($value)
    {
        if (!$this->categoryId) {
            $this->slug = Str::slug($value);
        }
    }

    protected function rules()
    {
        return [
            'name' => 'required|min:2',
            'slug' => 'required|unique:categories,slug,' . $this->categoryId,
            'type' => 'required|in:product,post',
            'parent_id' => 'nullable|exists:categories,id',
            'sort_order' => 'integer',
            'newImage' => 'nullable|image|max:2048',
        ];
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'slug' => $this->slug ?: Str::slug($this->name),
            'parent_id' => $this->parent_id ?: null,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'type' => $this->type,
        ];

        if ($this->newImage) {
            $data['image'] = $this->newImage->store('categories', 'public');
        }

        if ($this->categoryId) {
            Category::find($this->categoryId)->update($data);
        } else {
            Category::create($data);
        }

        return redirect()->route('admin.product-categories.index');
    }

    public function render()
    {
        return view('Admin::livewire.categories.category-form');
    }
}
