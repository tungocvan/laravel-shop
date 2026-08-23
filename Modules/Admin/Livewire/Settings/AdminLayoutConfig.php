<?php

namespace Modules\Admin\Livewire\Settings;

use Livewire\Component;
use Modules\Admin\Services\HeaderMenuService;
use Modules\Admin\Support\AdminLayoutManager;
use Modules\Admin\Support\ThemeManager;

class AdminLayoutConfig extends Component
{
    public array $config = [];
    public array $themes = [];
    public string $section = 'general';
    public bool $importedHeaderMenuItems = false;

    public function mount(AdminLayoutManager $manager, ThemeManager $themeManager, HeaderMenuService $headerMenuService, string $section = 'general'): void
    {
        abort_unless(in_array($section, $this->sections(), true), 404);
        $this->section = $section;
        $this->config = $manager->config();
        $this->themes = $themeManager->all();

        if ($section === 'header' && count((array) data_get($this->config, 'header.user_menu_config.items', [])) === 0) {
            $currentMenuItems = $headerMenuService->exportAdminConfigItems();
            if ($currentMenuItems !== []) {
                $this->config['header']['user_menu_config']['items'] = $currentMenuItems;
                $this->importedHeaderMenuItems = true;
            }
        }
    }

    public function updatedConfigLayoutPreset(mixed $value): void
    {
        if ($this->section !== 'general') return;
        $this->config['layout'] = array_replace_recursive($this->config['layout'] ?? [], $this->generalPreset((string) $value));
    }

    public function addHeaderAction(): void
    {
        abort_unless($this->section === 'header', 404);
        $this->config['header']['actions']['items'][] = ['enabled' => true, 'title' => 'Website', 'icon' => 'globe', 'url' => '/', 'target' => '_blank', 'priority' => 'secondary', 'badge' => null, 'permission' => null, 'order' => count((array) data_get($this->config, 'header.actions.items', []))];
    }

    public function removeHeaderAction(int $index): void
    {
        abort_unless($this->section === 'header', 404);
        $items = array_values((array) data_get($this->config, 'header.actions.items', []));
        unset($items[$index]);
        $this->config['header']['actions']['items'] = array_values($items);
    }

    public function addUserMenuItem(): void
    {
        abort_unless($this->section === 'header', 404);
        $this->config['header']['user_menu_config']['items'][] = ['enabled' => true, 'label' => 'Mục mới', 'icon' => 'link', 'url' => '/admin', 'target' => '_self', 'permission' => null, 'order' => count((array) data_get($this->config, 'header.user_menu_config.items', []))];
    }

    public function removeUserMenuItem(int $index): void
    {
        abort_unless($this->section === 'header', 404);
        $items = array_values((array) data_get($this->config, 'header.user_menu_config.items', []));
        unset($items[$index]);
        $this->config['header']['user_menu_config']['items'] = array_values($items);
    }

    public function save(AdminLayoutManager $manager): void
    {
        $this->authorizePermission('admin.layout.update');
        $validated = $this->validate($this->rules())['config'];
        $manager->save(array_replace_recursive($manager->config(), $validated));
        $this->config = $manager->config();
        $this->dispatch('admin-layout-updated');

        if (in_array($this->section, ['general', 'header', 'sidebar', 'footer'], true)) {
            $messages = [
                'general' => 'Thiết lập Layout tổng thể đã được lưu và áp dụng.',
                'header' => 'Thiết lập Header đã được lưu và áp dụng.',
                'sidebar' => 'Thiết lập Sidebar đã được lưu và áp dụng.',
                'footer' => 'Thiết lập Footer đã được lưu và áp dụng.',
            ];
            session()->flash('success', $messages[$this->section]);
            $this->redirect(url()->previous(), navigate: false);
            return;
        }

        $this->dispatch('notify', type: 'success', title: 'Đã lưu cấu hình', message: 'Thiết lập đã được lưu và áp dụng cho giao diện Admin.', duration: 1800);
    }

