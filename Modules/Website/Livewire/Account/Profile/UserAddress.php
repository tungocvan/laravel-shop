<?php

namespace Modules\Website\Livewire\Account\Profile;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Modules\Website\Services\Account\AddressService;

class UserAddress extends Component
{
    public $addresses;

    // Modal State
    public $isModalOpen = false;
    public $isEditMode = false;
    public $editingId = null;

    // Form Fields
    public $name, $phone, $address, $city, $district, $ward;
    public $is_default = false;

    protected $rules = [
        'name'     => 'required|string|max:255',
        'phone'    => 'required|string|max:20',
        'address'  => 'required|string|max:255',
        'city'     => 'required|string',
        'district' => 'required|string',
        'ward'     => 'required|string',
        'is_default' => 'boolean'
    ];

    public function mount(AddressService $service)
    {
        $this->loadAddresses($service);
    }

    public function loadAddresses(AddressService $service)
    {
        $this->addresses = $service->getUserAddresses(Auth::id());
    }

    // --- MODAL ACTIONS ---
    public function openCreateModal()
    {
        $this->reset(['name', 'phone', 'address', 'city', 'district', 'ward', 'is_default', 'editingId']);
        $this->isEditMode = false;
        $this->isModalOpen = true;
    }

    public function openEditModal($id)
    {
        $address = $this->addresses->firstWhere('id', $id);
        if ($address) {
            $this->editingId = $id;
            $this->name = $address->name;
            $this->phone = $address->phone;
            $this->address = $address->address;
            $this->city = $address->city;
            $this->district = $address->district;
            $this->ward = $address->ward;
            $this->is_default = $address->is_default;

            $this->isEditMode = true;
            $this->isModalOpen = true;
        }
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    // --- CRUD ACTIONS ---
    public function save(AddressService $service)
    {
        $this->validate();

        $data = [
            'name' => $this->name, 'phone' => $this->phone,
            'address' => $this->address, 'city' => $this->city,
            'district' => $this->district, 'ward' => $this->ward,
            'is_default' => $this->is_default
        ];

        if ($this->isEditMode) {
            $service->update($this->editingId, Auth::id(), $data);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Cập nhật địa chỉ thành công!']);
        } else {
            $service->create(Auth::id(), $data);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Thêm địa chỉ mới thành công!']);
        }

        $this->closeModal();
        $this->loadAddresses($service);
    }

    public function delete($id, AddressService $service)
    {
        $service->delete($id, Auth::id());
        $this->loadAddresses($service);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Đã xóa địa chỉ.']);
    }

    public function setAsDefault($id, AddressService $service)
    {
        $service->setDefault($id, Auth::id());
        $this->loadAddresses($service);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Đã đặt làm địa chỉ mặc định.']);
    }

    public function render()
    {
        return view('Website::livewire.account.profile.user-address');
    }
}
