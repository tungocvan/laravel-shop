<?php

namespace Modules\Website\Livewire\Admin\Header;

use Illuminate\Support\Str;
use Livewire\Component;
use Modules\System\Services\SettingsService;
use Modules\Website\Livewire\Concerns\AuthorizesAdminPermissions;
use Modules\Website\Services\HeaderComponentRegistry;
use Modules\Website\Services\HeaderPresentationService;
use Modules\Website\Services\PharmaBrandAppearanceService;

class HeaderSettingsHub extends Component
{
    use AuthorizesAdminPermissions;

    public $activeTab = 'general';
    public string $editingTemplate = 'default';
    public string $publishedTemplate = 'default';
    public array $builderSlots = [];
    public array $presentation = [];
    public array $layoutThemes = [];
    public array $accountSettings = [];
    public array $topbarSettings = [];
    public array $brandAppearance = [];
    public string $selectedTheme = '';
    public string $activeTheme = '';
    public string $starterProfile = '';
    public string $themeName = '';

    private const THEME_VERSION = 2;
    private const MAX_THEMES = 20;

    private const BUILDER_SLOTS = [
        'desktop.topbar',
        'desktop.main.left',
        'desktop.main.center',
        'desktop.main.right',
        'mobile.search',
        'mobile.drawer',
    ];

    public function mount(SettingsService $settingsService, HeaderPresentationService $presentationService): void
    {
        $this->publishedTemplate = $settingsService->get('website.public_template', 'default') === 'pharma' ? 'pharma' : 'default';
        $this->editingTemplate = $this->publishedTemplate;
        $this->loadTemplateSettings($settingsService, $presentationService);
    }

