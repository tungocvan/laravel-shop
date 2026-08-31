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

    public function test_header_footer_legacy_admin_runtime_trees_are_removed(): void
    {
        foreach ([
            'Modules/Admin/Http/Controllers/HeaderController.php',
            'Modules/Admin/Http/Controllers/FooterController.php',
            'Modules/Admin/Livewire/Header/GeneralSettings.php',
            'Modules/Admin/Livewire/Header/HeaderSettingsHub.php',
            'Modules/Admin/Livewire/Header/MenuManager.php',
            'Modules/Admin/Livewire/Footer/FooterInfo.php',
            'Modules/Admin/Livewire/Footer/FooterColumns.php',
            'Modules/Admin/Livewire/Footer/SocialLinks.php',
            'Modules/Admin/resources/views/pages/header/index.blade.php',
            'Modules/Admin/resources/views/pages/footer/index.blade.php',
            'Modules/Admin/resources/views/livewire/header/general-settings.blade.php',
            'Modules/Admin/resources/views/livewire/header/header-settings-hub.blade.php',
            'Modules/Admin/resources/views/livewire/header/menu-manager.blade.php',
            'Modules/Admin/resources/views/livewire/header/partials/menu-item-row.blade.php',
            'Modules/Admin/resources/views/livewire/header/partials/menu-tree-manager.blade.php',
            'Modules/Admin/resources/views/livewire/footer/footer-info.blade.php',
            'Modules/Admin/resources/views/livewire/footer/footer-columns.blade.php',
            'Modules/Admin/resources/views/livewire/footer/social-links.blade.php',
        ] as $path) {
            $this->assertFileDoesNotExist(base_path($path));
        }
    }

    public function test_website_header_footer_management_is_canonical(): void
    {
        $websiteRoutes = file_get_contents(base_path('Modules/Website/routes/web.php'));
        $headerController = file_get_contents(base_path('Modules/Website/Http/Controllers/Admin/HeaderController.php'));
        $footerController = file_get_contents(base_path('Modules/Website/Http/Controllers/Admin/FooterController.php'));
        $headerView = file_get_contents(base_path('Modules/Website/resources/views/pages/admin/header/index.blade.php'));
        $footerView = file_get_contents(base_path('Modules/Website/resources/views/pages/admin/footer/index.blade.php'));

        $this->assertNotFalse($websiteRoutes);
        $this->assertNotFalse($headerController);
        $this->assertNotFalse($footerController);
        $this->assertNotFalse($headerView);
        $this->assertNotFalse($footerView);

        $this->assertStringContainsString("Route::get('/header-settings', [HeaderController::class, 'index'])", $websiteRoutes);
        $this->assertStringContainsString('permission:website.menu.manage,admin', $websiteRoutes);
        $this->assertStringContainsString("Route::get('/footer-settings', [FooterController::class, 'index'])", $websiteRoutes);
        $this->assertStringContainsString('permission:website.footer.manage,admin', $websiteRoutes);
        $this->assertStringContainsString('Website::pages.admin.header.index', $headerController);
        $this->assertStringContainsString('Website::pages.admin.footer.index', $footerController);
        $this->assertStringContainsString("website.admin.header.header-settings-hub", $headerView);
        $this->assertStringContainsString("website.admin.footer.footer-info", $footerView);
        $this->assertStringContainsString("website.admin.footer.footer-columns", $footerView);
        $this->assertStringContainsString("website.admin.footer.social-links", $footerView);
        $this->assertStringContainsString("website.admin.footer.footer-settings-hub", $footerView);
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

    public function test_admin_shell_layout_and_website_presentation_remain_distinct(): void
    {
        $routes = file_get_contents(base_path('Modules/Admin/routes/web.php'));
        $layout = file_get_contents(base_path('Modules/Admin/resources/views/pages/admin/layout-section.blade.php'));

        $this->assertNotFalse($routes);
        $this->assertNotFalse($layout);

        $this->assertStringContainsString("Route::get('/header', [AdminController::class, 'layoutHeader'])->name('header')", $routes);
        $this->assertStringContainsString("Route::get('/footer', [AdminController::class, 'layoutFooter'])->name('footer')", $routes);
        $this->assertStringNotContainsString('HeaderController', $routes);
        $this->assertStringNotContainsString('FooterController', $routes);
        $this->assertStringContainsString("@livewire('admin.settings.admin-layout-config'", $layout);
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
