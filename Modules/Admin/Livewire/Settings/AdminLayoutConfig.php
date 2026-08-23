<?php

namespace Modules\Admin\Livewire\Settings;

use Livewire\Component;
use Modules\Admin\Support\AdminLayoutManager;
use Modules\Admin\Support\ThemeManager;

class AdminLayoutConfig extends Component
{
    public array $config = [];

    public array $themes = [];

    public string $section = 'general';

    public function mount(AdminLayoutManager $manager, ThemeManager $themeManager, string $section = 'general'): void
    {
        abort_unless(in_array($section, $this->sections(), true), 404);

        $this->section = $section;
        $this->config = $manager->config();
        $this->themes = $themeManager->all();
    }

    public function save(AdminLayoutManager $manager): void
    {
        $this->authorizePermission('admin.layout.update');

        $validated = $this->validate($this->rules())['config'];
        $payload = array_replace_recursive($manager->config(), $validated);

        $manager->save($payload);
        $this->config = $manager->config();

        $this->dispatch('notify', type: 'success', title: 'Đã lưu cấu hình', message: 'Thiết lập đã được lưu và sẽ được áp dụng sau khi tải lại.', action: 'reload', duration: 1200);
    }

    public function resetSection(AdminLayoutManager $manager): void
    {
        $this->authorizePermission('admin.layout.update');

        $payload = array_replace_recursive(
            $manager->config(),
            $this->sectionPayload($manager->defaults())
        );

        $manager->save($payload);
        $this->config = $manager->config();

        $this->dispatch('notify', type: 'warning', title: 'Đã khôi phục mặc định', message: 'Chỉ khu vực cấu hình hiện tại đã được khôi phục.', action: 'reload', duration: 1200);
    }

    public function render()
    {
        return view('Admin::livewire.settings.admin-layout-config', [
            'sectionTitle' => $this->sectionTitle(),
            'sectionDescription' => $this->sectionDescription(),
        ]);
    }

    private function rules(): array
    {
        $rules = [
            'general' => [
                'config.locale' => 'required|in:vi,en',
                'config.layout.preset' => 'required|in:default,data-heavy,focus,settings',
                'config.layout.container' => 'required|in:full,narrow,7xl,screen-2xl',
                'config.layout.density' => 'required|in:comfortable,compact,dense',
                'config.layout.sticky_header' => 'boolean',
            ],
            'header' => [
                'config.header.sticky' => 'boolean',
                'config.header.search' => 'boolean',
                'config.header.notifications' => 'boolean',
                'config.header.theme_switcher' => 'boolean',
                'config.header.user_menu' => 'boolean',
                'config.header.mobile_search_mode' => 'required|in:overlay',
            ],
            'sidebar' => [
                'config.sidebar.enabled' => 'boolean',
                'config.sidebar.desktop_collapsible' => 'boolean',
                'config.sidebar.mobile_drawer' => 'boolean',
                'config.sidebar.persist_state' => 'boolean',
                'config.sidebar.show_footer_profile' => 'boolean',
            ],
            'footer' => [
                'config.layout.show_footer' => 'boolean',
                'config.footer.show_app_name' => 'boolean',
                'config.footer.show_environment' => 'boolean',
            ],
            'design' => [
                'config.theme.default' => 'required|in:' . implode(',', $this->themes ?: ['corporate-blue']),
                'config.theme.dark_mode' => 'required|in:class',
                'config.theme.accent' => 'required|in:blue,indigo,emerald,rose,amber',
            ],
            'navigation' => [
                'config.navigation.cache_ttl' => 'required|integer|min:60|max:86400',
                'config.navigation.active_strategy' => 'required|in:url-prefix',
                'config.navigation.max_depth' => 'required|integer|min:1|max:3',
            ],
        ];

        return $rules[$this->section];
    }

    private function sectionPayload(array $config): array
    {
        return match ($this->section) {
            'general' => [
                'locale' => data_get($config, 'locale'),
                'layout' => [
                    'preset' => data_get($config, 'layout.preset'),
                    'container' => data_get($config, 'layout.container'),
                    'density' => data_get($config, 'layout.density'),
                    'sticky_header' => data_get($config, 'layout.sticky_header'),
                ],
            ],
            'header' => ['header' => $config['header'] ?? []],
            'sidebar' => ['sidebar' => $config['sidebar'] ?? []],
            'footer' => [
                'layout' => ['show_footer' => data_get($config, 'layout.show_footer')],
                'footer' => $config['footer'] ?? [],
            ],
            'design' => ['theme' => $config['theme'] ?? []],
            'navigation' => ['navigation' => $config['navigation'] ?? []],
        };
    }

    private function sections(): array
    {
        return ['general', 'header', 'sidebar', 'footer', 'design', 'navigation'];
    }

    private function sectionTitle(): string
    {
        return match ($this->section) {
            'general' => 'Layout tổng thể',
            'header' => 'Header',
            'sidebar' => 'Sidebar',
            'footer' => 'Footer',
            'design' => 'Giao diện & Theme',
            'navigation' => 'Navigation',
        };
    }

    private function sectionDescription(): string
    {
        return match ($this->section) {
            'general' => 'Thiết lập preset, container, density, locale và hành vi sticky tổng thể.',
            'header' => 'Quản lý các thành phần và hành vi của Header Admin.',
            'sidebar' => 'Quản lý khả năng hiển thị, collapse, mobile drawer và lưu trạng thái.',
            'footer' => 'Quản lý Footer và các thành phần thông tin được hiển thị.',
            'design' => 'Quản lý theme và accent đang được Admin runtime sử dụng.',
            'navigation' => 'Quản lý cache, active strategy và độ sâu navigation.',
        };
    }

    private function authorizePermission(string $permission): void
    {
        $user = auth('admin')->user() ?: auth()->user();

        abort_unless($user?->can($permission), 403);
    }
}
