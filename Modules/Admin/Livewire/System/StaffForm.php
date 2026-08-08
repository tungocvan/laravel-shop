<?php

namespace Modules\Admin\Livewire\System;

use Livewire\Component;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class StaffForm extends Component
{
    public $userId;
    public $isEdit = false;

    public $name, $email, $password, $is_active = true;
    public $selectedRoles = []; // Mảng ID các role được chọn

    public function mount($id = null)
    {
        if ($id) {
            $this->isEdit = true;
            $this->userId = $id;
            $user = User::findOrFail($id);
            
            $this->name = $user->name;
            $this->email = $user->email;
            $this->is_active = (bool)$user->is_active;
            
            // Lấy danh sách Role hiện tại của user (pluck tên hoặc id)
            $this->selectedRoles = $user->roles->pluck('name')->toArray();
        }
    }

    public function save()
    {
        $rules = [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $this->userId,
            'selectedRoles' => 'required|array|min:1', // Bắt buộc phải chọn ít nhất 1 role
        ];

        // Nếu tạo mới thì bắt buộc pass, nếu sửa thì ko bắt buộc
        if (!$this->isEdit) {
            $rules['password'] = 'required|min:6';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        $user = User::updateOrCreate(['id' => $this->userId], $data);

        // --- QUAN TRỌNG: Gán Roles ---
        // Hàm syncRoles của Spatie nhận mảng tên role hoặc id
        $user->syncRoles($this->selectedRoles);

        session()->flash('success', $this->isEdit ? 'Cập nhật nhân viên thành công.' : 'Tạo nhân viên thành công.');
        return redirect()->route('admin.staff.index');
    }

    public function render()
    {
        $roles = Role::all();
        return view('Admin::livewire.system.staff-form', ['roles' => $roles]);
    }
}