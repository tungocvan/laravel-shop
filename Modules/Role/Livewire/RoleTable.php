<?php

namespace Modules\Role\Livewire;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleTable extends Component
{
    use WithPagination;

    public $search = '';

    public $perPage = 10;

    public $selected = [];

    public $selectAll = false;

    // --- VARIABLES CHO ADD MODULE ---
    public $showPermissionModal = false;

    public $newModuleName = '';

    public $newModuleActions = [
        'view' => true,
        'create' => true,
        'edit' => true,
        'delete' => true,
        'export' => false, // Mặc định tắt
    ];
    // --- LOGIC MỚI: TẠO MODULE QUYỀN ---

    public function openPermissionModal()
    {
        $this->reset(['newModuleName']);
        $this->newModuleActions = [
            'view' => true, 'create' => true, 'edit' => true, 'delete' => true, 'export' => false,
        ];
        $this->showPermissionModal = true;
    }

    public function createModulePermissions()
    {
        $this->validate([
            'newModuleName' => 'required|alpha_dash|min:3', // Chỉ cho phép chữ cái, số, gạch ngang
        ], [
            'newModuleName.required' => 'Vui lòng nhập tên Module (VD: blog, marketing)',
            'newModuleName.alpha_dash' => 'Tên module không được chứa khoảng trắng hoặc ký tự đặc biệt.',
        ]);

        // Chuẩn hóa tên module: blog_post -> blog_post
        $module = Str::lower($this->newModuleName);
        $guard = 'admin'; // Cố định guard admin
        $createdCount = 0;

        DB::transaction(function () use ($module, $guard, &$createdCount) {
            foreach ($this->newModuleActions as $action => $isSelected) {
                if ($isSelected) {
                    // Tạo quyền: action_module (VD: view_blog)
                    $permName = $action.'_'.$module;

                    $perm = Permission::firstOrCreate(
                        ['name' => $permName, 'guard_name' => $guard]
                    );

                    if ($perm->wasRecentlyCreated) {
                        $createdCount++;
                    }
                }
            }
        });

        // Xóa cache của Spatie để hệ thống nhận diện quyền mới ngay lập tức
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->showPermissionModal = false;

        if ($createdCount > 0) {
            $this->dispatch('notify', content: "Đã tạo {$createdCount} quyền mới cho module '{$module}'.", type: 'success');
        } else {
            $this->dispatch('notify', content: "Các quyền của module '{$module}' đã tồn tại từ trước.", type: 'warning');
        }
    }
    // Reset & Select logic (Giống CustomerTable - Tôi lược bỏ cho ngắn gọn, bạn copy từ CustomerTable sang nhé)
    // ... include: updatedSearch, updatingPage, updatedSelectAll, resetSelection ...

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
        $this->selected = $value
            ? $this->queryRoles()
                ->where('name', '!=', 'Super Admin')
                ->paginate($this->perPage)
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

    public function deleteSelected()
    {
        $roles = Role::withCount('users')
            ->whereIn('id', $this->selected)
            ->get();

        $deletedCount = 0;
        $blockedCount = 0;

        foreach ($roles as $role) {
            if ($role->name === 'Super Admin' || $role->users_count > 0) {
                $blockedCount++;

                continue;
            }

            $role->delete();
            $deletedCount++;
        }

        $this->resetSelection();

        if ($deletedCount > 0 && $blockedCount > 0) {
            $this->dispatch('notify', content: "Đã xóa {$deletedCount} vai trò. {$blockedCount} vai trò bị chặn vì là Super Admin hoặc đang có tài khoản sử dụng.", type: 'warning');

            return;
        }

        if ($deletedCount > 0) {
            $this->dispatch('notify', content: "Đã xóa {$deletedCount} vai trò.", type: 'success');

            return;
        }

        $this->dispatch('notify', content: 'Không thể xóa vai trò đang có tài khoản sử dụng hoặc vai trò Super Admin.', type: 'error');
    }

    public function delete($id)
    {
        $role = Role::withCount('users')->find($id);

        if (! $role) {
            $this->dispatch('notify', content: 'Không tìm thấy vai trò.', type: 'error');

            return;
        }

        if ($role->name === 'Super Admin') {
            $this->dispatch('notify', content: 'Không thể xóa Super Admin!', type: 'error');

            return;
        }

        if ($role->users_count > 0) {
            $this->dispatch('notify', content: "Không thể xóa vai trò '{$role->name}' vì đang có {$role->users_count} tài khoản sử dụng.", type: 'error');

            return;
        }

        $role->delete();
        $this->dispatch('notify', content: 'Đã xóa vai trò.', type: 'success');
    }

    public function render()
    {
        $roles = $this->queryRoles()->paginate($this->perPage);

        return view('Role::livewire.role-table', ['roles' => $roles]);
    }

    private function queryRoles()
    {
        return Role::withCount('users')
            ->where('name', 'like', '%'.$this->search.'%')
            ->latest();
    }
}
