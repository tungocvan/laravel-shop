<?php

namespace Modules\Admin\Livewire\Settings;

use Livewire\Component;
use Modules\Admin\Support\AdminLayoutManager;
use Modules\Admin\Support\ThemeManager;

class AdminLayoutConfig extends Component
{
    public array $config = [];

    public array $themes = [];

    public function mount(AdminLayoutManager $manager, ThemeManager $themeManager): void
    {
        $this->config = $manager->config();
        $this->themes = $themeManager->all();
    }

    public function save(AdminLayoutManager $manager): void
    {
        $this->authorizePermission('admin.layout.update');

        $validated = $this->validate($this->rules())['config'];

        $manager->save($validated);
        $this->config = $manager->config();

        $this->dispatch('notify', type: 'success', title: 'Đã lưu cấu hình', message: 'Giao diện Admin sẽ được tải lại để áp dụng thay đổi.', action: 'reload', duration: 1200);
    }

    public function resetConfig(AdminLayoutManager $manager): void
    {
        $this->authorizePermission('admin.layout.update');

        $manager->reset();
        $this->config = $manager->config();

        $this->dispatch('notify', type: 'warning', title: 'Đã khôi phục mặc định', message: 'Cấu hình Admin đã quay về file config.', action: 'reload', duration: 1200);
    }

    public function render()
    {
        return view('Admin::livewire.settings.admin-layout-config');
    }

    private function rules(): array
    {
        return [
            'config.locale' => 'required|in:vi,en',
            'config.layout.preset' => 'required|in:default,data-heavy,focus,settings',
            'config.layout.container' => 'required|in:full,narrow,7xl,screen-2xl',
            'config.layout.density' => 'required|in:comfortable,compact,dense',
            'config.layout.sticky_header' => 'boolean',
            'config.layout.show_footer' => 'boolean',
            'config.sidebar.enabled' => 'boolean',
            'config.sidebar.desktop_collapsible' => 'boolean',
            'config.sidebar.mobile_drawer' => 'boolean',
            'config.sidebar.persist_state' => 'boolean',
            'config.sidebar.show_footer_profile' => 'boolean',
            'config.header.sticky' => 'boolean',
            'config.header.search' => 'boolean',
            'config.header.notifications' => 'boolean',
            'config.header.theme_switcher' => 'boolean',
            'config.header.user_menu' => 'boolean',
            'config.header.mobile_search_mode' => 'required|in:overlay',
            'config.theme.default' => 'required|in:' . implode(',', $this->themes ?: ['corporate-blue']),
            'config.theme.dark_mode' => 'required|in:class',
            'config.theme.accent' => 'required|in:blue,indigo,emerald,rose,amber',
            'config.navigation.cache_ttl' => 'required|integer|min:60|max:86400',
            'config.navigation.active_strategy' => 'required|in:url-prefix',
            'config.navigation.max_depth' => 'required|integer|min:1|max:3',
        ];
    }

    private function authorizePermission(string $permission): void
    {
        $user = auth('admin')->user() ?: auth()->user();

        abort_unless($user?->can($permission), 403);
    }
}
