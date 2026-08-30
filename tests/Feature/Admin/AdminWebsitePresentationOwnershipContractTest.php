<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminWebsitePresentationOwnershipContractTest extends TestCase
{
    public function test_banner_management_surface_uses_website_domain_ownership(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Banner/BannerManager.php'));

        $this->assertNotFalse($component);
        $this->assertStringContainsString('use Modules\\Website\\Services\\BannerService;', $component);
        $this->assertStringNotContainsString('Modules\\Admin\\Models\\Banner', $component);
        $this->assertStringNotContainsString('Modules\\Admin\\Services\\BannerService', $component);
    }

    public function test_header_management_surfaces_use_website_menu_domain(): void
    {
        foreach ([
            'Modules/Admin/Livewire/Header/MenuManager.php',
            'Modules/Admin/Livewire/Header/HeaderSettingsHub.php',
        ] as $path) {
            $component = file_get_contents(base_path($path));

            $this->assertNotFalse($component);
            $this->assertStringContainsString('Modules\\Website\\Services\\HeaderMenuService', $component);
            $this->assertStringContainsString('Modules\\Website\\Models\\HeaderMenuItem', $component);
            $this->assertStringNotContainsString('Modules\\Admin\\Services\\HeaderMenuService', $component);
            $this->assertStringNotContainsString('Modules\\Admin\\Models\\HeaderMenuItem', $component);
        }
    }

    public function test_legacy_admin_presentation_classes_are_compatibility_only(): void
    {
        $contracts = [
            'Modules/Admin/Models/Banner.php' => 'class Banner extends \\Modules\\Website\\Models\\Banner',
            'Modules/Admin/Models/HeaderMenu.php' => 'class HeaderMenu extends \\Modules\\Website\\Models\\HeaderMenu',
            'Modules/Admin/Models/HeaderMenuItem.php' => 'class HeaderMenuItem extends \\Modules\\Website\\Models\\HeaderMenuItem',
            'Modules/Admin/Services/BannerService.php' => 'class BannerService extends \\Modules\\Website\\Services\\BannerService',
            'Modules/Admin/Services/HeaderMenuService.php' => 'class HeaderMenuService extends \\Modules\\Website\\Services\\HeaderMenuService',
        ];

        foreach ($contracts as $path => $expected) {
            $source = file_get_contents(base_path($path));

            $this->assertNotFalse($source);
            $this->assertStringContainsString('@deprecated', $source);
            $this->assertStringContainsString($expected, $source);
        }

        $bannerModel = file_get_contents(base_path('Modules/Admin/Models/Banner.php'));
        $headerMenuModel = file_get_contents(base_path('Modules/Admin/Models/HeaderMenu.php'));
        $headerMenuItemModel = file_get_contents(base_path('Modules/Admin/Models/HeaderMenuItem.php'));

        $this->assertStringNotContainsString("protected $table", $bannerModel);
        $this->assertStringNotContainsString('protected $fillable', $bannerModel);
        $this->assertStringNotContainsString('protected $fillable', $headerMenuModel);
        $this->assertStringNotContainsString('protected $fillable', $headerMenuItemModel);
    }

    public function test_footer_management_already_uses_website_or_shared_settings_boundaries(): void
    {
        $columns = file_get_contents(base_path('Modules/Admin/Livewire/Footer/FooterColumns.php'));
        $social = file_get_contents(base_path('Modules/Admin/Livewire/Footer/SocialLinks.php'));
        $info = file_get_contents(base_path('Modules/Admin/Livewire/Footer/FooterInfo.php'));

        $this->assertStringContainsString('Modules\\Website\\Services\\FooterService', $columns);
        $this->assertStringContainsString('Modules\\Website\\Models\\FooterColumn', $columns);
        $this->assertStringContainsString('Modules\\Website\\Services\\FooterService', $social);
        $this->assertStringContainsString('Modules\\Website\\Models\\SocialLink', $social);
        $this->assertStringContainsString('Modules\\System\\Services\\SettingsService', $info);
    }

    public function test_admin_shell_layout_and_website_header_management_remain_distinct(): void
    {
        $routes = file_get_contents(base_path('Modules/Admin/routes/web.php'));
        $layout = file_get_contents(base_path('Modules/Admin/resources/views/pages/admin/layout-section.blade.php'));
        $websiteHeader = file_get_contents(base_path('Modules/Admin/resources/views/pages/admin/header/index.blade.php'));

        $this->assertStringContainsString("Route::get('/header', [AdminController::class, 'layoutHeader'])->name('header')", $routes);
        $this->assertStringContainsString("Route::get('/footer', [AdminController::class, 'layoutFooter'])->name('footer')", $routes);
        $this->assertStringContainsString("Route::get('/admin-header', [AdminController::class, 'adminHeader')", $routes);
        $this->assertStringContainsString("@livewire('admin.settings.admin-layout-config'", $layout);
        $this->assertStringContainsString('Website presentation', $websiteHeader);
        $this->assertStringContainsString("@livewire('admin.header.menu-manager')", $websiteHeader);
    }

    public function test_promotion_and_database_quarantine_remain_outside_this_slice(): void
    {
        $this->assertFileExists(base_path('Modules/Admin/Livewire/Marketing/CouponForm.php'));
        $this->assertFileExists(base_path('Modules/Admin/Livewire/Marketing/CouponTable.php'));
        $this->assertFileExists(base_path('Modules/Admin/Livewire/FlashSale'));
        $this->assertFileExists(base_path('Modules/Admin/Livewire/Affiliate'));
        $this->assertFileExists(base_path('Modules/Admin/Services/DatabaseService.php'));
    }
}
