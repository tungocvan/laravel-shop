<?php

namespace Modules\Admin\Livewire\Settings;

use Livewire\Component;
use Modules\Admin\Services\AdminDesignService;
use Modules\Admin\Services\AdminThemeProfileService;
use Modules\Admin\Support\AdminLayoutManager;
use Modules\Admin\Support\ThemeManager;

class AdminThemeEditor extends Component
{
    public array $config = [];
    public array $profiles = [];
    public array $sidebarPalettes = [];
    public string $selectedTheme = AdminThemeProfileService::DEFAULT_PROFILE;
    public string $newThemeName = '';

    public function mount(AdminLayoutManager $layoutManager, AdminThemeProfileService $profileService, ThemeManager $themeManager, AdminDesignService $designService): void
    {
        $this->config = $layoutManager->config();
        $this->config['design'] = $designService->sanitize((array) data_get($this->config, 'design', []));
        $this->profiles = $profileService->profiles();
        $this->selectedTheme = $profileService->activeName();
        $this->sidebarPalettes = $themeManager->all();
    }

    public function updatedConfigDesignColorsSidebarHeaderBackground(): void { $this->activateRegionalSidebarColors(); }
    public function updatedConfigDesignColorsSidebarNavigationBackground(): void { $this->activateRegionalSidebarColors(); }
    public function updatedConfigDesignColorsSidebarFooterBackground(): void { $this->activateRegionalSidebarColors(); }

    public function selectTheme(string $name, AdminThemeProfileService $profileService): void
    {
        abort_unless(array_key_exists($name, $profileService->profiles()), 404);
        $this->selectedTheme = $name;
        $this->config = $profileService->apply($name, $this->config);
    }

    public function duplicateTheme(string $name, AdminThemeProfileService $profileService): void
    {
        $this->authorizePermission('admin.layout.update');
        $this->selectedTheme = $profileService->duplicate($name);
        $this->profiles = $profileService->profiles();
        $this->config = $profileService->apply($this->selectedTheme, $this->config);
        session()->flash('success', 'Đã nhân bản Theme. Bản sao đang được chọn để bạn chỉnh sửa.');
    }

    public function deleteTheme(string $name, AdminLayoutManager $layoutManager, AdminThemeProfileService $profileService): void
    {
        $this->authorizePermission('admin.layout.update');
        $profile = $profileService->profiles()[$name] ?? null;
        abort_if(! $profile, 404);
        abort_if((bool) ($profile['built_in'] ?? false), 422, 'Theme hệ thống không thể xóa.');
        $wasSelected = $this->selectedTheme === $name;
        $profileService->deleteCustom($name);
        $this->profiles = $profileService->profiles();
        if ($wasSelected) {
            $this->selectedTheme = AdminThemeProfileService::DEFAULT_PROFILE;
            $this->config = $profileService->apply($this->selectedTheme, $layoutManager->config());
            $layoutManager->save($this->config);
            $profileService->setActive($this->selectedTheme);
        }
        session()->flash('warning', 'Đã xóa Theme tùy chỉnh.');
    }

    public function saveTheme(AdminLayoutManager $layoutManager, AdminThemeProfileService $profileService, AdminDesignService $designService): void
    {
        $this->authorizePermission('admin.layout.update');
        $this->validate($this->rules($designService));
        $layoutManager->save($this->config);
        $profileService->setActive($this->selectedTheme);
        session()->flash('success', 'Giao diện & Theme đã được lưu và áp dụng cho toàn bộ Admin.');
        $this->redirect(url()->previous(), navigate: false);
    }

    public function saveAsTheme(AdminLayoutManager $layoutManager, AdminThemeProfileService $profileService, AdminDesignService $designService): void
    {
        $this->authorizePermission('admin.layout.update');
        $this->validate(array_merge($this->rules($designService), ['newThemeName' => 'required|string|min:2|max:80']));
        $layoutManager->save($this->config);
        $this->config = $layoutManager->config();
        $this->selectedTheme = $profileService->saveCustom($this->newThemeName, $this->config);
        $this->newThemeName = '';
        $this->profiles = $profileService->profiles();
        session()->flash('success', 'Theme mới đã được lưu và đang được sử dụng.');
        $this->redirect(url()->previous(), navigate: false);
    }

    public function restoreDefaultTheme(AdminLayoutManager $layoutManager, AdminThemeProfileService $profileService): void
    {
        $this->authorizePermission('admin.layout.update');
        $this->selectedTheme = AdminThemeProfileService::DEFAULT_PROFILE;
        $this->config = $profileService->apply($this->selectedTheme, $layoutManager->config());
        $layoutManager->save($this->config);
        $profileService->setActive($this->selectedTheme);
        session()->flash('warning', 'Đã khôi phục Theme mặc định Professional Indigo.');
        $this->redirect(url()->previous(), navigate: false);
    }

    public function render(AdminDesignService $designService)
    {
        return view('Admin::livewire.settings.admin-theme-editor', [
            'colorOptions' => $designService->colorOptions(),
            'surfaceColorOptions' => $designService->surfaceColorOptions(),
            'menuFontFamilyOptions' => $designService->menuFontFamilyOptions(),
            'menuFontSizeOptions' => $designService->menuFontSizeOptions(),
        ]);
    }

    private function activateRegionalSidebarColors(): void { data_set($this->config, 'sidebar.presentation.background', 'system'); }