    public function resetSection(AdminLayoutManager $manager): void
    {
        $this->authorizePermission('admin.layout.update');
        $manager->save(array_replace_recursive($manager->config(), $this->sectionPayload($manager->defaults())));
        $this->config = $manager->config();
        $this->dispatch('admin-layout-updated');

        if (in_array($this->section, ['general', 'header', 'sidebar', 'footer'], true)) {
            $messages = [
                'general' => 'Layout tổng thể đã được khôi phục mặc định và áp dụng.',
                'header' => 'Header đã được khôi phục mặc định và áp dụng.',
                'sidebar' => 'Sidebar đã được khôi phục mặc định và áp dụng.',
                'footer' => 'Footer đã được khôi phục mặc định và áp dụng.',
            ];
            session()->flash('warning', $messages[$this->section]);
            $this->redirect(url()->previous(), navigate: false);
            return;
        }

        $this->dispatch('notify', type: 'warning', title: 'Đã khôi phục mặc định', message: 'Khu vực cấu hình hiện tại đã được khôi phục và áp dụng.', duration: 1800);
    }

    public function render()
    {
        if ($this->section === 'header') return view('Admin::livewire.settings.admin-header-config', ['sectionTitle' => $this->sectionTitle(), 'sectionDescription' => $this->sectionDescription()]);
        if ($this->section === 'sidebar') return view('Admin::livewire.settings.admin-sidebar-config', ['sectionTitle' => $this->sectionTitle(), 'sectionDescription' => $this->sectionDescription()]);
        if ($this->section === 'footer') return view('Admin::livewire.settings.admin-footer-config', ['sectionTitle' => $this->sectionTitle(), 'sectionDescription' => $this->sectionDescription()]);
        return view('Admin::livewire.settings.admin-layout-config', ['sectionTitle' => $this->sectionTitle(), 'sectionDescription' => $this->sectionDescription()]);
    }

