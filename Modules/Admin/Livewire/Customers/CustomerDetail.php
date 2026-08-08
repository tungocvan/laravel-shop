<?php

namespace Modules\Admin\Livewire\Customers;

use Livewire\Component;
use Livewire\WithPagination; // Dùng cho tab đơn hàng
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Modules\Website\Models\UserAddress;
class CustomerDetail extends Component
{
    use WithPagination;

    public $userId;
    public $activeTab = 'info'; // 'info', 'addresses', 'orders'

    // Form Profile
    public $name, $email, $phone, $is_active;
    public $new_password;

    // Form Address (Modal)
    public $showAddressModal = false;
    public $isEditAddress = false;
    public $addressId;
    public $addr_name, $addr_phone, $addr_address, $addr_city, $addr_is_default;

    public function mount($id)
    {
        $this->userId = $id;
        $user = User::findOrFail($id);
        
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->is_active = (bool)$user->is_active;
    }

    // --- TAB 1: CẬP NHẬT PROFILE ---
    public function updateProfile()
    {
        $this->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $this->userId,
            'phone' => 'nullable|numeric|digits_between:9,11',
            'new_password' => 'nullable|min:6',
        ]);

        $user = User::find($this->userId);
        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
        ];

        if ($this->new_password) {
            $data['password'] = Hash::make($this->new_password);
        }

        $user->update($data);
        $this->new_password = ''; // Reset password field
        session()->flash('success', 'Cập nhật hồ sơ thành công.');
    }

    // --- TAB 2: QUẢN LÝ ĐỊA CHỈ ---
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
            $this->addr_city = $addr->city; // Ở đây giả sử bạn nhập text, nếu dùng select Huyện/Xã thì cần thêm biến
            $this->addr_is_default = (bool)$addr->is_default;
        } else {
            $this->isEditAddress = false;
        }
        
        $this->showAddressModal = true;
    }

    public function saveAddress()
    {
        $this->validate([
            'addr_name' => 'required',
            'addr_phone' => 'required',
            'addr_address' => 'required',
        ]);

        // Nếu set mặc định, các địa chỉ khác phải bỏ mặc định
        if ($this->addr_is_default) {
            UserAddress::where('user_id', $this->userId)->update(['is_default' => false]);
        }

        $data = [
            'user_id' => $this->userId,
            'name' => $this->addr_name,
            'phone' => $this->addr_phone,
            'address' => $this->addr_address,
            'city' => $this->addr_city ?? '',
            'is_default' => $this->addr_is_default,
        ];

        if ($this->isEditAddress) {
            UserAddress::where('user_id', $this->userId)->where('id', $this->addressId)->update($data);
        } else {
            UserAddress::create($data);
        }

        $this->showAddressModal = false;
        session()->flash('success', 'Đã lưu địa chỉ.');
    }

    public function deleteAddress($id)
    {
        UserAddress::where('user_id', $this->userId)->where('id', $id)->delete();
        session()->flash('success', 'Đã xóa địa chỉ.');
    }

    // --- RENDER ---
    public function render()
    {
        $user = User::withSum('orders', 'total')->findOrFail($this->userId);
        
        // Lấy danh sách địa chỉ
        $addresses = $user->addresses()->latest()->get();

        // Lấy danh sách đơn hàng (Paginate)
        $orders = $user->orders()->latest()->paginate(5);

        return view('Admin::livewire.customers.customer-detail', [
            'user' => $user,
            'addresses' => $addresses,
            'orders' => $orders
        ]);
    }
}