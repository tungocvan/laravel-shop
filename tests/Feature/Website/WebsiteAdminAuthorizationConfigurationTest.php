<?php

namespace Tests\Feature\Website;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class WebsiteAdminAuthorizationConfigurationTest extends TestCase
{
    public function test_website_manifest_exposes_phase_1b_permissions(): void
    {
        $config = require base_path('Modules/Website/Config/module.php');
        $permissions = $config['permissions'] ?? [];

        foreach ([
            'website.view',
            'website.home.manage',
            'website.menu.manage',
            'website.footer.manage',
            'website.banner.manage',
            'website.settings.manage',
            'marketing.coupon.view',
            'marketing.coupon.manage',
            'marketing.flash-sale.view',
            'marketing.flash-sale.manage',
            'customer.view',
            'customer.create',
            'customer.update',
            'customer.delete',
            'affiliate.view',
            'affiliate.manage',
        ] as $permission) {
            $this->assertContains($permission, $permissions, "Missing Website permission [{$permission}].");
        }
    }

    public function test_website_admin_routes_have_expected_permission_middleware(): void
    {
        $matrix = [
            'admin.affiliate.index' => 'permission:affiliate.view,admin',
            'admin.home.settings' => 'permission:website.home.manage,admin',
            'admin.header.settings' => 'permission:website.menu.manage,admin',
            'admin.footer.settings' => 'permission:website.footer.manage,admin',
            'admin.banners' => 'permission:website.banner.manage,admin',
            'admin.flash-sales' => 'permission:marketing.flash-sale.view,admin',
            'admin.coupons.index' => 'permission:marketing.coupon.view,admin',
            'admin.coupons.create' => 'permission:marketing.coupon.manage,admin',
            'admin.coupons.edit' => 'permission:marketing.coupon.manage,admin',
            'admin.customers.index' => 'permission:customer.view,admin',
            'admin.customers.create' => 'permission:customer.create,admin',
            'admin.customers.show' => 'permission:customer.view,admin',
        ];

        foreach ($matrix as $routeName => $permissionMiddleware) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] was not registered.");
            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth:admin', $middleware, "Route [{$routeName}] lost admin authentication.");
            $this->assertContains($permissionMiddleware, $middleware, "Route [{$routeName}] has the wrong permission middleware.");
        }
    }

    public function test_persistent_livewire_mutations_have_capability_checks(): void
    {
        $expectations = [
            'Modules/Website/Livewire/Admin/Home/HomeSettings.php' => ['website.home.manage'],
            'Modules/Website/Livewire/Admin/Header/GeneralSettings.php' => ['website.menu.manage'],
            'Modules/Website/Livewire/Admin/Header/HeaderSettingsHub.php' => ['website.settings.manage'],
            'Modules/Website/Livewire/Admin/Header/MenuManager.php' => ['website.menu.manage'],
            'Modules/Website/Livewire/Admin/Footer/FooterInfo.php' => ['website.footer.manage'],
            'Modules/Website/Livewire/Admin/Footer/FooterColumns.php' => ['website.footer.manage'],
            'Modules/Website/Livewire/Admin/Footer/SocialLinks.php' => ['website.footer.manage'],
            'Modules/Website/Livewire/Admin/Footer/FooterSettingsHub.php' => ['website.footer.manage'],
            'Modules/Website/Livewire/Admin/Banner/BannerManager.php' => ['website.banner.manage'],
            'Modules/Website/Livewire/Admin/FlashSale/FlashSaleManager.php' => ['marketing.flash-sale.manage'],
            'Modules/Website/Livewire/Admin/Coupon/CouponForm.php' => ['marketing.coupon.manage'],
            'Modules/Website/Livewire/Admin/Coupon/CouponTable.php' => ['marketing.coupon.manage'],
            'Modules/Website/Livewire/Admin/Customers/CustomerCreate.php' => ['customer.create'],
            'Modules/Website/Livewire/Admin/Customers/CustomerDetail.php' => ['customer.update'],
            'Modules/Website/Livewire/Admin/Customers/CustomerTable.php' => ['customer.update', 'customer.delete'],
            'Modules/Website/Livewire/Admin/Affiliate/CommissionList.php' => ['affiliate.manage'],
            'Modules/Website/Livewire/Admin/Affiliate/CommissionMatrix.php' => ['affiliate.manage'],
        ];

        foreach ($expectations as $path => $permissions) {
            $contents = file_get_contents(base_path($path));
            $this->assertStringContainsString('AuthorizesAdminPermissions', $contents, "{$path} is missing the shared authorization helper.");

            foreach ($permissions as $permission) {
                $this->assertStringContainsString(
                    "authorizeAdminPermission('{$permission}')",
                    $contents,
                    "{$path} is missing mutation authorization [{$permission}]."
                );
            }
        }
    }

    public function test_authorization_helper_uses_the_admin_guard_explicitly(): void
    {
        $contents = file_get_contents(base_path('Modules/Website/Livewire/Concerns/AuthorizesAdminPermissions.php'));

        $this->assertStringContainsString("auth('admin')->user()", $contents);
        $this->assertStringContainsString("hasPermissionTo(\$permission, 'admin')", $contents);
        $this->assertStringContainsString('abort_unless', $contents);
    }
}