    private function rules(): array
    {
        $spacing = 'required|in:0,1,2,3,4,5,6,8,10,12';
        $rules = [
            'general' => [
                'config.locale' => 'required|in:vi,en', 'config.layout.preset' => 'required|in:default,data-heavy,focus,settings', 'config.layout.container' => 'required|in:full,narrow,7xl,screen-2xl', 'config.layout.density' => 'required|in:comfortable,compact,dense', 'config.layout.sticky_header' => 'boolean',
                'config.layout.spacing.content_padding_x' => $spacing, 'config.layout.spacing.content_padding_top' => $spacing, 'config.layout.spacing.content_padding_bottom' => $spacing, 'config.layout.spacing.section_gap' => $spacing, 'config.layout.spacing.tablet_padding_x' => $spacing, 'config.layout.spacing.mobile_padding_x' => $spacing,
                'config.layout.surface.page_background' => 'required|in:system,white,slate-50', 'config.layout.surface.content_surface' => 'required|in:transparent,system,white', 'config.layout.surface.border' => 'required|in:system,none', 'config.layout.surface.radius' => 'required|in:none,sm,md,lg', 'config.layout.behavior.reduced_motion' => 'boolean',
            ],
            'header' => [
                'config.header.height' => 'required|in:3.5rem,4rem,4.5rem', 'config.header.sticky' => 'boolean', 'config.header.search' => 'boolean', 'config.header.notifications' => 'boolean', 'config.header.theme_switcher' => 'boolean', 'config.header.user_menu' => 'boolean', 'config.header.mobile_search_mode' => 'required|in:overlay',
                'config.header.brand.enabled' => 'boolean', 'config.header.brand.logo' => 'nullable|string|max:255', 'config.header.brand.logo_size' => 'required|in:24,28,32,36,40', 'config.header.brand.show_title' => 'boolean', 'config.header.brand.title' => 'nullable|string|max:120', 'config.header.brand.show_subtitle' => 'boolean', 'config.header.brand.subtitle' => 'nullable|string|max:160', 'config.header.brand.url' => ['required', 'string', 'max:255', 'regex:/^\/(?!\/)/'],
                'config.header.user_menu_config.show_avatar' => 'boolean', 'config.header.user_menu_config.show_name' => 'boolean', 'config.header.user_menu_config.show_email' => 'boolean', 'config.header.user_menu_config.show_role' => 'boolean',
                'config.header.user_menu_config.items' => 'array|max:12', 'config.header.user_menu_config.items.*.enabled' => 'boolean', 'config.header.user_menu_config.items.*.label' => 'required|string|max:80', 'config.header.user_menu_config.items.*.icon' => 'nullable|in:user,gear,lock,key,shield,link', 'config.header.user_menu_config.items.*.url' => ['required', 'string', 'max:255', 'regex:/^\/(?!\/)/'], 'config.header.user_menu_config.items.*.target' => 'required|in:_self,_blank', 'config.header.user_menu_config.items.*.permission' => 'nullable|string|max:120', 'config.header.user_menu_config.items.*.order' => 'required|integer|min:0|max:99',
                'config.header.actions.notification.icon' => 'required|in:bell,globe,book,help,link,message,calendar,star', 'config.header.actions.notification.behavior' => 'required|in:dropdown,link', 'config.header.actions.notification.url' => 'nullable|string|max:255', 'config.header.actions.notification.target' => 'required|in:_self,_blank',
                'config.header.actions.items' => 'array|max:12', 'config.header.actions.items.*.enabled' => 'boolean', 'config.header.actions.items.*.title' => 'required|string|max:80', 'config.header.actions.items.*.icon' => 'required|in:bell,globe,book,help,link,message,calendar,star', 'config.header.actions.items.*.url' => 'required|string|max:255', 'config.header.actions.items.*.target' => 'required|in:_self,_blank', 'config.header.actions.items.*.priority' => 'required|in:primary,secondary', 'config.header.actions.items.*.badge' => 'nullable|string|max:4', 'config.header.actions.items.*.permission' => 'nullable|string|max:120', 'config.header.actions.items.*.order' => 'required|integer|min:0|max:99', 'config.header.actions.mobile_overflow' => 'boolean',
                'config.header.presentation.mode' => 'required|in:balanced,compact,action-heavy', 'config.header.presentation.padding_x' => $spacing, 'config.header.presentation.action_gap' => $spacing, 'config.header.presentation.background' => 'required|in:system,white,transparent', 'config.header.presentation.divider' => 'required|in:subtle,none', 'config.header.presentation.shadow' => 'required|in:none,subtle', 'config.header.presentation.backdrop_blur' => 'boolean',
                'config.header.responsive.mobile_brand' => 'required|in:logo-only,logo-title,hidden', 'config.header.responsive.hide_title_on_mobile' => 'boolean', 'config.header.responsive.overflow_secondary_actions' => 'boolean',
            ],
            'sidebar' => [
                'config.sidebar.enabled' => 'boolean', 'config.sidebar.desktop_collapsible' => 'boolean', 'config.sidebar.mobile_drawer' => 'boolean', 'config.sidebar.persist_state' => 'boolean', 'config.sidebar.show_footer_profile' => 'boolean', 'config.sidebar.navigation_search_threshold' => 'required|integer|min:4|max:50',
                'config.sidebar.header.enabled' => 'boolean', 'config.sidebar.header.show_mark' => 'boolean', 'config.sidebar.header.show_title' => 'boolean', 'config.sidebar.header.show_subtitle' => 'boolean', 'config.sidebar.header.subtitle' => 'required|string|max:80',
                'config.sidebar.footer.enabled' => 'boolean', 'config.sidebar.footer.show_avatar' => 'boolean', 'config.sidebar.footer.show_name' => 'boolean', 'config.sidebar.footer.show_subtitle' => 'boolean', 'config.sidebar.footer.subtitle' => 'required|string|max:80',
                'config.sidebar.search.enabled' => 'boolean', 'config.sidebar.presentation.background' => 'required|in:theme,system,white,dark',
            ],
            'footer' => [
                'config.layout.show_footer' => 'boolean', 'config.footer.show_app_name' => 'boolean', 'config.footer.copyright.enabled' => 'boolean', 'config.footer.copyright.owner' => 'nullable|string|max:120', 'config.footer.copyright.url' => 'nullable|string|max:255', 'config.footer.copyright.start_year' => 'nullable|integer|min:1900|max:' . date('Y'),
                'config.footer.datetime.show_date' => 'boolean', 'config.footer.datetime.show_time' => 'boolean', 'config.footer.datetime.date_format' => 'required|in:d/m/Y', 'config.footer.datetime.time_format' => 'required|in:H:i:s',
                'config.footer.presentation.alignment' => 'required|in:split,center', 'config.footer.presentation.background' => 'required|in:system,transparent', 'config.footer.presentation.divider' => 'required|in:subtle,none', 'config.footer.presentation.compact' => 'boolean',
            ],
            'design' => ['config.theme.default' => 'required|in:' . implode(',', $this->themes ?: ['corporate-blue']), 'config.theme.dark_mode' => 'required|in:class', 'config.theme.accent' => 'required|in:blue,indigo,emerald,rose,amber'],
            'navigation' => ['config.navigation.cache_ttl' => 'required|integer|min:60|max:86400', 'config.navigation.active_strategy' => 'required|in:url-prefix', 'config.navigation.max_depth' => 'required|integer|min:1|max:3'],
        ];
        return $rules[$this->section];
    }

