<?php

namespace Modules\Admin\Support;
use Illuminate\Support\Facades\File;
use Modules\Admin\Models\Setting;

class ThemeManager
{
    protected array $config;
    protected array $defaultTheme = [
        'background'        => 'bg-slate-50',
        'text'              => 'text-slate-700',
        'hover'             => 'hover:bg-slate-100',

        'active_bg'         => 'bg-indigo-600',
        'active_text'       => 'text-white',

        'icon_active'       => 'text-indigo-600',
        'icon_inactive'     => 'text-slate-400',

        'child_active_bg'   => 'bg-indigo-500/20',
        'child_active_text' => 'text-indigo-600',
        'child_text'        => 'text-slate-500',
        'child_hover'       => 'hover:bg-slate-100 hover:text-slate-900',

        'border'            => 'border-slate-200',
    ];

    public function __construct() 
    {
        $this->config = File::getRequire(
            base_path('Modules/Admin/config/sidebar.php')
        );
    }

    // ======================
    // GET ACTIVE THEME
    // ======================
    public function get(): array
    {
        $themeName = $this->getThemeName();

        $themes = $this->config['themes'] ?? [];

        $theme = $themes[$themeName] ?? [];

        // 🔥 merge fallback
        return array_merge($this->defaultTheme, $theme);
    }

    // ======================
    // GET THEME NAME
    // ======================
    public function getThemeName(): string
    {
        return session('admin_theme')
            ?? Setting::getValue('admin_sidebar_theme')
            ?? $this->config['theme']
            ?? 'soft-light';
    }

    // ======================
    // SET THEME (RUNTIME)
    // ======================
    public function set(string $theme): void
    {
        if (! in_array($theme, $this->all(), true)) {
            return;
        }

        session(['admin_theme' => $theme]);
        Setting::setValue('admin_sidebar_theme', $theme, 'admin_layout', 'text');
    }

    // ======================
    // ALL THEMES
    // ======================
    public function all(): array
    {
        return array_keys($this->config['themes'] ?? []);
    }
}
