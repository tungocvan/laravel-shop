<?php

namespace Modules\Admin\Livewire\Settings;

use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Modules\Admin\Support\AdminLayoutManager;
use Modules\Admin\Services\AdminDesignService;
use Modules\Admin\Support\ThemeManager;
use Modules\Website\Services\HeaderMenuService;

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
        if ($section === 'sidebar') {
            data_set($this->config, 'sidebar.presentation.background', $this->sidebarBackgroundMode(data_get($this->config, 'sidebar.presentation.background')));
        }
        $this->themes = $themeManager->all();
        if ($section === 'sidebar') {
            $this->config['design'] = app(AdminDesignService::class)->sanitize((array) data_get($this->config, 'design', []));
        }

        if ($section === 'header' && count((array) data_get($this->config, 'header.user_menu_config.items', [])) === 0) {
            $currentMenuItems = $this->exportWebsiteHeaderMenuItems($headerMenuService);
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

    public string $presetName = '';
    public bool $presetIncludeTypography = true;
    public ?string $selectedSidebarPresetKey = null;
    public bool $presetIncludeLayout = true;

    public function applySidebarLayoutPreset(string $preset): void
    {
        abort_unless($this->section === 'sidebar', 404);
        $options = [
            'compact' => ['item_height' => '40', 'padding_x' => '8', 'padding_y' => '6', 'content_gap' => '8', 'item_gap' => '2', 'menu_border_width' => '1'],
            'comfortable' => ['item_height' => '44', 'padding_x' => '12', 'padding_y' => '8', 'content_gap' => '12', 'item_gap' => '6', 'menu_border_width' => '1'],
            'spacious' => ['item_height' => '48', 'padding_x' => '16', 'padding_y' => '12', 'content_gap' => '16', 'item_gap' => '8', 'menu_border_width' => '2'],
        ];
        abort_unless(isset($options[$preset]), 404);
        foreach ($options[$preset] as $field => $value) {
            $path = in_array($field, ['menu_border_width'], true)
                ? 'design.sidebar_menu.active.'.$field
                : 'design.sidebar_menu.item.'.$field;
            data_set($this->config, $path, $value);
        }
        data_set($this->config, 'design.sidebar_menu.active.menu_border_style', 'solid');
    }


    public function sidebarVisualPresets(): array
    {
        return [
            'emerald' => ['name' => 'FlexBiz Emerald', 'background' => '#087F5B', 'normal' => ['title' => '#FFFFFF', 'icon' => '#FFFFFF', 'background' => 'transparent'], 'hover' => ['title' => '#064E3B', 'icon' => '#064E3B', 'background' => '#D1FAE5'], 'active' => ['title' => '#065F46', 'icon' => '#065F46', 'background' => '#FFFFFF']],
            'slate' => ['name' => 'Corporate Slate', 'background' => '#0F172A', 'normal' => ['title' => '#E2E8F0', 'icon' => '#E2E8F0', 'background' => 'transparent'], 'hover' => ['title' => '#FFFFFF', 'icon' => '#FFFFFF', 'background' => '#334155'], 'active' => ['title' => '#FFFFFF', 'icon' => '#FFFFFF', 'background' => '#4F46E5']],
            'light' => ['name' => 'Minimal Light', 'background' => '#F8FAFC', 'normal' => ['title' => '#334155', 'icon' => '#334155', 'background' => 'transparent'], 'hover' => ['title' => '#0F172A', 'icon' => '#0F172A', 'background' => '#E2E8F0'], 'active' => ['title' => '#1D4ED8', 'icon' => '#1D4ED8', 'background' => '#DBEAFE']],
        ];
    }

    public function applySidebarVisualPreset(string $key): void
    {
        abort_unless($this->section === 'sidebar', 404);
        $presets = array_merge($this->sidebarVisualPresets(), (array) data_get($this->config, 'sidebar.visual_presets', []));
        abort_unless(isset($presets[$key]) && is_array($presets[$key]), 404);
        $preset = $presets[$key];
        $this->selectedSidebarPresetKey = str_starts_with($key, 'custom_') ? $key : null;
        data_set($this->config, 'sidebar.presentation.background', 'custom');
        data_set($this->config, 'sidebar.presentation.custom_background', $preset['background']);
        // Accent must always be a six-digit HEX; saved palettes may use transparent.
        $activeBackground = (string) data_get($preset, 'active.background', '');
        $activeText = (string) data_get($preset, 'active.title', '');
        $accent = preg_match('/^#[0-9a-fA-F]{6}$/', $activeBackground) === 1
            && strcasecmp($activeBackground, '#FFFFFF') !== 0
            ? $activeBackground
            : (preg_match('/^#[0-9a-fA-F]{6}$/', $activeText) === 1 ? $activeText : '#087F5B');
        data_set($this->config, 'sidebar.presentation.custom_accent', $accent);
        foreach (['normal' => 'item', 'hover' => 'item', 'active' => 'active'] as $state => $target) {
            $prefix = 'design.sidebar_menu.'.$target.'.';
            $base = $state === 'hover' ? 'hover_' : '';
            data_set($this->config, $prefix.$base.'title_color', $preset[$state]['title']);
            if ($state !== 'active') {
                data_set($this->config, $prefix.$base.'icon_color', $preset[$state]['icon']);
            } else {
                data_set($this->config, $prefix.'icon_color', $preset[$state]['icon']);
            }
            $mode = $state === 'active' ? 'menu_background_mode' : $base.'background_mode';
            $color = $state === 'active' ? 'menu_background_color' : $base.'background_color';
            data_set($this->config, $prefix.$mode, $preset[$state]['background'] === 'transparent' ? 'transparent' : 'color');
            if ($preset[$state]['background'] !== 'transparent') data_set($this->config, $prefix.$color, $preset[$state]['background']);
        }
        // Legacy color-only presets intentionally leave typography untouched.
        $typography = data_get($preset, 'typography');
        if (is_array($typography)) {
            foreach (['font_family', 'font_size', 'font_weight', 'icon_size'] as $field) {
                if (array_key_exists($field, $typography)) {
                    data_set($this->config, 'design.sidebar_menu.item.'.$field, $typography[$field]);
                }
            }
        }
        $layout = data_get($preset, 'layout');
        if (is_array($layout)) {
            foreach (['item_height', 'padding_x', 'padding_y', 'content_gap', 'item_gap'] as $field) {
                if (array_key_exists($field, $layout)) {
                    data_set($this->config, 'design.sidebar_menu.item.'.$field, $layout[$field]);
                }
            }
            foreach (['menu_border_color', 'menu_border_width', 'menu_border_style'] as $field) {
                if (array_key_exists($field, $layout)) {
                    data_set($this->config, 'design.sidebar_menu.active.'.$field, $layout[$field]);
                }
            }
        }
        $this->dispatch('sidebar-preset-applied', background: $preset['background'], accent: data_get($this->config, 'sidebar.presentation.custom_accent'));
    }


    public function applySidebarQuickProfile(string $key): void
    {
        abort_unless($this->section === 'sidebar', 404);
        $this->authorizePermission($this->updatePermission());
        $presets = array_merge($this->sidebarVisualPresets(), (array) data_get($this->config, 'sidebar.visual_presets', []));
        abort_unless(isset($presets[$key]) && is_array($presets[$key]), 404);
        $this->applySidebarVisualPreset($key);

        // Older color-only presets must not overwrite unrelated Sidebar settings.
        $snapshot = data_get($presets[$key], 'snapshot');
        if (is_array($snapshot)) {
            foreach (['enabled', 'expanded_width', 'collapsed_width', 'desktop_collapsible', 'mobile_drawer', 'persist_state', 'show_footer_profile', 'navigation_search_threshold', 'controls', 'header', 'footer', 'search'] as $field) {
                if (array_key_exists($field, (array) data_get($snapshot, 'sidebar', []))) {
                    data_set($this->config, 'sidebar.'.$field, $snapshot['sidebar'][$field]);
                }
            }
            if (is_array(data_get($snapshot, 'design.sidebar_menu'))) {
                data_set($this->config, 'design.sidebar_menu', app(AdminDesignService::class)->sanitize([
                    'sidebar_menu' => array_replace_recursive((array) data_get($this->config, 'design.sidebar_menu', []), $snapshot['design']['sidebar_menu']),
                ])['sidebar_menu']);
            }
        }
        $this->dispatch('notify', type: 'info', title: 'Đã nạp profile', message: 'Kiểm tra bản xem trước và nhấn Lưu Sidebar để áp dụng.', duration: 2400);
    }

    public function saveSidebarVisualPreset(AdminLayoutManager $manager, bool $updateExisting = false): void
    {
        abort_unless($this->section === 'sidebar', 404);
        $this->authorizePermission($this->updatePermission());
        $current = (array) data_get($manager->config(), 'sidebar.visual_presets', []);
        $selected = $this->selectedSidebarPresetKey;
        if ($updateExisting && (!$selected || !array_key_exists($selected, $current))) {
            $this->addError('presetName', 'Vui lòng chọn một bộ màu tùy chỉnh đã lưu để cập nhật.');
            return;
        }
        if (!$updateExisting) {
            $this->validate(['presetName' => 'required|string|min:2|max:40']);
        }
        $name = $updateExisting ? (string) $current[$selected]['name'] : trim($this->presetName);
        if (!$updateExisting && count($current) >= 6) {
            $this->addError('presetName', 'Tối đa 6 bộ màu tùy chỉnh.');
            return;
        }
        $menu = (array) data_get($this->config, 'design.sidebar_menu', []);
        $read = static fn (string $path, string $fallback): string => (string) data_get($menu, $path, $fallback);
        $key = $updateExisting ? $selected : 'custom_'.(max(array_map(
            static fn ($key): int => preg_match('/^custom_(\\d+)$/', (string) $key, $matches) ? (int) $matches[1] : 0,
            array_keys($current)
        ) ?: [0]) + 1);
        $current[$key] = [
            'name' => $name,
            'background' => (string) data_get($this->config, 'sidebar.presentation.custom_background', '#087F5B'),
            'normal' => ['title' => $read('item.title_color', '#FFFFFF'), 'icon' => $read('item.icon_color', '#FFFFFF'), 'background' => $read('item.background_mode', 'transparent') === 'color' ? $read('item.background_color', '#087F5B') : 'transparent'],
            'hover' => ['title' => $read('item.hover_title_color', '#064E3B'), 'icon' => $read('item.hover_icon_color', '#064E3B'), 'background' => $read('item.hover_background_mode', 'transparent') === 'color' ? $read('item.hover_background_color', '#D1FAE5') : 'transparent'],
            'active' => ['title' => $read('active.title_color', '#065F46'), 'icon' => $read('active.icon_color', '#065F46'), 'background' => $read('active.menu_background_mode', 'transparent') === 'color' ? $read('active.menu_background_color', '#FFFFFF') : 'transparent'],
        ];
        if ($this->presetIncludeTypography) {
            $current[$key]['typography'] = array_intersect_key(
                (array) data_get($this->config, 'design.sidebar_menu.item', []),
                array_flip(['font_family', 'font_size', 'font_weight', 'icon_size'])
            );
        }
        if ($this->presetIncludeLayout) {
            $current[$key]['layout'] = array_merge(
                array_intersect_key((array) data_get($this->config, 'design.sidebar_menu.item', []), array_flip(['item_height', 'padding_x', 'padding_y', 'content_gap', 'item_gap'])),
                array_intersect_key((array) data_get($this->config, 'design.sidebar_menu.active', []), array_flip(['menu_border_color', 'menu_border_width', 'menu_border_style']))
            );
        }
        $current[$key]['snapshot'] = [
            'sidebar' => array_intersect_key((array) data_get($this->config, 'sidebar', []), array_flip([
                'enabled', 'expanded_width', 'collapsed_width', 'desktop_collapsible', 'mobile_drawer',
                'persist_state', 'show_footer_profile', 'navigation_search_threshold', 'controls', 'header', 'footer', 'search',
            ])),
            'design' => ['sidebar_menu' => (array) data_get($this->config, 'design.sidebar_menu', [])],
        ];
        $this->selectedSidebarPresetKey = $key;
        $payload = $manager->config();
        data_set($payload, 'sidebar.visual_presets', $current);
        $manager->save($payload);
        data_set($this->config, 'sidebar.visual_presets', $current);
        $this->presetName = '';
        $this->dispatch('notify', type: 'success', title: $updateExisting ? 'Đã cập nhật bộ màu' : 'Đã lưu bộ màu', message: 'Bộ màu đã được lưu để sử dụng sau.', duration: 1800);
    }

    public function save(AdminLayoutManager $manager): void
    {
        $this->authorizePermission($this->updatePermission());
        $validated = $this->validate($this->rules())['config'];
        if ($this->section === 'sidebar') {
            $designService = app(AdminDesignService::class);
            $validated['design']['sidebar_menu'] = data_get($validated, 'design.sidebar_menu', []);
            $validated['design']['sidebar_menu'] = data_get($designService->sanitize(array_replace_recursive((array) data_get($manager->config(), 'design', []), ['sidebar_menu' => $validated['design']['sidebar_menu']])), 'sidebar_menu');
        }
        $manager->save(array_replace_recursive($manager->config(), $validated));
        $this->config = $manager->config();
        $this->dispatch('admin-layout-updated');

        if ($this->section === 'sidebar') {
            // Keep the Alpine tab, URL fragment and scroll position after saving.
            // A full-page redirect would reset the editor to its initial tab.
            $this->dispatch('notify', type: 'success', title: 'Đã lưu Sidebar', message: 'Thiết lập Sidebar đã được lưu và áp dụng.', duration: 2400);
            return;
        }

        if (in_array($this->section, ['general', 'header', 'footer'], true)) {
            $messages = ['general' => 'Thiết lập Layout tổng thể đã được lưu và áp dụng.', 'header' => 'Thiết lập Header đã được lưu và áp dụng.', 'footer' => 'Thiết lập Footer đã được lưu và áp dụng.'];
            session()->flash('success', $messages[$this->section]);
            $this->redirect(url()->previous(), navigate: false);
            return;
        }

        $this->dispatch('notify', type: 'success', title: 'Đã lưu cấu hình', message: 'Thiết lập đã được lưu và áp dụng cho giao diện Admin.', duration: 1800);
    }

    public function resetSection(AdminLayoutManager $manager): void
    {
        $this->authorizePermission($this->updatePermission());
        $manager->save(array_replace_recursive($manager->config(), $this->sectionPayload($manager->defaults())));
        $this->config = $manager->config();
        $this->dispatch('admin-layout-updated');

        if (in_array($this->section, ['general', 'header', 'sidebar', 'footer'], true)) {
            $messages = ['general' => 'Layout tổng thể đã được khôi phục mặc định và áp dụng.', 'header' => 'Header đã được khôi phục mặc định và áp dụng.', 'sidebar' => 'Sidebar đã được khôi phục mặc định và áp dụng.', 'footer' => 'Footer đã được khôi phục mặc định và áp dụng.'];
            session()->flash('warning', $messages[$this->section]);
            $this->redirect(url()->previous(), navigate: false);
            return;
        }

        $this->dispatch('notify', type: 'warning', title: 'Đã khôi phục mặc định', message: 'Khu vực cấu hình hiện tại đã được khôi phục và áp dụng.', duration: 1800);
    }

    public function render()
    {
        if ($this->section === 'header') return view('Admin::livewire.settings.admin-header-config', ['sectionTitle' => $this->sectionTitle(), 'sectionDescription' => $this->sectionDescription()]);
        if ($this->section === 'sidebar') {
            $design = app(AdminDesignService::class);
            return view('Admin::livewire.settings.admin-sidebar-config', [
                'sectionTitle' => $this->sectionTitle(), 'sectionDescription' => $this->sectionDescription(),
                'colorOptions' => $design->colorOptions(),
                'menuFontFamilyOptions' => $design->menuFontFamilyOptions(),
                'menuFontSizeOptions' => $design->menuFontSizeOptions(),
            ]);
        }
        if ($this->section === 'footer') return view('Admin::livewire.settings.admin-footer-config', ['sectionTitle' => $this->sectionTitle(), 'sectionDescription' => $this->sectionDescription()]);
        return view('Admin::livewire.settings.admin-layout-config', ['sectionTitle' => $this->sectionTitle(), 'sectionDescription' => $this->sectionDescription()]);
    }

    private function exportWebsiteHeaderMenuItems(HeaderMenuService $headerMenuService): array
    {
        return $headerMenuService->getMenuTreeByLocation('admin')->map(function ($item, int $index) {
            $url = is_string($item->url) ? trim($item->url) : '';
            if ($url === '' || ! str_starts_with($url, '/') || str_starts_with($url, '//')) return null;
            return ['enabled' => true, 'label' => mb_substr((string) $item->title, 0, 80), 'icon' => $this->headerMenuConfigIcon($item->icon), 'url' => mb_substr($url, 0, 255), 'target' => $item->target === '_blank' ? '_blank' : '_self', 'permission' => null, 'order' => (int) ($item->sort_order ?? $index)];
        })->filter()->values()->all();
    }

    private function headerMenuConfigIcon(mixed $icon): string
    {
        $icon = is_string($icon) ? strtolower($icon) : '';
        return match (true) { str_contains($icon, 'user') => 'user', str_contains($icon, 'gear'), str_contains($icon, 'cog') => 'gear', str_contains($icon, 'lock') => 'lock', str_contains($icon, 'key') => 'key', str_contains($icon, 'shield') => 'shield', default => 'link' };
    }

    private function sidebarBackgroundMode(mixed $value): string
    {
        return match ((string) $value) {
            'system', 'white' => 'light',
            'theme', 'light', 'dark', 'custom' => (string) $value,
            default => 'theme',
        };
    }

    private function rules(): array
    {
        $spacing = 'required|in:0,1,2,3,4,5,6,8,10,12';
        $sidebarWidth = ['required', 'string', 'regex:/^\d+(?:\.\d+)?(?:px|rem)$/'];
        $hex = ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'];
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
                'config.sidebar.enabled' => 'boolean', 'config.sidebar.expanded_width' => $sidebarWidth, 'config.sidebar.collapsed_width' => $sidebarWidth, 'config.sidebar.desktop_collapsible' => 'boolean', 'config.sidebar.mobile_drawer' => 'boolean', 'config.sidebar.persist_state' => 'boolean', 'config.sidebar.show_footer_profile' => 'boolean', 'config.sidebar.navigation_search_threshold' => 'required|integer|min:4|max:50',
                'config.sidebar.controls.collapse_enabled' => 'boolean', 'config.sidebar.controls.fullscreen_enabled' => 'boolean',
                'config.sidebar.header.enabled' => 'boolean', 'config.sidebar.header.show_mark' => 'boolean', 'config.sidebar.header.show_title' => 'boolean', 'config.sidebar.header.title' => 'nullable|string|max:80', 'config.sidebar.header.show_subtitle' => 'boolean', 'config.sidebar.header.subtitle' => 'required|string|max:80',
                'config.sidebar.footer.enabled' => 'boolean', 'config.sidebar.footer.show_avatar' => 'boolean', 'config.sidebar.footer.show_name' => 'boolean', 'config.sidebar.footer.show_subtitle' => 'boolean', 'config.sidebar.footer.subtitle' => 'required|string|max:80',
                'config.sidebar.search.enabled' => 'boolean', 'config.sidebar.presentation.background' => 'required|in:theme,light,dark,custom', 'config.sidebar.presentation.custom_background' => ['exclude_unless:config.sidebar.presentation.background,custom', 'required', 'regex:/^#[0-9A-Fa-f]{6}$/'], 'config.sidebar.presentation.custom_accent' => ['exclude_unless:config.sidebar.presentation.background,custom', 'required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            ],
            'footer' => [
                'config.layout.show_footer' => 'boolean', 'config.footer.show_app_name' => 'boolean', 'config.footer.copyright.enabled' => 'boolean', 'config.footer.copyright.owner' => 'nullable|string|max:120', 'config.footer.copyright.url' => 'nullable|string|max:255', 'config.footer.copyright.start_year' => 'nullable|integer|min:1900|max:'.date('Y'),
                'config.footer.datetime.show_date' => 'boolean', 'config.footer.datetime.show_time' => 'boolean', 'config.footer.datetime.date_format' => 'required|in:d/m/Y', 'config.footer.datetime.time_format' => 'required|in:H:i:s',
                'config.footer.presentation.alignment' => 'required|in:split,center', 'config.footer.presentation.background' => 'required|in:system,transparent', 'config.footer.presentation.divider' => 'required|in:subtle,none', 'config.footer.presentation.compact' => 'boolean',
            ],
            'design' => ['config.theme.default' => 'required|in:'.implode(',', $this->themes ?: ['corporate-blue']), 'config.theme.dark_mode' => 'required|in:class', 'config.theme.accent' => 'required|in:blue,indigo,emerald,rose,amber'],
            'navigation' => ['config.navigation.cache_ttl' => 'required|integer|min:60|max:86400', 'config.navigation.active_strategy' => 'required|in:url-prefix', 'config.navigation.max_depth' => 'required|integer|min:1|max:3'],
        ];
        if ($this->section === 'sidebar') {
            $design = app(AdminDesignService::class);
            $menuColor = ['required', 'string', 'max:32', function (string $attribute, mixed $value, \Closure $fail) use ($design): void {
                if (! $design->isMenuColorReference($value)) $fail('Màu menu phải là preset hợp lệ hoặc mã HEX #RRGGBB.');
            }];
            foreach (['item', 'submenu', 'active', 'group'] as $area) {
                foreach ((array) data_get($this->config, 'design.sidebar_menu.'.$area, []) as $key => $value) {
                    $field = 'config.design.sidebar_menu.'.$area.'.'.$key;
                    if (str_contains($key, 'color')) $rules['sidebar'][$field] = $menuColor;
                    elseif (str_ends_with($key, '_mode')) $rules['sidebar'][$field] = 'required|in:transparent,color';
                    elseif (str_contains($key, 'font_family')) $rules['sidebar'][$field] = 'required|in:'.implode(',', AdminDesignService::menuFontFamilyKeys());
                    elseif (str_contains($key, 'font_size')) $rules['sidebar'][$field] = 'required|in:12,13,sm,15,base';
                    elseif (str_contains($key, 'font_weight')) $rules['sidebar'][$field] = 'required|in:normal,medium,semibold,bold';
                    elseif (str_contains($key, 'border_style')) $rules['sidebar'][$field] = 'required|in:solid,dashed,dotted,double';
                    elseif (is_scalar($value)) $rules['sidebar'][$field] = 'required|integer|min:0|max:64';
                }
            }
        }
        return $rules[$this->section];
    }

    private function sectionPayload(array $config): array
    {
        return match ($this->section) {
            'general' => ['locale' => data_get($config, 'locale'), 'layout' => ['preset' => data_get($config, 'layout.preset'), 'container' => data_get($config, 'layout.container'), 'density' => data_get($config, 'layout.density'), 'sticky_header' => data_get($config, 'layout.sticky_header'), 'spacing' => data_get($config, 'layout.spacing', []), 'surface' => data_get($config, 'layout.surface', []), 'behavior' => data_get($config, 'layout.behavior', [])]],
            'header' => ['header' => $config['header'] ?? []], 'sidebar' => ['sidebar' => $config['sidebar'] ?? [], 'design' => ['sidebar_menu' => data_get($config, 'design.sidebar_menu', [])]], 'footer' => ['layout' => ['show_footer' => data_get($config, 'layout.show_footer')], 'footer' => $config['footer'] ?? []], 'design' => ['theme' => $config['theme'] ?? []], 'navigation' => ['navigation' => $config['navigation'] ?? []],
        };
    }

    private function generalPreset(string $preset): array
    {
        $base = ['preset' => $preset, 'surface' => ['page_background' => 'system', 'content_surface' => 'transparent', 'border' => 'system', 'radius' => 'lg'], 'behavior' => ['reduced_motion' => true]];
        return array_replace_recursive($base, match ($preset) {
            'data-heavy' => ['container' => 'full', 'density' => 'compact', 'spacing' => ['content_padding_x' => '5', 'content_padding_top' => '4', 'content_padding_bottom' => '5', 'section_gap' => '4', 'tablet_padding_x' => '4', 'mobile_padding_x' => '3']],
            'focus' => ['container' => 'narrow', 'density' => 'comfortable', 'spacing' => ['content_padding_x' => '6', 'content_padding_top' => '6', 'content_padding_bottom' => '8', 'section_gap' => '6', 'tablet_padding_x' => '5', 'mobile_padding_x' => '4']],
            'settings' => ['container' => 'screen-2xl', 'density' => 'compact', 'spacing' => ['content_padding_x' => '6', 'content_padding_top' => '5', 'content_padding_bottom' => '6', 'section_gap' => '5', 'tablet_padding_x' => '4', 'mobile_padding_x' => '3']],
            default => ['container' => '7xl', 'density' => 'comfortable', 'spacing' => ['content_padding_x' => '6', 'content_padding_top' => '5', 'content_padding_bottom' => '6', 'section_gap' => '5', 'tablet_padding_x' => '5', 'mobile_padding_x' => '4']],
        });
    }

    private function updatePermission(): string
    {
        return $this->section === 'header' ? 'admin.header.update' : 'admin.layout.update';
    }

    private function authorizePermission(string $permission): void
    {
        $user = auth('admin')->user();
        abort_unless($user && Gate::forUser($user)->allows($permission), 403);
    }

    private function sectionTitle(): string
    {
        return match ($this->section) { 'general' => 'Cấu hình tổng thể', 'header' => 'Cấu hình Header', 'sidebar' => 'Cấu hình Sidebar', 'footer' => 'Cấu hình Footer', 'design' => 'Thiết kế & giao diện', 'navigation' => 'Điều hướng', default => 'Cấu hình Layout' };
    }

    private function sectionDescription(): string
    {
        return match ($this->section) { 'general' => 'Điều chỉnh preset, mật độ, chiều rộng nội dung và khoảng cách toàn cục.', 'header' => 'Điều chỉnh thương hiệu, hành động nhanh, menu tài khoản và cách Header thích ứng theo thiết bị.', 'sidebar' => 'Điều chỉnh kích thước, hành vi thu gọn, tìm kiếm và trình bày Sidebar.', 'footer' => 'Điều chỉnh bản quyền, ngày giờ và cách Footer hiển thị trong Admin Shell.', 'design' => 'Chọn theme mặc định và màu nhấn dùng trong Admin Shell.', 'navigation' => 'Điều chỉnh cache và chiến lược xác định menu đang hoạt động.', default => 'Điều chỉnh Admin Shell.' };
    }

    private function sections(): array
    {
        return ['general', 'header', 'sidebar', 'footer', 'design', 'navigation'];
    }
}
