<?php

namespace Modules\Admin\Livewire\System;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleTable extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $perPage = 10;
    public $selected = [];
    public $selectAll = false;
    
    public $showImportModal = false;
    public $importFile;

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
            'view' => true, 'create' => true, 'edit' => true, 'delete' => true, 'export' => false
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
                    $permName = $action . '_' . $module;
                    
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
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->showPermissionModal = false;
        
        if ($createdCount > 0) {
            $this->dispatch('notify', content: "Đã tạo {$createdCount} quyền mới cho module '{$module}'.", type: 'success');
        } else {
            $this->dispatch('notify', content: "Các quyền của module '{$module}' đã tồn tại từ trước.", type: 'warning');
        }
    }
    // Reset & Select logic (Giống CustomerTable - Tôi lược bỏ cho ngắn gọn, bạn copy từ CustomerTable sang nhé)
    // ... include: updatedSearch, updatingPage, updatedSelectAll, resetSelection ...

    public function deleteSelected()
    {
        // Chặn xóa Super Admin
        $roles = Role::whereIn('id', $this->selected)->get();
        foreach($roles as $role) {
            if($role->name !== 'Super Admin') $role->delete();
        }
        $this->resetSelection();
        $this->dispatch('notify', content: 'Đã xóa vai trò (trừ Super Admin).', type: 'success');
    }

    public function delete($id)
    {
        $role = Role::find($id);
        if($role->name === 'Super Admin') {
            $this->dispatch('notify', content: 'Không thể xóa Super Admin!', type: 'error');
            return;
        }
        $role->delete();
        $this->dispatch('notify', content: 'Đã xóa vai trò.', type: 'success');
    }

    // --- IMPORT / EXPORT JSON ---
    public function export()
    {
        $roles = Role::with('permissions')->get()->map(function($role) {
            return [
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions->pluck('name')->toArray()
            ];
        });

        $fileName = 'roles-export-' . date('Y-m-d') . '.json';
        return response()->streamDownload(function () use ($roles) {
            echo $roles->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $fileName);
    }

    public function import()
    {
        $this->validate(['importFile' => 'required|mimes:json,txt']);
        
        $json = json_decode(file_get_contents($this->importFile->getRealPath()), true);
        
        // 1. Xóa Cache phân quyền trước khi import để tránh xung đột
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        DB::transaction(function () use ($json) {
            foreach ($json as $item) {
                $guardName = $item['guard_name'] ?? 'web';

                // 2. Tạo hoặc Lấy Role (theo đúng guard)
                $role = Role::firstOrCreate(
                    ['name' => $item['name']], 
                    ['guard_name' => $guardName]
                );
                
                if (!empty($item['permissions'])) {
                    // 3. VÒNG LẶP QUAN TRỌNG: Tạo Permission nếu chưa có
                    foreach ($item['permissions'] as $permName) {
                        Permission::firstOrCreate(
                            ['name' => $permName, 'guard_name' => $guardName]
                        );
                    }
                    
                    // 4. Gán danh sách quyền cho Role
                    // Lúc này chắc chắn quyền đã tồn tại trong DB nên sẽ không lỗi nữa
                    $role->syncPermissions($item['permissions']);
                }
            }
        });

        // 5. Xóa Cache lại lần nữa sau khi xong
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->showImportModal = false;
        $this->dispatch('notify', content: 'Import cấu hình phân quyền thành công.', type: 'success');
    }

    public function render()
    {
        $roles = Role::withCount('users')
            ->where('name', 'like', '%' . $this->search . '%')
            ->latest()
            ->paginate($this->perPage);

        return view('Admin::livewire.system.role-table', ['roles' => $roles]);
    }
}