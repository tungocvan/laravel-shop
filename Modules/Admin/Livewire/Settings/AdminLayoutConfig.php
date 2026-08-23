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
        $manager->save(array_replace_recursive($manager->config(), $validated));
        $this->config = $manager->config();

        $this->dispatch('admin-layout-updated');
        $this->dispatch('notify', type: 'success', title: 'Đã lưu cấu hình', message: 'Thiết lập đã được lưu và áp dụng cho giao diện Admin.', duration: 1800);
    }

    public function resetSection(AdminLayoutManager $manager): void
    {
        $this->authorizePermission('admin.layout.update');
        $manager->save(array_replace_recursive($manager->config(), $this->sectionPayload($manager->defaults())));
        $this->config = $manager->config();

        $this->dispatch('admin-layout-updated');
        $this->dispatch('notify', type: 'warning', title: 'Đã khôi phục mặc định', message: 'Khu vực cấu hình hiện tại đã được khôi phục và áp dụng.', duration: 1800);
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
        $spacing = 'required|in:0,1,2,3,4,5,6,8,10,12';
        $rules = [
            'general' => [
                'config.locale' => 'required|in:vi,en',
                'config.layout.preset' => 'required|in:default,data-heavy,focus,settings',
                'config.layout.container' => 'required|in:full,narrow,7xl,screen-2xl',
                'config.layout.density' => 'required|in:comfortable,compact,dense',
                'config.layout.sticky_header' => 'boolean',
                'config.layout.spacing.content_padding_x' => $spacing,
                'config.layout.spacing.content_padding_top' => $spacing,
                'config.layout.spacing.content_padding_bottom' => $spacing,
                'config.layout.spacing.section_gap' => $spacing,
                'config.layout.spacing.tablet_padding_x' => $spacing,
                'config.layout.spacing.mobile_padding_x' => $spacing,
                'config.layout.surface.page_background' => 'required|in:system,white,slate-50',
                'config.layout.surface.content_surface' => 'required|in:transparent,system,white',
                'config.layout.surface.border' => 'required|in:system,none',
                'config.layout.surface.radius' => 'required|in:none,sm,md,lg',
                'config.layout.behavior.reduced_motion' => 'boolean',
            ],
            'header' => ['config.header.sticky' => 'boolean', 'config.header.search' => 'boolean', 'config.header.notifications' => 'boolean', 'config.header.theme_switcher' => 'boolean', 'config.header.user_menu' => 'boolean', 'config.header.mobile_search_mode' => 'required|in:overlay'],
            'sidebar' => ['config.sidebar.enabled' => 'boolean', 'config.sidebar.desktop_collapsible' => 'boolean', 'config.sidebar.mobile_drawer' => 'boolean', 'config.sidebar.persist_state' => 'boolean', 'config.sidebar.show_footer_profile' => 'boolean', 'config.sidebar.navigation_search_threshold' => 'required|integer|min:4|max:50'],
            'footer' => ['config.layout.show_footer' => 'boolean', 'config.footer.show_app_name' => 'boolean', 'config.footer.show_environment' => 'boolean'],
            'design' => ['config.theme.default' => 'required|in:' . implode(',', $this->themes ?: ['corporate-blue']), 'config.theme.dark_mode' => 'required|in:class', 'config.theme.accent' => 'required|in:blue,indigo,emerald,rose,amber'],
            'navigation' => ['config.navigation.cache_ttl' => 'required|integer|min:60|max:86400', 'config.navigation.active_strategy' => 'required|in:url-prefix', 'config.navigation.max_depth' => 'required|integer|min:1|max:3'],
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
                    'spacing' => data_get($config, 'layout.spacing', []),
                    'surface' => data_get($config, 'layout.surface', []),
                    'behavior' => data_get($config, 'layout.behavior', []),
                ],
            ],
            'header' => ['header' => $config['header'] ?? []],
            'sidebar' => ['sidebar' => $config['sidebar'] ?? []],
            'footer' => ['layout' => ['show_footer' => data_get($config, 'layout.show_footer')], 'footer' => $config['footer'] ?? []],
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
            'general' => 'Thiết lập workspace, container, mật độ, khoảng cách, surface và hành vi tổng thể.',
            'header' => 'Quản lý các thành phần và hành vi của Header Admin.',
            'sidebar' => 'Quản lý khả năng hiển thị, collapse, mobile drawer, profile và hỗ trợ điều hướng menu lớn.',
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
