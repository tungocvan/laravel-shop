<?php

namespace Modules\Admin\Livewire\Products;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Modules\Website\Models\WpProduct;
use Modules\Website\Models\Category;
use Modules\Admin\Exports\ProductsExport;
use Modules\Admin\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class ProductTable extends Component
{
    use WithPagination, WithFileUploads;

    // --- 1. FILTERS & PAGINATION ---
    public $search = '';
    public $category_id = '';
    public $perPage = 10;

    // --- 2. SORTING ---
    public $sortColumn = 'created_at';
    public $sortDirection = 'desc';

    // --- 3. BULK ACTIONS ---
    public $selected = [];
    public $selectAll = false;

    // --- 4. MODALS STATE ---
    public $importFile;
    public $showImportModal = false;

    public $showCategoryModal = false;
    public $bulkCategoryIds = [];

    // ==========================================
    // COMPUTED PROPERTIES
    // ==========================================
    public function getCategoriesProperty()
    {
        // Lấy toàn bộ danh mục sản phẩm
        $categories = Category::where('type', 'product')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Chuyển đổi thành dạng cây phẳng (Flat Tree) để hiển thị trong Select
        return collect($this->buildTreeOption($categories));
    }

    // 2. THÊM HÀM ĐỆ QUY TẠO VIEW NAME
    private function buildTreeOption($categories, $parentId = null, $prefix = '')
    {
        $result = [];

        foreach ($categories as $category) {
            if ($category->parent_id == $parentId) {
                // Tạo tên hiển thị có gạch đầu dòng
                // Ví dụ: "Điện tử", "-- Laptop", "---- Gaming"
                $category->view_name = $prefix . $category->name;

                $result[] = $category;

                // Gọi đệ quy cho con (thêm prefix dài hơn)
                $children = $this->buildTreeOption($categories, $category->id, $prefix . '— '); // Dùng dấu gạch dài em-dash cho đẹp
                $result = array_merge($result, $children);
            }
        }
        return $result;
    }

    // ==========================================
    // LIFECYCLE & UPDATES
    // ==========================================
    public function updatedSearch() { $this->resetPage(); }
    public function updatedCategoryId() { $this->resetPage(); }
    public function updatedPerPage() { $this->resetPage(); }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selected = $this->getProductsQuery()->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selected = [];
        }
    }

    // ==========================================
    // ACTIONS: FILTERS
    // ==========================================
    public function clearSearch()
    {
        $this->search = '';
        $this->resetPage();
    }

    public function clearCategory()
    {
        $this->category_id = '';
        $this->resetPage();
    }

    public function sortBy($column)
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }
    }

    // ==========================================
    // ACTIONS: SINGLE ROW
    // ==========================================
    public function toggleStatus($id)
    {
        $product = WpProduct::find($id);
        if ($product) {
            $product->is_active = !$product->is_active;
            $product->save();
        }
    }

    public function duplicate($id)
    {
        $product = WpProduct::with('categories')->find($id);
        if ($product) {
            $newProduct = $product->replicate();
            $newProduct->title = $product->title . ' (Copy)';
            $newProduct->slug = Str::slug($newProduct->title) . '-' . time();
            $newProduct->is_active = false;
            $newProduct->created_at = now();
            $newProduct->save();

            // Copy quan hệ category
            $newProduct->categories()->sync($product->categories->pluck('id'));
        }
    }

    public function delete($id)
    {
        WpProduct::destroy($id);
    }

    // ==========================================
    // ACTIONS: BULK (HÀNG LOẠT)
    // ==========================================
    public function deleteSelected()
    {
        WpProduct::whereIn('id', $this->selected)->delete();
        $this->reset(['selected', 'selectAll']);
    }

    public function openCategoryModal()
    {
        $this->reset('bulkCategoryIds');
        $this->showCategoryModal = true;
    }

    public function applyCategories()
    {
        $this->validate([
            'bulkCategoryIds' => 'required|array|min:1',
        ], ['bulkCategoryIds.required' => 'Vui lòng chọn ít nhất 1 danh mục.']);

        if (!empty($this->selected)) {
            $products = WpProduct::whereIn('id', $this->selected)->get();
            foreach ($products as $product) {
                // Thêm mới mà không xóa danh mục cũ
                $product->categories()->syncWithoutDetaching($this->bulkCategoryIds);
            }
        }

        $this->showCategoryModal = false;
        $this->reset(['selected', 'selectAll', 'bulkCategoryIds']);
    }

    // ==========================================
    // ACTIONS: IMPORT / EXPORT
    // ==========================================
    public function export()
    {
        $ids = !empty($this->selected) ? $this->selected : null;
        return Excel::download(new ProductsExport($ids), 'products_' . date('Y-m-d') . '.xlsx');
    }

    public function import()
    {
        $this->validate([
            'importFile' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        Excel::import(new ProductsImport, $this->importFile);

        $this->showImportModal = false;
        $this->importFile = null;
    }

    // ==========================================
    // RENDER
    // ==========================================
    private function getProductsQuery()
    {
        $query = WpProduct::with('categories')
            ->where('title', 'like', '%' . $this->search . '%')
            ->orderBy($this->sortColumn, $this->sortDirection);

        if (!empty($this->category_id)) {
            $query->whereHas('categories', function($q) {
                $q->where('id', $this->category_id);
            });
        }

        return $query;
    }

    // --- ACTION: GỠ DANH MỤC KHỎI SẢN PHẨM ---
    public function removeCategory($productId, $categoryId)
    {
        $product = WpProduct::find($productId);

        if ($product) {
            // detach: Chỉ gỡ mối quan hệ, không xóa danh mục gốc
            $product->categories()->detach($categoryId);
        }
    }

    public function render()
    {
        $query = $this->getProductsQuery();

        $products = $this->perPage === 'all'
            ? $query->paginate(999999)
            : $query->paginate($this->perPage);

        return view('Admin::livewire.products.product-table', [
            'products' => $products
        ]);
    }
}
