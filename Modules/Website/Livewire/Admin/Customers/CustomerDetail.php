<?php

namespace Modules\Website\Livewire\Admin\Customers;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\User\Models\UserAddress;
use Modules\User\Services\CustomerService;
use Modules\User\Services\UserAddressService;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;

class CustomerDetail extends Component
{
    use AuthorizesAdminPermissions, WithPagination;

    public $userId;

    public $activeTab = 'info';

    public $name;

    public $email;

    public $phone;

    public $is_active;

    public $new_password;

    public $showAddressModal = false;

    public $isEditAddress = false;

    public $addressId;

    public $addr_name;

    public $addr_phone;

    public $addr_address;

    public $addr_city;

    public $addr_is_default;

    public function mount($id)
    {
        $this->userId = $id;
        $user = User::findOrFail($id);
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->is_active = (bool) $user->is_active;
    }

    public function updateProfile(CustomerService $customerService)
    {
        $this->authorizeAdminPermission('customer.update');

        $this->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$this->userId,
            'phone' => 'nullable|numeric|digits_between:9,11',
            'new_password' => 'nullable|min:6',
        ]);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
        ];

        $data['password'] = $this->new_password;
        $customerService->update($this->userId, $data);
        $this->new_password = '';
        session()->flash('success', 'Cập nhật hồ sơ thành công.');
    }

    public function openAddressModal($id = null)
    {
        $this->resetValidation();
        $this->reset(['addr_name', 'addr_phone', 'addr_address', 'addr_city', 'addr_is_default', 'addressId']);

        if ($id) {
            $this->isEditAddress = true;
            $this->addressId = $id;
            $addr = UserAddress::where('user_id', $this->userId)->findOrFail($id);
            $this->addr_name = $addr->name;
            $this->addr_phone = $addr->phone;
            $this->addr_address = $addr->address;
            $this->addr_city = $addr->city;
            $this->addr_is_default = (bool) $addr->is_default;
        } else {
            $this->isEditAddress = false;
        }

        $this->showAddressModal = true;
    }

    public function saveAddress(UserAddressService $addressService)
    {
        $this->authorizeAdminPermission('customer.update');

        $this->validate([
            'addr_name' => 'required',
            'addr_phone' => 'required',
            'addr_address' => 'required',
        ]);

        $data = [
            'name' => $this->addr_name,
            'phone' => $this->addr_phone,
            'address' => $this->addr_address,
            'city' => $this->addr_city ?? '',
            'is_default' => $this->addr_is_default,
        ];

        if ($this->isEditAddress) {
            $addressService->update($this->addressId, $this->userId, $data);
        } else {
            $addressService->create($this->userId, $data);
        }

        $this->showAddressModal = false;
        session()->flash('success', 'Đã lưu địa chỉ.');
    }

    public function deleteAddress($id, UserAddressService $addressService)
    {
        $this->authorizeAdminPermission('customer.update');
        $addressService->delete($id, $this->userId);
        session()->flash('success', 'Đã xóa địa chỉ.');
    }

    public function render()
    {
        $user = User::withSum('orders', 'total')->findOrFail($this->userId);
        $addresses = $user->addresses()->latest()->get();
        $orders = $user->orders()->latest()->paginate(5);

        return view('Website::livewire.admin.customers.customer-detail', [
            'user' => $user,
            'addresses' => $addresses,
            'orders' => $orders,
        ]);
    }
}
