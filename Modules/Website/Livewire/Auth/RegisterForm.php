<?php

namespace Modules\Website\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Modules\User\Services\UserRegistrationService;

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

    public function register(UserRegistrationService $registration)
    {
        $this->validate();

        // 1. Tạo User
        $user = $registration->register([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
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
