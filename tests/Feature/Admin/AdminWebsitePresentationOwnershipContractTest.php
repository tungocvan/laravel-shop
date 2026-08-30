<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminWebsitePresentationOwnershipContractTest extends TestCase
{
    public function test_banner_legacy_admin_runtime_tree_is_removed_and_website_is_canonical(): void
    {
        foreach ([
            'Modules/Admin/Http/Controllers/BannerController.php',
            'Modules/Admin/Livewire/Banner/BannerManager.php',
            'Modules/Admin/resources/views/pages/banner/index.blade.php',
            'Modules/Admin/resources/views/livewire/banner/banner-manager.blade.php',
        ] as $path) {
            $this->assertFileDoesNotExist(base_path($path));
        }

        $adminRoutes = file_get_contents(base_path('Modules/Admin/routes/web.php'));
        $websiteRoutes = file_get_contents(base_path('Modules/Website/routes/web.php'));
        $websiteController = file_get_contents(base_path('Modules/Website/Http/Controllers/Admin/BannerController.php'));
        $websiteView = file_get_contents(base_path('Modules/Website/resources/views/pages/admin/banner/index.blade.php'));

        $this->assertNotFalse($adminRoutes);
        $this->assertNotFalse($websiteRoutes);
        $this->assertNotFalse($websiteController);
        $this->assertNotFalse($websiteView);
        $this->assertStringNotContainsString('BannerController', $adminRoutes);
        $this->assertStringContainsString('BannerController', $websiteRoutes);
        $this->assertStringContainsString("Website::pages.admin.banner.index", $websiteController);
        $this->assertStringContainsString("website.admin.banner.banner-manager", $websiteView);
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

        $this->assertStringNotContainsString('protected $table', $bannerModel);
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
        $websiteRoutes = file_get_contents(base_path('Modules/Website/routes/web.php'));
        $websiteHeaderController = file_get_contents(base_path('Modules/Website/Http/Controllers/Admin/HeaderController.php'));
        $websiteHeaderView = file_get_contents(base_path('Modules/Website/resources/views/pages/admin/header/index.blade.php'));

        $this->assertNotFalse($routes);
        $this->assertNotFalse($layout);
        $this->assertNotFalse($websiteRoutes);
        $this->assertNotFalse($websiteHeaderController);
        $this->assertNotFalse($websiteHeaderView);

        $this->assertStringContainsString("Route::get('/header', [AdminController::class, 'layoutHeader'])->name('header')", $routes);
        $this->assertStringContainsString("Route::get('/footer', [AdminController::class, 'layoutFooter'])->name('footer')", $routes);
        $this->assertStringNotContainsString("Route::get('/admin-header', [AdminController::class, 'adminHeader'])", $routes);
        $this->assertFileDoesNotExist(base_path('Modules/Admin/resources/views/pages/admin/header/index.blade.php'));
        $this->assertStringContainsString("@livewire('admin.settings.admin-layout-config'", $layout);

        $this->assertStringContainsString('HeaderController', $websiteRoutes);
        $this->assertStringContainsString('Website::pages.admin.header.index', $websiteHeaderController);
        $this->assertStringContainsString("website.admin.header.header-settings-hub", $websiteHeaderView);
    }

    public function test_promotion_and_database_quarantine_remain_outside_this_slice(): void
    {
        $this->assertFileExists(base_path('Modules/Admin/Livewire/Marketing/CouponForm.php'));
        $this->assertFileExists(base_path('Modules/Admin/Livewire/Marketing/CouponTable.php'));
        $this->assertDirectoryExists(base_path('Modules/Admin/Livewire/FlashSale'));
        $this->assertDirectoryExists(base_path('Modules/Admin/Livewire/Affiliate'));
        $this->assertFileExists(base_path('Modules/Admin/Services/DatabaseService.php'));
    }
}
