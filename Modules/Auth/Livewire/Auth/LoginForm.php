<?php

namespace Modules\Auth\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Component;
use Modules\Auth\Services\LoginPresentationService;
use Modules\System\Services\AdminLoginRedirectService;
use Modules\System\Services\SettingsService;

class LoginForm extends Component
{
    public $email = '';
    public $password = '';
    public $remember = false;
    public string $guard = 'web';
    public string $variant = 'default';
    public $logo = '';
    public $login_name_line_1 = '';
    public $login_name_line_2 = '';
    public $login_description = '';
    public array $presentation = [];

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required',
    ];

    public function mount(
        SettingsService $settings,
        LoginPresentationService $presentation,
        string $guard = 'web',
        string $variant = 'default',
    ): void {
        $this->guard = in_array($guard, ['web', 'admin'], true) ? $guard : 'web';
        $this->variant = in_array($variant, ['default', 'pwa'], true) ? $variant : 'default';

        $logo = $settings->get('site_logo');
        $this->logo = $logo
            ? asset('storage/'.$logo).'?v='.md5($logo)
            : ($this->variant === 'pwa' ? asset('pwa/icon.svg') : asset('storage/img/logo.png'));
        $this->login_name_line_1 = $settings->get('site_name_line_1') ?? config('app.school_managing_agency', '');
        $this->login_name_line_2 = $settings->get('site_name_line_2') ?? 'CÔNG TY TNHH INAFO VIỆT NAM';
        $this->login_description = $settings->get('login_description') ?? config('app.school_login_description', 'Hệ thống quản trị');
        $this->loadSchoolSettings();

        $this->presentation = $presentation->forGuard($this->guard, [
            'logo_url' => $this->logo,
            'title_line_1' => $this->login_name_line_1,
            'title_line_2' => $this->login_name_line_2,
            'description' => $this->login_description,
        ]);

        $this->logo = $this->presentation['logo_url'];
        $this->login_name_line_1 = $this->presentation['title_line_1'];
        $this->login_name_line_2 = $this->presentation['title_line_2'];
        $this->login_description = $this->presentation['description'];
    }

    public function login(AdminLoginRedirectService $redirect)
    {
        $this->validate();

        $credentials = [
            'email' => $this->email,
            'password' => $this->password,
            'is_active' => true,
        ];

        if (Auth::guard($this->guard)->attempt($credentials, $this->remember)) {
            session()->regenerate();

            if ($this->guard === 'admin') {
                return redirect()->route($redirect->configuredRoute());
            }

            return Route::has('client.apps.index')
                ? redirect()->route('client.apps.index')
                : redirect('/');
        }

        $this->addError('email', 'Thông tin đăng nhập không chính xác hoặc tài khoản chưa được kích hoạt.');
    }

    public function render()
    {
        return view($this->variant === 'pwa'
            ? 'Auth::livewire.auth.login-form-pwa'
            : 'Auth::livewire.auth.login-form');
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
