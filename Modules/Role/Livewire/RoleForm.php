<?php

namespace Modules\Role\Livewire;

use App\Modules\ModulePermissionManager;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Modules\Role\Services\RoleService;
use Spatie\Permission\Models\Permission;

class RoleForm extends Component
{
    public $roleId;

    public $isEdit = false;

    public $name;

    public $selectedPermissions = [];

    public $permissionGroups = [];

    public array $preservedPermissions = [];

    public function mount(ModulePermissionManager $modulePermissions, RoleService $roles, $id = null): void
    {
        $this->authorizeCapability($id ? 'edit_role' : 'create_role');

        $groups = $modulePermissions->activeGroups();
        $activeNames = collect($groups)->flatten()->unique()->values();
        $permissionsByName = Permission::query()
            ->where('guard_name', RoleService::ADMIN_GUARD)
            ->whereIn('name', $activeNames)
            ->get()
            ->keyBy('name');

        foreach ($groups as $module => $permissionNames) {
            $permissions = collect($permissionNames)
                ->map(fn (string $permissionName) => $permissionsByName->get($permissionName))
                ->filter()
                ->values()
                ->all();

            if ($permissions !== []) {
                $this->permissionGroups[$module] = $permissions;
            }
        }

        ksort($this->permissionGroups);

        if ($id) {
            $this->isEdit = true;
            $this->roleId = (int) $id;
            $role = $roles->findAdminRole((int) $id);

            abort_if($role->name === RoleService::PROTECTED_ROLE, 403, 'Super Admin không thể chỉnh sửa từ màn hình quản lý vai trò.');

            $this->name = $role->name;
            $assigned = $role->permissions->pluck('name');
            $this->selectedPermissions = $assigned->intersect($activeNames)->values()->all();
            $this->preservedPermissions = $assigned->diff($activeNames)->values()->all();
        }
    }

    public function save(RoleService $roles)
    {
        $this->authorizeCapability($this->isEdit ? 'edit_role' : 'create_role');

        $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->where(fn ($query) => $query->where('guard_name', RoleService::ADMIN_GUARD))
                    ->ignore($this->roleId),
            ],
            'selectedPermissions' => ['nullable', 'array'],
            'selectedPermissions.*' => ['string', 'max:255'],
            'preservedPermissions' => ['array'],
            'preservedPermissions.*' => ['string', 'max:255'],
        ]);

        $roles->save(
            $this->roleId ? (int) $this->roleId : null,
            (string) $this->name,
            $this->selectedPermissions,
            $this->preservedPermissions,
        );

        session()->flash('success', 'Lưu vai trò thành công (Guard: Admin).');

        return redirect()->route('admin.role.index');
    }

    public function render()
    {
        return view('Role::livewire.role-form');
    }

    private function authorizeCapability(string $permission): void
    {
        $actor = auth('admin')->user();

        abort_unless(
            auth('admin')->check() && $actor?->can($permission),
            403
        );
    }
}
