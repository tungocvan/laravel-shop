<?php

namespace Modules\Auth\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Modules\Admin\Models\Setting;

class LoginForm extends Component
{
    public $email = '';

    public $password = '';

    public $remember = false;

    public $logo = '';

    public $login_name_line_1 = '';

    public $login_name_line_2 = '';

    public $login_description = '';

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required',
    ];

    public function mount(): void
    {
        $logo = Setting::getValue('site_logo');
        $this->logo = $logo
            ? asset('storage/'.$logo).'?v='.md5($logo)
            : asset('storage/img/logo.png');

        $this->login_name_line_1 = Setting::getValue('site_name_line_1') ?? config('app.school_managing_agency', '');
        $this->login_name_line_2 = Setting::getValue('site_name_line_2') ?? 'CÔNG TY TNHH INAFO VIỆT NAM';
        $this->login_description = Setting::getValue('login_description') ?? config('app.school_login_description', 'Hệ thống quản trị');

        $this->loadSchoolSettings();
    }

    public function login()
    {
        $this->validate();

        if (Auth::guard('admin')->attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->regenerate();

            return redirect()->route('admin.dashboard');
        }

        $this->addError('email', 'Thông tin đăng nhập không chính xác.');
    }

    public function render()
    {
        return view('Auth::livewire.auth.login-form');
    }

    private function loadSchoolSettings(): void
    {
        if (! config('modules.registry.Admission.enabled', false)) {
            return;
        }

        $serviceClass = 'Modules\\Admission\\Services\\SchoolSettingService';

        if (! class_exists($serviceClass)) {
            return;
        }

        $settings = app($serviceClass)->all();

        $this->login_name_line_1 = $settings['school_managing_agency'] ?? $this->login_name_line_1;
        $this->login_name_line_2 = $settings['school_name'] ?? $this->login_name_line_2;
        $this->login_description = $settings['school_login_description'] ?? $this->login_description;
    }
}
