<?php

namespace Modules\Category\Livewire\Categories;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Category\Services\CategoryService;
use Modules\Category\Services\CategoryTypeService;

class CategoryForm extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?int $categoryId = null;

    public string $name = '';

    public ?string $slug = null;

    public ?string $type = null;

    public ?int $parent_id = null;

    public int $sort_order = 0;

    public bool $is_active = true;

    public $newImage = null;

    #[Locked]
    public ?string $oldImage = null;

    public bool $showTypeModal = false;

    public ?string $selectedType = null;

    public string $editTitle = '';

    public ?string $editIcon = null;

    public bool $editActive = true;

    public string $newType = '';

    public string $newTypeTitle = '';

    public ?string $newTypeIcon = null;

    public bool $confirmingTypeDelete = false;

    protected CategoryService $categoryService;

    protected CategoryTypeService $categoryTypeService;

    public function boot(
        CategoryService $categoryService,
        CategoryTypeService $categoryTypeService
    ): void {
        $this->categoryService = $categoryService;
        $this->categoryTypeService = $categoryTypeService;
    }

    public function mount(?int $id = null): void
    {
        if ($id !== null) {
            $this->authorizePermission('edit_category');
            $category = $this->categoryService->findForEdit($id);

            $this->fill([
                'categoryId' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'type' => $category->type,
                'parent_id' => $category->parent_id,
                'sort_order' => $category->sort_order,
                'is_active' => $category->is_active,
                'oldImage' => $category->image,
            ]);

            return;
        }

        $this->authorizePermission('create_category');
        $this->type = $this->categoryTypeService->firstActiveType();
    }

    public function getTypesProperty()
    {
        return $this->categoryTypeService->listForAdmin();
    }

    public function getParentsProperty(): array
    {
        if ($this->type === null) {
            return [];
        }

        return $this->categoryService->parentOptions($this->type, $this->categoryId);
    }

    public function openTypeModal(): void
    {
        $this->authorizePermission('view_category');
        $this->resetTypeForm();
        $this->showTypeModal = true;
    }

    public function updatedSelectedType(?string $value): void
    {
        $this->confirmingTypeDelete = false;

        if (! $value) {
            $this->reset(['editTitle', 'editIcon']);
            $this->editActive = true;

            return;
        }

        $categoryType = $this->categoryTypeService->find($value);
        $this->editTitle = $categoryType->title;
        $this->editIcon = $categoryType->icon;
        $this->editActive = $categoryType->is_active;
    }

    public function createType(): void
    {
        $this->authorizePermission('create_category');

        $validated = $this->validate([
            'newType' => ['required', 'alpha_dash', 'max:255', 'unique:category_types,type'],
            'newTypeTitle' => ['required', 'string', 'min:2', 'max:255'],
            'newTypeIcon' => ['nullable', 'string', 'max:255'],
        ]);

        $categoryType = $this->categoryTypeService->create([
            'type' => $validated['newType'],
            'title' => $validated['newTypeTitle'],
            'icon' => $validated['newTypeIcon'],
        ]);

        $this->type = $categoryType->type;
        $this->showTypeModal = false;
        $this->dispatch('notify', content: 'Tạo loại danh mục thành công', type: 'success');
    }

    public function updateType(): void
    {
        $this->authorizePermission('edit_category');

        $validated = $this->validate([
            'selectedType' => ['required', 'string', 'exists:category_types,type'],
            'editTitle' => ['required', 'string', 'min:2', 'max:255'],
            'editIcon' => ['nullable', 'string', 'max:255'],
            'editActive' => ['required', 'boolean'],
        ]);

        $this->categoryTypeService->update($validated['selectedType'], [
            'title' => $validated['editTitle'],
            'icon' => $validated['editIcon'],
            'is_active' => $validated['editActive'],
        ]);

        $this->dispatch('notify', content: 'Cập nhật loại thành công', type: 'success');
    }

    public function requestTypeDelete(): void
    {
        $this->authorizePermission('delete_category');

        $this->validate([
            'selectedType' => ['required', 'string', 'exists:category_types,type'],
        ]);

        $this->confirmingTypeDelete = true;
    }

    public function cancelTypeDelete(): void
    {
        $this->confirmingTypeDelete = false;
    }

    public function confirmTypeDelete(): void
    {
        $this->authorizePermission('delete_category');

        $validated = $this->validate([
            'selectedType' => ['required', 'string', 'exists:category_types,type'],
        ]);

        $this->categoryTypeService->delete($validated['selectedType']);
        $this->selectedType = null;
        $this->confirmingTypeDelete = false;
        $this->dispatch('notify', content: 'Xóa loại thành công', type: 'success');
    }

    public function updatedName(string $name): void
    {
        if ($this->categoryId === null) {
            $this->slug = Str::slug($name);
        }
    }

    public function updatedType(): void
    {
        $this->parent_id = null;
    }

    public function save()
    {
        $permission = $this->categoryId === null ? 'create_category' : 'edit_category';
        $this->authorizePermission($permission);
        $this->slug = $this->categoryService->normalizeSlug($this->slug, $this->name);

        $validated = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('categories', 'slug')->ignore($this->categoryId),
            ],
            'type' => ['required', 'string', 'exists:category_types,type'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'is_active' => ['required', 'boolean'],
            'newImage' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $data = [
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'type' => $validated['type'],
            'parent_id' => $validated['parent_id'],
            'sort_order' => $validated['sort_order'],
            'is_active' => $validated['is_active'],
            'newImage' => $validated['newImage'] ?? null,
        ];

        if ($this->categoryId === null) {
            $this->categoryService->create($data);
        } else {
            $this->categoryService->update($this->categoryId, $data);
        }

        return redirect()->route('admin.category.index');
    }

    public function render()
    {
        return view('Category::livewire.categories.category-form');
    }

    private function resetTypeForm(): void
    {
        $this->reset([
            'selectedType',
            'editTitle',
            'editIcon',
            'newType',
            'newTypeTitle',
            'newTypeIcon',
            'confirmingTypeDelete',
        ]);
        $this->editActive = true;
    }

    private function authorizePermission(string $permission): void
    {
        $user = Auth::guard('admin')->user();

        abort_unless($user, 403);
        Gate::forUser($user)->authorize($permission);
    }
}
