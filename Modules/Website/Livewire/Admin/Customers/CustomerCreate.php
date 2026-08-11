<?php

namespace Modules\Website\Livewire\Admin\Customers;

use Livewire\Component;
use Modules\User\Services\CustomerService;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;

class CustomerCreate extends Component
{
    use AuthorizesAdminPermissions;

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

    public function save(CustomerService $customerService)
    {
        $this->authorizeAdminPermission('customer.create');
        $this->validate();

        $user = $customerService->create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
        ]);

        session()->flash('success', 'Thêm khách hàng mới thành công!');

        return redirect()->route('admin.customers.show', $user->id);
    }

    public function render()
    {
        return view('Website::livewire.admin.customers.customer-create');
    }
}