    private function rules(AdminDesignService $designService): array
    {
        $colors = implode(',', array_keys($designService->colorOptions()));
        $surfaces = implode(',', array_keys($designService->surfaceColorOptions()));
        $sidebarPalettes = implode(',', $this->sidebarPalettes ?: ['soft-light']);
        return [
            'selectedTheme'=>'required|string|max:100','config.design.typography.font_family'=>'required|in:sans','config.design.typography.body_size'=>'required|in:xs,sm,base,lg,2xl','config.design.typography.page_title_size'=>'required|in:xs,sm,base,lg,2xl','config.design.typography.heading_weight'=>'required|in:normal,medium,semibold,bold',
            'config.design.colors.surface_base'=>'required|in:'.$colors,'config.design.colors.surface_raised'=>'required|in:'.$colors,'config.design.colors.text_primary'=>'required|in:'.$colors,'config.design.colors.text_secondary'=>'required|in:'.$colors,'config.design.colors.text_muted'=>'required|in:'.$colors,'config.design.colors.border_subtle'=>'required|in:'.$colors,'config.design.colors.accent'=>'required|in:'.$colors,'config.design.colors.focus_ring'=>'required|in:'.$colors,'config.design.colors.success'=>'required|in:'.$colors,'config.design.colors.warning'=>'required|in:'.$colors,'config.design.colors.danger'=>'required|in:'.$colors,'config.design.colors.info'=>'required|in:'.$colors,'config.design.colors.header_background'=>'required|in:'.$colors,'config.design.colors.footer_background'=>'required|in:'.$colors,'config.design.colors.page_background'=>'required|in:'.$surfaces,'config.design.colors.content_background'=>'required|in:'.$surfaces,'config.design.colors.sidebar_header_background'=>'required|in:'.$colors,'config.design.colors.sidebar_navigation_background'=>'required|in:'.$colors,'config.design.colors.sidebar_footer_background'=>'required|in:'.$colors,
            'config.design.sidebar_menu.item.font_family'=>'required|in:inherit,sans,serif,mono','config.design.sidebar_menu.item.font_size'=>'required|in:12,13,sm,15,base','config.design.sidebar_menu.item.font_weight'=>'required|in:normal,medium,semibold,bold','config.design.sidebar_menu.item.title_color'=>'required|in:'.$colors,'config.design.sidebar_menu.item.icon_color'=>'required|in:'.$colors,'config.design.sidebar_menu.item.icon_size'=>'required|in:16,18,20,22,24','config.design.sidebar_menu.item.item_height'=>'required|in:40,44,48,52',
            'config.design.sidebar_menu.submenu.font_family'=>'required|in:inherit,sans,serif,mono','config.design.sidebar_menu.submenu.font_size'=>'required|in:12,13,sm,15,base','config.design.sidebar_menu.submenu.font_weight'=>'required|in:normal,medium,semibold,bold','config.design.sidebar_menu.submenu.title_color'=>'required|in:'.$colors,'config.design.sidebar_menu.submenu.icon_color'=>'required|in:'.$colors,'config.design.sidebar_menu.submenu.indent'=>'required|in:20,24,28,32,36','config.design.sidebar_menu.submenu.item_height'=>'required|in:32,36,40,44','config.design.sidebar_menu.active.title_color'=>'required|in:'.$colors,'config.design.sidebar_menu.active.icon_color'=>'required|in:'.$colors,'config.design.sidebar_menu.active.font_weight'=>'required|in:normal,medium,semibold,bold',
            'config.design.spacing.tight'=>'required|in:1,2,3,4,6,8','config.design.spacing.control'=>'required|in:1,2,3,4,6,8','config.design.spacing.content'=>'required|in:1,2,3,4,6,8','config.design.spacing.section'=>'required|in:1,2,3,4,6,8','config.design.radius.control'=>'required|in:sm,md,lg,xl','config.design.radius.panel'=>'required|in:sm,md,lg,xl','config.design.radius.overlay'=>'required|in:sm,md,lg,xl',
            'config.theme.default'=>'required|in:'.$sidebarPalettes,'config.theme.dark_mode'=>'required|in:class','config.theme.accent'=>'required|in:blue,indigo,emerald,rose,amber','config.sidebar.presentation.background'=>'required|in:theme,system,white,dark','config.header.presentation.mode'=>'required|in:balanced,compact,action-heavy','config.header.presentation.padding_x'=>'required|in:0,1,2,3,4,5,6,8,10,12','config.header.presentation.action_gap'=>'required|in:0,1,2,3,4,5,6,8,10,12','config.header.presentation.background'=>'required|in:system,white,transparent','config.header.presentation.divider'=>'required|in:subtle,none','config.header.presentation.shadow'=>'required|in:none,subtle','config.header.presentation.backdrop_blur'=>'boolean','config.footer.presentation.alignment'=>'required|in:split,center','config.footer.presentation.background'=>'required|in:system,transparent','config.footer.presentation.divider'=>'required|in:subtle,none','config.footer.presentation.compact'=>'boolean','config.layout.surface.page_background'=>'required|in:system,white,slate-50','config.layout.surface.content_surface'=>'required|in:transparent,system,white','config.layout.surface.border'=>'required|in:system,none','config.layout.surface.radius'=>'required|in:none,sm,md,lg',
        ];
    }

    private function authorizePermission(string $permission): void
    {
        $user = auth('admin')->user() ?: auth()->user();
        abort_unless($user?->can($permission), 403);
    }
}
