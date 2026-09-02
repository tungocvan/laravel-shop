<?php

namespace Modules\System\Livewire\Settings;

use Livewire\Component;

class SettingForm extends Component
{
    private const TAB_COMPONENTS = [
        'theme' => 'admin.theme-switcher',
        'general' => 'system.settings.partials.general',
        'login_theme' => 'system.settings.partials.login-theme',
        'login_redirect' => 'system.settings.partials.login-redirect',
        'images' => 'system.settings.partials.images',
        'seo' => 'system.settings.partials.seo',
        'custom' => 'system.settings.partials.custom',
    ];

    public array $tabs = [
        'theme' => 'Quản lý Themes',
        'general' => 'Cấu hình chung',
        'login_theme' => 'Giao diện đăng nhập',
        'login_redirect' => 'Đăng nhập & Điều hướng',
        'images' => 'Hình ảnh',
        'seo' => 'SEO/Mạng xã hội',
        'custom' => 'Cấu hình tùy chỉnh',
    ];

    public string $activeTab = 'theme';

    public function setTab(string $tab): void
    {
        $this->activeTab = array_key_exists($tab, self::TAB_COMPONENTS) ? $tab : 'theme';
    }

    public function getTabComponent(): string
    {
        return self::TAB_COMPONENTS[$this->activeTab] ?? self::TAB_COMPONENTS['theme'];
    }

    public function render()
    {
        return view('System::livewire.settings.setting-form');
    }
}
