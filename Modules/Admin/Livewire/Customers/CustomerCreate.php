<?php

namespace Modules\Admin\Livewire\Customers;

use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class CustomerCreate extends Component
{
    public $name;
    public $email;
    public $password;
    public $phone;
    public $is_active = true;

    protected $rules = [
        'name' => 'required|min:2',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:6',
        'phone' => 'nullable|numeric|digits_between:9,11',
    ];

    public function save()
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'phone' => $this->phone,
            'is_active' => $this->is_active,
            // 'avatar' => null, (Mặc định null, User model đã có accessor tự tạo ảnh chữ cái)
        ]);

        session()->flash('success', 'Thêm khách hàng mới thành công!');
        
        // Chuyển hướng về trang chi tiết của khách vừa tạo
        return redirect()->route('admin.customers.show', $user->id);
    }

    public function render()
    {
        return view('Admin::livewire.customers.customer-create');
    }
}