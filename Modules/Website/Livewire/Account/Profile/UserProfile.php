<?php

namespace Modules\Website\Livewire\Account\Profile;

use Livewire\Component;
use Livewire\WithFileUploads; // Trait để upload ảnh
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Modules\Website\Services\Account\ProfileService;

class UserProfile extends Component
{
    use WithFileUploads;

    // -- State: Thông tin chung --
    public $name;
    public $email;
    public $phone;
    public $avatar;      // Avatar đang có trong DB
    public $newAvatar;   // Avatar mới upload (Livewire Temporary Upload)

    // -- State: Đổi mật khẩu --
    public $current_password;
    public $password;
    public $password_confirmation;

    public function mount()
    {
        $user = Auth::user();
        $this->name   = $user->name;
        $this->email  = $user->email;
        $this->phone  = $user->phone;
        $this->avatar = $user->avatar;
    }

    // --- ACTION: CẬP NHẬT THÔNG TIN ---
    public function updateProfile(ProfileService $service)
    {
        $user = Auth::user();

        $this->validate([
            'name'      => 'required|string|max:255',
            'phone'     => 'nullable|string|max:20',
            'newAvatar' => 'nullable|image|max:2048', // Max 2MB
        ]);

        try {
            $data = [
                'name'  => $this->name,
                'phone' => $this->phone,
            ];

            $service->updateInfo($user, $data, $this->newAvatar);

            // Reset input file sau khi upload thành công
            $this->newAvatar = null;
            $this->avatar = $user->fresh()->avatar; // Cập nhật lại UI

            $this->dispatch('notify', ['type' => 'success', 'message' => 'Cập nhật hồ sơ thành công!']);

        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
    }

    // --- ACTION: ĐỔI MẬT KHẨU ---
    public function changePassword(ProfileService $service)
    {
        $this->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed|different:current_password',
        ], [
            'password.different' => 'Mật khẩu mới không được trùng với mật khẩu cũ.'
        ]);

        try {
            $service->updatePassword(Auth::user(), $this->current_password, $this->password);

            $this->reset(['current_password', 'password', 'password_confirmation']);

            $this->dispatch('notify', ['type' => 'success', 'message' => 'Đổi mật khẩu thành công!']);

        } catch (\Exception $e) {
            $this->addError('current_password', $e->getMessage());
        }
    }

    public function render()
    {
        return view('Website::livewire.account.profile.user-profile');
    }
}