    private function sectionPayload(array $config): array
    {
        return match ($this->section) {
            'general' => ['locale' => data_get($config, 'locale'), 'layout' => ['preset' => data_get($config, 'layout.preset'), 'container' => data_get($config, 'layout.container'), 'density' => data_get($config, 'layout.density'), 'sticky_header' => data_get($config, 'layout.sticky_header'), 'spacing' => data_get($config, 'layout.spacing', []), 'surface' => data_get($config, 'layout.surface', []), 'behavior' => data_get($config, 'layout.behavior', [])]],
            'header' => ['header' => $config['header'] ?? []], 'sidebar' => ['sidebar' => $config['sidebar'] ?? []], 'footer' => ['layout' => ['show_footer' => data_get($config, 'layout.show_footer')], 'footer' => $config['footer'] ?? []], 'design' => ['theme' => $config['theme'] ?? []], 'navigation' => ['navigation' => $config['navigation'] ?? []],
        };
    }

    private function generalPreset(string $preset): array
    {
        $base = ['preset' => $preset, 'surface' => ['page_background' => 'system', 'content_surface' => 'transparent', 'border' => 'system', 'radius' => 'lg'], 'behavior' => ['reduced_motion' => true]];
        return array_replace_recursive($base, match ($preset) {
            'data-heavy' => ['container' => 'full', 'density' => 'compact', 'spacing' => ['content_padding_x' => '5', 'content_padding_top' => '4', 'content_padding_bottom' => '5', 'section_gap' => '4', 'tablet_padding_x' => '4', 'mobile_padding_x' => '3']],
            'focus' => ['container' => 'narrow', 'density' => 'comfortable', 'spacing' => ['content_padding_x' => '6', 'content_padding_top' => '6', 'content_padding_bottom' => '8', 'section_gap' => '6', 'tablet_padding_x' => '5', 'mobile_padding_x' => '4']],
            'settings' => ['container' => '7xl', 'density' => 'comfortable', 'spacing' => ['content_padding_x' => '6', 'content_padding_top' => '5', 'content_padding_bottom' => '6', 'section_gap' => '5', 'tablet_padding_x' => '5', 'mobile_padding_x' => '4']],
            default => ['preset' => 'default', 'container' => 'screen-2xl', 'density' => 'comfortable', 'spacing' => ['content_padding_x' => '6', 'content_padding_top' => '6', 'content_padding_bottom' => '8', 'section_gap' => '6', 'tablet_padding_x' => '5', 'mobile_padding_x' => '4']],
        });
    }

    private function sections(): array { return ['general', 'header', 'sidebar', 'footer', 'design', 'navigation']; }
    private function sectionTitle(): string { return match ($this->section) { 'general' => 'Layout tổng thể', 'header' => 'Header', 'sidebar' => 'Sidebar', 'footer' => 'Footer', 'design' => 'Giao diện & Theme', 'navigation' => 'Navigation' }; }
    private function sectionDescription(): string { return match ($this->section) { 'general' => 'Thiết lập workspace, container, mật độ, khoảng cách, surface và hành vi tổng thể.', 'header' => 'Quản lý nhận diện thương hiệu, thành phần, thao tác nhanh, UserMenu, presentation và responsive của Header Admin.', 'sidebar' => 'Quản lý Header Sidebar, tìm chức năng, Footer Sidebar, background và hành vi hiển thị.', 'footer' => 'Quản lý bản quyền, ngày giờ và cách trình bày Footer Admin.', 'design' => 'Quản lý theme và accent đang được Admin runtime sử dụng.', 'navigation' => 'Quản lý cache, active strategy và độ sâu navigation.' }; }

    private function authorizePermission(string $permission): void
    {
        $user = auth('admin')->user() ?: auth()->user();
        abort_unless($user?->can($permission), 403);
    }
}
