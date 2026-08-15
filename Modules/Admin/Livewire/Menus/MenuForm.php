<?php

namespace Modules\Admin\Livewire\Menus;

use Livewire\Component;
use Modules\Admin\Services\MenuService;

class MenuForm extends Component
{
    protected MenuService $menuService;

    public int|string|null $menuId = null;
    public bool $isEdit = false;
    public string $name = '';
    public ?string $url = null;
    public ?string $icon = null;
    public ?string $can = null;
    public int|string|null $parent_id = null;
    public bool $is_active = true;
    public bool $is_section = false;

    public function boot(MenuService $menuService): void
    {
        $this->menuService = $menuService;
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'url' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:100',
            'can' => 'nullable|string|max:255',
            'parent_id' => 'nullable|integer',
            'is_active' => 'boolean',
            'is_section' => 'boolean',
        ];
    }

    public function mount(int|string|null $id = null): void
    {
        if ($id === null) {
            return;
        }

        $menu = $this->menuService->findForForm($id);
        $this->isEdit = true;
        $this->menuId = $menu->getKey();
        $this->name = (string) $menu->name;
        $this->url = $menu->url;
        $this->icon = $menu->icon;
        $this->can = $menu->can;
        $this->parent_id = $menu->parent_id;
        $this->is_active = (bool) $menu->is_active;
        $this->is_section = empty($menu->url);
    }

    public function updatedIsSection(bool $value): void
    {
        if ($value) {
            $this->url = null;
        }
    }

    public function save()
    {
        $this->authorizePermission($this->isEdit ? 'admin.menu.update' : 'admin.menu.create');
        $validated = $this->validate();

        try {
            $this->menuService->saveForm($validated, $this->menuId);
            session()->flash('success', 'Đã lưu thông tin menu.');

            return redirect()->route('admin.menus.index');
        } catch (\Throwable $exception) {
            report($exception);
            session()->flash('error', 'Không thể lưu menu. Vui lòng thử lại hoặc kiểm tra log hệ thống.');

            return null;
        }
    }

    public function render()
    {
        return view('Admin::livewire.menus.menu-form', [
            'parents' => $this->buildTreeOptions($this->menuService->parentOptions($this->menuId)),
            'permissions' => $this->menuService->permissionOptions(),
        ]);
    }

    private function buildTreeOptions(iterable $menus, int|string|null $parentId = null, string $prefix = ''): array
    {
        $result = [];

        foreach ($menus as $menu) {
            if ((string) $menu->parent_id !== (string) $parentId) {
                continue;
            }

            $result[] = ['id' => $menu->getKey(), 'name' => $prefix.$menu->name];
            $result = array_merge($result, $this->buildTreeOptions($menus, $menu->getKey(), $prefix.'-- '));
        }

        return $result;
    }

    private function authorizePermission(string $permission): void
    {
        $user = auth('admin')->user() ?: auth()->user();
        abort_unless($user?->can($permission), 403);
    }
}
