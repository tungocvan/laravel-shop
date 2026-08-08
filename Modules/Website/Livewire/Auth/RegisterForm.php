<?php

namespace Modules\Website\Livewire\Auth;

use Livewire\Component;
use App\Models\User; // Sử dụng User model mặc định của Laravel
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class RegisterForm extends Component
{
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';

    protected $rules = [
        'name' => 'required|min:3|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:6|confirmed', // check password_confirmation
    ];

    protected $messages = [
        'email.unique' => 'Email này đã được đăng ký.',
        'password.confirmed' => 'Mật khẩu nhập lại không khớp.',
        'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
    ];

    public function register()
    {
        $this->validate();

        // 1. Tạo User
        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        // 2. Auto Login
        Auth::login($user);

        // 3. Chuyển hướng về trang chủ
        return redirect()->route('home');
    }

    public function render()
    {
        return view('Website::livewire.auth.register-form');
    }
}
