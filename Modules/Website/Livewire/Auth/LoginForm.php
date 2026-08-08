<?php

namespace Modules\Website\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class LoginForm extends Component
{
    public $email = '';
    public $password = '';
    public $remember = false;

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required',
    ];

    public function login()
    {
        $this->validate();

        // Thử đăng nhập
        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {

            session()->regenerate();

            // Nếu user đến từ trang checkout hoặc trang cần login, trả về trang đó
            return redirect()->intended(route('home'));
        }

        // Đăng nhập thất bại
        $this->addError('email', 'Thông tin đăng nhập không chính xác.');
    }

    public function render()
    {
        return view('Website::livewire.auth.login-form');
    }
}