    public function updatedEditingTemplate(SettingsService $settingsService, HeaderPresentationService $presentationService): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        $this->editingTemplate = $this->editingTemplate === 'pharma' ? 'pharma' : 'default';
        $this->loadTemplateSettings($settingsService, $presentationService);
    }

    private function settingKey(string $suffix): string
    {
        return $this->editingTemplate === 'pharma' ? 'website.pharma_header.'.$suffix : 'header.'.$suffix;
    }

    private function defaultLayout(): array
    {
        return $this->editingTemplate === 'pharma'
            ? (array) config('website.header.pharma_layout', [])
            : (array) config('website.header.layout', []);
    }

    private function loadTemplateSettings(SettingsService $settingsService, HeaderPresentationService $presentationService): void
    {
        $savedLayout = $settingsService->get($this->settingKey('layout'));
        $this->loadBuilderLayout(is_array($savedLayout) ? $savedLayout : $this->defaultLayout());
        if ($this->editingTemplate === 'pharma') {
            $this->normalizePharmaBuilder();
        }
        $savedPresentation = $settingsService->get($this->settingKey('presentation'));
        $this->presentation = $presentationService->resolve(is_array($savedPresentation) ? $savedPresentation : null);
        $savedThemes = $settingsService->get($this->settingKey('layout_themes'), []);
        $this->layoutThemes = is_array($savedThemes) ? $savedThemes : [];
        $this->selectedTheme = '';
        $this->starterProfile = '';
        $active = $settingsService->get($this->settingKey('active_theme'), '');
        $this->activeTheme = is_string($active) && isset($this->layoutThemes[$active]) ? $active : '';
        $this->themeName = '';
        $this->accountSettings = $this->normalizeAccountSettings($settingsService->get('website.pharma_header.account'));
        $this->topbarSettings = $this->normalizeTopbarSettings($settingsService->get('website.pharma_header.topbar'));
        $this->brandAppearance = app(PharmaBrandAppearanceService::class)->resolve($settingsService->get('website.pharma_header.brand_appearance'));
        $this->resetErrorBag();
    }

    private function normalizeAccountSettings(mixed $saved): array
    {
        $defaults = ['enabled' => true, 'style' => 'avatar_name', 'show_name' => true, 'show_orders' => true, 'show_admin' => true, 'show_profile' => true, 'show_password' => true];
        $values = is_array($saved) ? $saved : [];
        return [
            'enabled' => (bool) ($values['enabled'] ?? true),
            'style' => in_array($values['style'] ?? null, ['avatar_name', 'avatar', 'button'], true) ? $values['style'] : $defaults['style'],
            'show_name' => (bool) ($values['show_name'] ?? true),
            'show_orders' => (bool) ($values['show_orders'] ?? true),
            'show_admin' => (bool) ($values['show_admin'] ?? true),
            'show_profile' => (bool) ($values['show_profile'] ?? true),
            'show_password' => (bool) ($values['show_password'] ?? true),
        ];
    }

    /** Account edits belong to the profile snapshot, never to the live Header directly. */
    public function saveAccountSettings(SettingsService $settingsService): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if ($this->editingTemplate !== 'pharma') {
            return;
        }
        $this->addError('theme', 'Hãy dùng Cập nhật tham số profile để lưu Account Dropdown.');
    }

    private function normalizeTopbarSettings(mixed $saved): array
    {
        $values = is_array($saved) ? $saved : [];
        return [
            'enabled' => (bool) ($values['enabled'] ?? true),
            'show_desktop' => (bool) ($values['show_desktop'] ?? true),
            'show_mobile' => (bool) ($values['show_mobile'] ?? false),
            'left_text' => mb_substr((string) ($values['left_text'] ?? 'THÔNG TIN DƯỢC PHẨM · TRA CỨU VÀ THAM KHẢO'), 0, 180),
            'right_text' => mb_substr((string) ($values['right_text'] ?? 'Thông tin không thay thế tư vấn chuyên môn y tế'), 0, 180),
            'show_hotline' => (bool) ($values['show_hotline'] ?? false),
            'show_email' => (bool) ($values['show_email'] ?? false),
        ];
    }

    public function saveTopbarSettings(SettingsService $settingsService): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if ($this->editingTemplate === 'pharma' && $this->activeTheme !== '') {
            $this->addError('theme', 'Header Pharma đang quản lý bằng profile. Hãy dùng Cập nhật tham số profile rồi kích hoạt profile để xuất bản.');
            return;
        }
        if ($this->editingTemplate !== 'pharma') {
            return;
        }
        $this->validate([
            'topbarSettings.left_text' => ['nullable', 'string', 'max:180'],
            'topbarSettings.right_text' => ['nullable', 'string', 'max:180'],
        ]);
        $this->topbarSettings = $this->normalizeTopbarSettings($this->topbarSettings);
        $settingsService->set('website.pharma_header.topbar', $this->topbarSettings, 'header', 'json');
        $this->dispatch('show-toast', [['type' => 'success', 'message' => 'Đã lưu Topbar Pharma.']]);
    }

    public function saveBrandAppearance(SettingsService $settingsService, PharmaBrandAppearanceService $service): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if ($this->editingTemplate === 'pharma' && $this->activeTheme !== '') {
            $this->addError('theme', 'Header Pharma đang quản lý bằng profile. Hãy dùng Cập nhật tham số profile rồi kích hoạt profile để xuất bản.');
            return;
        }
        if ($this->editingTemplate !== 'pharma') {
            return;
        }
        $this->brandAppearance = $service->resolve($this->brandAppearance);
        $settingsService->set('website.pharma_header.brand_appearance', $this->brandAppearance, 'header', 'json');
        $saved = $settingsService->get('website.pharma_header.brand_appearance');
        if (! is_array($saved) || $service->resolve($saved) !== $this->brandAppearance) {
            $this->addError('brandAppearance', 'Không thể xác nhận cấu hình thương hiệu đã lưu.');
            return;
        }
        $this->resetErrorBag('brandAppearance');
        $this->dispatch('show-toast', [['type' => 'success', 'message' => 'Đã lưu thương hiệu và slogan.']]);
    }

    public function resetBrandAppearance(PharmaBrandAppearanceService $service): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if ($this->editingTemplate !== 'pharma') {
            return;
        }
        $this->brandAppearance = $service->resolve(null);
    }

    public function setTopbarDeviceVisibility(string $visibility): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if (! in_array($visibility, ['both', 'desktop', 'mobile', 'hidden'], true)) {
            return;
        }

        $this->topbarSettings['show_desktop'] = in_array($visibility, ['both', 'desktop'], true);
        $this->topbarSettings['show_mobile'] = in_array($visibility, ['both', 'mobile'], true);
    }

    public function setComponentVisibility(string $slot, int $index, string $visibility): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if (! $this->hasItem($slot, $index) || ! in_array($visibility, ['both', 'desktop', 'mobile', 'hidden'], true)) {
            return;
        }

        if ($this->editingTemplate === 'pharma' && ($this->builderSlots[$slot][$index]['type'] ?? null) === 'mobile-menu') {
            $visibility = $visibility === 'hidden' ? 'hidden' : 'mobile';
        }
        $this->builderSlots[$slot][$index]['enabled'] = $visibility !== 'hidden';
        $this->builderSlots[$slot][$index]['config']['visibility'] = $visibility;
    }

    public function toggleComponent(string $slot, int $index): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if (! $this->hasItem($slot, $index)) {
            return;
        }

        $this->builderSlots[$slot][$index]['enabled'] = ! (bool) ($this->builderSlots[$slot][$index]['enabled'] ?? true);
    }

    public function moveUp(string $slot, int $index): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if ($index <= 0 || ! $this->hasItem($slot, $index)) {
            return;
        }

        [$this->builderSlots[$slot][$index - 1], $this->builderSlots[$slot][$index]] = [
            $this->builderSlots[$slot][$index],
            $this->builderSlots[$slot][$index - 1],
        ];
        $this->builderSlots[$slot] = array_values($this->builderSlots[$slot]);
    }

    public function moveDown(string $slot, int $index): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if (! isset($this->builderSlots[$slot][$index], $this->builderSlots[$slot][$index + 1])) {
            return;
        }

        [$this->builderSlots[$slot][$index], $this->builderSlots[$slot][$index + 1]] = [
            $this->builderSlots[$slot][$index + 1],
            $this->builderSlots[$slot][$index],
        ];
        $this->builderSlots[$slot] = array_values($this->builderSlots[$slot]);
    }

    public function moveComponent(string $fromSlot, int $index, string $toSlot, HeaderComponentRegistry $registry): void
    {
        $this->moveComponentByDrag($fromSlot, $index, $toSlot, count($this->builderSlots[$toSlot] ?? []), $registry);
    }

    public function moveComponentByDrag(
        string $fromSlot,
        int $fromIndex,
        string $toSlot,
        int $toIndex,
        HeaderComponentRegistry $registry
    ): void {
        $this->authorizeAdminPermission('website.settings.manage');
        $this->resetErrorBag('builder');

        if (! in_array($fromSlot, self::BUILDER_SLOTS, true)
            || ! in_array($toSlot, self::BUILDER_SLOTS, true)
            || ! $this->hasItem($fromSlot, $fromIndex)) {
            return;
        }

        $item = $this->builderSlots[$fromSlot][$fromIndex];
        try {
            $registry->resolve((string) ($item['type'] ?? ''), $toSlot);
        } catch (\InvalidArgumentException) {
            $this->addError('builder', 'Component không được phép chuyển vào vị trí đã chọn.');
            return;
        }

        array_splice($this->builderSlots[$fromSlot], $fromIndex, 1);
        $this->builderSlots[$fromSlot] = array_values($this->builderSlots[$fromSlot]);
        $toIndex = max(0, min($toIndex, count($this->builderSlots[$toSlot] ?? [])));
        array_splice($this->builderSlots[$toSlot], $toIndex, 0, [$item]);
        $this->builderSlots[$toSlot] = array_values($this->builderSlots[$toSlot]);
    }

    /**
     * Persist the Pharma builder with device choices supplied by the browser.
     * The old deferred $wire.set(..., false) did not reliably reach the
     * server action and could silently restore the previous saved layout.
     */
    public function savePharmaBuilder(
        array $visibilityOverrides,
        SettingsService $settingsService,
        HeaderComponentRegistry $registry,
        HeaderPresentationService $presentationService
    ): void {
        $this->authorizeAdminPermission('website.settings.manage');
        if ($this->editingTemplate === 'pharma' && $this->activeTheme !== '') {
            $this->addError('theme', 'Header Pharma đang quản lý bằng profile. Hãy dùng Cập nhật tham số profile rồi kích hoạt profile để xuất bản.');
            return;
        }
        if ($this->editingTemplate !== 'pharma') {
            return;
        }

        $this->normalizePharmaBuilder();
        foreach ($this->builderSlots as $slot => &$items) {
            foreach ($items as &$item) {
                $type = $item['type'] ?? '';
                if (! array_key_exists($type, $visibilityOverrides)) {
                    continue;
                }
                $visibility = $visibilityOverrides[$type];
                if (! is_string($visibility) || ! in_array($visibility, ['both', 'desktop', 'mobile', 'hidden'], true)) {
                    continue;
                }
                if ($type === 'mobile-menu') {
                    $visibility = $visibility === 'hidden' ? 'hidden' : 'mobile';
                }
                $item['enabled'] = $visibility !== 'hidden';
                $item['config'] = is_array($item['config'] ?? null) ? $item['config'] : [];
                $item['config']['visibility'] = $visibility;
            }
            unset($item);
        }
        unset($items);

        // Save the already-normalized layout once. A second normalization inside
        // saveBuilder() can reinterpret hidden Search visibility as a legacy
        // desktop/mobile combination and silently overwrite the user's choice.
        $layout = $this->safeLayout($this->builderSlots, $registry);
        $presentation = $presentationService->resolve($this->presentation);
        $key = $this->settingKey('layout');

        $settingsService->updateMany([
            $key => $layout,
            $this->settingKey('presentation') => $presentation,
            'website.pharma_header.brand_appearance' => app(PharmaBrandAppearanceService::class)->resolve($this->brandAppearance),
        ], 'header');

        // Verify actual storage read-back before reporting success.
        $stored = $settingsService->get($key);
        if (! is_array($stored) || $stored !== $layout) {
            $this->addError('builder', 'Không thể xác nhận bố cục đã được lưu. Vui lòng thử lại.');
            return;
        }

        $this->presentation = $presentation;
        $this->loadBuilderLayout($stored);
        $this->dispatch('show-toast', [[
            'type' => 'success',
            'message' => 'Đã lưu và xác nhận bố cục Header.',
        ]]);
    }

    /** Persist menu appearance independently of the builder layout and menu content. */
    public function saveMenuAppearance(SettingsService $settingsService, HeaderPresentationService $presentationService): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if ($this->editingTemplate === 'pharma' && $this->activeTheme !== '') {
            $this->addError('theme', 'Header Pharma đang quản lý bằng profile. Hãy dùng Cập nhật tham số profile rồi kích hoạt profile để xuất bản.');
            return;
        }
        if ($this->editingTemplate !== 'pharma') {
            return;
        }

        $presentation = $presentationService->resolve($this->presentation);
        $key = $this->settingKey('presentation');
        $settingsService->set($key, $presentation, 'header', 'json');
        $stored = $settingsService->get($key);
        if (! is_array($stored) || $presentationService->resolve($stored) !== $presentation) {
            $this->addError('menuAppearance', 'Không thể xác nhận thiết lập menu đã được lưu.');
            return;
        }

        $this->presentation = $presentation;
        $this->resetErrorBag('menuAppearance');
        $this->dispatch('show-toast', [['type' => 'success', 'message' => 'Đã lưu giao diện Menu điều hướng.']]);
    }

    public function resetMenuAppearance(HeaderPresentationService $presentationService): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if ($this->editingTemplate !== 'pharma') {
            return;
        }
        $defaults = $presentationService->resolve([]);
        $this->presentation['menu_style'] = $defaults['menu_style'];
    }

    public function saveBuilder(
        SettingsService $settingsService,
        HeaderComponentRegistry $registry,
        HeaderPresentationService $presentationService
    ): void {
        $this->authorizeAdminPermission('website.settings.manage');
        if ($this->editingTemplate === 'pharma' && $this->activeTheme !== '') {
            $this->addError('theme', 'Header Pharma đang quản lý bằng profile. Hãy dùng Cập nhật tham số profile rồi kích hoạt profile để xuất bản.');
            return;
        }
        if ($this->editingTemplate === 'pharma') {
            $this->normalizePharmaBuilder();
        }
        $layout = $this->safeLayout($this->builderSlots, $registry);
        $presentation = $presentationService->resolve($this->presentation);

        $settingsService->updateMany([
            $this->settingKey('layout') => $layout,
            $this->settingKey('presentation') => $presentation,
        ], 'header');

        $this->presentation = $presentation;
        $this->dispatch('show-toast', [[
            'type' => 'success',
            'message' => 'Đã lưu bố cục Header.',
        ]]);
    }

    public function resetBuilder(HeaderPresentationService $presentationService): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        $this->loadBuilderLayout($this->defaultLayout());
        if ($this->editingTemplate === 'pharma') {
            $this->normalizePharmaBuilder();
        }
        $this->presentation = $presentationService->resolve((array) config('website.header.presentation', []));
        $this->selectedTheme = '';
        $this->resetErrorBag(['builder', 'theme']);
    }

    public function saveTheme(
        SettingsService $settingsService,
        HeaderComponentRegistry $registry,
        HeaderPresentationService $presentationService
    ): void {
        $this->authorizeAdminPermission('website.settings.manage');
        $this->validateThemeName();
        if (count($this->layoutThemes) >= self::MAX_THEMES) {
            $this->addError('theme', 'Chỉ được lưu tối đa '.self::MAX_THEMES.' Header themes.');
            return;
        }

        $baseSlug = Str::slug($this->themeName) ?: 'header-theme';
        $slug = $baseSlug;
        $suffix = 2;
        while (isset($this->layoutThemes[$slug])) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        $this->layoutThemes[$slug] = $this->themeSnapshot($this->themeName, $registry, $presentationService);
        $this->selectedTheme = $slug;
        $this->persistThemes($settingsService);
        $this->dispatchThemeToast('Đã lưu Header theme mới.');
    }

    /** Preview-only starter profiles; never persist or publish without an explicit save. */
    public function previewStarterTheme(string $profile, HeaderPresentationService $presentationService): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if ($this->editingTemplate !== 'pharma') {
            return;
        }

        $presets = [
            'pharma-classic' => [
                'name' => 'Pharma Classic',
                'container' => 'standard',
                'size' => 'normal',
                'shadow' => 'soft',
                'colors' => ['background' => '#ffffff', 'foreground' => '#0f172a', 'accent' => '#047857', 'border' => '#d1fae5', 'topbar_background' => '#064e3b', 'topbar_foreground' => '#ffffff'],
                'menu_style' => ['primary' => ['font_size' => 14, 'font_weight' => '600', 'item_gap' => 20, 'hover_effect' => 'underline']],
            ],
            'flexbiz-modern' => [
                'name' => 'FlexBiz Modern',
                'container' => 'wide',
                'size' => 'comfortable',
                'shadow' => 'medium',
                'colors' => ['background' => '#ffffff', 'foreground' => '#0f172a', 'accent' => '#2563eb', 'border' => '#dbeafe', 'topbar_background' => '#1e3a8a', 'topbar_foreground' => '#ffffff'],
                'menu_style' => ['primary' => ['font_size' => 14, 'font_weight' => '700', 'item_gap' => 24, 'hover_effect' => 'color']],
            ],
        ];

        if (! isset($presets[$profile])) {
            $this->addError('theme', 'Mẫu Header không hợp lệ.');
            return;
        }

        $preset = $presets[$profile];
        $this->presentation = $presentationService->resolve(array_replace_recursive(
            $this->presentation,
            ['mode' => 'basic', 'inherit_colors' => false, 'container' => $preset['container'], 'size' => $preset['size'], 'shadow' => $preset['shadow'], 'colors' => $preset['colors'], 'menu_style' => $preset['menu_style']]
        ));
        // Starter profiles include an explicit canonical layout, not only colors.
        $this->loadBuilderLayout($this->defaultLayout());
        $this->normalizePharmaBuilder();
        $this->selectedTheme = '';
        $this->starterProfile = $profile;
        $this->themeName = $preset['name'];
        $this->resetErrorBag('theme');
        $this->dispatchThemeToast('Đã nạp mẫu '.$preset['name'].' vào Preview. Chọn Lưu thành theme mới để lưu mẫu, hoặc Lưu bố cục để xuất bản.');
    }

    /** Activate a built-in profile directly, reusing its saved snapshot when present. */
    public function activateStarterTheme(
        string $profile,
        SettingsService $settingsService,
        HeaderComponentRegistry $registry,
        HeaderPresentationService $presentationService
    ): void {
        $this->authorizeAdminPermission('website.settings.manage');
        if ($this->editingTemplate !== 'pharma' || ! in_array($profile, ['pharma-classic', 'flexbiz-modern'], true)) {
            return;
        }

        foreach ($this->layoutThemes as $slug => $theme) {
            if (is_array($theme) && ($theme['starter_profile'] ?? '') === $profile) {
                $this->selectedTheme = (string) $slug;
                $this->publishTheme($settingsService, $registry, $presentationService);
                return;
            }
        }

        if (count($this->layoutThemes) >= self::MAX_THEMES) {
            $this->addError('theme', 'Thư viện đã đủ 20 profile. Hãy giải phóng một vị trí trước khi kích hoạt mẫu mới.');
            return;
        }

        $this->previewStarterTheme($profile, $presentationService);
        $this->saveTheme($settingsService, $registry, $presentationService);
        if ($this->selectedTheme !== '') {
            $this->publishTheme($settingsService, $registry, $presentationService);
        }
    }

    public function applyTheme(HeaderComponentRegistry $registry, HeaderPresentationService $presentationService): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        $theme = $this->selectedThemeData();
        if ($theme === null) {
            return;
        }

        $this->loadBuilderLayout($this->safeLayout(is_array($theme['layout'] ?? null) ? $theme['layout'] : [], $registry));
        $this->presentation = $presentationService->resolve(is_array($theme['presentation'] ?? null) ? $theme['presentation'] : null);
        if ($this->editingTemplate === 'pharma') {
            $this->normalizePharmaBuilder();
        }
        if ($this->editingTemplate === 'pharma' && is_array($theme['account_settings'] ?? null)) {
            $this->accountSettings = $this->normalizeAccountSettings($theme['account_settings']);
        }
        if ($this->editingTemplate === 'pharma' && is_array($theme['topbar_settings'] ?? null)) {
            $this->topbarSettings = $this->normalizeTopbarSettings($theme['topbar_settings']);
        }
        if ($this->editingTemplate === 'pharma' && is_array($theme['brand_appearance'] ?? null)) {
            $this->brandAppearance = app(PharmaBrandAppearanceService::class)->resolve($theme['brand_appearance']);
        }
        $this->themeName = (string) ($theme['name'] ?? '');
        $this->starterProfile = is_string($theme['starter_profile'] ?? null) ? $theme['starter_profile'] : '';
        $this->resetErrorBag('theme');
        $this->dispatchThemeToast('Đã nạp theme vào Builder. Lưu bố cục, Tài khoản, Topbar và Thương hiệu riêng để xuất bản các thay đổi.');
    }

    /** Explicitly publish every profile-owned Pharma Header setting as one operation. */
    public function publishTheme(
        SettingsService $settingsService,
        HeaderComponentRegistry $registry,
        HeaderPresentationService $presentationService
    ): void {
        $this->authorizeAdminPermission('website.settings.manage');
        if ($this->editingTemplate !== 'pharma') {
            return;
        }
        $theme = $this->selectedThemeData();
        if ($theme === null) {
            return;
        }

        $this->loadBuilderLayout($this->safeLayout(is_array($theme['layout'] ?? null) ? $theme['layout'] : [], $registry));
        $this->normalizePharmaBuilder();
        $this->presentation = $presentationService->resolve(is_array($theme['presentation'] ?? null) ? $theme['presentation'] : null);
        $this->accountSettings = $this->normalizeAccountSettings($theme['account_settings'] ?? []);
        $this->topbarSettings = $this->normalizeTopbarSettings($theme['topbar_settings'] ?? []);
        if (is_array($theme['brand_appearance'] ?? null)) {
            $this->brandAppearance = app(PharmaBrandAppearanceService::class)->resolve($theme['brand_appearance']);
        }

        $settingsService->updateMany([
            $this->settingKey('layout') => $this->safeLayout($this->builderSlots, $registry),
            $this->settingKey('presentation') => $this->presentation,
            'website.pharma_header.account' => $this->accountSettings,
            'website.pharma_header.topbar' => $this->topbarSettings,
            'website.pharma_header.brand_appearance' => $this->brandAppearance,
            $this->settingKey('active_theme') => $this->selectedTheme,
        ], 'header');
        $this->activeTheme = $this->selectedTheme;
        $this->dispatchThemeToast('Đã nạp và áp dụng profile Header lên website.');
    }

    public function activateSelectedTheme(
        SettingsService $settingsService,
        HeaderComponentRegistry $registry,
        HeaderPresentationService $presentationService
    ): void {
        $this->publishTheme($settingsService, $registry, $presentationService);
    }

    public function updateTheme(
        array $visibilityOverrides,
        SettingsService $settingsService,
        HeaderComponentRegistry $registry,
        HeaderPresentationService $presentationService
    ): void {
        $this->authorizeAdminPermission('website.settings.manage');
        $theme = $this->selectedThemeData();
        if ($theme === null) {
            return;
        }
        if ($this->editingTemplate === 'pharma' && $this->activeTheme !== $this->selectedTheme) {
            $this->addError('theme', 'Chỉ có thể cập nhật tham số profile đang áp dụng. Hãy kích hoạt profile trước.');
            return;
        }

        // Advanced layout visibility controls are Alpine-owned until submitted.
        // Apply them to the profile snapshot, not to the live Header settings.
        if ($this->editingTemplate === 'pharma') {
            foreach ($this->builderSlots as &$items) {
                foreach ($items as &$item) {
                    $type = $item['type'] ?? '';
                    $visibility = $visibilityOverrides[$type] ?? null;
                    if (! is_string($visibility) || ! in_array($visibility, ['both', 'desktop', 'mobile', 'hidden'], true)) {
                        continue;
                    }
                    if ($type === 'mobile-menu') {
                        $visibility = $visibility === 'hidden' ? 'hidden' : 'mobile';
                    }
                    $item['enabled'] = $visibility !== 'hidden';
                    $item['config'] = is_array($item['config'] ?? null) ? $item['config'] : [];
                    $item['config']['visibility'] = $visibility;
                }
                unset($item);
            }
            unset($items);
        }

        $name = trim($this->themeName) !== '' ? trim($this->themeName) : (string) ($theme['name'] ?? 'Header Theme');
        $this->themeName = $name;
        $this->validateThemeName();
        $this->layoutThemes[$this->selectedTheme] = $this->themeSnapshot($name, $registry, $presentationService);
        $this->persistThemes($settingsService);
        $this->dispatchThemeToast('Đã cập nhật Header theme.');
    }

    public function renameTheme(SettingsService $settingsService): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if ($this->selectedThemeData() === null) {
            return;
        }

        $this->validateThemeName();
        $this->layoutThemes[$this->selectedTheme]['name'] = trim($this->themeName);
        $this->layoutThemes[$this->selectedTheme]['updated_at'] = now()->toIso8601String();
        $this->persistThemes($settingsService);
        $this->dispatchThemeToast('Đã đổi tên Header theme.');
    }

    public function deleteTheme(SettingsService $settingsService): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if ($this->selectedTheme === '' || ! isset($this->layoutThemes[$this->selectedTheme])) {
            $this->addError('theme', 'Hãy chọn Header theme cần xóa.');
            return;
        }

        unset($this->layoutThemes[$this->selectedTheme]);
        if ($this->activeTheme === $this->selectedTheme) {
            $this->activeTheme = '';
            $settingsService->set($this->settingKey('active_theme'), '', 'header', 'string');
        }
        $this->selectedTheme = '';
        $this->themeName = '';
        $this->persistThemes($settingsService);
        $this->dispatchThemeToast('Đã xóa Header theme.');
    }

    public function selectTheme(string $slug): void
    {
        if (! isset($this->layoutThemes[$slug])) {
            return;
        }

        $this->selectedTheme = $slug;
        $this->themeName = (string) ($this->layoutThemes[$slug]['name'] ?? '');
        $this->starterProfile = is_string($this->layoutThemes[$slug]['starter_profile'] ?? null) ? $this->layoutThemes[$slug]['starter_profile'] : '';
        $this->resetErrorBag('theme');
    }

    public function render(HeaderComponentRegistry $registry, HeaderPresentationService $presentationService)
    {
        return view('Website::livewire.admin.header.header-settings-hub', [
            'headerComponents' => $registry->all(),
            'previewPresentation' => $presentationService->resolve($this->presentation),
            'builderSlotNames' => [
                'desktop.topbar' => 'Topbar',
                'desktop.main.left' => 'Desktop · Trái',
                'desktop.main.center' => 'Desktop · Giữa',
                'desktop.main.right' => 'Desktop · Phải',
                'mobile.search' => 'Mobile · Tìm kiếm',
                'mobile.drawer' => 'Mobile · Drawer',
            ],
        ]);
    }

    private function themeSnapshot(string $name, HeaderComponentRegistry $registry, HeaderPresentationService $presentationService): array
    {
        return [
            'version' => self::THEME_VERSION,
            'starter_profile' => $this->editingTemplate === 'pharma' ? $this->starterProfile : '',
            'name' => trim($name),
            'layout' => $this->safeLayout($this->builderSlots, $registry),
            'presentation' => $presentationService->resolve($this->presentation),
            'brand_appearance' => $this->editingTemplate === 'pharma' ? app(PharmaBrandAppearanceService::class)->resolve($this->brandAppearance) : null,
            'account_settings' => $this->editingTemplate === 'pharma' ? $this->normalizeAccountSettings($this->accountSettings) : null,
            'topbar_settings' => $this->editingTemplate === 'pharma' ? $this->normalizeTopbarSettings($this->topbarSettings) : null,
            'updated_at' => now()->toIso8601String(),
        ];
    }

    private function safeLayout(array $source, HeaderComponentRegistry $registry): array
    {
        $layout = [];
        foreach (self::BUILDER_SLOTS as $slot) {
            $items = array_key_exists($slot, $source) ? $source[$slot] : data_get($source, $slot, []);
            $clean = [];
            foreach (is_array($items) ? $items : [] as $item) {
                if (! is_array($item) || ! is_string($item['type'] ?? null)) {
                    continue;
                }
                try {
                    $registry->resolve($item['type'], $slot);
                } catch (\InvalidArgumentException) {
                    continue;
                }
                $clean[] = [
                    'type' => $item['type'],
                    'enabled' => (bool) ($item['enabled'] ?? true),
                    'config' => is_array($item['config'] ?? null) ? $item['config'] : [],
                ];
            }
            data_set($layout, $slot, $clean);
        }
        return $layout;
    }

    private function loadBuilderLayout(array $layout): void
    {
        foreach (self::BUILDER_SLOTS as $slot) {
            $items = array_key_exists($slot, $layout) ? $layout[$slot] : data_get($layout, $slot, []);
            $this->builderSlots[$slot] = is_array($items) ? array_values($items) : [];
        }
    }

    /**
     * Pharma has one Search component. Older layouts stored separate desktop and
     * mobile Search entries; fold them into one canonical desktop Search entry.
     * The frontend uses its visibility for both breakpoints.
     */
    private function normalizePharmaBuilder(): void
    {
        foreach ($this->builderSlots['mobile.drawer'] ?? [] as $index => $item) {
            if (($item['type'] ?? null) === 'mobile-menu') {
                $this->builderSlots['mobile.drawer'][$index]['config']['visibility'] = ($item['enabled'] ?? true) ? 'mobile' : 'hidden';
            }
        }

        $search = null;
        $searchSlot = 'desktop.main.right';
        $desktopVisible = false;
        $mobileVisible = false;
        $explicit = null;

        foreach (self::BUILDER_SLOTS as $slot) {
            $kept = [];
            foreach ($this->builderSlots[$slot] ?? [] as $item) {
                if (($item['type'] ?? null) !== 'search') {
                    $kept[] = $item;
                    continue;
                }
                if ($search === null || ($searchSlot === 'desktop.main.right' && $slot !== 'mobile.search')) {
                    $search = $item;
                    if (str_starts_with($slot, 'desktop.main.')) {
                        $searchSlot = $slot;
                    }
                }
                $visibility = $item['config']['visibility'] ?? (($item['enabled'] ?? true) ? (str_starts_with($slot, 'mobile.') ? 'mobile' : 'desktop') : 'hidden');
                if ($slot !== 'mobile.search' && array_key_exists('visibility', $item['config'] ?? [])) {
                    $explicit = $visibility;
                }
                if (($item['enabled'] ?? true) && in_array($visibility, ['desktop', 'both'], true)) {
                    $desktopVisible = true;
                }
                if (($item['enabled'] ?? true) && in_array($visibility, ['mobile', 'both'], true)) {
                    $mobileVisible = true;
                }
            }
            $this->builderSlots[$slot] = array_values($kept);
        }

        if ($search === null) {
            return;
        }

        $visibility = ($explicit === null || ($explicit === 'hidden' && $mobileVisible))
            ? ($desktopVisible && $mobileVisible ? 'both' : ($desktopVisible ? 'desktop' : ($mobileVisible ? 'mobile' : 'hidden')))
            : $explicit;
        $search['enabled'] = $visibility !== 'hidden';
        $search['config'] = is_array($search['config'] ?? null) ? $search['config'] : [];
        $search['config']['visibility'] = $visibility;
        $this->builderSlots[$searchSlot][] = $search;
    }

    public function setPharmaComponentPosition(string $slot, int $index, string $position, HeaderComponentRegistry $registry): void
    {
        $this->authorizeAdminPermission('website.settings.manage');
        if ($this->editingTemplate !== 'pharma' || ! $this->hasItem($slot, $index)) {
            return;
        }
        if (($this->builderSlots[$slot][$index]['type'] ?? null) === 'mobile-menu') {
            return;
        }
        if (! in_array($position, ['desktop.main.left', 'desktop.main.center', 'desktop.main.right'], true)) {
            return;
        }
        $this->moveComponent($slot, $index, $position, $registry);
    }

    private function selectedThemeData(): ?array
    {
        if ($this->selectedTheme === '' || ! is_array($this->layoutThemes[$this->selectedTheme] ?? null)) {
            $this->addError('theme', 'Hãy chọn Header theme trước.');
            return null;
        }
        return $this->layoutThemes[$this->selectedTheme];
    }

    private function validateThemeName(): void
    {
        $this->themeName = trim($this->themeName);
        $this->validate(['themeName' => ['required', 'string', 'min:2', 'max:60']], [], ['themeName' => 'tên Header theme']);
    }

    private function persistThemes(SettingsService $settingsService): void
    {
        $settingsService->set($this->settingKey('layout_themes'), $this->layoutThemes, 'header', 'json');
        $this->resetErrorBag('theme');
    }

    private function dispatchThemeToast(string $message): void
    {
        $this->dispatch('show-toast', [[
            'type' => 'success',
            'message' => $message,
        ]]);
    }

    private function hasItem(string $slot, int $index): bool
    {
        return in_array($slot, self::BUILDER_SLOTS, true) && isset($this->builderSlots[$slot][$index]);
    }
}
