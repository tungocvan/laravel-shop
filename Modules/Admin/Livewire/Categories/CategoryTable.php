<?php

namespace Modules\Admin\Livewire\Categories;

use Livewire\Component;
use Modules\Website\Models\Category;
use Illuminate\Support\Facades\Storage;

class CategoryTable extends Component
{
    public $type = 'product'; // Mặc định là Product

    // Chuyển Tab (Product <-> Post)
    public function setType($type)
    {
        $this->type = $type;
    }

    public function delete($id)
    {
        $category = Category::find($id);
        if ($category) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            // Lưu ý: Cần xử lý logic con cái (Xóa con hoặc set parent_id = null)
            // Ở đây ta dùng delete() mặc định
            $category->delete();
        }
    }

    public function toggleStatus($id)
    {
        $category = Category::find($id);
        if($category){
            $category->is_active = !$category->is_active;
            $category->save();
        }
    }

    public function render()
    {
        // Load danh mục theo TYPE và cấu trúc 3 cấp (Cha -> Con -> Cháu)
        $categories = Category::where('type', $this->type)
            ->whereNull('parent_id')
            ->with(['children' => function($q) {
                $q->orderBy('sort_order', 'asc')
                  ->with(['children' => function($q2) { // Cấp 3
                      $q2->orderBy('sort_order', 'asc');
                  }]);
            }])
            ->withCount('products') // Đếm sản phẩm (nếu có relation)
            ->orderBy('sort_order', 'asc')
            ->get();

        return view('Admin::livewire.categories.category-table', [
            'categories' => $categories
        ]);
    }
}
