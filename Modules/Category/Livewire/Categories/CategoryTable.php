<?php

namespace Modules\Category\Livewire\Categories;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Category\Services\CategoryService;
use Modules\Category\Services\CategoryTypeService;

class CategoryTable extends Component
{
    use WithPagination;

    public string $search = '';

    public ?string $type = null;

    public string $status = '';

    public string $sortBy = 'sort_order';

    public string $sortDirection = 'asc';

    public int $perPage = 10;

    public array $perPageOptions = [10, 25, 50, 100];

    public ?int $pendingDeleteId = null;

    protected CategoryService $categoryService;

    protected CategoryTypeService $categoryTypeService;

    public function boot(
        CategoryService $categoryService,
        CategoryTypeService $categoryTypeService
    ): void {
        $this->categoryService = $categoryService;
        $this->categoryTypeService = $categoryTypeService;
    }

    public function mount(): void
    {
        $this->authorizePermission('view_category');
        $this->type = $this->categoryTypeService->firstActiveType();
    }

    public function getTypesProperty()
    {
        return $this->categoryTypeService->listForAdmin(activeOnly: true);
    }

    public function setType(?string $type): void
    {
        $this->authorizePermission('view_category');

        if ($type !== null) {
            $categoryType = $this->categoryTypeService->find($type);

            if (! $categoryType->is_active) {
                throw ValidationException::withMessages([
                    'type' => 'Loại danh mục không hoạt động.',
                ]);
            }
        }

        $this->type = $type;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(string $status): void
    {
        if (! in_array($status, ['', 'active', 'inactive'], true)) {
            $this->status = '';
        }

        $this->resetPage();
    }

    public function updatedPerPage(int $perPage): void
    {
        if (! in_array($perPage, $this->perPageOptions, true)) {
            $this->perPage = 10;
        }

        $this->resetPage();
    }

    public function requestDelete(int $id): void
    {
        $this->authorizePermission('delete_category');
        $this->pendingDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->pendingDeleteId = null;
        $this->resetErrorBag('delete');
    }

    public function confirmDelete(): void
    {
        $this->authorizePermission('delete_category');

        if ($this->pendingDeleteId === null) {
            return;
        }

        $this->categoryService->delete($this->pendingDeleteId);
        $this->pendingDeleteId = null;
        $this->dispatch('notify', content: 'Xóa danh mục thành công', type: 'success');
    }

    public function setActive(int $id, bool $active): void
    {
        $this->authorizePermission('edit_category');
        $this->categoryService->setActive($id, $active);
        $this->dispatch('notify', content: 'Cập nhật trạng thái thành công', type: 'success');
    }

    public function render()
    {
        $this->authorizePermission('view_category');

        return view('Category::livewire.categories.category-table', [
            'categories' => $this->categoryService->paginateForAdmin([
                'search' => $this->search,
                'type' => $this->type,
                'status' => $this->status,
                'sortBy' => $this->sortBy,
                'sortDirection' => $this->sortDirection,
                'perPage' => $this->perPage,
            ]),
        ]);
    }

    private function authorizePermission(string $permission): void
    {
        $user = Auth::guard('admin')->user();

        abort_unless($user, 403);
        Gate::forUser($user)->authorize($permission);
    }
}
