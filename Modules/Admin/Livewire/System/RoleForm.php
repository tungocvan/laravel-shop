<?php

namespace Modules\Admin\Livewire\System;

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
 
    public function mount($id = null)
    {
        // 1. Lấy tất cả quyền, nhưng lọc TRÙNG tên
        // Chỉ giữ lại 1 bản ghi duy nhất cho mỗi tên quyền (view_product, edit_order...)
        $allPermissions = Permission::all()->unique('name'); 

        // 2. Nhóm quyền theo Module
        foreach ($allPermissions as $perm) {
            // Tách chuỗi: view_product -> ['view', 'product']
            $parts = explode('_', $perm->name);
            
            // Lấy phần cuối làm tên nhóm (product, order, system...)
            // Nếu tên quyền không có dấu _ (ví dụ: 'dashboard'), đưa vào nhóm 'other'
            $module = count($parts) > 1 ? end($parts) : 'other';
            
            $this->permissionGroups[$module][] = $perm;
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
            $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
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
        if (!empty($this->selectedPermissions)) {
            $role->syncPermissions($this->selectedPermissions);
        }

        session()->flash('success', 'Lưu vai trò thành công (Guard: Admin).');
        return redirect()->route('admin.roles.index');
    }

    public function render()
    {
        return view('Admin::livewire.system.role-form');
    }
}