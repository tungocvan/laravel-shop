<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteHeaderPhase9FConfigurationTest extends TestCase
{
    public function test_header_brand_logo_has_site_fallback_and_safe_upload_contract(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Header/GeneralSettings.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/general-settings.blade.php'));
        $provider = file_get_contents(base_path('Modules/Website/Providers/WebsiteServiceProvider.php'));

        $this->assertStringContainsString('WithFileUploads', $component);
        $this->assertStringContainsString("'header.brand_logo'", $component);
        $this->assertStringContainsString("'header/brand'", $component);
        $this->assertStringContainsString("str_starts_with(\$path, 'header/brand/')", $component);
        $this->assertStringContainsString('removeBrandLogo', $component);

        $this->assertStringContainsString('wire:model="brand_logo_upload"', $view);
        $this->assertStringContainsString('Fallback từ site_logo', $view);
        $this->assertStringContainsString('wire:click="removeBrandLogo"', $view);

        $normalizedProvider = preg_replace('/\s+/', '', $provider);
        $this->assertStringContainsString("\$headerBrandLogo=\$settings->get('header.brand_logo')", $normalizedProvider);
        $this->assertStringContainsString("'logo'=>\$headerBrandLogo?:\$settings->get('site_logo')", $normalizedProvider);
    }

    public function test_header_navigation_locations_are_config_driven_and_render_has_no_database_seed_side_effect(): void
    {
        $config = file_get_contents(base_path('Modules/Website/Config/header.php'));
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Header/MenuManager.php'));
        $service = file_get_contents(base_path('Modules/Website/Services/HeaderMenuService.php'));
        $mobile = file_get_contents(base_path('Modules/Website/resources/views/components/header/mobile-menu.blade.php'));

        $this->assertStringContainsString("'menu_locations' =>", $config);
        $this->assertStringContainsString('getAvailableLocations()', $component);
        $this->assertStringContainsString('getMenuTreeForAdmin(', $component);
        $this->assertStringNotContainsString('public $menuLocations = [', $component);
        $this->assertStringNotContainsString('firstOrCreate(', $this->renderMethodOnly($component));
        $this->assertStringContainsString('ensureMenu(', $this->saveMethodOnly($component));

        $this->assertStringContainsString("config('website.header.menu_locations'", $service);
        $this->assertStringContainsString('moveItemByDrag(', $service);
        $this->assertStringContainsString("where('header_menu_id', \$menuId)", $service);

        $this->assertStringNotContainsString("'/my-apps'", $mobile);
        $this->assertStringNotContainsString('Ứng dụng của tôi', $mobile);
    }

    public function test_header_themes_snapshot_only_layout_and_presentation_and_apply_preview_first(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Header/HeaderSettingsHub.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/header-settings-hub.blade.php'));
        $themeView = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/partials/theme-manager.blade.php'));

        $this->assertStringContainsString("'header.layout_themes'", $component);
        $this->assertStringContainsString('THEME_VERSION = 1', $component);
        $this->assertStringContainsString('MAX_THEMES = 20', $component);
        $this->assertStringContainsString('saveTheme(', $component);
        $this->assertStringContainsString('applyTheme(', $component);
        $this->assertStringContainsString('updateTheme(', $component);
        $this->assertStringContainsString('renameTheme(', $component);
        $this->assertStringContainsString('deleteTheme(', $component);
        $this->assertStringContainsString("'layout' => \$this->safeLayout", $component);
        $this->assertStringContainsString("'presentation' => \$presentationService->resolve", $component);
        $this->assertStringNotContainsString("'mainMenu'", $component);
        $this->assertStringNotContainsString("'header.brand_logo'", $component);

        $this->assertStringContainsString('partials.theme-manager', $view);
        $this->assertStringContainsString('Header Layout Themes', $themeView);
        $this->assertStringContainsString('wire:click="applyTheme"', $themeView);
        $this->assertStringContainsString('frontend chỉ đổi sau khi bấm', $themeView);

        $applyStart = strpos($component, 'public function applyTheme(');
        $updateStart = strpos($component, 'public function updateTheme(');
        $applyMethod = substr($component, $applyStart, $updateStart - $applyStart);
        $this->assertStringContainsString('loadBuilderLayout', $applyMethod);
        $this->assertStringNotContainsString('updateMany', $applyMethod);
        $this->assertStringNotContainsString("'header.layout' =>", $applyMethod);
    }

    public function test_header_admin_inputs_follow_shared_visibility_standard(): void
    {
        $general = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/general-settings.blade.php'));
        $menu = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/menu-manager.blade.php'));

        foreach ([$general, $menu] as $view) {
            $this->assertStringContainsString('border border-gray-300', $view);
            $this->assertStringContainsString('bg-white', $view);
            $this->assertStringContainsString('focus:border-blue-500', $view);
            $this->assertStringContainsString('focus:ring-2', $view);
        }

        $this->assertStringContainsString('sticky bottom-4', $general);
        $this->assertStringContainsString('draggable="true"', $menu);
        $this->assertStringContainsString('$wire.moveItemByDrag', $menu);
    }

    private function renderMethodOnly(string $component): string
    {
        $start = strpos($component, 'public function render(');
        $end = strpos($component, 'public function openModal(', $start);
        return substr($component, $start, $end - $start);
    }

    private function saveMethodOnly(string $component): string
    {
        $start = strpos($component, 'public function save(');
        $end = strpos($component, 'public function delete(', $start);
        return substr($component, $start, $end - $start);
    }
}
