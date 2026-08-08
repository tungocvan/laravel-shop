<?php

namespace Modules\Role\Livewire;

use App\Modules\ModulePermissionManager;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleForm extends Component
{
    public $roleId;
    public $isEdit = false;
    public $name;
    
    // Mảng chứa các quyền được chọn: ['view_product', 'edit_order', ...]
    public $selectedPermissions = []; 
    
    // Dữ liệu hiển thị (Không wire:model)
    public $permissionGroups = [];
    public array $preservedPermissions = [];
 
    public function mount(ModulePermissionManager $modulePermissions, $id = null)
    {
        $groups = $modulePermissions->activeGroups();
        $activeNames = collect($groups)->flatten()->unique()->values();
        $permissionsByName = Permission::query()
            ->where('guard_name', 'admin')
            ->whereIn('name', $activeNames)
            ->get()
            ->keyBy('name');

        foreach ($groups as $module => $permissionNames) {
            $permissions = collect($permissionNames)
                ->map(fn (string $name) => $permissionsByName->get($name))
                ->filter()
                ->values()
                ->all();

            if ($permissions !== []) {
                $this->permissionGroups[$module] = $permissions;
            }
        }
        
        // Sắp xếp nhóm theo alphabet (a-z) cho dễ nhìn
        ksort($this->permissionGroups);

        // 3. Load dữ liệu nếu đang Edit
        if ($id) {
            $this->isEdit = true;
            $this->roleId = $id;
            $role = Role::findOrFail($id);
            $this->name = $role->name;
            // Chỉ lấy mảng tên quyền để mapping vào checkbox
            $assigned = $role->permissions->pluck('name');
            $this->selectedPermissions = $assigned->intersect($activeNames)->values()->all();
            $this->preservedPermissions = $assigned->diff($activeNames)->values()->all();
        }
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|unique:roles,name,' . $this->roleId,
            'selectedPermissions' => 'nullable|array' // Validate mảng quyền
        ]);

        // --- SỬA Ở ĐÂY: Đổi 'web' thành 'admin' ---
        $role = Role::updateOrCreate(
            ['id' => $this->roleId],
            [
                'name' => $this->name, 
                'guard_name' => 'admin' // <--- Bắt buộc dùng guard admin
            ]
        );

        // Khi Role có guard là 'admin', Spatie sẽ tự động tìm các permission 
        // có guard 'admin' tương ứng để gán.
        $role->syncPermissions(array_values(array_unique(array_merge(
            $this->selectedPermissions,
            $this->preservedPermissions,
        ))));

        session()->flash('success', 'Lưu vai trò thành công (Guard: Admin).');
        return redirect()->route('admin.role.index');
    }

    public function render()
    {
        return view('Role::livewire.role-form');
    }
}
