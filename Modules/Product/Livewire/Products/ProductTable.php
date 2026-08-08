<?php

namespace Modules\Product\Livewire\Products;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Product\Exports\ProductsExport;
use Modules\Product\Imports\ProductsImport;
use Modules\Product\Services\ProductService;

class ProductTable extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $search = '';
    public $category_id = '';
    public $perPage = 10;

    public $sortColumn = 'created_at';
    public $sortDirection = 'desc';

    public $selected = [];
    public $selectAll = false;

    public $importFile;
    public $showImportModal = false;

    public $showCategoryModal = false;
    public $bulkCategoryIds = [];

    protected ProductService $products;

    public function boot(ProductService $products): void
    {
        $this->products = $products;
    }

    public function getCategoriesProperty()
    {
        return $this->products->flatProductCategoryOptions();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedCategoryId(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedPerPage(): void
    {
        $this->perPage = $this->products->normalizePerPage($this->perPage);
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedSelectAll($value): void
    {
        $this->selected = $value ? $this->products->currentPageIds($this->filters()) : [];
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
        $this->resetSelection();
    }

    public function clearCategory(): void
    {
        $this->category_id = '';
        $this->resetPage();
        $this->resetSelection();
    }

    public function sortBy($column): void
    {
        $column = $this->products->normalizeSortColumn((string) $column);

        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetSelection();
    }

    public function toggleStatus($id): void
    {
        $this->authorizeAdmin('edit_product');
        $this->products->toggleStatus((int) $id);
    }

    public function duplicate($id): void
    {
        $this->authorizeAdmin('create_product');
        $this->products->duplicate((int) $id);
    }

    public function delete($id): void
    {
        $this->authorizeAdmin('delete_product');
        $this->products->delete((int) $id);
        $this->resetSelection();
    }

    public function deleteSelected(): void
    {
        $this->authorizeAdmin('delete_product');
        $this->products->deleteMany($this->selected);
        $this->resetSelection();
    }

    public function openCategoryModal(): void
    {
        $this->authorizeAdmin('edit_product');
        $this->reset('bulkCategoryIds');
        $this->showCategoryModal = true;
    }

    public function applyCategories(): void
    {
        $this->authorizeAdmin('edit_product');
        $this->validate([
            'selected' => 'required|array|min:1',
            'selected.*' => 'integer',
            'bulkCategoryIds' => 'required|array|min:1',
            'bulkCategoryIds.*' => 'integer',
        ], [
            'selected.required' => 'Vui lòng chọn ít nhất 1 sản phẩm.',
            'bulkCategoryIds.required' => 'Vui lòng chọn ít nhất 1 danh mục.',
        ]);

        $this->products->addCategoriesToProducts($this->selected, $this->bulkCategoryIds);
        $this->showCategoryModal = false;
        $this->reset(['bulkCategoryIds']);
        $this->resetSelection();
    }

    public function removeCategory($productId, $categoryId): void
    {
        $this->authorizeAdmin('edit_product');
        $this->products->removeCategory((int) $productId, (int) $categoryId);
    }

    public function export()
    {
        $this->authorizeAdmin('view_product');

        $ids = $this->selected !== [] ? $this->selected : null;

        return Excel::download(new ProductsExport($ids), 'products_'.date('Y-m-d').'.xlsx');
    }

    public function import(): void
    {
        $this->authorizeAdmin('create_product');
        $this->validate([
            'importFile' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        Excel::import(new ProductsImport, $this->importFile);

        $this->showImportModal = false;
        $this->importFile = null;
    }

    public function render()
    {
        return view('product::livewire.products.product-table', [
            'products' => $this->products->paginateForAdmin($this->filters()),
        ]);
    }

    private function filters(): array
    {
        return [
            'search' => $this->search,
            'category_id' => $this->category_id,
            'perPage' => $this->perPage,
            'sortColumn' => $this->sortColumn,
            'sortDirection' => $this->sortDirection,
        ];
    }

    private function resetSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    private function authorizeAdmin(string $permission): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can($permission), 403);
    }
}
