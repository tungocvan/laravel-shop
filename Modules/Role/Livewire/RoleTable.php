<?php

namespace Modules\Role\Livewire;

use App\Modules\ModulePermissionManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Role\Services\RoleService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleTable extends Component
{
    use WithPagination;

    public $search = '';

    public $perPage = 10;

    public $selected = [];

    public $selectAll = false;

    public $showPermissionModal = false;

    public $newModuleName = '';

    public $newModuleActions = [
        'view' => true,
        'create' => true,
        'edit' => true,
        'delete' => true,
        'export' => false,
    ];

    public function mount(): void
    {
        $this->authorizeCapability('view_role');
    }

    public function openPermissionModal(): void
    {
        $this->authorizeSuperAdmin();
        $this->reset(['newModuleName']);
        $this->newModuleActions = [
            'view' => true,
            'create' => true,
            'edit' => true,
            'delete' => true,
            'export' => false,
        ];
        $this->showPermissionModal = true;
    }

    public function createModulePermissions(ModulePermissionManager $modulePermissions): void
    {
        $this->authorizeSuperAdmin();

        $this->validate([
            'newModuleName' => ['required', 'alpha_dash', 'min:2'],
            'newModuleActions' => ['array'],
            'newModuleActions.*' => ['boolean'],
        ], [
            'newModuleName.required' => 'Vui lòng nhập tên Module.',
            'newModuleName.alpha_dash' => 'Tên module không được chứa khoảng trắng hoặc ký tự đặc biệt.',
        ]);

        $module = Str::lower((string) $this->newModuleName);
        $groups = collect($modulePermissions->activeGroups());
        $group = $groups->first(function (array $permissions, string $moduleName) use ($module): bool {
            return Str::lower($moduleName) === $module;
        });

        if (! is_array($group)) {
            $this->addError('newModuleName', 'Module này không tồn tại trong catalog module đang hoạt động.');
            return;
        }

        $requested = collect($this->newModuleActions)
            ->filter(fn (bool $selected): bool => $selected)
            ->keys()
            ->map(fn (string $action): string => $action.'_'.$module)
            ->values();

        $approved = $requested->intersect($group)->values();

        if ($approved->count() !== $requested->count()) {
            $this->addError('newModuleActions', 'Một hoặc nhiều quyền được chọn không được module khai báo trong catalog.');
            return;
        }

        $createdCount = 0;

        DB::transaction(function () use ($approved, &$createdCount): void {
            foreach ($approved as $permissionName) {
                $permission = Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => RoleService::ADMIN_GUARD,
                ]);

                if ($permission->wasRecentlyCreated) {
                    $createdCount++;
                }
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->showPermissionModal = false;

        $this->dispatch(
            'notify',
            content: $createdCount > 0
                ? "Đã đồng bộ {$createdCount} quyền được module '{$module}' khai báo."
                : "Các quyền được module '{$module}' khai báo đã tồn tại.",
            type: $createdCount > 0 ? 'success' : 'warning'
        );
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
        $this->resetSelection();
    }

    public function updatedSelectAll(bool $value): void
    {
        $roles = app(RoleService::class);

        $this->selected = $value
            ? $roles->queryRoles((string) $this->search)
                ->where('name', '!=', RoleService::PROTECTED_ROLE)
                ->paginate((int) $this->perPage)
                ->pluck('id')
                ->map(fn (int $id): string => (string) $id)
                ->all()
            : [];
    }

    public function resetSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    public function deleteSelected(RoleService $roles): void
    {
        $this->authorizeCapability('delete_role');

        $result = $roles->deleteMany(array_map('intval', $this->selected));
        $this->resetSelection();

        if ($result['deleted'] > 0 && $result['blocked'] > 0) {
            $this->dispatch('notify', content: "Đã xóa {$result['deleted']} vai trò. {$result['blocked']} vai trò bị chặn vì là Super Admin hoặc đang có tài khoản sử dụng.", type: 'warning');
            return;
        }

        if ($result['deleted'] > 0) {
            $this->dispatch('notify', content: "Đã xóa {$result['deleted']} vai trò.", type: 'success');
            return;
        }

        $this->dispatch('notify', content: 'Không thể xóa vai trò đang có tài khoản sử dụng hoặc vai trò Super Admin.', type: 'error');
    }

    public function delete($id, RoleService $roles): void
    {
        $this->authorizeCapability('delete_role');

        $result = $roles->delete((int) $id);

        if ($result === 'protected') {
            $this->dispatch('notify', content: 'Không thể xóa Super Admin!', type: 'error');
            return;
        }

        if ($result === 'in_use') {
            $this->dispatch('notify', content: 'Không thể xóa vai trò vì đang có tài khoản sử dụng.', type: 'error');
            return;
        }

        $this->dispatch('notify', content: 'Đã xóa vai trò.', type: 'success');
    }

    public function render(RoleService $roles)
    {
        $this->authorizeCapability('view_role');

        return view('Role::livewire.role-table', [
            'roles' => $roles->queryRoles((string) $this->search)->paginate((int) $this->perPage),
        ]);
    }

    private function authorizeCapability(string $permission): void
    {
        $actor = auth('admin')->user();

        abort_unless(auth('admin')->check() && $actor?->can($permission), 403);
    }

    private function authorizeSuperAdmin(): void
    {
        $actor = auth('admin')->user();

        abort_unless(
            auth('admin')->check() && $actor?->hasRole(RoleService::PROTECTED_ROLE),
            403
        );
    }
}
