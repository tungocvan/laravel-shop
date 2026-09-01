<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminWebsitePresentationOwnershipContractTest extends TestCase
{
    public function test_legacy_admin_runtime_trees_stay_removed(): void
    {
        $removed = [
            'Modules/Admin/Http/Controllers/Auth/GoogleController.php',
            'Modules/Admin/Http/Controllers/AuthController.php',
            'Modules/Admin/Http/Controllers/BannerController.php',
            'Modules/Admin/Http/Controllers/HeaderController.php',
            'Modules/Admin/Http/Controllers/FooterController.php',
            'Modules/Admin/Http/Controllers/HomeSettingsController.php',
            'Modules/Admin/Http/Controllers/CouponController.php',
            'Modules/Admin/Http/Controllers/FlashSaleController.php',
            'Modules/Admin/Http/Controllers/AffiliateController.php',
            'Modules/Admin/Http/Controllers/EnvConfigController.php',
            'Modules/Admin/Http/Controllers/Api/AdminController.php',
            'Modules/Admin/Http/Controllers/OrderController.php',
            'Modules/Admin/Http/Controllers/ProductController.php',
            'Modules/Admin/Http/Controllers/ProductCommissionController.php',
            'Modules/Admin/Http/Controllers/RoleController.php',
            'Modules/Admin/Http/Controllers/SettingController.php',
            'Modules/Admin/Http/Controllers/StaffController.php',
            'Modules/Admin/Livewire/FlashSale/FlashSaleManager.php',
            'Modules/Admin/Livewire/Marketing/CouponForm.php',
            'Modules/Admin/Livewire/Marketing/CouponTable.php',
            'Modules/Admin/Livewire/System/RoleForm.php',
            'Modules/Admin/Livewire/System/RoleTable.php',
            'Modules/Admin/Livewire/System/StaffForm.php',
            'Modules/Admin/Livewire/System/StaffTable.php',
            'Modules/Admin/Livewire/Auth/LoginForm.php',
            'Modules/Admin/Livewire/Affiliate/CommissionList.php',
            'Modules/Admin/Livewire/Affiliate/CommissionMatrix.php',
            'Modules/Admin/Livewire/Orders/OrderDetailModal.php',
            'Modules/Admin/Livewire/Settings/AdvancedConfig.php',
            'Modules/Admin/Livewire/Settings/DatabaseConfig.php',
            'Modules/Admin/Livewire/Settings/EnvManager.php',
            'Modules/Admin/Livewire/Settings/MailConfig.php',
            'Modules/Admin/Livewire/Settings/ModulesForm.php',
            'Modules/Admin/Livewire/Settings/MomoConfig.php',
            'Modules/Admin/Livewire/Settings/SettingForm.php',
            'Modules/Admin/Livewire/Settings/SocialConfig.php',
            'Modules/Admin/Livewire/Settings/StorageConfig.php',
            'Modules/Admin/resources/views/livewire/auth/login-form.blade.php',
            'Modules/Admin/Events/MessageSent.php',
            'Modules/Admin/Jobs/TestQueueJob.php',
            'Modules/Admin/Services/AuthService.php',
            'Modules/Admin/Services/HeaderMenuService.php',
            'Modules/Admin/resources/views/pages/affiliate/index.blade.php',
            'Modules/Admin/resources/views/pages/flash-sale/index.blade.php',
            'Modules/Admin/resources/views/pages/home/index.blade.php',
            'Modules/Admin/resources/views/pages/coupons/index.blade.php',
            'Modules/Admin/resources/views/pages/coupons/create.blade.php',
            'Modules/Admin/resources/views/pages/coupons/edit.blade.php',
            'Modules/Admin/resources/views/pages/products/index.blade.php',
            'Modules/Admin/resources/views/pages/products/create.blade.php',
            'Modules/Admin/resources/views/pages/products/edit.blade.php',
            'Modules/Admin/resources/views/pages/roles/index.blade.php',
            'Modules/Admin/resources/views/pages/roles/create.blade.php',
            'Modules/Admin/resources/views/pages/roles/edit.blade.php',
            'Modules/Admin/resources/views/pages/staff/index.blade.php',
            'Modules/Admin/resources/views/pages/staff/create.blade.php',
            'Modules/Admin/resources/views/pages/staff/edit.blade.php',
        ];

        foreach ($removed as $path) {
            $this->assertFileDoesNotExist(base_path($path), $path);
        }
    }

    public function test_auth_and_chat_specialized_runtime_remain_canonical(): void
    {
        $authController = file_get_contents(base_path('Modules/Auth/Http/Controllers/AuthController.php'));
        $googleController = file_get_contents(base_path('Modules/Auth/Http/Controllers/GoogleController.php'));
        $authLogin = file_get_contents(base_path('Modules/Auth/resources/views/pages/auth/login.blade.php'));
        $chatRoutes = file_get_contents(base_path('Modules/Chat/routes/web.php'));

        $this->assertNotFalse($authController);
        $this->assertNotFalse($googleController);
        $this->assertNotFalse($authLogin);
        $this->assertNotFalse($chatRoutes);
        $this->assertStringContainsString('function adminLogin', $authController);
        $this->assertStringContainsString("'guard' => 'admin'", $authController);
        $this->assertStringContainsString('Modules\\Auth\\Services\\AuthService', $googleController);
        $this->assertFileExists(base_path('Modules/Auth/Services/AuthService.php'));
        $this->assertStringContainsString("@livewire('auth.auth.login-form'", $authLogin);
        $this->assertStringContainsString("->prefix('admin')", $chatRoutes);
        $this->assertStringContainsString('ChatController::class', $chatRoutes);
    }

    public function test_website_presentation_routes_remain_canonical(): void
    {
        $adminRoutes = file_get_contents(base_path('Modules/Admin/routes/web.php'));
        $websiteRoutes = file_get_contents(base_path('Modules/Website/routes/web.php'));

        $this->assertNotFalse($adminRoutes);
        $this->assertNotFalse($websiteRoutes);

        foreach (['HomeSettingsController', 'CouponController', 'FlashSaleController', 'AffiliateController'] as $legacyController) {
            $this->assertStringNotContainsString($legacyController, $adminRoutes);
        }

        foreach (['/homepage-settings', '/header-settings', '/footer-settings', '/banners', '/flash-sales', '/affiliate'] as $uri) {
            $this->assertStringContainsString($uri, $websiteRoutes);
        }

        $this->assertStringContainsString("Route::prefix('coupons')", $websiteRoutes);
        $this->assertStringContainsString('CouponController::class', $websiteRoutes);
    }

    public function test_website_batch_one_dead_and_duplicate_runtime_stays_removed(): void
    {
        foreach ([
            'Modules/Website/Http/Controllers/Admin/ProductController.php',
            'Modules/Website/resources/views/admin/products/index.blade.php',
            'Modules/Website/resources/views/admin.blade.php',
            'Modules/Website/resources/views/products/index.blade.php',
            'Modules/Website/resources/views/products/detail.blade.php',
        ] as $path) {
            $this->assertFileDoesNotExist(base_path($path), $path);
        }

        $storefrontProductController = file_get_contents(base_path('Modules/Website/Http/Controllers/ProductController.php'));

        $this->assertNotFalse($storefrontProductController);
        $this->assertStringContainsString("view('Website::pages.shop')", $storefrontProductController);
        $this->assertStringContainsString("view('Website::products.show'", $storefrontProductController);
        $this->assertStringNotContainsString('function detail(', $storefrontProductController);
    }

    public function test_specialized_modules_own_product_role_order_and_account_routes(): void
    {
        foreach ([
            'Modules/Product/routes/web.php',
            'Modules/Role/routes/web.php',
            'Modules/Order/routes/web.php',
            'Modules/Account/routes/web.php',
        ] as $path) {
            $this->assertFileExists(base_path($path));
            $this->assertNotFalse(file_get_contents(base_path($path)));
        }
    }

    public function test_admin_shell_layout_remains_canonical(): void
    {
        $routes = file_get_contents(base_path('Modules/Admin/routes/web.php'));
        $layout = file_get_contents(base_path('Modules/Admin/resources/views/pages/admin/layout-section.blade.php'));
        $layoutConfig = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminLayoutConfig.php'));

        $this->assertNotFalse($routes);
        $this->assertNotFalse($layout);
        $this->assertNotFalse($layoutConfig);
        $this->assertStringContainsString('AdminController', $routes);
        $this->assertStringContainsString('DashboardController', $routes);
        $this->assertStringContainsString('MenuController', $routes);
        $this->assertStringContainsString('ProfileController', $routes);
        $this->assertStringContainsString("@livewire('admin.settings.admin-layout-config'", $layout);
        $this->assertStringContainsString('Modules\\Website\\Services\\HeaderMenuService', $layoutConfig);
        $this->assertStringNotContainsString('Modules\\Admin\\Services\\HeaderMenuService', $layoutConfig);
    }

    public function test_deferred_website_compatibility_and_admin_quarantine_are_explicit(): void
    {
        foreach ([
            'Modules/Admin/Models/Banner.php',
            'Modules/Admin/Models/HeaderMenu.php',
            'Modules/Admin/Models/HeaderMenuItem.php',
            'Modules/Admin/Services/BannerService.php',
            'Modules/Admin/Models/FlashSale.php',
            'Modules/Admin/Models/FlashSaleItem.php',
            'Modules/Admin/Services/FlashSaleService.php',
            'Modules/Admin/Services/AdminAffiliateService.php',
        ] as $path) {
            $this->assertFileExists(base_path($path), $path);
        }

        foreach ([
            'Modules/Admin/Models/ModuleRouteTitle.php',
            'Modules/Admin/database/migrations/2026_08_04_000002_create_module_route_titles_table.php',
            'Modules/Admin/Http/Controllers/DatabaseController.php',
            'Modules/Admin/Services/DatabaseService.php',
        ] as $path) {
            $this->assertFileExists(base_path($path), $path);
        }
    }
}
