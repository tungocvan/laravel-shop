<?php

namespace Modules\Admin\Livewire\Menus;

use Illuminate\Support\Facades\Cache;
// use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;
use Modules\Admin\Models\AdminMenu;
use Spatie\Permission\Models\Permission;

class MenuForm extends Component
{
    public $menuId;
    public $isEdit = false;

    public $name, $url, $icon, $can;
    public $parent_id = null;
    public $is_active = true;
    public $is_section = false;
    private const CACHE_KEY = 'admin.menus';

    public function getParentsProperty()
    {
        $query = AdminMenu::menu();

        if ($this->menuId) {
            $query->where('id', '!=', $this->menuId);
        }

        $menus = $query->orderBy('sort_order')->orderBy('name')->get();

        return $this->buildTreeOption($menus);
    }

    protected function buildTreeOption($menus, $parentId = null, $prefix = '')
    {
        $result = [];

        foreach ($menus as $menu) {
            if ((string) $menu->parent_id === (string) $parentId) {
                $menu->view_name = $prefix . $menu->name;
                $result[] = $menu;

                $children = $this->buildTreeOption($menus, $menu->getKey(), $prefix . '-- ');
                $result = array_merge($result, $children);
            }
        }

        return $result;
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'url' => 'nullable|string|max:500',
            'icon' => 'nullable|string|max:100',
            'can' => 'nullable|string|max:255', // Bỏ exists check để tránh lỗi khi permission bị xóa
            'parent_id' => 'nullable|integer', // Bỏ exists check, sẽ check trong code nếu cần
            'is_active' => 'boolean',
            'is_section' => 'boolean',
        ];
    }

    public function mount($id = null)
    {
        
        if ($id) {
            $this->isEdit = true;
            $this->menuId = $id;
            $menu = AdminMenu::findOrFail($id);

            $this->name = $menu->name;
            $this->url = $menu->url;
            $this->icon = $menu->icon;
            $this->can = $menu->can;
            $this->parent_id = $menu->parent_id;
            $this->is_active = (bool)$menu->is_active;

            // Logic nhận diện section
            if (empty($menu->url) && $menu->children->count() > 0 && empty($menu->parent_id)) {
                 // Đây là logic tương đối, bạn có thể tick thủ công
            }
             // Nếu user đã chủ động set url null khi tạo
            $this->is_section = empty($menu->url);
        }
    }

    public function updatedIsSection($val)
    {
        if ($val) {
            $this->url = null;
        }
        
    }

    public function save()
    {
        $this->authorizePermission($this->isEdit ? 'admin.menu.update' : 'admin.menu.create');

        $this->validate();

        // Log::info('MenuForm save validation passed', [
        //     'menuId' => $this->menuId,
        //     'name' => $this->name,
        //     'isEdit' => $this->isEdit,
        // ]);
        
        try {
            // Kiểm tra parent_id hợp lệ
            if ($this->parent_id && ! AdminMenu::where('id', $this->parent_id)->exists()) {
                $this->parent_id = null;
            }

            $data = [
                'name' => $this->name,
                'url' => $this->is_section ? null : ($this->url ?: null),
                'icon' => $this->icon ?: null,
                'can' => $this->can ?: null,
                'parent_id' => $this->parent_id ?: null,
                'is_active' => (bool) $this->is_active,
            ];
           
            // Tạo hoặc cập nhật slug nếu cần
            if (! $this->isEdit || ($this->isEdit && $this->name !== AdminMenu::find($this->menuId)->name)) {
                $data['slug'] = $this->generateUniqueSlug($this->name, $this->menuId);
            }

            if (!$this->isEdit) {
                $data['sort_order'] = ((int) AdminMenu::menu()->max('sort_order')) + 1;
            }

            // Log::info('MenuForm save data prepared', ['data' => $data]);

            $menu = AdminMenu::updateOrCreate(
                ['id' => $this->menuId],
                $data
            );
            Cache::forget(self::CACHE_KEY);
            //Cache::forget(config('menu.cache.key', 'admin.menus'));

            // Log::info('Menu saved', [
            //     'menu_id' => $menu->getKey(),
            //     'menu_name' => $menu->name,
            //     'action' => $this->isEdit ? 'updated' : 'created',
            //     'user_id' => auth()->id(),
            // ]);

            session()->flash('success', 'Đã lưu thông tin menu.');

            return redirect()->route('admin.menus.index');
        } catch (\Throwable $e) {
            // Log::error('Menu save failed', [
            //     'error' => $e->getMessage(),
            //     'user_id' => auth()->id(),
            //     'menu_id' => $this->menuId,
            //     'trace' => $e->getTraceAsString(),
            //     'payload' => [
            //         'name' => $this->name,
            //         'url' => $this->url,
            //         'icon' => $this->icon,
            //         'can' => $this->can,
            //         'parent_id' => $this->parent_id,
            //         'is_active' => $this->is_active,
            //         'is_section' => $this->is_section,
            //     ],
            // ]);

            session()->flash('error', 'Lỗi khi lưu menu: ' . $e->getMessage());
        }
    }

    private function generateUniqueSlug(string $name, $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        $query = AdminMenu::where('slug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
            $query = AdminMenu::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $slug;
    }

    public function render()
    {
        return view('Admin::livewire.menus.menu-form', [
            'permissions' => Permission::query()->orderBy('name')->get(),
        ]);
    }

    private function authorizePermission(string $permission): void
    {
        $user = auth('admin')->user() ?: auth()->user();

        abort_unless($user?->can($permission), 403);
    }
}
